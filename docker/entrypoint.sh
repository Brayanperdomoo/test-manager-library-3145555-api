#!/bin/sh
set -eu

php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
exec apache2-foreground
