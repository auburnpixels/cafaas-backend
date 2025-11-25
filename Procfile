release: php artisan migrate --force && php artisan optimize && php artisan config:cache
web: vendor/bin/heroku-php-apache2 public/
worker: php artisan horizon
