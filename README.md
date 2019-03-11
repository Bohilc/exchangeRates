Installation
------------

* Download repository
* Make sure you have installed `compose`
* Run command: `composer install`
* Next step is rename file `.env.test` to `.env`
* Edit `.env` exactly `DATABASE_URL` section. Write: `DATABASE_URL=mysql://{user}:{password}@127.0.0
.1:3306/{database}`
* You can create database with command:`php bin/console doctrine:database:create` or create database manually.
* Run this command to create database tables: `php bin/console doctrine:migration:migrate`
* Last step is set data to database. `php bin/console doctrine:fixtures:load` Data will be added from nbp.pl.
* Run application: `php bin/console server:run`

Endpoints
-------------
* All exchange rate: `http://127.0.0.1:8000/api/exchangerates`
* One currency `http://127.0.0.1:8000/api/exchangerates/{currency}`
* Avg currency from all time `http://127.0.0.1:8000/api/exchangerates/avgcurrency/{currency}`

Accessories
-------------

* `php bin/console app:check:act-nbp` Will be check actualisation NBP (Narodowy Bank Polski)
* `php bin/console app:clean:cache` clean cache for all application
* Changes will be logged (var/log/*.log) if data from the NBP will be different than our data np: `...app.INFO: 
Actualisation data 
NBP: 
THB - bat (Tajlandia). From 12 to 0.1209...`


Others
-------------
* [Project created on Symfony 4][1]

[1]: https://symfony.com/