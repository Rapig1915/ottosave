#Laravel Service Documentation
You'll find information about running the laravel-prodage-service here.
### Setup Production Server
These steps are used to setup a new production, staging, or QA server.
1. Setup service using `/cloud66/service.yml`
1. Add the env variables necessary for front-end build (see `/docker/node/DEFAULT.env`)
1. Test the build
1. Setup a database service or third party provider
    1. DigitalOcean has `sql_require_primary_key` set to true by default. It must be disabled. See https://www.digitalocean.com/community/questions/how-to-disable-sql_require_primary_key-in-digital-ocean-manged-database-for-mysql
1. Setup other services if they're not run by docker on production
1. Populate the necessary env variables using the defaults found in the other docker services.
1. Populate the env variables
    1. Populate APP_KEY
        1. Run `php artisan key:generate --show` on the production server
        1. Update the env variable
    1. Populate OAuth keys
        1. Run `php artisan passport:keys` on the production server
        1. Copy the two files in `storage/oauth-*.key` to the related ENV variables
    1. Add a password client
        1. Run `php artisan passport:client --password`
            1. The name doesn't matter
            1. Paste the values it shows in the related ENV variables
1. Run `php artisan db:migrate`
1. Test the re-build