Run the suite with `php artisan test` or `vendor\bin\phpunit` so `phpunit.xml` forces `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:`.
`tests/TestCase.php` boots the app and then refuses `mysql` or `mariadb`, and any database name that is not `:memory:` and does not end in `_test`.
That guard runs for every class extending `TestCase`, before test code, so a live MySQL database from `.env` cannot be migrated or wiped.
Never pass `--no-configuration`: PHPUnit would ignore `phpunit.xml`, load the default `.env` connection, and the guard would abort the run.
