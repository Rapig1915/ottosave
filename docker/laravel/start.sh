#!/bin/sh

echo "Running dym-laravel-container's entrypoint file..."

echo "Modifying user (hack for mac)..."
usermod -u 1000 www-data #a hack for macs

echo "Copying config file if it isn't already present...."
cp -n /var/www/html/docker/laravel/.env.docker /var/www/html/.env

echo "Waiting for mysql-service..."
while ! mysqladmin ping -h"mysql-service" --silent; do
    echo "Waiting for mysql-service"
    sleep 1
done
echo "mysql-service is running..."

echo "Build the autoload file..."
composer dump-autoload

echo "Installing oauth keys..."
php artisan passport:keys

echo "Running db migrations..."
php artisan --verbose migrate --seed

echo "Starting Cron"
/etc/init.d/cron start

echo "Starting redis-server..."
redis-server --daemonize yes

echo "Deleting existing apache pid if present..."
if [ -f "$APACHE_PID_FILE" ]; then
    rm "$APACHE_PID_FILE"
fi

echo "Running PHPUnit..."
vendor/bin/phpunit

echo "Creating needed supervisor files..."
echo '' > /var/www/html/storage/logs/queue-runner.log # ensure the log file exists
echo '' > /tmp/supervisor.sock # ensure the sockfile exists

echo "Starting Supervisor..."
supervisord -c /etc/supervisor/supervisord.conf

echo "Watching for php file changes..."
node docker/laravel/test_on_changes.js &

echo "dym-laravel-container is ready!"
/usr/sbin/apache2ctl -D FOREGROUND
