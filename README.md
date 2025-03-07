Aktin - vypracování úkolu
=================

Tento projekt obsahuje vypracovaný úkol, který je součástí přijímacího řízení společnosti Velgain.



Instalace
------------

Nejprve je třeba nainstalovat composer

	composer install


Dále se v dockeru přihlásit pod rootem k DB kontejneru a vytvořit databázi:

	docker exec -it db mysql -uroot -p
    CREATE DATABASE aktin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

Následně je třeba spustit migrace:

    docker exec -it app php vendor/bin/doctrine-migrations migrations:diff
    docker exec -it app php vendor/bin/doctrine-migrations migrations:migrate