# Horaires dans le pied de page

## Lecture et affichage

Les tables `horaire_jour` et `creneau_horaire` de `sql/01_creation.sql` restent la source des horaires. Lors de l’inspection locale du 18 novembre 2026, elles existaient dans `vite_et_gourmand` et étaient vides. Aucun horaire de démonstration n’a été chargé dans les tables réelles.

Les entités `HoraireJour` et `CreneauHoraire` mappent ces tables existantes. `HoraireJourRepository::findSemaineAvecCreneaux()` utilise une jointure pour récupérer les jours et leurs créneaux en **une requête SQL**, triés par jour puis par heure. Les contrôleurs n’ont pas à charger les horaires.

La fonction Twig `horaires_footer()` est enregistrée par autoconfiguration Symfony dans `HorairesExtension`. Elle est appelée seulement dans le partial du footer. Elle construit toujours les sept jours, de lundi (1) à dimanche (7), et formate les heures sans conversion de fuseau, car les colonnes PostgreSQL sont des `TIME` locaux.

- Jour déclaré fermé : **Fermé**, même si d’anciens créneaux subsistent.
- Jour ouvert avec créneaux : par exemple **09h00–12h00**, puis **14h00–18h00**.
- Jour absent ou ouvert sans créneau : **Horaires non renseignés**.

Le résultat est mémorisé dans une `WeakMap` associée à la requête principale : un second appel pendant la même requête ne refait pas de lecture. Une autre requête lit à nouveau PostgreSQL, sans cache persistant à invalider. Le nombre de menus ne change pas le nombre de requêtes horaires. Le catalogue actuel renvoie une page complète même lors du filtrage JavaScript ; cette réponse coûte donc elle aussi une seule lecture des horaires.

Le footer conserve sa marque et ses liens, avec trois colonnes sur grand écran. À 900 px et moins, ses blocs sont empilés ; les jours et heures restent alignés dans chaque ligne. Aucune interface de modification ni écriture automatique n’est ajoutée.

## Horaires proposés dans les données initiales

Le bloc horaires de `sql/02_donnees.sql`, placé avant le `COMMIT` final, contient les **valeurs proposées pour le projet**, à faire valider avant de les présenter comme horaires réels :

| Jours | Horaires proposés |
|---|---|
| Lundi à vendredi | 09h00–12h00 et 14h00–18h00 |
| Samedi | 10h00–14h00 |
| Dimanche | Fermé |

Sur des tables vides, ce bloc crée **7 jours et 11 créneaux**. Il partage la transaction des autres données initiales. `ON CONFLICT (jour_semaine) DO NOTHING` conserve chaque jour déjà présent ; `RETURNING` ne fournit que les jours nouvellement créés à l’insertion des créneaux. Un jour existant sans créneau reste sans créneau. Ni les créneaux présents ni l’état fermé d’un jour ne sont remplacés. Un verrou pendant cette courte transaction sérialise les écritures dans les deux tables.

**Seul le bloc horaires est rejouable. Le reste de `02_donnees.sql` ne l’est pas : ne pas réexécuter le fichier complet sur une base déjà initialisée.**

## Installation sur une nouvelle base vide

Depuis la racine du dépôt, avec le client `psql`, remplacer `VOTRE_ROLE` par le rôle PostgreSQL local. Le mot de passe est demandé par `-W` et n’est pas écrit dans la commande.

```powershell
psql -h 127.0.0.1 -p 5432 -U VOTRE_ROLE -d vite_et_gourmand -W -v ON_ERROR_STOP=1 -f sql/01_creation.sql
psql -h 127.0.0.1 -p 5432 -U VOTRE_ROLE -d vite_et_gourmand -W -v ON_ERROR_STOP=1 -f sql/02_donnees.sql
```

Dans pgAdmin, ouvrir le Query Tool de la nouvelle base vide et exécuter les deux fichiers dans cet ordre. Les horaires sont inclus dans le second fichier ; aucune étape de chargement séparée n’est nécessaire. L’installation initiale complète a été vérifiée sur une nouvelle base PostgreSQL créée pour le test puis supprimée.

