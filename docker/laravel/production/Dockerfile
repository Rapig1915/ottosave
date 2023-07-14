FROM dym-node-production-image

# Add start file to new environment
COPY ./docker/laravel/production/start.sh /var/www/docker/laravel/production/start.sh
RUN sed -i 's/\r//' /var/www/docker/laravel/production/start.sh # Modify the executable to have the linux line endings
RUN chmod u+x /var/www/docker/laravel/production/start.sh # Give permissions to run executable

RUN composer dump-autoload

COPY . /var/www/html

# Add queue-runner config file to new environment
COPY ./docker/laravel/production/queue-worker.conf /etc/supervisor/conf.d/queue-runner.conf

# Add Google Cloud Proxy worker config file
COPY ./docker/laravel/production/db-cloud-proxy.conf /etc/supervisor/conf.d/db-cloud-proxy.conf

# Download Google Cloud SQL Proxy
RUN wget https://dl.google.com/cloudsql/cloud_sql_proxy.linux.amd64 -O /etc/cloud_sql_proxy

# Make the proxy executable
RUN chmod +x /etc/cloud_sql_proxy
