<?php

namespace App\Http\Controllers;

use App\Services\LogViewerService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogViewerController extends Controller
{
    protected LogViewerService $logViewer;

    public function __construct(LogViewerService $logViewer)
    {
        $this->logViewer = $logViewer;
    }

    /**
     * Display the log viewer UI.
     */
    public function index(Request $request)
    {
        $source = $request->query('source', LogViewerService::SOURCE_LARAVEL);
        $file = $request->query('file');
        $level = $request->query('level', 'ALL');
        $search = $request->query('search');
        $date = $request->query('date');
        $limit = (int) $request->query('limit', 100);

        $availableFiles = $this->logViewer->getLogFiles($source);
        $logData = $this->logViewer->getLogs($source, $file, $level, $search, $date, $limit);

        $levelCounts = [
            'ALL' => 0,
            'EMERGENCY' => 0,
            'ALERT' => 0,
            'CRITICAL' => 0,
            'ERROR' => 0,
            'WARNING' => 0,
            'NOTICE' => 0,
            'INFO' => 0,
            'DEBUG' => 0,
        ];

        // Raw unfiltered logs for quick counts
        $allLogs = $this->logViewer->getLogs($source, $file, 'ALL', null, null, 500);
        $levelCounts['ALL'] = count($allLogs['entries']);
        foreach ($allLogs['entries'] as $entry) {
            $lvl = strtoupper($entry['level'] ?? 'INFO');
            if (isset($levelCounts[$lvl])) {
                $levelCounts[$lvl]++;
            }
        }

        return view('logs.index', compact('source', 'file', 'level', 'search', 'date', 'availableFiles', 'logData', 'levelCounts'));
    }

    /**
     * Clear / Truncate current log file.
     */
    public function clear(Request $request)
    {
        $source = $request->input('source', LogViewerService::SOURCE_LARAVEL);
        $file = $request->input('file');

        $this->logViewer->clearLog($source, $file);

        return redirect()->route('logs.index', ['source' => $source, 'file' => $file])
            ->with('success', 'Selected log file cleared successfully.');
    }

    /**
     * Download the selected log file.
     */
    public function download(Request $request)
    {
        $fileName = $request->query('file', 'laravel.log');
        $filePath = storage_path('logs/' . $fileName);

        if (!file_exists($filePath)) {
            return back()->with('error', 'Log file not found for download.');
        }

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
