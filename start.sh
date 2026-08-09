#!/bin/bash

if [ ! -f .env ]; then
    cp .env.example .env
fi

php artisan key:generate --force
php artisan config:clear
php artisan cache:clear

chmod -R 777 storage database

exec apache2-foreground
