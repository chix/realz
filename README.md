# Realz API

## Installation
- run `git clone https://github.com/chix/realz.git` to pull the source code
- run `cp .env .env.local` and configure DB credentials
- run `composer install` to install 3rd party dependencies
- run `php bin/console doctrine:database:create` to create DB
- run `php bin/console doctrine:migrations:migrate` to create DB schema
- run `php bin/console app:import:registry` to import the city registry
- run `php bin/console app:import:adverts` to import latest adverts
- run `php bin/console app:push-notifications:send` to send out out push notifications about new adverts
