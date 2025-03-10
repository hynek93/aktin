Vilgain - vypracování úkolu
=================

Tento projekt obsahuje vypracovaný úkol, který je součástí přijímacího řízení společnosti Vilgain.


# Instalace

Nejprve je třeba nainstalovat composer

	composer install


Dále spustit docker a přihlásit se pod rootem k DB kontejneru a vytvořit databázi:

	docker compose up
    docker exec -it db mysql -uroot -p
    
    CREATE DATABASE aktin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

Následně je třeba spustit migrace:

    docker exec -it app php vendor/bin/doctrine-migrations migrations:diff
    docker exec -it app php vendor/bin/doctrine-migrations migrations:migrate


# API

API poskytuje správu uživatelů a článků + autentizaci.

## Autentizace
Endpointy `users` a `articles` vyžadují **JWT token** v hlavičce:
    
    Authorization: Bearer {JWT_TOKEN}

## **Přihlášení / registrace (`/auth`)**

Parametr `name` u vytvoření a editace uživatele je volitelný.

| Metoda  | Endpoint          | Popis                       | Oprávnění  | Parametry (JSON)                    |
|---------|------------------|-----------------------------|------------|-------------------------------------|
| `POST`  | `/auth/register` | Registrace uživatele        | Veřejné    | `email`, `password`, `role`, `name` |
| `POST`  | `/auth/login`    | Přihlášení uživatele        | Veřejné    | `email`, `password`                 |


## **Uživatelé (`/users`)**

Parametr `name` u vytvoření a editace uživatele je volitelný.

| Metoda  | Endpoint          | Popis                      | Oprávnění  | Parametry (JSON)                    |
|---------|------------------|---------------------------|------------|-------------------------------------|
| `GET`   | `/users/`        | Získat seznam uživatelů   | **Admin**  | -                                   |
| `GET`   | `/users/{id}`    | Získat detail uživatele   | **Admin**  | -                                   |
| `POST`  | `/users/`        | Vytvořit nového uživatele | **Admin**  | `email`, `password`, `role`, `name` |
| `PUT`   | `/users/{id}`    | Upravit uživatele         | **Admin**  | `email`, `password`, `role`, `name` |
| `DELETE`| `/users/{id}`    | Smazat uživatele          | **Admin**  | -                                   |

## **Články (`/articles`)**

| Metoda  | Endpoint           | Popis                    | Oprávnění       | Parametry (JSON) |
|---------|-------------------|--------------------------|-----------------|------------------|
| `GET`   | `/articles/`      | Získat seznam článků     | Přihlášený      | - |
| `GET`   | `/articles/{id}`  | Získat detail článku     | Přihlášený      | - |
| `POST`  | `/articles/`      | Vytvořit nový článek     | **Admin/Autor** | `title`, `content` |
| `PUT`   | `/articles/{id}`  | Upravit článek           | **Admin/Autor** | `title`, `content` |
| `DELETE`| `/articles/{id}`  | Smazat článek            | **Admin/Autor** | - |

## **Příklad požadavků**

### **Přihlášení a získání JWT tokenu**
    curl -X POST http://localhost:8080/auth/login \
         -H "Content-Type: application/json" \
         -d '{"email": "admin", "password": "admin123"}'

# Testy

Připraveny jsou 3 Unit testy:

- Uživatel s rolí čtenář nemá přístup ke správě uživatelů
- Uživatel s rolí autora může upravovat pouze vlastní článek
- Uživatel s rolí admin může upravit uživatele
- Uživatel s rolí čtenář nemá přístup ke správě článků

V rámci testů je nutné založit uživatele a články pro potřeby testování, vše se založí i odstraní automaticky.

Testy se spustí následujícím příkazem:

    docker exec -ti app vendor/bin/phpunit tests