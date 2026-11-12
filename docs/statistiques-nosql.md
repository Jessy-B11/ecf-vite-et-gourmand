# Synchronisation des statistiques NoSQL — premier lot

## Configuration locale

Le projet utilise la bibliothèque officielle `mongodb/mongodb` et l’extension PHP
`mongodb` (branche 2.5). Le Dockerfile installe l’extension 2.5.7 avant Composer.
Avec PHP installé sur la machine, activer aussi cette extension pour le PHP CLI ;
sous Windows, choisir la DLL correspondant à la version PHP, à TS/NTS et à x64.

Pour charger ponctuellement une DLL PHP 8.4 TS x64 placée dans
`var/tools/mongodb-2.5.7/` (ignoré par Git), sans modifier le `php.ini` global :

```powershell
$mongoExtension = (Get-ChildItem var/tools/mongodb-2.5.7 -Filter '*.dll').FullName
php -d "extension=$mongoExtension" bin/console app:stats:sync
php -d "extension=$mongoExtension" tests/stats_sync.php
```

Sur un autre poste, télécharger l’extension depuis les
[releases officielles du pilote PHP](https://github.com/mongodb/mongo-php-driver/releases).

```powershell
php --ri mongodb
composer install
docker compose -p vite-gourmand -f compose.mongodb.yaml up -d
php bin/console app:stats:sync
```

MongoDB local est exposé uniquement sur `127.0.0.1:27017`, avec un volume persistant.
Les valeurs publiques de `.env` sont `MONGODB_URI=mongodb://127.0.0.1:27017` et
`MONGODB_DATABASE=vite_et_gourmand_stats`. PostgreSQL conserve sa configuration
`DATABASE_URL` habituelle.

Placer toute URI authentifiée ou propre à un environnement dans `.env.local`
(ignoré par Git et Docker), ou dans les variables d’environnement de Render.
Ne pas versionner les mots de passe. Dans Render, définir `MONGODB_URI` et
`MONGODB_DATABASE`, en plus des variables PostgreSQL existantes.

## Documents synchronisés

Collection : `commandes` dans la base `MONGODB_DATABASE`.

| Champ | Contenu |
| --- | --- |
| `_id` | Identifiant entier de la commande PostgreSQL, clé unique stable |
| `menu.id`, `menu.nom` | Identifiant et nom courant du menu lié |
| `nombre_personnes` | Nombre de personnes enregistré pour la commande |
| `date_creation` | Date/heure de création, BSON UTCDateTime |
| `date_prestation` | Jour civil `YYYY-MM-DD`, sans conversion de fuseau |
| `statut` | Statut courant enregistré dans PostgreSQL |
| `montant_menu_centimes` | Montant du menu enregistré sur la commande |
| `montant_remise_centimes` | Remise enregistrée sur la commande |
| `frais_livraison_centimes` | Frais de livraison enregistrés sur la commande |
| `montant_total_centimes` | Total enregistré sur la commande |

Tous les montants sont des entiers en centimes. Ils proviennent de `commande`,
jamais d’un recalcul avec `menu.prix_minimum_centimes`. Une modification autorisée
de commande actualise donc aussi ces montants lors de la prochaine synchronisation.
Le nom du menu n’est pas historisé dans le modèle SQL : il reflète son nom courant.

Les huit statuts sont copiés : `en_attente`, `acceptee`, `en_preparation`,
`en_livraison`, `livree`, `attente_retour_materiel`, `terminee`, `annulee`.
Ce premier lot n’agrège pas le chiffre d’affaires et n’écarte pas les annulations.

Aucune identité, adresse, téléphone, clé utilisateur ou jeton de validation n’est
exporté. La lecture SQL sélectionne uniquement les champs statistiques nécessaires.

## Comportement

Chaque document est remplacé avec `replaceOne` et `upsert: true`, filtré sur `_id`.
Une seconde exécution n’ajoute aucun doublon. Les statuts et montants modifiés
sont mis à jour, et les anciens champs hors projection sont supprimés du document.
Les écritures sont acquittées avant de compter un succès.

La commande ne modifie jamais PostgreSQL. Elle renvoie 0 en cas de succès et 1
si MongoDB est inaccessible, si une opération est refusée ou si la lecture SQL
échoue. Les erreurs n’affichent aucune URI ni exception brute contenant des secrets.
Un échec peut laisser une synchronisation partielle dans MongoDB ; relancer la
commande complète les écritures sans doublon. Une absence de commandes reste
un succès si MongoDB est accessible.

Les suppressions SQL ne sont pas propagées dans ce lot. Il s’agit d’une projection
de l’état courant, pas d’un journal des transitions. Utiliser une base MongoDB
distincte pour chaque base PostgreSQL source, car les identifiants peuvent se répéter.

## Vérifications et Docker

```powershell
php tests/stats_sync.php
php bin/console app:stats:sync
php bin/console app:stats:sync
php bin/console lint:container
composer validate --strict --no-check-publish
git diff --check
docker build --progress plain -t vite-gourmand-stats .
docker run --rm vite-gourmand-stats php --ri mongodb
docker run --rm vite-gourmand-stats composer check-platform-reqs --no-dev
```

Le test utilise la connexion PostgreSQL locale, avec des tables temporaires qui
masquent les vraies tables uniquement dans sa transaction. Il vérifie huit statuts,
les dates, les montants historiques, l’absence de données personnelles,
l’idempotence, la mise à jour d’un statut et un serveur MongoDB inaccessible.
Sa base MongoDB porte un nom aléatoire `vg_stats_test_*` et est supprimée à la fin.
Les tables et données PostgreSQL existantes restent inchangées.

Lors d’une exécution de l’image Docker sur le poste, `127.0.0.1` désigne le conteneur.
Utiliser `host.docker.internal` dans les URI pour joindre les services du poste,
ou le nom du service MongoDB si les conteneurs partagent un réseau Docker.