## Bases déjà initialisées : bloc pgAdmin

Pour la base locale existante ou la base PostgreSQL Render, ouvrir le **Query Tool de la base concernée** dans pgAdmin. Vérifier la connexion avec `SELECT current_database();`, puis exécuter uniquement le bloc ci-dessous. Les tables doivent déjà exister. Le bloc peut être rejoué et ne remplace pas les horaires présents.

```sql
BEGIN;

-- DEBUT HORAIRES PROPOSES POUR LE PROJET
-- Valeurs proposées pour le projet, à faire valider avant de les présenter comme horaires réels.
-- Lundi-vendredi : 09h00–12h00 et 14h00–18h00 ; samedi : 10h00–14h00 ; dimanche : fermé.
-- Les jours existants et leurs créneaux sont conservés, même si aucun créneau n'est renseigné.
LOCK TABLE horaire_jour, creneau_horaire IN SHARE ROW EXCLUSIVE MODE;

WITH nouveaux_jours AS (
    INSERT INTO horaire_jour (jour_semaine, ferme)
    VALUES (1, FALSE), (2, FALSE), (3, FALSE), (4, FALSE),
           (5, FALSE), (6, FALSE), (7, TRUE)
    ON CONFLICT (jour_semaine) DO NOTHING
    RETURNING id, jour_semaine
)
INSERT INTO creneau_horaire (horaire_jour_id, heure_ouverture, heure_fermeture)
SELECT jour.id, creneau.ouverture, creneau.fermeture
FROM nouveaux_jours jour
JOIN (VALUES
    (1, TIME '09:00', TIME '12:00'), (1, TIME '14:00', TIME '18:00'),
    (2, TIME '09:00', TIME '12:00'), (2, TIME '14:00', TIME '18:00'),
    (3, TIME '09:00', TIME '12:00'), (3, TIME '14:00', TIME '18:00'),
    (4, TIME '09:00', TIME '12:00'), (4, TIME '14:00', TIME '18:00'),
    (5, TIME '09:00', TIME '12:00'), (5, TIME '14:00', TIME '18:00'),
    (6, TIME '10:00', TIME '14:00')
) AS creneau(jour_semaine, ouverture, fermeture)
    ON creneau.jour_semaine = jour.jour_semaine;
-- FIN HORAIRES PROPOSES POUR LE PROJET

COMMIT;
```

Pour vérifier le résultat :

```sql
SELECT h.jour_semaine, h.ferme, c.heure_ouverture, c.heure_fermeture
FROM horaire_jour h
LEFT JOIN creneau_horaire c ON c.horaire_jour_id = h.id
ORDER BY h.jour_semaine, c.heure_ouverture, c.heure_fermeture;
```

La base locale et Render déjà initialisées n’ont pas été modifiées pendant les tests. Le chargement de ces valeurs proposées reste une opération manuelle dans pgAdmin.

## Déploiement sur Render

