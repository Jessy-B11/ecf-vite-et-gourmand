# Actualisation automatique des statistiques

## Mise en place

Après installation du code, appliquer la migration additive PostgreSQL :

```powershell
php bin/console doctrine:migrations:migrate --no-interaction
```

Elle ajoute `commande.version_statistiques`, initialisée à 1, et un trigger qui
l’incrémente à chaque UPDATE. La révision est générée par PostgreSQL et mappée en
lecture seule dans Doctrine. Elle n’introduit pas de verrouillage optimiste dans
les règles métier existantes. Pour une reconstruction depuis les scripts SQL
initiaux du dépôt, appliquer également cette migration après ces scripts.

MongoDB doit accepter les mises à jour par pipeline (MongoDB 4.2+, compatible avec
la configuration locale MongoDB 8 et Atlas). Le projet utilise la bibliothèque
officielle PHP déjà installée. Les URI authentifiées restent hors Git.

## Points de déclenchement

Ces neuf services appellent `ActualisationStatistiquesCommande` uniquement après
le retour réussi de `EntityManager::wrapInTransaction`, donc après flush et commit :

- `EnregistrementCommande` : création ou renvoi du même jeton, avec l’identifiant
  de la commande effectivement enregistrée ou retrouvée ;
- `AcceptationCommande` et `SuiviCommande` : acceptation et transitions, dont la
  fin d’une commande sans matériel ;
- `RetourMaterielCommande` : fin d’une commande après restitution ;
- `AnnulationCommande` et `AnnulationCommandeEquipe` : annulations client/équipe ;
- `ModificationCommande` et `ModificationCommandeEquipe` : modification des
  personnes, dates, coordonnées ou montants ;
- `PretMaterielCommande` : modification du prêt prévu.

Les setters de menu des contrôleurs concernent les brouillons de nouvelles commandes.
Les écrans de modification existants conservent le menu. La projection ciblée relit
néanmoins le menu courant et tous les montants dans PostgreSQL : elle ne réutilise
jamais un ancien objet ORM ou un ancien récapitulatif.

Même une action idempotente sans nouvelle modification tente la synchronisation,
afin de pouvoir rattraper un précédent échec sur cette commande. Si le commit SQL
échoue, aucun appel MongoDB n’a lieu.

La méthode ciblée refuse une connexion qui possède encore une transaction active.
Les contrôleurs actuels n’ouvrent pas de transaction extérieure aux services. Si
un futur appel englobe le service dans une autre transaction, la projection est
refusée et journalisée ; il devra être raccordé au commit extérieur ou rattrapé
par la commande CLI. Aucune donnée non validée n’est publiée.

## Ordre des écritures et rattrapage

`SynchronisationStatistiques::synchroniserCommande` reçoit uniquement un identifiant,
relit l’état SQL validé et utilise la même projection que `app:stats:sync`.

MongoDB conserve `_id = commande.id` et `version_source = commande.version_statistiques`.
Une pipeline `updateOne` avec `upsert` compare atomiquement la révision avant de
remplacer le document. Si elle est inférieure à la révision déjà présente, MongoDB
conserve le document récent. Une révision identique est rejouable sans doublon.
`$literal` préserve aussi les noms de menus contenant un dollar.

Cette protection s’applique également à une synchronisation complète retardée,
à un renvoi et à une requête réseau dont l’exécution arriverait après une autre.
Les anciens documents sans révision sont considérés comme antérieurs à la révision 1.
La projection conserve la liste explicite des seules données statistiques ; aucune
identité, adresse, téléphone, clé client ou jeton n’est ajouté dans MongoDB.

Une erreur de synchronisation automatique n’annule pas l’action déjà validée dans
PostgreSQL. Le journal contient un message fixe, l’identifiant de commande et la
classe de l’erreur, jamais l’URI, le message d’exception ou l’objet exception.
Un arrêt du processus entre commit et projection reste également rattrapable :

```powershell
php bin/console app:stats:sync
```

La commande complète continue de signaler ses propres erreurs avec un code d’échec.
Elle ne recrée pas de doublon et ne peut pas régresser la révision d’un document.

## Tests

```powershell
php tests/statistiques_automatiques.php
php tests/stats_sync.php
php tests/statistiques_dashboard.php
php bin/console lint:container
git diff --check
```

Sur le poste Windows, si l’extension n’est pas activée dans le PHP CLI :

```powershell
$mongoExtension = (Get-ChildItem var/tools/mongodb-2.5.7 -Filter '*.dll').FullName
php -d "extension=$mongoExtension" tests/statistiques_automatiques.php
```

Le test automatique force MongoDB local (`127.0.0.1:27017`), sans écrire sur Atlas.
Il crée un schéma PostgreSQL et une base MongoDB aléatoires `vg_stats_auto_test_*`,
supprimés à la fin. Les transactions métier sont réellement validées dans ce schéma.
La vraie migration et son trigger y sont exécutés.

Cas vérifiés : création et renvoi, transitions successives et fin de commande,
restitution du matériel, annulations client/équipe, modifications client/équipe,
menu relu depuis SQL, refus avant commit extérieur, panne MongoDB lors de création,
acceptation et annulation, journaux sans secret, puis rattrapage CLI. Un second
lecteur PostgreSQL en REPEATABLE READ rejoue un état ancien après les mises à jour
et vérifie qu’il ne remplace pas le document récent. Un trigger différé provoque
enfin un refus au COMMIT, après le flush, pour vérifier l’absence de projection.

Référence : [mises à jour MongoDB par pipeline](https://www.mongodb.com/docs/manual/tutorial/update-documents-with-aggregation-pipeline/).
