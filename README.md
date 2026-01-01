# PHP_Laravel12_Docker_Push_Image_In_DockerHUB

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" />
  <img src="https://img.shields.io/badge/Platform-Windows_CMD-0078D6?style=for-the-badge&logo=windows&logoColor=white" />
  <img src="https://img.shields.io/badge/Status-Working_Successfully-success?style=for-the-badge" />
</p>


##  Tech Stack

* Laravel 12
* PHP 8.3
* Docker & Docker Hub
* Composer
* Windows CMD

---

##  Features Overview

This project demonstrates a **complete, real-world Laravel 12 Docker setup** with proper best practices. Below are the key features included in this repository:

###  Docker Features

* Fully Dockerized Laravel 12 application
* Custom `Dockerfile` with PHP 8.3
* Secure `.dockerignore` configuration
* Docker Hub–ready image
* Container runs using `php artisan serve`
* Windows CMD–compatible Docker commands

###  Security & Configuration

* `.env` file **never stored inside Docker image**
* `.env` securely loaded using Docker volume mount
* `APP_KEY` handled correctly to avoid encryption errors
* Production-safe handling of sensitive configuration

###  Laravel Runtime Features

* Automatic creation of required Laravel directories:

  * `storage/framework/views`
  * `storage/framework/cache`
  * `storage/framework/sessions`
* Correct file & directory permissions
* Blade view cache working correctly
* Laravel logging enabled and functional

###  Error Handling & Debugging

* Step-by-step fixes for common Laravel Docker errors:

  * `500 Server Error`
  * `No application encryption key`
  * `Please provide a valid cache path`
* Debug-friendly configuration (`APP_DEBUG=true`)
* Useful Docker & Artisan debug commands included

###  Windows-Friendly Setup

* Designed specifically for **Windows CMD users**
* No PowerShell-only syntax required
* No Linux-only commands in final workflow
* Tested on Windows with Docker Desktop

###  Ready for Extension

* Can be easily extended with:

  * `docker-compose` (MySQL, phpMyAdmin)
  * Nginx + PHP-FPM production setup
  * CI/CD with GitHub Actions

---

##  Project Structure

```text
laravel12-app/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
│   └── framework/
│       ├── cache/
│       ├── sessions/
│       └── views/
├── tests/
├── vendor/
├── .dockerignore
├── .env.example
├── Dockerfile
├── artisan
├── composer.json
└── README.md
```

---

## 1️ Create Laravel Project

```cmd
composer create-project laravel/laravel laravel12-app
cd laravel12-app
```

---

## 2️ Create `.dockerignore` (EXACT FILE)

```text
.git
node_modules
vendor
.env
storage/logs
docker-compose.yml
```

 `.env` is ignored intentionally (security reason).

---

## 3️ Create `Dockerfile` (EXACT WORKING FILE)

```dockerfile
FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project
COPY . .

# Create Laravel required cache directories
RUN mkdir -p storage/framework/views \
    storage/framework/cache \
    storage/framework/sessions

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
```

---

## 4️ Build Docker Image

```cmd
docker build -t my-laravel-app:latest .
```

---

## 5️ Login to Docker Hub

```cmd
docker login
```

---

## 6️ Tag Image

```cmd
docker tag my-laravel-app:latest hardik112002/my-laravel-app:latest
```

---

## 7️ Push Image to Docker Hub

```cmd
docker push hardik112002/my-laravel-app:latest
```
<img width="1919" height="994" alt="Screenshot 2026-01-01 161513" src="https://github.com/user-attachments/assets/0d5e23cc-bcb2-4ba8-a477-a988e25b16fb" />

---

## 8️ Prepare `.env` (LOCAL MACHINE)

```cmd
copy .env.example .env
php artisan key:generate
```

 `.env` is NOT copied into the image.
It is mounted using Docker volume.

---

## 9️ Run Container (FINAL – WINDOWS CMD SAFE)

```cmd
docker run -d -p 8000:8000 -v "%cd%\.env:/var/www/.env" --name laravel12-app hardik112002/my-laravel-app:latest
```

---

##  Errors Faced & Exact Fixes

###  500 Server Error

Cause: Storage cache directories missing

Fix:

```cmd
docker exec -it laravel12-app bash

mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions

chmod -R 777 storage bootstrap/cache

php artisan optimize:clear

docker restart laravel12-app
```

---

###  No application encryption key

Cause: `.env` not loaded

Fix:

```cmd
php artisan key:generate
```

---

###  Please provide a valid cache path

Cause: Blade cache directory missing

Fix:

```cmd
mkdir -p storage/framework/views

chmod -R 777 storage
```

---

##  Verify Application

Open browser:

```
http://localhost:8000
```

 Laravel Welcome Page should appear

---
<img width="1917" height="993" alt="Screenshot 2026-01-01 154520" src="https://github.com/user-attachments/assets/53c8417d-e7a7-44eb-ae3e-3297efffdb51" />


##  Debug Commands

```cmd
docker ps

docker logs laravel12-app

docker exec -it laravel12-app bash

php artisan about
```

---

##  Final Result

✔ Laravel 12 running inside Docker
✔ Image pushed to Docker Hub
✔ `.env` securely mounted
✔ Windows CMD compatible
✔ No 500 errors

---


