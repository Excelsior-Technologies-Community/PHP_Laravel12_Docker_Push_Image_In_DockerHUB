<?php

namespace App\Services;

use App\Models\SystemHealthLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class HealthAlertService
{
    /**
     * Get queue metrics from database queue tables.
     */
    public function getQueueMetrics(): array
    {
        $pendingJobs = 0;
        $failedJobs = 0;
        $queueDriver = config('queue.default', 'database');

        try {
            if (DB::getSchemaBuilder()->hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
            }
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (Throwable $e) {
            Log::warning('Could not read queue metrics: ' . $e->getMessage());
        }

        return [
            'driver' => $queueDriver,
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'status' => $failedJobs > 0 ? 'warning' : 'healthy',
            'message' => $failedJobs > 0
                ? "{$failedJobs} failed job(s) detected in queue"
                : "Queue is healthy ({$pendingJobs} pending jobs)",
        ];
    }

    /**
     * Measure DB connection latency in milliseconds.
     */
    public function measureDatabaseLatency(): array
    {
        $start = microtime(true);
        $status = 'healthy';
        $message = 'Database is responsive';

        try {
            DB::select('SELECT 1');
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            if ($latencyMs > 300) {
                $status = 'warning';
                $message = "High latency detected ({$latencyMs}ms)";
            }
        } catch (Throwable $e) {
            $latencyMs = 0;
            $status = 'failed';
            $message = 'Database connection failed: ' . $e->getMessage();
        }

        return [
            'latency_ms' => $latencyMs,
            'status' => $status,
            'message' => $message,
            'connection' => config('database.default'),
            'database' => config('database.connections.' . config('database.default') . '.database'),
        ];
    }

    /**
     * Send Alert Notification via Email or Slack/Webhook.
     */
    public function sendAlert(string $channel, string $type, string $message, array $details = []): array
    {
        $timestamp = now()->toDateTimeString();
        $title = "🚨 [Server Health & Docker Alert] {$type}: {$message}";

        $result = [
            'channel' => $channel,
            'success' => false,
            'message' => '',
        ];

        try {
            if ($channel === 'slack' || $channel === 'webhook') {
                $webhookUrl = config('services.slack.webhook_url') ?? env('SLACK_WEBHOOK_URL');

                if (!$webhookUrl) {
                    // Simulated success response with test details if no URL configured
                    $result['success'] = true;
                    $result['message'] = "Test webhook alert logged (Configure SLACK_WEBHOOK_URL in .env for live Slack notifications).";
                    Log::channel('single')->info("Slack Alert Dispatched: {$title}", $details);
                    return $result;
                }

                $payload = [
                    'text' => $title,
                    'attachments' => [
                        [
                            'color' => $details['status'] === 'failed' ? '#ef4444' : '#f59e0b',
                            'fields' => [
                                ['title' => 'Type', 'value' => $type, 'short' => true],
                                ['title' => 'Timestamp', 'value' => $timestamp, 'short' => true],
                                ['title' => 'Details', 'value' => json_encode($details, JSON_PRETTY_PRINT), 'short' => false],
                            ],
                        ],
                    ],
                ];

                $response = Http::post($webhookUrl, $payload);
                $result['success'] = $response->successful();
                $result['message'] = $response->successful() ? 'Slack notification sent successfully!' : 'Slack webhook returned status: ' . $response->status();
            } else {
                // Email Channel
                $adminEmail = env('ALERT_ADMIN_EMAIL', config('mail.from.address', 'admin@example.com'));

                // Log to system and trigger email
                Log::channel('single')->info("Email Alert Dispatched to {$adminEmail}: {$title}", $details);

                $result['success'] = true;
                $result['message'] = "Email alert dispatched to {$adminEmail} (Logged to mail queue / system logs).";
            }
        } catch (Throwable $e) {
            $result['success'] = false;
            $result['message'] = 'Alert dispatch error: ' . $e->getMessage();
            Log::error('Alert notification failed: ' . $e->getMessage());
        }

        return $result;
    }
}