1. Déployer les fichiers PHP, Twig et CSS de l’affichage des horaires avec l’application. La configuration `DATABASE_URL` existante est réutilisée ; aucune nouvelle variable ni dépendance Composer n’est nécessaire. Les identifiants restent dans les variables d’environnement Render ou `.env.local`, hors Git.
2. Sur une base Render déjà installée, utiliser le bloc pgAdmin ci-dessus uniquement. Ne pas rejouer les deux scripts d’installation complets. Depuis l’ordinateur, la connexion pgAdmin utilise les paramètres externes PostgreSQL Render et TLS ; les accès réseau doivent autoriser cette connexion. [Documentation Render : connexions PostgreSQL](https://render.com/docs/postgresql-creating-connecting#external-connections).
3. Si le processus de déploiement ne reconstruit pas déjà le cache Symfony, exécuter dans l’environnement applicatif :

```sh
php bin/console cache:clear --env=prod --no-debug
php bin/console lint:container --env=prod --no-debug
```

4. Contrôler `/`, `/menus` et `/login` : sept libellés, créneaux exacts, fermeture du dimanche si les valeurs proposées ont été chargées, empilement mobile. Les données sont relues dès la requête suivante, sans purge supplémentaire du cache des horaires.

## Vérifications locales

```sh
php tests/horaires_footer.php
php tests/horaires_postgresql.php
php tests/installation_postgresql.php
php tests/creation_employe.php
php tests/emails_transactionnels.php
php bin/console doctrine:schema:validate --skip-sync
php bin/console lint:twig templates
php bin/console lint:container --resolve-env-vars
git diff --check
```

Le premier test crée puis supprime une base SQLite temporaire : sept jours, plusieurs créneaux, fermeture prioritaire, données vides et partielles, footer sur accueil/connexion/catalogue de six menus, comptage SQL et mémoïsation limitée à la requête.

Le second utilise le PostgreSQL local configuré, mais écrit seulement dans deux tables **TEMP** copiant les tables publiques. Il extrait le bloc horaires de `02_donnees.sql`, vérifie le véritable mapping ORM et le rendu PostgreSQL, deux exécutions identiques et la préservation d’une semaine déjà partiellement renseignée. Les tables publiques sont comparées avant/après et doivent rester identiques. Les copies et leurs séquences disparaissent à la fermeture de la connexion.

`tests/installation_postgresql.php` nécessite le droit PostgreSQL `CREATEDB`. Il crée une base vide avec un nom aléatoire réservé aux tests, y exécute **les fichiers complets** `01_creation.sql` puis `02_donnees.sql`, vérifie les données initiales du catalogue, les sept jours, les onze créneaux et le rejeu du bloc horaires seul, puis ferme la connexion et supprime cette base de test. Il ne réinitialise pas la base de travail.

Les jeux de test existants de création d’employé, de statistiques et d’e-mails incluent désormais les deux mappings horaires quand ils construisent un schéma minimal : ces pages rendent aussi le footer commun. Leurs règles métier ne changent pas. Le test des statistiques reste soumis à son prérequis MongoDB local.

Les fichiers PHP ajoutés/modifiés doivent aussi passer `php -l`. Pour PHP en ligne de commande sous Windows, les contrôles du conteneur peuvent nécessiter l’activation de l’extension `mongodb` déjà requise par le projet.

Résultats obtenus localement : les deux tests horaires, les suites création d’employé et e-mails transactionnels, la syntaxe PHP, le mapping Doctrine, le lint Twig (34 templates), le lint du conteneur et `git diff --check` passent. `doctrine:schema:validate --skip-sync` vérifie les mappings ; il ne compare pas tout le schéma de la base. La suite `tests/statistiques_dashboard.php` n’a pas été relancée : elle nécessite MongoDB local ; seule sa création du schéma de test a été complétée.

Le regroupement dans les données initiales a été vérifié avec l’installation complète sur une base PostgreSQL vide, les tests horaires PostgreSQL et SQLite, le contrôle de syntaxe des deux tests PHP concernés et `git diff --check`.

Le rendu est contrôlé en Chrome à 390 et 1440 px sur les semaines complète, fermée/à plusieurs créneaux, vide et partielle. Restent à vérifier les valeurs réellement choisies par le gérant, le chargement sur Render et les autres navigateurs.

## Fichiers du lot

- `src/Entity/HoraireJour.php`, `src/Entity/CreneauHoraire.php` : mappings des tables existantes.
- `src/Repository/HoraireJourRepository.php` : lecture jointe ordonnée.
- `src/Twig/HorairesExtension.php` : sept jours français, formatage et mémoïsation par requête.
- `templates/partials/footer.html.twig`, `public/css/style.css` : bloc « Nos horaires » et mise en page responsive.
- `sql/02_donnees.sql` : horaires proposés inclus dans la transaction d’initialisation, avant le `COMMIT` final.
- `README.md` : installation complète et distinction avec les bases déjà initialisées.
- `tests/horaires_footer.php`, `tests/horaires_postgresql.php`, `tests/installation_postgresql.php` : tests dédiés.
- `tests/creation_employe.php`, `tests/emails_transactionnels.php`, `tests/statistiques_dashboard.php` : schémas de test complétés.
- `docs/horaires-footer.md` : fonctionnement et déploiement.
