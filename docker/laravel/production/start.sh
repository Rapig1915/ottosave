#!/bin/sh

echo "Running laravel-container's entrypoint file..."

echo "Adding Oauth keys for Laravel..."
echo $OAUTH_PUBLIC_KEY > /var/www/html/storage/oauth-public.key
sed -i 's/\r /\n/g' /var/www/html/storage/oauth-public.key
echo $OAUTH_PRIVATE_KEY > /var/www/html/storage/oauth-private.key
sed -i 's/\r /\n/g' /var/www/html/storage/oauth-private.key
echo "...Oauth keys added."

echo "Build the autoload file..."
composer dump-autoload -o
echo "...autoload dumped."

echo "Ensure log file has proper permissions..."
touch /var/www/html/storage/logs/laravel.log # ensure log file exists
chmod 664 /var/www/html/storage/logs/laravel.log # give group write access
echo "...log file updated."

echo "Give apache permission to edit logs/cache..."
chgrp -R www-data storage bootstrap/cache
echo "...permission given."

echo "Checking for apache pid..."
if [ -f "$APACHE_PID_FILE" ]; then
    echo "...deleting pid..."
    rm "$APACHE_PID_FILE"
    echo "...pid deleted..."
fi
echo "...apache pid check done"

echo "Copying env settings for cron"
env >> /etc/environment

echo "Caching config"
php artisan config:cache

echo "Caching routes"
php artisan route:cache

echo "Starting Cron"
/etc/init.d/cron start

echo "Starting redis-server..."
redis-server --daemonize yes

echo "Adding database credentials..."
echo $DB_CREDENTIAL_FILE > /var/www/html/storage/database-credentials.json
sed -i ':a;N;$!ba;s/\n/\\n/g' /var/www/html/storage/database-credentials.json

echo "Creating needed supervisor files..."
echo '' > /var/www/html/storage/logs/queue-runner.log # ensure the log file exists
echo '' > /tmp/supervisor.sock # ensure the sockfile exists
echo '' > /tmp/cloudsql.sock # ensure the sockfile exists
echo '' > /var/www/html/storage/logs/db-cloud-proxy.log # ensure the log file exists
echo "command=/etc/cloud_sql_proxy -dir=/tmp -instances=$DB_INSTANCE_CONNECTION_NAME=unix:/tmp/cloudsql.sock -credential_file=/var/www/html/storage/database-credentials.json" >> /etc/supervisor/conf.d/db-cloud-proxy.conf

echo "Starting Supervisor..."
supervisord -c /etc/supervisor/supervisord.conf

echo "Waiting for cloud_sql_proxy..."
while ! grep -q "Ready for new connections" /var/www/html/storage/logs/db-cloud-proxy.log; do
    echo "Waiting for cloud_sql_proxy"
    sleep 1
done
echo "cloud_sql_proxy is running..."

echo "Running db migrations..."
php artisan --verbose migrate --force
if [ $? = 1 ]; then # if migrations failed
    echo "Migrations failed, alerting webmaster..."
    curl -s --user "api:${MAILGUN_SECRET}" https://api.mailgun.net/v3/$MAILGUN_DOMAIN/messages \
        -F from=$WEBMASTER_EMAIL \
        -F to=$WEBMASTER_EMAIL \
        -F subject="URGENT: ${APP_NAME} Failed To Migrate" \
        -F text="Your database may have been partially migrated but your container failed. This means your existing container could be using broken data."
    echo "Migrations failed, exiting..."
    exit 1
fi
echo "...migrations succeeded"

echo "laravel-container is ready!"
/usr/sbin/apache2ctl -D FOREGROUND
