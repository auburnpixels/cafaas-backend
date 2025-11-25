#!/bin/sh

php artisan migrate --force
php artisan optimize
php artisan config:cache
php artisan view:cache
php artisan view:clear
php artisan storage:link
