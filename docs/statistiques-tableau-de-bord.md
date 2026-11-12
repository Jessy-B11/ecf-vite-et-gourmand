# Tableau de bord du gérant

## Accès et données

La page `/admin/statistiques` et son lien de navigation sont réservés à
`ROLE_ADMIN`. Le contrôleur utilise `IsGranted` côté serveur et vérifie que le
compte est actif, comme les autres pages de gestion. Les filtres sont des requêtes
GET : cette page ne modifie aucune donnée.

Les statistiques et les options de menus proviennent exclusivement de la collection
MongoDB `commandes`, via `MONGODB_URI` et `MONGODB_DATABASE`, définies au premier lot.
PostgreSQL reste utilisé pour l’authentification, pas pour les chiffres du tableau
de bord. Les actions sur les commandes actualisent leur document après commit.
En cas de panne MongoDB, `app:stats:sync` rattrape les écritures manquées ; ouvrir
la page ne déclenche pas de synchronisation ni d’écriture.

## Règles affichées

- Le graphique en barres et son tableau comptent les documents de commande par
  identifiant de menu, tous statuts et toutes dates confondus.
- Les filtres par menu, début et fin concernent uniquement le chiffre d’affaires.
- Seul le statut `terminee` entre dans le CA. Une commande livrée ou en attente de
  retour du matériel n’est pas encore terminée et reste exclue.
- Pour chaque commande, le CA est `montant_menu_centimes - montant_remise_centimes`.
  Les frais de livraison sont exclus. Aucun tarif courant de menu n’est recalculé.
- Les sommes MongoDB et le total PHP restent des entiers en centimes. La conversion
  en euros intervient uniquement lors du formatage Twig.
- La période porte sur `date_creation` (BSON UTCDateTime), pas sur `date_prestation`.
  Les deux jours saisis sont inclus dans le fuseau `Europe/Paris` : début à minuit
  inclus et lendemain de la fin à minuit exclu. Ce lendemain est un jour civil,
  y compris lors des changements d’heure (journées de 23 ou 25 heures).
- Chaque borne peut être laissée vide pour une période ouverte.

Le graphique est rendu en HTML/CSS, sans dépendance JavaScript externe, et les
valeurs restent lisibles dans un tableau avec en-têtes et total.

Une période impossible/inversée ou un menu invalide produit une réponse 400 avec
des erreurs lisibles, sans afficher de CA non filtré. Un résultat vide reste une
réponse 200 avec un message explicite et un CA nul. Une indisponibilité MongoDB
produit une réponse 503 et un message distinct : aucun zéro trompeur ni exception
brute contenant une URI authentifiée n’est affiché.

## Vérifications locales

Prérequis : dépendances Composer installées, extension PHP `mongodb` activée,
MongoDB local démarré. Lancer depuis la racine du projet :

```powershell
docker compose -p vite-gourmand -f compose.mongodb.yaml up -d
php bin/console cache:clear --env=test
php tests/statistiques_dashboard.php
php bin/console lint:twig templates
php bin/console lint:container
php bin/console debug:router app_admin_statistiques
git diff --check
```

Sur le poste Windows avec la DLL locale du premier lot, remplacer `php` par :

```powershell
$mongoExtension = (Get-ChildItem var/tools/mongodb-2.5.7 -Filter '*.dll').FullName
php -d "extension=$mongoExtension" tests/statistiques_dashboard.php
```

L’extension doit également être chargée par le PHP qui sert les pages web. Pour
le serveur de développement PHP, après avoir libéré le port 8000 si nécessaire :

```powershell
php -d "extension=$mongoExtension" -S 127.0.0.1:8000 -t public
```

Avec Apache/Wamp, activer l’extension dans la configuration PHP utilisée par Apache,
puis redémarrer ce service. L’image Docker du projet charge déjà l’extension.

Le test HTTP utilise les vrais formulaires de connexion et les vraies agrégations
MongoDB, avec des comptes dans SQLite temporaire et une base MongoDB aléatoire
`vg_dashboard_test_*`, supprimées à la fin. Aucune table SQL de commandes ou de menus
n’est créée : une dépendance involontaire à PostgreSQL pour les statistiques ferait
échouer le test.

Le jeu initial contient 13 documents : 6 commandes pour Menu Bordeaux, 6 pour
Menu Jardin et 1 pour Menu Sans vente. Cinq commandes sont terminées :
39 500 centimes pour Bordeaux et 10 000 pour Jardin, soit **495,00 €** au total.
Le 10 janvier 2026, le total attendu est **360,00 €**, dont **260,00 €** pour
Bordeaux. Des dates de prestation différentes et des frais de livraison élevés
vérifient qu’ils ne participent pas au filtre ou au CA. Tous les autres statuts
sont représentés et exclus du CA.

Les autres vérifications couvrent les droits visiteur/client/employé/gérant,
la navigation, les montants entiers, les bornes inclusives à la milliseconde,
les dates ouvertes, les journées de changement d’heure, les menus sans vente,
les paramètres invalides, la base vide et MongoDB inaccessible.

À contrôler visuellement dans le navigateur : lisibilité du graphique et des
tableaux sur ordinateur/mobile, navigation au clavier et présentation des erreurs.
