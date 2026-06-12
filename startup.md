# Startup Guide

This document outlines the commands needed to initialize the project environment and database for the first time or after a clean setup.

## Prerequisites

- Ensure Docker and Docker Compose are installed and running.
- Ensure the `.env` file is configured (copy from `.env.example` if necessary).

## 1. Start Docker Containers

Start all required services defined in `docker-compose.yml`:

```bash
sudo docker compose up -d
```

## 2. Initialize Database and Seed Data

To perform a fresh installation or reset the database state, run the following commands inside the `laravel.test` container:

```bash
# Run migrations and seed the database
sudo docker compose exec laravel.test php artisan migrate:fresh --force
sudo docker compose exec laravel.test php artisan db:seed --force
```

## 3. Install/Build Dependencies

Ensure all PHP and JavaScript dependencies are installed and the UI is built:

```bash
# Install PHP dependencies
sudo docker compose exec laravel.test composer install

# Install JS dependencies
sudo docker compose exec laravel.test npm install

# Build the Web UI
sudo docker compose exec laravel.test npm run build
```

---
*Note: Use `sudo` if your user lacks direct permissions to the Docker socket. If you are using Laravel Sail, you may replace `sudo docker compose exec laravel.test` with `./vendor/bin/sail` where applicable.*
