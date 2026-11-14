# Vite & Gourmand

Application de commande de menus pour un traiteur bordelais, réalisée dans le cadre de l’ECF Studi. Symfony gère le catalogue, les commandes et les comptes dans PostgreSQL. MongoDB alimente le tableau de bord des statistiques.

- [Application déployée sur Render](https://vite-gourmand-recette.onrender.com/)
- [Suivi du projet sur Trello](https://trello.com/b/zi6AeD7k/projet-vite-gourmand)

## 1. Prérequis

- Git et Composer 2.
- PHP 8.4 avec `pdo_pgsql` et l’extension `mongodb` 2.5.x. Sous Windows, utilise une DLL MongoDB correspondant à la version de PHP, à l’architecture et au mode TS ou NTS du PHP indiqué par `php -i`. Active-la dans le fichier indiqué par `php --ini`. Ajoute `pdo_sqlite` pour les tests qui utilisent SQLite.
- PostgreSQL et le client `psql`. Les scripts peuvent aussi être exécutés dans pgAdmin.
- Docker avec Compose pour démarrer MongoDB en local.
- Mailpit pour recevoir les e-mails de développement.

Les commandes ci-dessous sont prévues pour PowerShell. Depuis un terminal où PHP et Composer sont accessibles, clone la branche utilisée pour le déploiement.

```powershell
git clone --branch develop https://github.com/Jessy-B11/ecf-vite-et-gourmand.git
Set-Location ecf-vite-et-gourmand
php -v
php --ri pdo_pgsql
php --ri mongodb
composer install
composer check-platform-reqs
```

## 2. Initialiser PostgreSQL

Connecte-toi avec le compte d’administration PostgreSQL. Dans `psql`, crée un rôle local et sa base vide. La commande `\password` te demande le mot de passe à utiliser pour ce rôle.

```powershell
psql -h 127.0.0.1 -U postgres -d postgres
```

```sql
CREATE ROLE vite_gourmand LOGIN;
\password vite_gourmand
CREATE DATABASE vite_et_gourmand OWNER vite_gourmand;
\q
```

Dans pgAdmin, tu peux créer le même rôle et la même base avec **Login/Group Roles** et **Databases**, puis lancer les deux fichiers dans le **Query Tool** de la base vide en te connectant avec le rôle créé. Avec `psql`, depuis la racine du projet, lance les fichiers dans cet ordre.

```powershell
psql -h 127.0.0.1 -U vite_gourmand -d vite_et_gourmand -v ON_ERROR_STOP=1 -f sql/01_creation.sql
psql -h 127.0.0.1 -U vite_gourmand -d vite_et_gourmand -v ON_ERROR_STOP=1 -f sql/02_donnees.sql
```

## 3. Configurer Symfony

Crée `.env.local` à la racine. Remplace `MOT_DE_PASSE` et `VERSION_POSTGRES` dans l’URL. Pour connaître la version, exécute `SHOW server_version;` dans PostgreSQL et utilise sa partie numérique, par exemple `16.6`. Encode les caractères réservés du mot de passe dans l’URL si nécessaire.

```dotenv
APP_ENV=dev
DEFAULT_URI=http://127.0.0.1:8000
DATABASE_URL="postgresql://vite_gourmand:MOT_DE_PASSE@127.0.0.1:5432/vite_et_gourmand?serverVersion=VERSION_POSTGRES&charset=utf8"
MONGODB_URI="mongodb://127.0.0.1:27017"
MONGODB_DATABASE=vite_et_gourmand_stats
MAILER_DSN=smtp://127.0.0.1:1025
MAILER_FROM_EMAIL=bonjour@vite-et-gourmand.example
ORS_API_KEY=
```

Renseigne une clé OpenRouteService pour tester le calcul de livraison hors Bordeaux. Place les secrets dans `.env.local`. Ce fichier est ignoré par Git.

Applique la migration Doctrine après l’import SQL. Elle ajoute la révision utilisée pour synchroniser les commandes vers MongoDB.

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:status
```

## 4. Démarrer les services locaux

```powershell
docker compose -p vite-gourmand -f compose.mongodb.yaml up -d
docker compose -p vite-gourmand -f compose.mongodb.yaml ps
```

Lance Mailpit dans un terminal avec `mailpit`, ou `.\mailpit.exe` depuis le dossier de l’exécutable Windows. Ouvre [la boîte de réception locale](http://127.0.0.1:8025/).

Dans un autre terminal, à la racine du projet, lance le serveur PHP.

```powershell
php -S 127.0.0.1:8000 -t public
```

Ouvre [Vite & Gourmand en local](http://127.0.0.1:8000/). Le catalogue se trouve sur `/menus`.

## 5. Préparer les comptes de démonstration

- **Client** — crée un compte sur `/register` ou lance `php bin/console app:demo:clients`. Cette commande affiche la base ciblée avant confirmation et affiche les mots de passe des nouveaux comptes une seule fois.
- **Gérant** — inscris un compte dédié sur `/register`. Sur la base **locale**, attribue-lui `ROLE_ADMIN` avec la requête suivante. Remplace l’adresse d’exemple par celle du compte créé, puis reconnecte-toi sur `/login`.
- **Employé** — connecté comme gérant, crée un compte sur `/admin/employes/creer`. Le message de notification apparaît dans Mailpit.

```sql
UPDATE public.utilisateur
SET roles = '["ROLE_ADMIN"]'
WHERE email = 'gerant@example.com'
  AND roles::jsonb = '["ROLE_CLIENT"]'::jsonb
RETURNING id, email, roles;
```

La requête doit retourner une ligne. Les identifiants du site hébergé sont à fournir dans le manuel de démonstration.

## 6. Vérifier l’installation

```powershell
php bin/console lint:container
php bin/console lint:twig templates
php bin/console app:stats:sync
```

1. Ouvre le catalogue et passe une commande avec un compte client. Vérifie qu’elle apparaît dans `/mes-commandes` et que le message de confirmation arrive dans Mailpit.
2. Avec un compte employé, ouvre `/gestion/commandes` et fais avancer la commande jusqu’à **Terminée**.
3. Avec le gérant, ouvre `/admin/statistiques` et vérifie que la commande alimente le graphique et le chiffre d’affaires. L’actualisation est automatique. La commande `app:stats:sync` sert à l’import initial et au rattrapage des écritures vers MongoDB.

## Documentation du dépôt

- [Documentation du projet](docs/documentation-projet.md) et [dictionnaire de données](docs/dictionnaire-donnees.md)
- [Statistiques MongoDB](docs/statistiques-nosql.md) et [actualisation automatique](docs/statistiques-automatiques.md)
- [E-mails avec Mailpit et Brevo sur Render](docs/emails-brevo-render.md)
- [Wireframes](docs/WireFrames.pdf), [maquettes en couleur](docs/Vite%20%26%20Gourmand%20%E2%80%94%20Mockups%20couleur.pdf) et [charte graphique](docs/Charte%20Graphique.pdf)
