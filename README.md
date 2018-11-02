# Realz backend

## Installation
- run `git clone ...` to pull the source code
- run `cp app/config/parameters.yml.dist app/config/parameters.yml` and configure DB credentials
- run `composer install` to install 3rd party dependencies
- run `php bin/console doctrine:database:create` to create DB
- run `php bin/console doctrine:migrations:migrate` to create DB schema
- run `php bin/console app:import:registry` to import the city registry
