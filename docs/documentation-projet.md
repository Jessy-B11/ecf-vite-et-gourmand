# Vite & Gourmand — Documentation du projet

**Auteur : Jessy Boehm**  
**ECF : TP Développeur Web et Web Mobile — Studi**  
**Version : 1.4 — 18 novembre 2026**\
**Remise annoncée : 19 novembre 2026**

> Document de travail évolutif. Les sections « Réalisé » reposent sur les extraits de code et les essais partagés pendant le développement. Le dépôt complet n’a pas fait l’objet d’un audit indépendant. Une fonctionnalité prévue ou représentée dans une maquette n’est pas présentée comme développée. Les versions indiquées sont celles de l’environnement utilisé, sans affirmation sur les dernières versions disponibles.

## 1. Objet et sources

Ce document commence les deux documentations demandées pages 11–12 de l’énoncé : la gestion de projet et la documentation technique. Elles sont regroupées ici pour faciliter les mises à jour ; elles pourront être séparées avant la remise.

Sources de cette version. Énoncé Studi, dictionnaire de données, scripts et extraits PHP/Twig/JS partagés, essais confirmés jusqu’au 15 novembre, suivi documentaire et demandes du 16 au 18 novembre, historique public de `develop`, vérifications visuelles sur Render et tableau Trello. Le modèle de copie à rendre Studi sert à préparer la section 15.

Ce document ne remplace ni la copie à rendre Studi, ni le README d’installation, ni le manuel utilisateur PDF, ni les exports graphiques.

| Ressource | Lien ou emplacement prévu |
|---|---|
| Dépôt GitHub | https://github.com/Jessy-B11/ecf-vite-et-gourmand |
| Gestion de projet | https://trello.com/b/zi6AeD7k/projet-vite-gourmand |
| Figma | https://www.figma.com/design/t3Ibv9fml9omqR1O9QEzZq/Sans-titre |
| Documentation évolutive | docs/documentation-projet.md |
| Dictionnaire | docs/dictionnaire-donnees.md |
| Création et données SQL | `01_creation.sql`, `02_donnees.sql` et `03_menus_demo.sql` dans `sql/` |
| Application publique | https://vite-gourmand-recette.onrender.com/ — recette Render |

## 2. Présentation et objectifs

Vite & Gourmand est une entreprise bordelaise de Julie et José, active depuis 25 ans. Le projet doit rendre ses menus accessibles au public et permettre aux clients de commander. Les équipes doivent gérer le catalogue, les commandes, les horaires et les avis. L’administrateur dispose en plus de la gestion des employés et de statistiques.

Le périmètre comprend une base relationnelle et une base non relationnelle, toutes deux obligatoires. L’application doit être déployée et accessible conformément aux exigences du sujet. Le choix de Symfony, de PostgreSQL ou d’un hébergeur particulier n’est pas imposé par l’énoncé.

### Acteurs et droits attendus

| Acteur | Fonctions attendues | État au 15 novembre |
|---|---|---|
| Visiteur | Consulter accueil/menus/détails, filtrer, contacter, créer un compte | Accueil, catalogue de huit menus et fiche menu déployés. Le footer commun est présent. Lecture des horaires réalisée localement. Chargement Render, contact et pages légales à terminer. |
| Client connecté | Commander, consulter ses commandes, modifier son profil, modifier/annuler avant acceptation, suivre et donner un avis | Comptes, commande, consultation personnelle, historique, annulation et modification avant acceptation testés ; profil validé. Dépôt d’un avis par commande terminée et invitation par e-mail réalisés. |
| Employé | Gérer menus/plats/horaires, suivre et gérer commandes après contact, modérer avis | Liste, détail/historique, acceptation, préparation/livraison, terminaison et prêt/restitution testés ; filtres, pagination, modification et annulation après contact, et modération des avis réalisés. Catalogue et horaires à développer. |
| Administrateur | Droits employé, créer/désactiver employés, statistiques | Création d’employés et statistiques automatiques opérationnelles sur Render. La notification de création a été reçue sur Render. La désactivation des employés reste à traiter. |

Une table utilisateur commune est retenue. La séparation des droits doit être effectuée côté serveur : avoir des tables séparées ne garantirait pas la sécurité. La propriété des commandes doit être contrôlée en complément du rôle.

## 3. Gestion de projet

### 3.1 Méthode de travail

Le travail avance par fonctionnalités, avec une première conception, une implémentation limitée, un contrôle local puis un commit. L’accompagnement est progressif : le développeur réalise les manipulations dans son environnement et vérifie les résultats. Les décisions doivent pouvoir être expliquées à l’évaluation, y compris lorsque des outils d’assistance ont aidé à préparer du code ou des documents.

Trello sert de tableau Kanban : Backlog, À faire, En cours, À vérifier et Terminé. Les cartes contiennent l’objectif, le travail déjà réalisé, les critères de réussite et le reste à faire. Les cartes séparatrices ne représentent pas des fonctionnalités. Certaines cartes concernent le traitement serveur et d’autres son écran ; elles ne doivent pas être comptées comme autant de fonctionnalités indépendantes.

« Terminé » signifie que le périmètre local décrit a été testé, et non que tout le domaine a été audité. La carte de sécurité, les tests globaux et la validation en production restent distincts. La création d’une table ne suffit pas à terminer la fonctionnalité qui l’utilise.

### 3.2 Git et organisation des branches

Le sujet demande une branche principale, une branche de développement et des branches de fonctionnalité issues de develop. Après validation, une fonctionnalité rejoint develop ; main recevra une version testée.

État Git au 12 novembre. `feature/gestion-commandes` a rejoint `develop` au commit `04ea298`. Le service Render suit `develop`. Le tableau de bord MongoDB et son actualisation automatique sont déployés et testés sur Render selon le retour de recette. Au 15 novembre, `develop` est à `3eae360` et `main` à `1110f34` sur GitHub. Les observations sur Render sont consignées par page en section 11. La fusion de remise et le SHA effectivement déployé restent à consigner.

| Branche | Dernier commit confirmé | Situation |
|---|---|---|
| main | 1110f34, dernière sortie communiquée | Intégration finale à vérifier. |
| develop | 04ea298, jalon d’intégration vérifié | Branche reliée au déploiement Render. Des commits plus récents ont été poussés pour le déploiement, l’accueil, l’interface et les comptes. |
| feature/gestion-commandes | 04ea298 | Branche de fonctionnalité intégrée à develop. |
| feature/base-de-donnees | 7029f37 | Branche historique ayant aussi accueilli catalogue et comptes |
| feature/commandes | Dernier SHA non communiqué ; eb17e48 est un repère antérieur | Enregistrement/stock/historique, livraison ORS, confirmation mail, consultation, annulation, modification client, profil/navigation et liste/acceptation employé : commits/push confirmés ; arbre propre confirmé après le dernier jalon |

La branche `feature/commandes` contient les jalons précédents. La commande `git log --oneline HEAD..origin/feature/commandes`, exécutée après `git fetch origin`, permet de vérifier leur présence dans l’historique de la branche active. Les secrets locaux DATABASE_URL, MAILER_DSN et ORS_API_KEY restent dans la configuration locale. La visibilité publique du dépôt est à vérifier avant la remise.

### 3.3 Jalons et priorités proposées

Ces dates sont un objectif de travail, pas des réalisations acquises.

| Période indicative | Objectif |
|---|---|
| 18–25 octobre | Commande : données, formulaire, calculs, stock, historique et email ; choisir l’hébergement et faire un premier déploiement technique |
| 26 octobre–2 novembre | Espace client puis gestion employé/admin, statuts, matériel et avis |
| 3–8 novembre | Statistiques NoSQL, accueil/contact/horaires/légal, finition visuelle ; déploiement fonctionnel |
| 9–15 novembre | Recette complète en ligne, sécurité, accessibilité, corrections et documents |
| 16–18 novembre | Vérifier tous les liens et livrables, compte de démonstration, copie Studi et marge de correction |
| 19 novembre | Remise annoncée |

Le chemin critique reste le parcours de commande, ses autorisations et ses règles. Le déploiement et les documents avancent en parallèle pour éviter de les découvrir en fin de projet.

## 4. Choix techniques et environnement

### 4.1 Choix retenus et justification

| Choix | Justification pour ce projet | Limite ou alternative considérée |
|---|---|---|
| Symfony 7.4 / PHP | Première expérience personnelle de Symfony ; structure contrôleurs/services/formulaires et composants d’authentification réutilisables | Plus d’abstractions que PHP/PDO direct ; comprendre les mécanismes générés reste nécessaire |
| Doctrine ORM/DBAL | Associer les objets PHP aux tables et construire des requêtes paramétrées ; relations catalogue explicites | L’ORM ne remplace pas le SQL ; scripts de création/données conservés et nécessaires au livrable |
| PostgreSQL | Base déjà disponible ; relations, clés étrangères, contraintes et transactions adaptées aux commandes | Le schéma manuel et les mappings doivent rester cohérents |
| Twig et CSS personnalisé | Rendu serveur simple et cohérent avec Symfony ; styles adaptés à la charte | Bootstrap n’est pas utilisé ; responsive et accessibilité restent à vérifier par composants |
| JavaScript natif et fetch | Actualiser le catalogue sans recharger la page sans ajouter un framework frontal | Gestion explicite du chargement et des erreurs nécessaire |
| Symfony Security/Form/Validator | Centraliser authentification, formulaires et validations serveur | Rôles, propriété et règles métier doivent encore être programmés |
| Symfony HttpClient | Appeler géocodage et itinéraire depuis PHP, avec délais et validation de réponse ; conserver les secrets côté serveur | Dépendance à des services externes ; disponibilité et quotas à gérer |
| IGN pour le géocodage / OpenRouteService pour l’itinéraire | Proposer une adresse numérotée puis calculer une distance routière aller en voiture ; ORS retenu après des erreurs 404 IGN | Clé ORS locale, quotas, attribution à finaliser ; aucun fournisseur ne garantit à lui seul la démonstration hors réseau |
| Symfony Mailer + Mailpit local / Brevo HTTPS sur Render | Tester les messages en local puis envoyer par API HTTPS en production | Envoi direct ; pas de file persistante ni de réessai automatique. Configuration section 4.3 et fonctionnement section 7.4. |
| SymfonyCasts ResetPasswordBundle | Parcours de jeton de réinitialisation avec expiration et usage unique | Configuration et tests des cas d’erreur à compléter |
| MongoDB Atlas et bibliothèque PHP MongoDB | Fournir les données du graphique et du chiffre d’affaires. PostgreSQL conserve la référence des commandes. | Intégré et vérifié sur Render. |

Ces justifications expliquent les choix actuels du projet ; elles ne constituent pas un classement général des technologies.

### 4.2 Poste de développement

Windows, VS Code, PowerShell intégré ; projet dans `C:\Env\Workspace\ECF\App ECF`.

| Composant | Configuration constatée |
|---|---|
| PHP | 8.4.15, C:\Env\Wamp\bin\php\php8.4.15 |
| Configuration PHP CLI | C:\Env\Wamp\bin\php\php8.4.15\php.ini |
| Extension PostgreSQL | pdo_pgsql activée |
| Symfony | 7.4.20 constaté initialement ; HttpClient 7.4.21 confirmé installé le 19 octobre ; relire composer.lock pour les versions finales |
| PostgreSQL | 18.3, localhost:5432 |
| Base de travail | vite_et_gourmand |
| Base de reconstruction antérieure | vite_et_gourmand_test_sql |
| Gestion des dépendances | Composer et composer.lock versionné |
| Serveur local | PHP intégré, 127.0.0.1:8000, racine public/ |
| Capture des mails | Mailpit : SMTP 127.0.0.1:1025, interface 127.0.0.1:8025 |

Le port de l’interface pgAdmin est distinct du port PostgreSQL. Le serveur PHP intégré sert au développement local ; Render utilise l’image Docker du dépôt (section 13).

Commandes locales déjà utilisées, depuis la racine du projet :

```powershell
composer install
php bin/console about
php bin/console cache:clear
php -S 127.0.0.1:8000 -t public
```

Mailpit est lancé dans un autre terminal :

```powershell
& "C:\Env\Mailpit\mailpit.exe"
```

Les variables de livraison `ORS_API_KEY`, `LIVRAISON_DEPART_ADRESSE`, `LIVRAISON_DEPART_LONGITUDE`, `LIVRAISON_DEPART_LATITUDE`, `LIVRAISON_COMMUNE_GRATUITE` et `LIVRAISON_DISTANCE_MAX_METRES` gardent leur rôle décrit section 8. Le README fournit le clonage et l’import initial ; `sql/03_menus_demo.sql` complète le catalogue en option.

<a id="configuration-services"></a>

### 4.3 Configuration PostgreSQL, MongoDB et e-mails

Les réglages propres au poste vont dans `.env.local`, ignoré par Git et par `.dockerignore`. Sur Render, les saisir dans les variables d’environnement du service web. Les noms ci-dessous et les exemples locaux sont publics ; aucune URI authentifiée, clé API ou valeur de `APP_SECRET` réelle ne doit être versionnée.

| Variable | Local | Render |
|---|---|---|
| `APP_ENV`, `APP_DEBUG` | Développement ; valeurs propres au poste | `prod`, `0` |
| `APP_SECRET` | Secret local non versionné | Secret du service, hors Git |
| `DATABASE_URL` | PostgreSQL local ; valeur privée, avec version serveur et encodage adaptés | PostgreSQL Render : URL interne pour le service web ; URL externe depuis le PC |
| `MONGODB_URI` | `mongodb://127.0.0.1:27017` | URI Atlas authentifiée, hors Git |
| `MONGODB_DATABASE` | `vite_et_gourmand_stats` | Nom de la base de statistiques liée à cette source PostgreSQL |
| `MAILER_DSN` | `smtp://127.0.0.1:1025` pour Mailpit | `brevo+api://VOTRE_CLE_API_BREVO@default` : exemple à remplacer par une clé **API** Brevo |
| `MAILER_FROM_EMAIL` | `bonjour@vite-et-gourmand.example`, adresse fictive | Adresse d’expéditeur validée dans Brevo ; authentifier aussi son domaine |
| `DEFAULT_URI` | URL locale du site, par exemple `http://127.0.0.1:8000` | URL publique HTTPS ; base des liens générés hors requête HTTP |

Encoder les caractères réservés d’une clé avant de la placer dans le DSN. Le transport Mailer est sélectionné uniquement par `MAILER_DSN`. Le bridge `symfony/brevo-mailer` et `symfony/http-client` 7.4 sont des dépendances de production ; le service `http_client` est disponible pour les appels HTTPS Brevo à `https://api.brevo.com/v3/smtp/email`. Le bridge gère le POST, le JSON et l’en-tête d’authentification à partir du DSN.

MongoDB utilise la bibliothèque officielle `mongodb/mongodb` et l’extension PHP `mongodb` 2.5.x. Le Dockerfile installe `pdo_pgsql` et `mongodb` **2.5.7** avant `composer install --no-dev`, afin que Composer puisse contrôler les extensions pendant le build. MongoDB doit accepter les mises à jour par pipeline (4.2 ou ultérieur) ; le Compose local utilise `mongo:8.0`.

```powershell
php --ri mongodb
composer install
docker compose -p vite-gourmand -f compose.mongodb.yaml up -d
docker compose -p vite-gourmand -f compose.mongodb.yaml ps
```

Le Compose publie MongoDB seulement sur `127.0.0.1:27017`, avec un volume persistant et un contrôle de santé. Une base MongoDB distincte est nécessaire pour chaque base PostgreSQL source : des identifiants de commande peuvent être identiques entre environnements.

Sous Windows, choisir une DLL correspondant à la version PHP, à x64 et au mode TS/NTS du PHP utilisé. Contrôler `php --ini` et `php --ri mongodb`. Une DLL locale peut être chargée ponctuellement sans modifier le `php.ini` global :

```powershell
$mongoExtension = (Get-ChildItem var/tools/mongodb-2.5.7 -Filter '*.dll').FullName
php -d "extension=$mongoExtension" bin/console app:stats:sync
php -d "extension=$mongoExtension" -S 127.0.0.1:8000 -t public
```

Le dossier `var/` est ignoré par Git ; sur un autre poste, télécharger une DLL adaptée depuis les releases officielles référencées en section 15.7. L’extension doit être active dans **le PHP CLI et le PHP web** : sous Apache/Wamp, modifier la configuration utilisée par Apache puis redémarrer ce service. Dans un conteneur, `127.0.0.1` désigne le conteneur lui-même ; pour joindre le poste Docker Desktop, utiliser `host.docker.internal`, ou le nom du service MongoDB sur un réseau Docker partagé. Mailpit doit fonctionner avant les parcours locaux d’envoi.

## 5. Architecture et organisation du code

Le navigateur appelle une route Symfony. Le contrôleur lit et valide la demande, utilise un repository pour lire les données puis transmet les variables à Twig. Doctrine mappe les entités PHP sur PostgreSQL. Les formulaires et Symfony Security assurent leurs traitements dédiés. Le service EmailBienvenue utilise Mailer ; le parcours de reset utilise un email Twig et son helper.

| Emplacement | Responsabilité actuelle |
|---|---|
| src/Controller/MenuController.php | Catalogue filtré et détail d’un menu |
| src/Repository/MenuRepository.php | Requêtes de consultation et findByFiltres |
| src/Entity/ | Entités et relations Doctrine |
| src/Controller/SecurityController.php | Affichage connexion et route de déconnexion |
| src/Controller/RegistrationController.php | Inscription, hachage et persistance puis email/connexion |
| src/Controller/ResetPasswordController.php | Demande du lien, confirmation et changement du mot de passe |
| src/Service/EmailCreationEmploye.php | Notification après création du compte, sans mot de passe ; récupération des erreurs dans AdminEmployeController |
| src/Service/SynchronisationStatistiques.php et ActualisationStatistiquesCommande.php | Projection MongoDB complète/ciblée, protection par révision et journalisation après commit |
| src/Command/StatsSyncCommand.php | Import initial et rattrapage PostgreSQL vers MongoDB |
| src/Controller/AdminStatistiquesController.php et src/Service/StatistiquesCommandes.php | Accès gérant, validation des filtres et agrégations MongoDB |
| src/Form/ | Formulaires d’inscription et réinitialisation |
| src/Security/UtilisateurChecker.php | Refus d’une connexion pour compte inactif |
| src/Service/EmailBienvenue.php | Message de bienvenue |
| src/Service/CalculPrixCommande.php | Montant du menu, remise et total, en centimes |
| src/Controller/CommandeController.php | Saisie, brouillon de session, choix d’adresse, calcul du récapitulatif, confirmation GET/POST, appel du service puis page de réussite |
| src/Form/ConfirmationAdresseType.php | Choix radio parmi les propositions conservées côté serveur ; CSRF du formulaire |
| src/Service/GeocodageAdresse.php | Appel IGN et sélection des résultats numérotés de type housenumber |
| src/Service/ItineraireLivraison.php | Appel POST OpenRouteService/HeiGIT, conduite automobile, distance aller en mètres |
| src/Service/CalculFraisLivraison.php | Gratuité Bordeaux ; ailleurs 500 centimes + 59 centimes/km, arrondi au centime |
| src/Service/EnregistrementCommande.php | Transaction, relecture/verrou du menu, contrôle du tarif/stock/délai, écriture commande et historique, protection du double envoi |
| src/Service/EmailConfirmationCommande.php | Email après validation de la transaction ; erreur SMTP journalisée sans annuler la commande |
| src/Controller/MesCommandesController.php | Liste du client, détail avec historique et route POST d’annulation |
| src/Controller/ProfilController.php et src/Form/ProfilType.php | Profil du client connecté, six coordonnées, validation et CSRF puis flush et redirection 303 |
| src/Controller/GestionCommandeController.php | Liste filtrée et paginée, détail, acceptation, suivi et matériel. Déclenche l’invitation à laisser un avis après terminaison. |
| src/Controller/AvisCommandeController.php, src/Form/AvisType.php et src/Entity/Avis.php | Dépôt client, contrôle de propriété et statut, validation du formulaire et mapping PostgreSQL. |
| src/Controller/GestionAvisController.php et templates/gestion/avis/ | Liste des avis en attente et modération par l’équipe. |
| src/Controller/AccueilController.php et templates/accueil/ | Accueil public et six avis approuvés les plus récents. |
| src/Service/EmailInvitationAvis.php | Invitation après transition réussie vers la terminaison, avec lien absolu vers le formulaire. |
| src/Service/SuiviCommande.php | Transitions autorisées et contrôles des statuts sous verrou ; historique atomique ; aucun changement de stock |
| src/Service/PretMaterielCommande.php | Choix explicite du prêt avant préparation et historique sans changement de statut |
| src/Service/EmailRetourMateriel.php | Email après nouveau passage en attente de retour ; transport SMTP journalisé en cas d’échec |
| src/Service/RetourMaterielCommande.php | Restitution datée et terminaison atomiques ; second envoi sans nouvelle date ni événement |
| src/Service/AcceptationCommande.php | Transaction, relecture/verrous, passage en_attente vers acceptee et historique avec auteur ; stock inchangé |
| templates/profil/ et templates/gestion/commande/ | Formulaire personnel et liste équipe avec formulaire d’acceptation |
| src/Service/AnnulationCommande.php | Verrous menu/commande, propriétaire/statut, restitution unique du stock et historique |
| src/Form/CommandeType.php | Champs client autorisés et contraintes ; pas de montants ni de statut modifiables |
| src/Entity/Commande.php et HistoriqueCommande.php | Mappings des tables existantes ; dates initialisées en PHP |
| templates/commande/ | index : saisie ; adresse : choix/calcul ; confirmation : bouton final ; succes : commande créée ; mes_commandes : liste ; detail : suivi et annulation |
| templates/emails/confirmation_commande.html.twig | Email avec référence, prestation, adresse et montants enregistrés |
| templates/ | Pages Twig, gabarit commun et contenu du mail de reset |
| public/css/style.css | Styles communs et cartes/formulaires |
| public/js/menu-filters.js | Soumission/réinitialisation dynamique des filtres |
| public/images/menus/ | Illustration de démonstration |
| config/packages/security.yaml | Provider, hachage, firewall et connexion/déconnexion |

Les contrôleurs coordonnent HTTP, session et affichage. Les services portent les calculs, les écritures métier et les notifications. Les appels mail sont effectués après les écritures de commande. Les filtres, la pagination, les actions d’équipe après contact et le parcours d’avis sont décrits dans les sections 7 et 8.

## 6. Données et modèle

### 6.1 Schéma relationnel de travail

Le schéma initial compte 14 tables : theme, regime, allergene, plat, plat_allergene, menu, menu_plat, image_menu, utilisateur, commande, historique_commande, avis, horaire_jour et creneau_horaire. Le mécanisme de reset ajoute reset_password_request : 15 tables attendues dans le schéma actualisé. Son stockage est opérationnel d’après le parcours testé ; la présence de tous les derniers ajustements dans les scripts doit être revérifiée.

| Relation | Cardinalités de travail |
|---|---|
| utilisateur — commande | Un utilisateur possède 0..n commandes ; une commande appartient à un seul utilisateur |
| menu — commande | Un menu peut être commandé plusieurs fois ; une commande porte sur un menu |
| theme / regime — menu | Chaque menu référence un thème et un régime ; chacun est partageable |
| menu — plat | Relation n..n par menu_plat |
| plat — allergene | Relation n..n par plat_allergene |
| menu — image_menu | Un menu possède plusieurs images possibles ; chaque image référence un menu |
| commande — historique_commande | Plusieurs événements horodatés par commande |
| commande — avis | 0..1 avis par commande, garanti par UNIQUE |
| horaire_jour — creneau_horaire | 0..n créneaux par jour |
| utilisateur — reset_password_request | Plusieurs demandes possibles par utilisateur, selon les limites du helper |

Le détail des champs et contraintes figure dans le dictionnaire. Un diagramme conceptuel a été préparé dans [FigJam](https://www.figma.com/board/e5mjhdlorQg83bN0U3dD3r) à partir du dictionnaire et des règles déjà documentées. L’annexe 1 de l’énoncé présente le modèle de référence page 10. La page 12 demande un MCD ou un diagramme de classes dans la documentation technique. L’export PDF du diagramme et son ajout dans le dépôt restent à effectuer. Le dernier code SQL distant n’a pas pu être relu lors de la préparation du diagramme.

### 6.1.1 Lecture du diagramme conceptuel

Le diagramme représente les utilisateurs, les commandes et événements d’historique, les avis, le catalogue et ses images, ainsi que les jours et créneaux d’ouverture. Chaque commande référence un utilisateur et un menu. Un utilisateur peut ne posséder aucune commande. Une commande possède au plus un avis et l’application écrit au moins un événement initial d’historique. Un événement peut avoir un auteur absent lorsque l’action est automatique.

Les associations menu–plat et plat–allergène sont multiples dans les deux sens. Leur implémentation SQL utilise les tables de liaison `menu_plat` et `plat_allergene`. Les rôles client, employé et administrateur sont portés par `utilisateur.roles`. Les champs détaillés restent décrits dans le dictionnaire.

Le diagramme représente les liens métier avec une notation entité-association. Les champs techniques de réinitialisation et la révision statistique sont expliqués dans les sections dédiées. La projection MongoDB est alimentée à partir des commandes SQL et conserve leurs montants historiques.

La relation commande–historique porte une exigence métier d’au moins un événement. La clé étrangère garantit que chaque événement référence une commande existante. Le service transactionnel garantit l’écriture de l’événement initial avec la commande. Pour les relations menu–plat, le minimum à zéro reflète aussi les données de démonstration actuelles, dont le menu de Noël vegan d’origine était dépourvu de composition dans le script initial. L’obligation d’avoir une entrée, un plat principal et un dessert doit être vérifiée lors de la gestion future du catalogue.

### 6.2 Mappings et précautions

Les six entités catalogue Theme, Regime, Allergene, Plat, Menu et ImageMenu sont mappées et lues avec leurs relations. Utilisateur et ResetPasswordRequest ont été ajoutées pour les comptes. Commande et HistoriqueCommande sont mappées depuis le 18 octobre. Après les premières lectures vides, des insertions réelles et la lecture de leurs dates, montants et statuts ont été confirmées lors des parcours de commande et d’annulation. Les autres tables métier ne sont pas encore entièrement mappées ; ne pas lancer de synchronisation destructrice fondée sur un mapping incomplet.

Les noms de colonnes explicitent notamment minimum_personnes, prix_minimum_centimes, theme_id, regime_id, texte_alternatif et mot_de_passe. Les dates utilisateur ont été ajustées à une précision de secondes après une erreur de lecture liée aux fractions de seconde. Le trait de reset utilise des dates sans fuseau ; les colonnes proposées pour cette table sont requested_at et expires_at en TIMESTAMP(0) WITHOUT TIME ZONE. Vérifier la cohérence de chaque type lors de la reconstruction.

### 6.3 Ajout du jeton de validation

La table commande possède désormais `jeton_validation VARCHAR(32) UNIQUE`, ajouté au script de création et à la base existante par ALTER TABLE. L’entité mappe jetonValidation avec nom explicite, longueur 32, unique et nullable. La colonne accepte NULL pour compatibilité avec d’éventuelles lignes antérieures ; le service exige un jeton hexadécimal de 32 caractères pour toute nouvelle commande de ce parcours. La contrainte UNIQUE interdit deux lignes utilisant le même brouillon ; une seconde validation retrouve la commande existante au lieu de consommer une autre unité de stock. Le jeton n’est pas une preuve d’autorisation : propriétaire, rôle et CSRF sont contrôlés séparément.

### 6.4 Décisions encore nécessaires

- Conserver le tarif de référence, le minimum et la composition commandés si le catalogue change ; archiver un menu utilisé plutôt que détruire son historique.
- Enrichir si nécessaire la trace de modification existante avec un différentiel avant/après ; l’événement sans transition est déjà enregistré.
- Formaliser le calendrier des jours ouvrés pour le retour du matériel. La distance aller et les arrondis de livraison sont définis section 8.
- Tester deux validations réellement concurrentes du dernier stock et un échec SQL provoqué ; les transactions de création, modification, annulation, suivi et matériel sont implémentées.
- Mettre à jour le modèle de données et les schémas avec `commande.version_statistiques` et la collection de statistiques MongoDB décrite en section 7.8.
- Actualiser le dictionnaire pour les comptes/reset et les décisions finales.

<a id="projection-mongodb"></a>

### 6.5 Projection MongoDB et migration de révision

La collection `commandes`, dans `MONGODB_DATABASE`, est une projection de l’état courant des commandes SQL, pas un journal de transitions. `SynchronisationStatistiques` sélectionne explicitement les seuls champs utiles :

| Champ MongoDB | Source et type |
|---|---|
| `_id` | Identifiant entier PostgreSQL de la commande, clé stable unique |
| `menu.id`, `menu.nom` | Identifiant et nom **courant** du menu lié ; le nom n’est pas historisé en SQL |
| `nombre_personnes` | Effectif enregistré sur la commande |
| `date_creation` | Date/heure de création en BSON `UTCDateTime` |
| `date_prestation` | Jour civil `YYYY-MM-DD`, sans conversion de fuseau |
| `statut` | `en_attente`, `acceptee`, `en_preparation`, `en_livraison`, `livree`, `attente_retour_materiel`, `terminee` ou `annulee` |
| `montant_menu_centimes`, `montant_remise_centimes` | Montant et remise historiques enregistrés sur `commande` |
| `frais_livraison_centimes`, `montant_total_centimes` | Livraison et total historiques de la commande |
| `version_source` | Révision issue de `commande.version_statistiques` |

Les montants sont des entiers en centimes ; ils ne sont jamais recalculés depuis `menu.prix_minimum_centimes`. Identité, adresse, téléphone, identifiant utilisateur et jetons restent dans PostgreSQL. Le remplacement d’un document enlève aussi les anciens champs hors de cette projection.

La migration additive `Version20261112000000` ajoute `version_statistiques BIGINT NOT NULL DEFAULT 1`, la fonction `incrementer_version_statistiques_commande()` et le trigger `commande_version_statistiques`. Chaque `UPDATE` de commande incrémente cette révision en base. Doctrine la mappe comme valeur générée, non insérable et non modifiable ; ce mécanisme n’ajoute pas de verrouillage optimiste aux règles métier. **La migration reste nécessaire après reconstruction par les scripts SQL initiaux.** Les commandes de déploiement sont en section 13.

Une pipeline conditionnelle `updateOne` avec `upsert: true`, filtrée sur `_id`, compare atomiquement la révision et remplace le document seulement si sa révision source est au moins égale à celle stockée. Un état ancien ne peut pas écraser une commande plus récente ; une révision identique est rejouable sans doublon. Un document ancien sans révision est traité comme antérieur à la révision 1. `$literal` protège les noms de menus contenant un dollar. Les écritures sont acquittées avec `WriteConcern::MAJORITY` avant d’être comptées comme réussies.

Les suppressions SQL ne sont pas propagées. La projection n’exclut aucun statut ; c’est l’agrégation du tableau de bord qui applique les règles de CA (section 7.8).

## 7. Fonctionnalités réalisées

### 7.1 Catalogue et détail

La route GET /menus, nommée app_menu, affiche les données lues depuis PostgreSQL : nom, description, minimum de personnes, prix correspondant, prix indicatif par personne, thème, régime, stock et lien de détail. /menus/{id}, app_menu_show, présente les informations du menu, plats/allergènes et image ; un identifiant absent renvoie une 404.

La galerie de démonstration affiche poulet-roti.png. L’illustration a été générée par IA ; elle doit être identifiée comme telle et ne prouve pas l’existence d’une photo de prestation réelle. Le lien Commander a été ajouté au détail. Le formulaire affiche les conditions et le délai ; le passage visiteur vers connexion puis formulaire a été testé. La présentation finale et la galerie complète restent à vérifier.

### 7.2 Filtres dynamiques

Les paramètres GET prix_min, prix_max, theme, regime et nombre_personnes sont traités côté serveur. Le prix filtré est celui du menu pour son minimum de personnes, pas le prix par personne. L’effectif filtre les menus dont le minimum est inférieur ou égal au nombre de convives prévu ; c’est l’interprétation fonctionnelle retenue et libellée dans l’interface.

Les montants sont convertis depuis une chaîne décimale en centimes entiers. Le repository combine les conditions avec paramètres liés, puis trie par prix et identifiant. Une fourchette inversée affiche une erreur dans la page ; certains paramètres malformés peuvent encore provoquer une réponse 400 à améliorer.

Le JavaScript intercepte la soumission et la réinitialisation, appelle fetch, extrait le bloc des résultats, met à jour l’URL et annonce le résultat. Le catalogue s’actualise sans rechargement complet. Un état de chargement évite les soumissions simultanées. Le formulaire conserve son fonctionnement GET sans JavaScript.

### 7.3 Comptes et connexion

L’inscription collecte nom, prénom, téléphone, email et adresse. Les validations du formulaire imposent les champs requis et les critères du mot de passe : au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial. Le mot de passe est haché avant enregistrement dans mot_de_passe. Le rôle ROLE_CLIENT est attribué côté serveur et le compte est actif ; ROLE_USER est ajouté par l’entité.

L’email est unique dans la base et contrôlé par UniqueEntity pour donner un message compréhensible. La connexion utilise email/mot de passe, form_login et un jeton CSRF. Le compte inactif est refusé par un user checker. La déconnexion est prise en charge par le firewall. La révocation d’une session déjà ouverte lors d’une désactivation reste à vérifier.

<a id="emails-transactionnels"></a>

### 7.4 E-mails transactionnels et réinitialisation

Symfony Mailer utilise Mailpit en local et l’API HTTPS Brevo sur Render selon la configuration de la section 4.3. Tous les expéditeurs lisent `MAILER_FROM_EMAIL` et gardent le nom **Vite & Gourmand**, y compris `ResetPasswordController`.

| Message | Déclenchement et contenu |
|---|---|
| `EmailBienvenue` | Après persistance de l’inscription ; confirmation de création du compte |
| `EmailConfirmationCommande` | Après commit d’une nouvelle commande ; référence, prestation et montants enregistrés. Le renvoi du même brouillon ne renvoie pas le mail (section 8.5). |
| `EmailInvitationAvis` | Après transition réussie vers `terminee`, sans prêt ou après restitution ; lien absolu vers le formulaire de la commande |
| `EmailRetourMateriel` | Après nouveau passage en `attente_retour_materiel` ; rappel du matériel à restituer (section 8.13) |
| `EmailCreationEmploye` | Après persistance du compte par le gérant ; lien de connexion et demande de récupérer le mot de passe initial auprès du gérant, **sans mot de passe ni hachage dans le mail** |
| Réinitialisation | `ResetPasswordController`, modèle `reset_password/email.html.twig` ; lien de changement à jeton |

Messenger n’est pas installé et aucun transport de messages ni worker Render n’est configuré. `config/packages/mailer.yaml` impose en plus `framework.mailer.message_bus: false` sous `when@prod`. L’envoi est donc direct pendant la requête, sans worker nécessaire pour ces e-mails, même si Messenger est ajouté pour un autre usage. Il n’existe ni file persistante, ni réessai automatique, ni garantie de réception dans la boîte du destinataire.

Les erreurs de transport de bienvenue, confirmation, invitation et retour de matériel sont interceptées et journalisées sans annuler les écritures SQL déjà effectuées. Pour la création d’employé, le Mailer est résolu à la demande **après** l’enregistrement : toute erreur de notification, y compris un DSN invalide, conserve le compte et la redirection avec un avertissement distinct au gérant. Le journal de ce parcours ne contient que l’identifiant utilisateur et la classe d’erreur. Une validation refusée ou une adresse déjà utilisée ne déclenche aucune notification.

Le reset conserve la réponse générique pour une adresse inconnue ou une demande non générée. Après ouverture du lien, le jeton est gardé en session pour être retiré de l’URL ; le nouveau mot de passe est saisi deux fois, haché, puis la demande de reset est supprimée. Les templates `request`, `check_email`, `email` et `reset` sont traduits. Le contrôleur appelle actuellement `Mailer::send` sans interception dédiée des erreurs de transport : ne pas lui attribuer le comportement de récupération propre à la création d’employé.

Réception du lien, changement, connexion avec le nouveau mot de passe et refus du lien déjà utilisé ont été confirmés lors de la recette initiale. L’ancien mot de passe n’avait pas été retesté car il avait été oublié ; l’expiration effective, la panne transport et la réponse pour une adresse inconnue restent à vérifier dans la recette navigateur. Réinitialisation et notification employé ont aussi été reçues sur Render selon les recettes consignées en section 11.8 ; la notification employé est arrivée dans les indésirables. Ces résultats ne prouvent pas la réception de tous les autres types de messages. Les vérifications automatisées, la recette complémentaire et le dépannage sont regroupés en sections 11.3 et 13.

### 7.5 Avis et accueil public

- Dépôt client par GET/POST `/mes-commandes/{id}/avis` (`app_commande_avis`). Le client actif doit être propriétaire d’une commande `terminee`. `AvisType` propose une note de 1 à 5 et un commentaire obligatoire de 2 000 caractères maximum, avec protection CSRF. Le lien apparaît sur le détail client d’une commande terminée. Après un dépôt, un nouvel accès au lien donne un message indiquant que l’avis existe déjà.
- Doctrine mappe `Avis` sur `public.avis`. La contrainte UNIQUE sur `commande_id` garantit un avis maximum par commande. Les contraintes PostgreSQL contrôlées dans pgAdmin imposent une note de 1 à 5, un commentaire non vide après retrait des espaces et l’un des statuts `en_attente`, `valide`, `refuse`. Un dépôt démarre à `en_attente`.
- `/gestion/avis` présente les avis en attente aux employés actifs. Les deux actions POST, protégées par CSRF, mettent à jour la ligne seulement si son statut est encore `en_attente`. Un second envoi depuis un formulaire périmé ne remplace pas la première décision.
- L’accueil public GET `/` affiche la présentation de Julie et José, une sélection de menus disponibles, puis jusqu’à six avis `valide`, du plus récent au plus ancien. Render affichait un état vide pour les avis lors du contrôle. Le dépôt et la modération sont décrits dans les sections précédentes.
- `EmailInvitationAvis` est appelé après un changement réussi vers `terminee`, depuis `suivi()` pour la terminaison sans prêt ou depuis `retourMateriel()` après restitution. Le mail texte contient un lien absolu vers le formulaire de la commande. La réception dans Mailpit est confirmée. La réception réelle de ce type de message sur Render reste à consigner ; les transports et limites de reprise sont décrits en section 7.4.

### 7.6 Gérant, employés et clients de démonstration

- Le premier compte gérant a été créé par l’inscription Symfony sur Render, puis son rôle a été promu à `ROLE_ADMIN` dans PostgreSQL. Sa connexion est confirmée.
- `/admin/employes/creer` est accessible au gérant via la navigation. `AdminEmployeController` contrôle le rôle côté serveur. `EmployeType` reprend les validations d’inscription, demande confirmation du mot de passe et protège le formulaire par CSRF. Symfony hache le mot de passe et le serveur impose `ROLE_EMPLOYE`. Une adresse déjà utilisée déclenche une erreur. Le parcours et la connexion employé ont été confirmés sur PostgreSQL local puis Render. Le nombre d’employés effectivement créés sur Render reste à relever. La notification via Brevo a été reçue sur Render. La désactivation reste à implémenter.
- `php bin/console app:demo:clients` affiche la base cible et demande confirmation avant écriture. Elle crée huit comptes `ROLE_CLIENT` avec un mot de passe aléatoire distinct et haché. Les identifiants sont affichés dans le terminal au premier passage. Une adresse déjà présente est ignorée et le profil est préservé. Premier passage local : huit créations. Deuxième passage : huit comptes ignorés. L’exécution sur Render a été confirmée.
- Le jeu couvre quatre profils à Bordeaux et un à Mérignac, Pessac, Talence et Bègles. En ligne, `client03` a validé le parcours Bordeaux après remplacement de son adresse par le 30 rue Gustave Eiffel, 33100 Bordeaux. `client06` à Pessac a validé le calcul hors Bordeaux après configuration d’OpenRouteService. Les six autres adresses restent à tester dans le parcours. Vérifier dans Git la synchronisation de l’adresse de remplacement dans la commande et dans le test.

### 7.7 Accueil et boutons de l’interface

L’en-tête commun utilise l’emblème du site et les polices locales DM Sans et Playfair Display. Le menu mobile est piloté par `public/js/navigation.js`. Sur une page courte, le contenu extensible pousse le footer vers le bas de l’écran. Sur une page longue, celui-ci suit le contenu. Les routes de contact et les pages légales restent à créer.

`AccueilController` fournit au plus deux menus avec un stock strictement positif. La sélection suit le prix minimum croissant, puis l’identifiant en cas d’égalité. Le partial `templates/menu/_card.html.twig` sert à l’accueil et à `/menus`. Il prend la première image selon sa position et prévoit un emplacement sans photo. Les filtres gardent leurs paramètres GET et le compteur se met à jour après filtrage. La fiche sépare galerie, résumé de commande, conditions et composition avec allergènes.

`sql/03_menus_demo.sql` ajoute six menus au catalogue des deux menus initiaux. Les six ajouts sont illustrés et associés chacun à une entrée, un plat principal et un dessert. Le script ajoute aussi une image au menu de Noël vegan d’origine quand sa première image manque. Rejouer le script conserve les prix et stocks existants. Les images et le code sont livrés avec Git, puis les données sont chargées dans la base PostgreSQL ciblée. Render affichait huit menus lors du contrôle.

### 7.7.1 Reprise des écrans privés et du dépôt d’avis

La branche `develop` contient la mise en forme des étapes de commande, de la connexion, de l’inscription, de la réinitialisation, du profil et des commandes personnelles. Elle contient aussi les vues de gestion des commandes, de modération, de création d’employé et de statistiques. Ces modifications portent principalement sur Twig et CSS. L’écran de commande distingue la saisie, le prix provisoire et la confirmation après vérification de l’adresse.

Dans `templates/commande/avis.html.twig`, le formulaire Symfony existant est rendu dans une carte avec une note, un commentaire, des erreurs et une action de retour. La branche à `3eae360` contient déjà une version en carte. Le dernier prompt prévoit de mieux espacer les labels et d’élargir le commentaire. Le résultat de cette finition et son déploiement restent à confirmer.

<a id="statistiques-gerant"></a>

### 7.8 Statistiques du gérant

`/admin/statistiques` et le lien de navigation sont réservés à `ROLE_ADMIN`, avec `IsGranted` côté serveur et contrôle du compte actif. Le contrôleur `AdminStatistiquesController` et le service `StatistiquesCommandes` lisent exclusivement MongoDB pour les chiffres et les options de menus. PostgreSQL sert toujours à l’authentification et aux horaires du footer, pas au calcul des statistiques. Ouvrir la page ne lance aucune synchronisation ni écriture.

#### Tableau de bord et règles du chiffre d’affaires

- Le graphique HTML/CSS et son tableau comptent les commandes par identifiant de menu, **tous statuts et toutes dates confondus**. Aucune bibliothèque JavaScript externe n’est nécessaire.
- Les paramètres GET `menu`, `debut` et `fin` filtrent **uniquement le CA**, pas le graphique global.
- Seul le statut `terminee` entre dans le CA. Une commande livrée, en attente de retour du matériel ou annulée est exclue.
- Pour chaque commande retenue : `montant_menu_centimes - montant_remise_centimes`, **hors frais de livraison**, depuis les montants historiques. Les sommes MongoDB et le total PHP restent en centimes ; Twig convertit uniquement pour l’affichage en euros.
- La période porte sur `date_creation`, et non sur `date_prestation`. Les deux dates sont inclusives dans le fuseau **Europe/Paris** : minuit du début inclus, minuit du lendemain de la fin exclu. Le lendemain est calculé en **jour civil**, même lors des journées de 23 ou 25 heures. Chaque borne peut rester vide.
- Paramètre non scalaire, menu absent/invalide, date impossible ou période inversée : réponse **400**, erreurs lisibles et aucun CA non filtré trompeur. Résultat vide : **200**, message explicite et total nul. Extension manquante ou MongoDB inaccessible : **503** et message distinct, sans afficher d’exception brute ni présenter la panne comme un CA nul.

Le graphique, les tableaux et les filtres sont présentés dans des sections distinctes ; le tableau de CA peut défiler horizontalement au clavier sur mobile. Le schéma de projection et les garanties de révision sont décrits en section 6.5.

#### Actualisation après validation PostgreSQL

Neuf services appellent `ActualisationStatistiquesCommande::apresValidation` après le retour réussi de `EntityManager::wrapInTransaction`, donc après flush **et commit** : `EnregistrementCommande`, `AcceptationCommande`, `SuiviCommande`, `RetourMaterielCommande`, `AnnulationCommande`, `AnnulationCommandeEquipe`, `ModificationCommande`, `ModificationCommandeEquipe` et `PretMaterielCommande`.

La méthode ciblée `SynchronisationStatistiques::synchroniserCommande` reçoit uniquement l’identifiant de la commande effectivement créée ou retrouvée et relit l’état SQL validé : menu courant, statut, dates et montants. Elle n’utilise ni un ancien objet ORM ni un récapitulatif de session. Les écrans actuels de modification conservent le menu ; les setters de menu des contrôleurs concernent les brouillons de nouvelles commandes.

Une action idempotente tente aussi la projection pour rattraper un échec précédent sur cette commande. Un commit SQL refusé ne déclenche aucun appel MongoDB. Une connexion encore en transaction est refusée par la méthode ciblée : si un futur appel ajoute une transaction englobante, il faudra déclencher la projection après son commit extérieur ou utiliser le rattrapage CLI.

Un échec automatique ne remet pas en cause la commande ou l’action déjà enregistrée dans PostgreSQL. Le journal garde un message fixe, l’identifiant de commande et la classe d’erreur, sans URI, message brut ni objet exception. Un arrêt du processus entre commit et projection laisse également une écriture à rattraper. La procédure unique d’import et de rattrapage `app:stats:sync` figure en section 13.

## 8. Commandes : fonctionnement actuel et règles restantes

### 8.1 Parcours réellement implémenté

| Route / nom | Traitement et protections |
|---|---|
| GET/POST /menus/{id}/commander — app_commande | ROLE_CLIENT ; menu lu côté serveur ; préremplissage ; Form/Validator pour nombre/date/coordonnées ; recherche d’adresse après saisie valide |
| GET/POST /commande/adresse/{jeton} — app_commande_adresse | Brouillon lié au compte, jeton vérifié et expiration ; choix parmi les seules propositions de session ; recontrôle minimum/délai/stock puis calcul de livraison et prix |
| GET/POST /commande/confirmer/{jeton} — app_commande_confirmation | Relecture de la confirmation et du brouillon, propriétaire/jetons/menu/expiration ; GET affiche, POST exige un CSRF puis construit la commande à partir des seules données serveur |
| GET /commande/succes/{id} — app_commande_succes | Lecture autorisée uniquement au propriétaire, sinon 404 ; confirme l’enregistrement, pas l’acceptation par l’équipe |
| GET /mes-commandes — app_mes_commandes | Repository filtré sur l’utilisateur connecté, tri date de création puis id décroissants |
| GET /mes-commandes/{id} — app_mes_commandes_show | Recherche par id ET utilisateur ; montants enregistrés et historique chronologique ; 404 pour autrui |
| POST /mes-commandes/{id}/annuler — app_mes_commandes_annuler | ROLE_CLIENT, CSRF lié à la commande, recherche propriétaire, appel du service et redirection 303 avec message flash |
| GET /gestion/commandes/{id} — app_gestion_commandes_show | Équipe active, détail/historique, client 403 et commande absente 404 |
| POST /gestion/commandes/{id}/suivi/{statut} — app_gestion_commandes_suivi | Équipe, CSRF id/cible, transition contrôlée et historique ; email après nouveau passage en attente retour |
| POST /gestion/commandes/{id}/materiel/{pret} — app_gestion_commandes_materiel | Choix 0/1, CSRF, prêt modifiable avant préparation uniquement |
| POST /gestion/commandes/{id}/materiel/retour — app_gestion_commandes_retour_materiel | CSRF, restitution datée et terminaison avec historique, second envoi sans doublon |

Le compte et le menu ne sont pas des choix libres dans le formulaire client. Les montants, le statut et le matériel ne sont pas exposés comme champs modifiables. Le stock n’est pas réservé à l’ouverture du formulaire.

### 8.2 Brouillon, adresse et récapitulatif

Après validation du formulaire, commande_brouillon conserve en session : jeton aléatoire, ids utilisateur/menu, nombre de personnes, date, heure, téléphone, propositions d’adresse et expire_a fixé à time()+1800. Un nouveau brouillon remplace le précédent : il n’y a pas plusieurs commandes en préparation indépendantes dans les onglets d’une même session. Il ne s’agit pas encore d’une commande SQL.

GeocodageAdresse interroge `https://data.geopf.fr/geocodage/search`, avec q et limit=5, délais d’inactivité/durée totale 5/10 secondes. Seuls les résultats de type housenumber, avec numéro et géométrie Point, sont proposés. Une rue seule sans numéro n’est pas acceptée pour le calcul définitif : le résultat Rue Rocherol à Libourne a permis de constater cette limite. Un résultat vide ne prouve pas qu’une adresse n’existe pas. L’utilisateur confirme la proposition ; le géocodage ne constitue pas une preuve absolue de l’adresse réelle.

ConfirmationAdresseType utilise un ChoiceType radio dont les valeurs sont les indices des propositions conservées en session. Son jeton CSRF et son message de choix invalide protègent ce formulaire. L’indice zéro est un choix valide. L’adresse retenue fournit name, postcode, city et citycode ; le texte affiché vient de label. Un contrôle plus strict de tous les champs et de la correspondance avec la saisie reste à compléter.

Après calcul réussi, commande_confirmation conserve : un jeton de confirmation distinct, le jeton de brouillon, utilisateur/menu, prestation et adresse sélectionnée, récapitulatif chiffré et expiration héritée. Une nouvelle soumission du choix d’adresse retire l’ancienne confirmation avant recalcul. La page finale vérifie son rattachement au brouillon courant ; un ancien onglet ne doit pas confirmer un autre brouillon. Les cas multi-onglets/expiration restent à tester systématiquement.

### 8.3 Prix, livraison et choix du fournisseur

CalculPrixCommande calcule le montant proportionnel en centimes, puis 10 % de remise dès minimum+5 convives, puis le total. L’arrondi est au centime le plus proche, avec les demi-centimes positifs vers le haut. Exemple du menu minimum 4 à 120 € : 6 personnes → 180 € sans remise ; 9 → 270 € − 27 € = 243 € avant livraison. Les montants affichés par personne, arrondis pour l’affichage, ne servent pas de base au calcul.

CalculFraisLivraison reçoit une distance entière en mètres : Bordeaux identifié par le code INSEE 33063 est gratuit ; sinon `500 + intdiv($distanceMetres * 59 + 500, 1000)` centimes. Le tarif est donc 5 € + 0,59 €/km, arrondi au centime, sans arrondi à l’euro supérieur. La gratuité de Bordeaux et le choix d’un trajet routier aller le plus rapide en voiture sont les interprétations retenues du sujet. La distance n’est pas une distance à vol d’oiseau ni un aller-retour.

Point de départ de démonstration : 21 Rue des Menuts, 33000 Bordeaux ; longitude -0.567855, latitude 44.834358. Cette adresse publique réelle est utilisée comme repère pédagogique ; elle ne présente pas l’entreprise réelle comme partenaire. Paramètres partagés : LIVRAISON_DEPART_ADRESSE, LIVRAISON_DEPART_LONGITUDE, LIVRAISON_DEPART_LATITUDE, LIVRAISON_COMMUNE_GRATUITE.

L’appel IGN initial à /navigation/itineraire a d’abord fonctionné, puis renvoyé 404, erreur reproduite pendant le diagnostic. La documentation et les capacités ne donnaient pas alors une solution fonctionnelle vérifiée. La cause interne et la durée de l’incident ne sont pas établies. Après comparaison, Google Routes n’a pas été retenu car Jessy ne souhaite pas activer une facturation avec carte bancaire pour cet ECF.

ItineraireLivraison utilise désormais POST `https://api.heigit.org/openrouteservice/v2/directions/driving-car/json`, en-tête Authorization avec ORS_API_KEY, coordonnées [longitude, latitude], preference=fastest, units=m, geometry=false et instructions=false. Le service lit routes[0].summary.distance, vérifie type/valeur finie/positivité/borne puis arrondit au mètre. Timeout 5 secondes, durée maximale 15 secondes. La méthode publique calculerDistance reste la même : le contrôleur et les services de prix n’ont pas eu à être réécrits pour changer de fournisseur.

La véritable clé est dans .env.local ; .env contient seulement ORS_API_KEY=. Aucun secret ne figure dans ce document. L’appel reste côté PHP. L’offre Standard et les quotas doivent être revérifiés à la livraison ; l’attribution ORS/HeiGIT et OpenStreetMap requise pour l’usage retenu reste à formaliser dans l’interface et les documents. Une autre API reste susceptible de panne : aucun fonctionnement hors ligne n’est garanti.

Essai ORS confirmé : 1 Impasse des Lilas, 33650 Martillac → 17 980 m ; 5 + 17,980 × 0,59 = 15,6082 €, soit 15,61 €. Pour 4 personnes à 120 € sans remise, total 135,61 €. L’ancien essai IGN de 19 274 m et 16,37 € est une référence historique, pas la valeur attendue avec ORS. Bordeaux évite le calcul d’itinéraire, mais dépend encore du géocodage.

### 8.4 Enregistrement atomique et double validation

EnregistrementCommande reçoit un objet Commande préparé côté serveur. Il vérifie les ids/relations et le jeton, puis exécute wrapInTransaction. Le menu est rechargé avec PESSIMISTIC_WRITE pour lire les valeurs actuelles et verrouiller sa ligne. Après le verrou, une recherche du jeton retourne la commande existante si elle correspond au même utilisateur/menu. Une nouvelle commande exige compte actif, stock positif, effectif valide (borne 999999), délai encore respecté et montants compatibles avec INTEGER PostgreSQL. Le montant/remise/total sont recalculés depuis le menu actuel et comparés au récapitulatif ; un changement de tarif exige un nouveau calcul, sans confirmation silencieuse d’un autre prix. Les frais de livraison viennent du récapitulatif serveur, pas du POST.

Le service fixe en_attente, retire une unité de stock, crée HistoriqueCommande(en_attente, auteur=client), prépare les deux nouveaux objets avec persist puis laisse wrapInTransaction effectuer flush et commit. Une exception annule les changements de la transaction ; l’EntityManager peut alors être fermé. Les erreurs métier interceptées dans le contrôleur conduisent à une redirection, sans nouvelle requête avec ce manager dans la même réponse. Aucun appel IGN/ORS ni email ne se déroule sous le verrou SQL.

La session reçoit commande_id après succès et les réponses POST redirigent en 303. Un nouvel envoi du même brouillon retrouve la référence créée ; la contrainte UNIQUE constitue une garantie supplémentaire. Ce mécanisme ne confond pas deux nouveaux brouillons ayant la même adresse : ceux-ci correspondent à deux demandes distinctes. L’état initial en_attente signifie que l’équipe n’a pas encore accepté la prestation.

Tests confirmés : commande n°1, 4 personnes, menu 12000 centimes, remise 0, livraison 1222, total 13222 ; une ligne initiale d’historique avec auteur ; stock du menu de 5 à 4. Nouvelle validation du même brouillon : même référence, pas de seconde consommation de stock. Le scénario de deux clients distincts sur le dernier stock et un rollback SQL provoqué ne sont pas encore exécutés ; la présence d’un verrou ne remplace pas ces essais.

### 8.5 Confirmation par email

EmailConfirmationCommande et templates/emails/confirmation_commande.html.twig construisent un TemplatedEmail contenant référence, état en attente, prestation, adresse, menu, remise, livraison et total enregistrés. L’expéditeur vient de `MAILER_FROM_EMAIL`, selon la configuration locale/Render de la section 4.3.

Le contrôleur appelle le service après la transaction, uniquement si l’objet retourné est le nouvel objet Commande (`$commandeEnregistree === $commande`). Le retour d’une commande existante ne redéclenche pas l’envoi. Un TransportExceptionInterface est journalisé avec référence de commande ; l’échec SMTP ne remet pas en cause l’enregistrement. Cette solution synchrone n’est pas une garantie de livraison du mail au destinataire et n’est pas une file d’attente.

Essais confirmés : réception dans Mailpit ; second envoi du formulaire sans second mail ; arrêt de Mailpit, nouvelle commande enregistrée avec succès, puis redémarrage sans mail tardif. Aucune nouvelle tentative automatique n’existe. Si le processus s’arrête entre le commit SQL et l’envoi, une notification peut manquer : mécanisme de reprise/outbox à étudier selon le périmètre, sans le présenter comme implémenté.

### 8.6 Consultation et suivi du client

MesCommandesController filtre les listes par le compte authentifié. Le détail recherche id+utilisateur, lit l’historique trié par date et id et affiche des libellés lisibles pour les huit statuts. Les montants proviennent de la commande enregistrée ; modifier le tarif du catalogue ne recalcule pas le total d’une ancienne commande. Le nom et la composition du menu restent cependant issus du catalogue courant : leur photographie historique n’est pas encore complète. Les motifs internes et coordonnées d’autres comptes ne sont pas affichés au client.

Essais : deux comptes voient leurs listes séparées ; ouverture d’une commande d’autrui → 404 sans adresse ni montant ; détail avec événement initial. La page succes reste protégée elle aussi. Les boutons de navigation ne remplacent jamais les conditions serveur.

### 8.7 Annulation avant acceptation

AnnulationCommande exécute une transaction avec verrou du menu puis de la commande, relit les relations/propriétaire et le statut. Si déjà annulee, il retourne false sans écriture. Si le statut n’est plus en_attente, il refuse l’annulation en ligne et demande de contacter l’équipe. Sinon il passe annulee, rend une unité de stock et crée un événement avec auteur client et motif explicatif. Les montants et coordonnées sont conservés ; la ligne commande n’est pas supprimée. Aucun mode de contact employé n’est inventé pour cette action du client.

Le formulaire Twig n’est affiché qu’en attente ; le serveur ne s’appuie pas sur cet affichage pour autoriser. POST seul, CSRF spécifique et propriété sont contrôlés. Le service ne gère pas encore l’annulation par un employé ni une notification d’annulation.

Essai n°5 : annulation, stock +1, historique annulee. Pour tester le second POST, le bouton a été temporairement affiché sur le statut annulé : message déjà annulée, stock inchangé et COUNT des événements annulee égal à 1. La condition initiale doit rester limitée à en_attente dans le code livré. Essai n°4 : page ouverte en attente, statut passé temporairement à acceptee en SQL, clic sur l’ancien bouton sans recharger → refus serveur, stock inchangé, zéro événement d’annulation. Retour du statut à en_attente après le test. Il s’agit de données de test, pas d’une interface d’acceptation employé.

### 8.8 Modification avant acceptation

ModificationCommandeController prépare les changements du propriétaire sur une commande en_attente. Le menu reste fixé. L’effectif, la date, l’heure, l’adresse et le téléphone sont les données de prestation préparées ; les montants sont calculés côté serveur. Le parcours distingue adresse conservée (frais initiaux) et adresse changée (géocodage, choix puis nouveau calcul de livraison par AdresseModificationCommandeController). Aucun enregistrement n’a lieu au stade du récapitulatif.

La session modification_commande_adresse porte le brouillon d’adresse et l’empreinte_source. modification_commande_confirmation conserve jeton aléatoire de 32 caractères hexadécimaux, ids commande/utilisateur/menu, empreinte, donnees, recapitulatif et expire_a. Une nouvelle soumission invalide la confirmation précédente ; l’expiration vaut 30 minutes dans le parcours direct et est héritée du brouillon dans le parcours adresse.

ConfirmationModificationCommandeController, route GET/POST /mes-commandes/modification/confirmer/{jeton} (app_modification_commande_confirmation), vérifie compte, propriétaire, jeton, expiration, statut et empreinte. GET affiche ; POST exige le CSRF modifier_commande_{jeton} avant l’écriture. Après succès, enregistree=true et redirection 303 vers le détail ; un renvoi de cette confirmation retrouve la commande sans réécriture.

ModificationCommande utilise wrapInTransaction, recharge/verrouille le menu puis la commande avec PESSIMISTIC_WRITE et recontrôle propriétaire, statut, ids, expiration, empreinte, compte actif, minimum, délai et prix. calculerEmpreinte produit un SHA-256 de l’état métier sérialisé ; il détecte un état devenu différent, sans autoriser l’action ni mémoriser toutes les versions intermédiaires. Les frais conservés ou recalculés et les montants doivent correspondre au récapitulatif serveur. Les limites INTEGER sont contrôlées. Les API ne sont pas rappelées sous ces verrous.

Le service modifie la même ligne, sans setMenu ni changement de stock. Un événement HistoriqueCommande garde en_attente, l’auteur client et le motif « Modification de la prestation par le client avant acceptation. ». Une saisie identique retourne false sans événement ; une modification retourne true. L’historique décrit donc aussi une action sans transition de statut, mais ne conserve pas un différentiel détaillé avant/après.

Correction d’interface : dans modifier.html.twig, le lien Continuer vers la confirmation avait été placé sous adresses_proposees. Il est maintenant dans recapitulatif is not null ; le parcours sans changement d’adresse affiche bien le lien vers la confirmation finale.

Essais confirmés par Jessy le 25 octobre : effectif, date et adresse modifiés ; même référence/menu/statut, frais conservés ou recalculés, stock inchangé, événement ajouté ; second envoi sans événement supplémentaire ; confirmation déjà ouverte refusée après passage temporaire du statut à acceptee. Le premier résultat ambigu de ce dernier test a été corrigé puis le refus confirmé. Ces essais ne constituent pas une preuve de concurrence simultanée. Commit/push sur feature/commandes confirmés, SHA non communiqué. Pas d’email de modification implémenté dans ce parcours.

### 8.9 Profil client et navigation

GET/POST /mon-profil (app_profil) utilise ProfilController et ProfilType, limité à ROLE_CLIENT et au compte actif issu de getUser(). Aucun id de compte à choisir dans l’URL. Les champs nom, prenom, telephone, adresse, codePostal et ville reprennent NotBlank et Length de l’inscription. data_class=Utilisateur permet le préremplissage et l’écriture par setters. Le formulaire refuse les champs supplémentaires et utilise csrf_token_id=modifier_profil ; email, mot de passe, rôles et actif ne sont pas exposés. Après soumission valide, flush de l’utilisateur déjà suivi puis redirection 303. Aucune migration nécessaire pour ce périmètre.

Tests confirmés le 26 octobre : préremplissage, modifications persistantes après actualisation, nom vide refusé y compris après retrait de required HTML, coordonnées des anciennes commandes inchangées. Le profil n’est pas un recalcul de commande et ne vérifie pas l’adresse par géocodage.

base.html.twig utilise une seule balise html avec lang=fr et regroupe les liens dans nav. Visiteur : Se connecter (app_login). Client : Mon profil et Mes commandes. Connecté : Se déconnecter (app_logout). Employé/admin : Commandes, vers app_gestion_commandes. Les doublons Mon profil/Mes commandes ont été supprimés et le lien employé manquant rétabli le 29 octobre. Connexion/déconnexion et retour à une demande de connexion sur /mon-profil après déconnexion confirmés. Masquer les liens ne remplace pas les contrôles des routes. Navigation finale complète/mobile/accessibilité à terminer.

### 8.10 Liste et acceptation par l’équipe

security.yaml définit ROLE_ADMIN: ROLE_EMPLOYE. L’admin hérite des droits employé, sans héritage automatique de ROLE_CLIENT. getRoles() ajoute ROLE_USER mais ne développe pas cette hiérarchie. GestionCommandeController porte IsGranted(ROLE_EMPLOYE) sur la classe ; un compte actif est également exigé. GET /gestion/commandes (app_gestion_commandes) utilise findDernieresPourGestion avec les paramètres de statut, de recherche client et de page. Le contrôleur valide la saisie et transmet au template le total, la page courante et le nombre de pages.

POST /gestion/commandes/{id}/accepter (app_gestion_commandes_accepter) exige le CSRF accepter_commande_{id}, retrouve la commande et appelle AcceptationCommande. Le service verrouille/recharge le menu puis la commande, recharge l’auteur et exige actif avec ROLE_EMPLOYE ou ROLE_ADMIN explicitement. Après relecture du menu/statut : acceptee retourne false ; tout statut autre que en_attente est refusé. Sinon, passage à acceptee et événement avec auteur et motif « Commande acceptée par un membre de l’équipe. » dans la même transaction. Aucun changement de stock et aucun email d’acceptation dans ce service. Redirection 303 avec flash après action. L’acceptation ne renseigne pas de mode de contact. La modification et l’annulation après contact sont des actions séparées, décrites en section 8.16.

Compte employé de test préparé par inscription publique puis remplacement SQL du rôle sur ce compte dédié, suivi d’une déconnexion/reconnexion. Ceci n’est pas l’interface administrateur de création d’employés. Le problème de connexion initial a été résolu par Jessy sans cause établie ; ne pas conclure qu’un changement de rôle modifie le mot de passe.

Tests confirmés : client → 403 sur /gestion/commandes ; accès et affichage employé ; acceptation, auteur/motif/date dans l’historique et stock constant ; renvoi du POST avec bouton temporaire → un seul événement acceptee ; page employé laissée ouverte, annulation par le client dans une fenêtre privée, puis ancien POST d’acceptation → refus « Seule une commande en attente peut être acceptée. ». Ce dernier essai est séquentiel avec deux sessions, pas une concurrence simultanée. Le bouton livré est limité à en_attente ; la condition temporaire de renvoi doit être retirée avant commit.

Commit/push du profil/navigation puis de gestion/acceptation confirmés sur feature/commandes ; SHA non communiqués. L’espace admin n’a pas été testé avec un compte admin. Les essais CSRF invalides et compte employé désactivé restent à exécuter directement.

### 8.11 Détail employé et historique

GET /gestion/commandes/{id} (app_gestion_commandes_show) exige les droits équipe et un compte actif. show() charge la commande ou retourne 404, puis son historique par dateChangement ASC et id ASC. Le template gestion/commande/detail.html.twig affiche compte client actuel, coordonnées de prestation persistées, menu courant, montants enregistrés, statut, prêt et historique avec auteur/motif/mode de contact si présents. Un auteur NULL est indiqué Non renseigné ; il n’est pas assimilé automatiquement à une action système. Les instants sont affichés Europe/Paris ; date et heure de prestation restent les champs métier séparés.

Tests confirmés : ouverture depuis la liste employé, informations/historique, acceptation depuis le détail ; client connecté → 403 sur ce détail ; référence inexistante avec employé → 404. Les motifs internes restent réservés à cette vue équipe. Les noms/composition du menu et nom/email du compte restent des données courantes, pas une photographie historique complète.

### 8.12 Suivi jusqu’à livraison et terminaison sans prêt

SuiviCommande définit une table de transitions vers en_preparation depuis acceptee, en_livraison depuis en_preparation, livree depuis en_livraison et terminee depuis livree sans matériel. POST /gestion/commandes/{id}/suivi/{statut} (app_gestion_commandes_suivi) limite les statuts dans la route et exige le CSRF suivi_commande_{id}_{statut}. Le service vérifie à nouveau la transition après verrou menu puis commande, relecture auteur actif/rôle et relation menu. Statut et historique sont enregistrés ensemble ; aucun mouvement de stock. Demander le statut déjà atteint retourne false. Un ancien formulaire visant un statut précédent après une nouvelle avancée est refusé, sans retour en arrière.

Le template associe le statut courant à l’action suivante via actionsSuivi ; merge ajoute les actions propres au matériel. Sans matériel, livree peut devenir terminee avec motif explicite. Avec matériel, cette terminaison directe est refusée côté serveur. Tests confirmés : les trois étapes et leurs événements/auteurs, stock constant ; double envoi vers le même statut → message déjà atteint ; ancien bouton de préparation depuis en_livraison → refus. Terminaison sans prêt, historique et disparition du bouton confirmés. Ces essais ne sont pas des transactions simultanées.

### 8.13 Prêt, demande de retour et email

PretMaterielCommande::definir reçoit un booléen explicite. POST /gestion/commandes/{id}/materiel/{pret} (app_gestion_commandes_materiel) limite pret à 0 ou 1, CSRF materiel_commande_{id}_{pret}. Sous les mêmes verrous et contrôles auteur, seul en_attente ou acceptee avec dateRetourMateriel NULL permet de modifier l’indication. Un choix identique ne crée pas d’événement. Sinon materielPrete change et l’historique garde le statut courant avec motif prêt prévu/retiré. Il s’agit d’une indication logistique, pas d’un inventaire quantifié. Le blocage dès préparation est un choix de fonctionnement retenu pour ce parcours.

Tests confirmés : Oui/Non, motifs/auteur, statut et stock constants, aucune date de retour ; formulaire disparu dès préparation ; ancien formulaire ouvert avant préparation renvoyé ensuite → refus côté serveur.

Avec prêt, livree → attente_retour_materiel est ajouté à SuiviCommande. Le contrôle exige materielPrete=true et aucune date de retour. Le formulaire propose Demander le retour du matériel. Après succès de la transaction, suivi() appelle EmailRetourMateriel uniquement si modifiee=true et statut cible attente_retour_materiel. Le template emails/retour_materiel.html.twig rappelle 10 jours ouvrés à compter de la notification et 600 € prévus en cas de non-restitution ; il n’effectue ni calcul de date limite ni facturation.

Diagnostic corrigé : l’appel email avait été collé dans catch ; il n’était jamais exécuté lors d’une transition réussie et utilisait potentiellement une variable non définie. L’appel est désormais après le catch, avant le flash de succès. Des accolades mal placées ont ensuite été corrigées par remplacement complet du contrôleur. php -l valide la syntaxe ; la réception dans Mailpit a confirmé le fonctionnement.

EmailRetourMateriel reprend Mailer/TemplatedEmail, l’expéditeur configuré dans `MAILER_FROM_EMAIL` et Logger. Un TransportExceptionInterface est journalisé sans annuler le statut déjà enregistré. Mailpit doit tourner avant le clic : démarrer le SMTP après un échec ne relance pas le mail. Aucun mécanisme de reprise/outbox ni garantie de réception client réelle n’existe. Le booléen modifiee évite l’envoi sur un renvoi sans transition ; un test dédié de double mail et une panne SMTP propres à ce nouveau service restent à consigner. Ne pas revenir artificiellement en arrière en SQL pour simuler une reprise.

Tests confirmés : état attente_retour_materiel, historique/auteur, matériel=true, date_retour_materiel NULL, stock constant et mail reçu dans Mailpit pour la bonne commande.

### 8.14 Restitution et terminaison avec prêt

RetourMaterielCommande::confirmer exige auteur équipe actif, prêt enregistré, statut attente_retour_materiel et date de retour encore NULL. POST /gestion/commandes/{id}/materiel/retour (app_gestion_commandes_retour_materiel), CSRF retour_materiel_commande_{id}, appelle ce service. Le menu puis la commande sont relus sous verrou. La date serveur now Europe/Paris est enregistrée dans dateRetourMateriel et utilisée aussi comme date de l’événement terminee, auteur et motif de restitution. Commande et historique sont écrits dans la même transaction. materielPrete reste true pour conserver la trace du prêt ; stock inchangé. La date représente la confirmation par l’équipe, sans saisie rétroactive de la date de réception physique.

Déjà terminee avec date de retour → false : ni nouvelle date ni événement. Tests confirmés : terminaison/date/motif/auteur et bouton disparu ; second envoi depuis un second onglet → message déjà enregistré, date initiale inchangée et COUNT de restitution égal à 1. Après une restitution qui termine la commande, le contrôleur déclenche l’invitation à donner un avis après le retour du service.

### 8.15 État des données locales après nettoyage

Le 29 octobre, Jessy a confirmé le nettoyage des dix commandes de test et la remise du stock de tous les menus à 10. La procédure locale transactionnelle supprimait avis liés, historiques puis commandes, avant UPDATE menu SET stock_commandes=10. Ce réglage est un jeu de test, pas une limite globale à dix commandes ni la règle métier de réapprovisionnement. La numérotation n’a pas été remise à zéro dans la procédure fournie ; les comptes et le catalogue ont été conservés. Déconnexion/reconnexion recommandée pour abandonner les anciens brouillons ; exécution de cette déconnexion non confirmée séparément.

Les références et résultats SQL antérieurs sont des preuves de recette historiques : leurs lignes ne sont plus forcément présentes dans la base nettoyée. Préparer de nouvelles commandes pour les prochaines démonstrations. Cette opération n’est ni une fonctionnalité de suppression métier du site ni un script à exécuter sur des données réelles.

### 8.16 Filtres, pagination et actions d’équipe après contact

La liste équipe valide le statut demandé, le texte de recherche client et le numéro de page. Un filtre inconnu ou une saisie mal formée entraîne une réponse 400. Une page au-delà des résultats entraîne une réponse 404. Le fonctionnement des filtres a été confirmé localement.

L’employé peut annuler ou modifier une commande via des parcours dédiés après contact avec le client. Le mode de contact et le motif documentent l’action. Le parcours de modification conserve un brouillon côté serveur, calcule un récapitulatif avant confirmation et identifie la version initiale de la commande par son empreinte. Pour l’annulation, le renvoi d’un formulaire déjà traité a été essayé depuis un autre onglet et l’événement reste unique.

### 8.17 Suite à développer

Gestion du catalogue et des horaires, désactivation des employés, échéance de retour en jours ouvrés, retards et procédure de frais. La notification de création d’employé a été reçue sur Render. Le lot de lecture et affichage des horaires est livré et testé localement selon le compte rendu de Codex. Les horaires ont été validés par Jessy pour le projet. Le regroupement SQL et le chargement sur Render restent à confirmer. Rejouer les contrôles de rôles, les demandes concurrentes et l’installation sur un environnement neuf lors de la recette finale.

<a id="horaires-footer"></a>

### 8.18 Horaires du footer

Le 18 novembre, Jessy a retenu les horaires suivants pour le projet pédagogique. Ce sont des **valeurs proposées pour le projet**, à faire valider avant de les présenter comme horaires réels.

| Jour | Horaires retenus pour le projet |
|---|---|
| Lundi | 09h00–12h00 et 14h00–18h00 |
| Mardi | 09h00–12h00 et 14h00–18h00 |
| Mercredi | 09h00–12h00 et 14h00–18h00 |
| Jeudi | 09h00–12h00 et 14h00–18h00 |
| Vendredi | 09h00–12h00 et 14h00–18h00 |
| Samedi | 10h00–14h00 |
| Dimanche | Fermé |

#### Lecture et affichage

Les tables `horaire_jour` et `creneau_horaire`, définies dans `sql/01_creation.sql`, restent la source. Les entités `HoraireJour` et `CreneauHoraire` les mappent sans nouvelle migration. `HoraireJourRepository::findSemaineAvecCreneaux()` charge les jours et créneaux en **une requête jointe**, triée par jour puis heures d’ouverture/fermeture. Les contrôleurs n’ont pas à charger les horaires.

`HorairesExtension`, autoconfigurée par Symfony, expose `horaires_footer()`, appelée dans `templates/partials/footer.html.twig`. Elle construit toujours sept jours français, de lundi (1) à dimanche (7). Les colonnes SQL `TIME` sont des heures locales, formatées `09h00–12h00` sans conversion de fuseau :

- Un jour fermé affiche **Fermé**, même si d’anciens créneaux subsistent.
- Un jour ouvert affiche tous ses créneaux, dans l’ordre.
- Un jour absent ou ouvert sans créneau affiche **Horaires non renseignés**.

Une `WeakMap` mémorise le résultat pour la requête principale : plusieurs appels dans cette requête ne refont pas la lecture, et la requête suivante relit PostgreSQL sans cache persistant à invalider. Le nombre de menus n’ajoute aucune requête horaire. Le filtrage JavaScript actuel du catalogue renvoie aussi une page complète, donc une seule lecture des horaires pour cette réponse.

Le footer bordeaux conserve marque et liens, avec trois colonnes sur grand écran ; ses blocs s’empilent à 900 px et moins et les jours/heures restent alignés. Aucune écriture automatique ni interface de modification n’est ajoutée. `DATABASE_URL` est réutilisée, sans nouvelle variable ni dépendance Composer.

#### Initialisation et conservation des données

Le bloc horaires est désormais dans `sql/02_donnees.sql`, avant le `COMMIT` final. Sur une base vide, il crée **sept jours et onze créneaux** dans la transaction des données initiales. Exécuter `01_creation.sql` puis `02_donnees.sql` avec `psql -v ON_ERROR_STOP=1 -f ...`, ou dans le Query Tool pgAdmin, selon la procédure du README. Appliquer ensuite la migration de révision des commandes (sections 6.5 et 13).

`ON CONFLICT (jour_semaine) DO NOTHING` conserve les jours présents ; `RETURNING` transmet seulement les jours nouvellement insérés à l’ajout des créneaux. Un jour existant sans créneau reste sans créneau ; son état fermé et ses créneaux ne sont pas remplacés. Le verrou de transaction sérialise les écritures dans les deux tables. **Seul le bloc horaires est rejouable ; le fichier `02_donnees.sql` complet ne l’est pas.**

<a id="horaires-pgadmin"></a>

#### Bases déjà initialisées : bloc SQL pour pgAdmin

Sur la base locale existante ou PostgreSQL Render, ouvrir le **Query Tool de la base concernée**, vérifier `SELECT current_database();`, puis exécuter uniquement ce bloc. Les deux tables doivent déjà exister. Depuis le PC, Render utilise les paramètres de connexion externes et TLS ; autoriser l’accès réseau concerné. Les identifiants restent hors Git (section 4.3).

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

Contrôler le résultat avec :

```sql
SELECT h.jour_semaine, h.ferme, c.heure_ouverture, c.heure_fermeture
FROM horaire_jour h
LEFT JOIN creneau_horaire c ON c.horaire_jour_id = h.id
ORDER BY h.jour_semaine, c.heure_ouverture, c.heure_fermeture;
```

Déployer les fichiers PHP/Twig/CSS avec l’application et reconstruire le cache prod selon la section 13 si le déploiement ne le fait pas déjà. Contrôler `/`, `/menus` et `/login` : sept jours, valeurs, fermeture et empilement mobile. Les changements SQL sont visibles dès la requête suivante. Les vérifications isolées et les résultats déjà rapportés figurent en section 11.4.

Le chargement local des horaires choisis et leur déploiement sur PostgreSQL Render restent à confirmer. L’interface employé de modification des horaires reste à développer. Les horaires inscrits ici sont ceux retenus pour le projet pédagogique.

### 8.19 Accès à la modération

La page de traitement des avis est `/gestion/avis`, attendue pour les employés et le gérant. Le 16 novembre, Jessy a signalé la difficulté à la retrouver. Un prompt a été fourni pour ajouter le lien « Modérer les avis » dans la navigation ordinateur et mobile. La livraison et la recette de ce lien restent à confirmer.

## 9. Sécurité, données personnelles et accessibilité

### 9.1 Mesures mises en place

Paramètres de requête liés par Doctrine ; contraintes SQL ; validation serveur des formulaires et filtres ; échappement de sortie Twig dans les rendus ordinaires ; mots de passe hachés ; CSRF connexion, formulaires Symfony et POST final/annulation/modification/acceptation/suivi/prêt/restitution et formulaire de profil ; rôle ROLE_CLIENT et propriété des commandes contrôlés ; jeton de validation UNIQUE contre les doublons ; unicité email ; refus des comptes inactifs à la connexion ; lien reset déjà utilisé refusé ; configuration sensible locale exclue de Git.

Ces mesures ne constituent pas un audit de sécurité. Il reste notamment les autorisations des fonctions employé/admin encore absentes et les tests directs du rôle admin/de la désactivation, les essais directs CSRF/propriété sur toutes les écritures et les tentatives répétées, la normalisation des emails, la cohérence entre limites des mots de passe et hacheur, les erreurs mail hors confirmation de commande et bienvenue, les uploads de gestion, les secrets de production, HTTPS/cookies et les logs sans données sensibles. Les durées de conservation, informations RGPD, mentions légales et CGV sont à formaliser.

### 9.2 Accessibilité et interface

Déjà travaillés : langue française, viewport, titres, labels associés, focus visible, texte alternatif, essais initiaux à 320 px, messages d’erreur et annonces des filtres dynamiques. Aucun audit RGAA complet n’a été réalisé ; aucune conformité globale n’est revendiquée.

À vérifier : clavier et ordre du focus sur tous les parcours, contrastes de la charte, erreurs associées aux champs, annonces de chargement/résultats, zoom et redistribution, images, boutons et formulaires. Consigner les critères testés et les corrections plutôt que déclarer une conformité sans preuve.

## 10. Conception graphique

Trois écrans de référence ont été conçus en version ordinateur et mobile pour l’accueil, le catalogue et la commande. `WireFrames.pdf` contient les six wireframes sur une planche. `Charte Graphique.pdf` contient les sections identité et logo, palette et typographie, interface et images. Les deux exports ont été ouverts et vérifiés le 12 novembre. Le PDF des mockups en couleur présente les mêmes trois écrans en ordinateur et mobile. Les trois PDF figurent dans `docs/`.

| Élément | Valeur retenue |
|---|---|
| Bordeaux principal | #7B2D3E |
| Bordeaux foncé | #5A1F2D |
| Rose clair | #F5E8EA |
| Beige de fond | #F8F5F1 |
| Blanc | #FFFFFF |
| Texte principal / secondaire | #2C2C2A / #62615B |
| Titres et logo | Playfair Display |
| Texte et interface | DM Sans |

Le chargement effectif des polices et l’harmonisation finale du site sont encore à vérifier. Les suggestions d’accord vin et d’alternatives sans alcool sont un ajout éditorial dans les maquettes : boissons non vendues ni fournies. Leur intégration fonctionnelle et leur stockage ne sont pas confirmés. Elles ne doivent pas retarder le périmètre obligatoire.

## 11. Registre de validation

« Confirmé » désigne un résultat partagé pendant l’accompagnement, sans relecture indépendante de tout le code.

| Test | Résultat connu au 25 octobre | Limite / suite |
|---|---|---|
| PHP/Doctrine accèdent à la bonne base et aux thèmes | Confirmé | Maintenir la configuration locale |
| Reconstruction SQL dans une base de test | Confirmée sur une version antérieure | Refaire avec le dernier schéma, y compris reset et jeton_validation |
| Catalogue et jointures thème/régime/plats/allergènes | Confirmé | Étendre les jeux de données |
| Image et détail, identifiant absent | Confirmé, 404 obtenue | Vérifier galerie et tous les champs |
| Grille et image à 320 px | Premier contrôle réussi | Pas de recette responsive globale |
| Filtres combinés, réinitialisation, sans rechargement | Confirmé avec deux menus | Tester davantage de combinaisons et erreurs réseau |
| Fourchette minimum 150 / maximum 100 | Message dans la page | Paramètres malformés à harmoniser |
| Inscription avec mot de passe faible | Refus et message confirmés | Tester toutes les bornes |
| Inscription valide et doublon email | Compte créé, doublon refusé et une seule ligne | Normalisation email à définir |
| Connexion/déconnexion ; compte inactif | Confirmé | Session ouverte puis désactivation à tester |
| Email bienvenue | Reçu dans Mailpit | Transport réel et reprise à préparer |
| Reset : mail → lien → nouveau mot de passe → connexion | Confirmé | Ancien mot de passe non retesté |
| Reset : lien déjà consommé | Refus confirmé | Expiration, adresse inconnue, saisies invalides et panne SMTP à tester |
| Lecture DQL initiale Commande et HistoriqueCommande | Résultats vides à l’origine ; lignes réelles lues depuis | Tests de persistance ci-dessous |
| Calcul montant : 4/6/9 convives et minimum refusé | Confirmé via scripts PHP PowerShell | Objets en mémoire, sans écriture SQL |
| Calcul arrondi montant : 12001 centimes / minimum 4 × 6 | 18002 centimes confirmés | Arrondi demi-centime vers le haut |
| Remise : 8/9/10 convives pour minimum 4 | 0/2700/3000 centimes confirmés | Seuil minimum +5 vérifié |
| Arrondi remise : minimum 4, prix 12002, 9 convives | 2701 centimes confirmés | Double étape d’arrondi documentée |
| Total : sans remise, avec remise, avec frais 1090 | 18000/24300/25390 centimes confirmés | Frais 1090 fictifs, pas un calcul de distance |
| Frais de livraison négatifs | Refus confirmé | Calcul réel ORS testé ci-dessous |
| Commande GET : préremplissage, connexion et retour | Confirmé ; effectif initial 4 | Aucune écriture |
| Délai : premier jour autorisé et validation serveur | Exemple du 21/10 observé le 18/10 ; date trop proche refusée au POST ensuite | Recontrôle au moment de la confirmation présent |
| Validation serveur minimum/date après contournement HTML | Refus confirmés par soumission | Ne pas se limiter aux champs grisés du navigateur |
| Géocodage : résultat street sans numéro | Écarté ; aucune adresse précise proposée | Tous les schémas API et correspondances de saisie non testés |
| Sélection d’une adresse précise | Adresse affichée puis confirmée | Indice forcé, jeton expiré, multi-onglets à tester |
| IGN itinéraire puis erreur 404 | Succès historique puis panne reproduite | Cause côté fournisseur non établie ; ORS remplace cet appel |
| ORS Martillac, 4 personnes | 17 980 m, frais 15,61 €, total 135,61 € | Réception d’un calcul, pas garantie de disponibilité future |
| Création SQL + stock + événement initial | Commande, montants et auteur vérifiés ; stock −1 | Injection d’échec SQL/rollback non réalisée |
| Renvoi de confirmation | Même référence, stock décrémenté une fois | Deux clients concurrents sur le dernier stock non testés |
| Email de commande | Reçu dans Mailpit avec référence et montants | Production non testée |
| Renvoi et email | Aucun mail supplémentaire | Reprise après arrêt du processus non implémentée |
| Mailpit arrêté pendant nouvelle commande | Commande enregistrée ; aucun envoi après redémarrage | Pas de nouvelle tentative automatique |
| Listes et détails de deux clients | Listes indépendantes ; accès au détail d’autrui → 404 | POST hostile d’annulation sur autrui à tester directement |
| Annulation n°5 | Statut annulee, stock +1, historique ajouté | Notification d’annulation non développée |
| Renvoi d’annulation n°5 | Déjà annulée ; stock inchangé ; un événement annulee | Bouton de test temporaire remis dans son état initial |
| Annulation après acceptation temporaire n°4 | Refus depuis une ancienne page ; stock inchangé ; aucun événement annulee | Essai historique par SQL ; acceptation employé ajoutée le 26/10 |
| Prix modifié entre récapitulatif et validation | Refus codé | Cas à exécuter |
| CSRF manquant/invalide sur confirmation et annulation | Contrôle codé | Cas négatifs directs à exécuter |
| Modification : effectif/date/adresse et frais | Confirmée sur la même commande ; stock inchangé ; événement en_attente | Heure/téléphone non isolés comme essais distincts |
| Renvoi de confirmation de modification | Aucun nouvel événement ; stock inchangé | Concurrence simultanée non testée |
| Modification après acceptation depuis page ouverte | Refus confirmé | Essai historique par SQL ; acceptation employé ajoutée le 26/10 |
| Empreinte périmée, expiration, CSRF de modification et tarif changé | Protections codées | Essais négatifs directs à exécuter |
| Profil client (26/10) | Prérempli ; modifications persistées ; nom vide refusé ; commandes antérieures inchangées | Changement email/mot de passe hors périmètre |
| Navigation et déconnexion (26/10) | Liens adaptés ; profil demande connexion après déconnexion | Responsive/clavier global à vérifier |
| Gestion équipe (26/10) | Client 403 ; employé connecté voit la liste | Compte admin et employé désactivé à tester |
| Acceptation employé (26/10) | Statut, événement/auteur/motif et stock inchangé | Essai initial du 26/10 ; suivi ajouté depuis |
| Second POST d’acceptation (26/10) | Un seul événement acceptee après deux validations | Bouton temporaire à retirer avant commit |
| Annulation client puis ancien POST employé (26/10) | Refus confirmé sur commande annulée | Essai séquentiel, pas simultané |
| Détail équipe (27–29/10) | Informations/historique ; client 403 ; référence absente 404 ; acceptation depuis détail | Admin dédié toujours à tester |
| Suivi préparation/livraison/livrée | Événements/auteurs et stock constant confirmés | CSRF falsifiés et concurrence simultanée à tester |
| Renvoi et ancien bouton de suivi | Déjà même statut sans doublon ; retour en arrière refusé | Tests séquentiels par onglets |
| Terminaison sans prêt | terminee, historique et bouton disparu ; stock constant | Invitation ajoutée ensuite, voir la ligne de recette dédiée |
| Choix de prêt | Oui/Non tracés ; pas de changement de statut/stock ; date vide | Pas d’inventaire quantifié |
| Ancien formulaire de prêt après préparation | Refus confirmé | Pas de contournement par bouton visible |
| Attente retour + email | Statut/historique et réception Mailpit confirmés ; date de retour vide | Reprise email et calcul d’échéance absents |
| Restitution puis second envoi | Date serveur, terminaison, un seul événement et date conservée | Pas de test simultané ni email de restitution |
| Nettoyage local (29/10) | Dix commandes d’essai supprimées et stocks remis à 10, confirmation utilisateur | Résultats de recette antérieurs historiques |
| Filtres et actions d’équipe après contact | Filtres fonctionnels, modification validée, second envoi d’annulation sans événement supplémentaire | Recette globale à rejouer sur la version livrée |
| Dépôt d’avis client | Une ligne dans pgAdmin, statut en_attente et second dépôt refusé | Rejouer les refus directs de propriété et de statut |
| Modération et accueil | Page équipe accessible, accès client refusé, décision conservée sur renvoi, avis approuvé visible sur l’accueil | Rejouer les scénarios avec un compte admin dédié |
| Invitation à laisser un avis | Réception confirmée dans Mailpit | Consigner les résultats séparément pour les deux parcours, le renvoi et le clic sur le lien |
| Concurrence réelle | Non validée | Distinguer les essais séquentiels par onglets d’un vrai test simultané |
| Accessibilité globale | À valider | Recette nécessaire |

Des scripts de tests ciblés sont livrés, notamment pour les statistiques, les e-mails et la création d’employé (section 11.3). Ils ne constituent pas une suite exhaustive : les compléter avec les parcours manuels et leurs résultats datés, notamment sur seuils, transitions, propriété et concurrence.

### 11.1 Vérifications depuis le 8 novembre

| Vérification | Résultat communiqué | Suite |
|---|---|---|
| Reconstruction PostgreSQL | Scripts de création puis de données exécutés sur une base locale vide, puis sur Render. Deux menus et zéro avis au départ. | Ajouter les données finales de démonstration. |
| Déploiement Render | Accueil, catalogue et détail du menu classique accessibles. Le réglage explicite de la version PostgreSQL dans Doctrine en mode prod a résolu le premier HTTP 500. | Rejouer la recette complète sur la version de remise. |
| Création d’employés | `tests/creation_employe.php` couvre notamment les rôles, le doublon, le CSRF, les validations et la connexion sur SQLite temporaire. Le parcours gérant et la connexion employé ont aussi été validés manuellement sur PostgreSQL local et Render. | Notification employé testée localement et reçue sur Render. Désactivation à développer. |
| Clients de démonstration | Huit profils locaux et répartition contrôlés. Deuxième exécution sans doublon. Comptes créés sur Render. | Contrôler les six adresses restantes au récapitulatif. |
| Géocodage et frais sur Render | Pessac validé après ajout de `ORS_API_KEY`. Bordeaux validé avec le 30 rue Gustave Eiffel. | Conserver les résultats attendus par commune et vérifier l’adresse dans la commande de démonstration. |

### 11.2 Validation des statistiques le 12 novembre

| Vérification | Résultat communiqué |
|---|---|
| Synchronisation initiale en local | Cinq documents créés, puis cinq inchangés et zéro doublon lors du second passage. Changement de statut reflété sur le document existant. |
| Tableau de bord en local | Droits, filtres, période inclusive, changements d’heure, indisponibilité et montants connus testés. Le jeu de test comporte 13 commandes et 495,00 € de CA. |
| Écriture après transaction | Rejeu sans doublon, révision ancienne refusée, commit SQL refusé sans écriture MongoDB, commande SQL conservée pendant une panne MongoDB simulée en local et rattrapage par la commande Symfony. |
| Mise en ligne sur Render | Atlas connecté. Deux commandes visibles après synchronisation initiale. Migration appliquée et deux commandes initialisées à la révision 1 dans pgAdmin. Test de création et de passage à Terminée confirmé avec l’actualisation automatique. |

<a id="verification-services"></a>

### 11.3 Vérifications reproductibles des statistiques et des e-mails

Depuis la racine, installer les dépendances, activer les extensions PHP nécessaires (`mongodb`, `pdo_pgsql`, `pdo_sqlite`) et démarrer MongoDB local pour les scripts de statistiques. Activer aussi l’extension dans le serveur web pour la recette du tableau de bord (section 4.3).

```powershell
# Fixer explicitement MongoDB local avant les tests utilisant l’URI configurée.
$env:MONGODB_URI = 'mongodb://127.0.0.1:27017'
docker compose -p vite-gourmand -f compose.mongodb.yaml up -d
php -d variables_order=EGPCS tests/stats_sync.php
php -d variables_order=EGPCS tests/statistiques_automatiques.php
php -d variables_order=EGPCS tests/statistiques_dashboard.php
php tests/emails_transactionnels.php
php tests/creation_employe.php
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console lint:container
php bin/console lint:container --env=prod --no-debug
composer validate --strict --no-check-publish
git diff --check
```

`variables_order=EGPCS` rend les variables d’environnement du processus disponibles au PHP CLI du poste ; la configuration privée ne doit pas rediriger ces tests vers Atlas. Ajouter `-d "extension=$mongoExtension"` après `php` si la DLL locale n’est pas activée dans le `php.ini`. Les tests sont isolés, mais leurs prérequis doivent être satisfaits avant lancement.

| Script | Isolation et contrôles |
|---|---|
| `tests/stats_sync.php` | Tables PostgreSQL temporaires masquant les tables réelles dans sa transaction ; base MongoDB aléatoire `vg_stats_test_*`, supprimée à la fin. Huit statuts, dates, montants historiques, absence de données personnelles, rejeu sans doublon, mise à jour et panne MongoDB. Utilise l’URI MongoDB configurée, d’où le réglage local explicite ci-dessus. |
| `tests/statistiques_automatiques.php` | Force `127.0.0.1:27017`, sans Atlas ; schéma PostgreSQL et base MongoDB `vg_stats_auto_test_*` aléatoires, supprimés à la fin. Exécute la vraie migration et son trigger, valide les transactions métier, teste création/renvoi, transitions/terminaison, restitution, annulations et modifications client/équipe, menu relu depuis SQL, panne puis rattrapage et journaux sans secret. Une lecture ancienne en `REPEATABLE READ` ne doit pas écraser le document récent ; un trigger différé refuse le commit après flush pour vérifier l’absence de projection. |
| `tests/statistiques_dashboard.php` | Authentification et horaires du footer en SQLite temporaire ; aucune table SQL de commandes ou menus. Vraies agrégations dans une base MongoDB `vg_dashboard_test_*` aléatoire, supprimée à la fin. Droits/navigation, filtres et paramètres invalides, bornes à la milliseconde, périodes ouvertes et changements d’heure, base vide et panne. |
| `tests/emails_transactionnels.php` | Boîte en mémoire et parcours HTTP du reset en SQLite temporaire. Vérifie les From des quatre services de notification client et du reset, simule Brevo 201, contrôle POST HTTPS/authentification/contenu et sélection SMTP de Mailpit. Aucun envoi réel. |
| `tests/creation_employe.php` | SQLite temporaire et Mailer relié à un transport de test. Droits, CSRF, validation/doublon, rôle imposé, hachage/connexion ; destinataire et From, absence de mot de passe/hachage dans le mail, compte visible depuis une autre connexion avant envoi ; compte conservé et avertissement sur panne ou DSN invalide. Aucun compte créé dans la base de travail. |

Repères du jeu de tableau de bord : **13 commandes**, réparties **6 / 6 / 1** entre Menu Bordeaux, Menu Jardin et Menu Sans vente. Les cinq commandes terminées totalisent **49 500 centimes (495,00 €)** : 39 500 pour Bordeaux et 10 000 pour Jardin. Pour le 10 janvier 2026 inclus : **360,00 €**, dont **260,00 €** pour Bordeaux. Les dates de prestation différentes et les frais de livraison élevés vérifient leur exclusion du filtre et du CA ; les autres statuts sont représentés et exclus du CA.

Pour vérifier l’image, sans affirmer ici un nouveau build ou une nouvelle recette :

```powershell
docker build --progress plain -t vite-gourmand-stats .
docker run --rm vite-gourmand-stats php --ri mongodb
docker run --rm vite-gourmand-stats composer check-platform-reqs --no-dev
```

Les commandes ci-dessus sont les procédures disponibles ; leur présence dans la documentation ne signifie pas qu’elles ont toutes été relancées lors de cette consolidation. Les résultats datés déjà consignés restent dans les autres sous-sections du registre. Dans le navigateur, contrôler aussi graphique/tableaux à 390 et 1440 px, clavier, erreurs/indisponibilité et réception réelle de chaque type de mail vers une boîte de test autorisée.

### 11.4 Vérifications reproductibles des horaires

Depuis la racine, avec les dépendances installées et les extensions PHP/connexions configurées :

```powershell
php tests/horaires_footer.php
php tests/horaires_postgresql.php
php tests/installation_postgresql.php
php bin/console doctrine:schema:validate --skip-sync
php bin/console lint:container --resolve-env-vars
```

Les contrôles Twig, conteneurs dev/prod, e-mails et création d’employé sont communs avec la section 11.3. Contrôler aussi la syntaxe des fichiers PHP concernés avec `php -l`. Sous Windows, activer l’extension `mongodb` ou utiliser le préfixe de la section 4.3 pour les commandes Symfony qui en ont besoin.

- `tests/horaires_footer.php` crée puis supprime une base SQLite temporaire : semaine complète, plusieurs créneaux, fermeture prioritaire, données absentes/partielles, footer sur accueil/connexion/catalogue de six menus, une requête SQL par page et mémoïsation limitée à la requête, y compris la réponse AJAX complète.
- `tests/horaires_postgresql.php` utilise PostgreSQL local mais écrit dans deux tables **TEMP** copiant les tables publiques. Il extrait le bloc de `02_donnees.sql`, vérifie les mappings/rendus, deux exécutions identiques et la conservation d’une semaine partielle. Les tables publiques sont comparées avant/après ; copies et séquences temporaires disparaissent avec la connexion.
- `tests/installation_postgresql.php` nécessite le droit **CREATEDB**. Il crée une base vide aléatoire réservée aux tests, y exécute les fichiers complets `01_creation.sql` puis `02_donnees.sql`, vérifie le catalogue, les sept jours/onze créneaux et le rejeu du seul bloc horaires, puis supprime cette base. Il ne réinitialise pas la base de travail.

Les schémas minimaux des tests employé, e-mails et statistiques incluent les deux mappings horaires puisque ces pages rendent le footer commun ; cela ne change pas leurs règles métier. `doctrine:schema:validate --skip-sync` contrôle les mappings, sans comparer l’intégralité du schéma SQL.

Le compte rendu local antérieur au regroupement documentaire rapporte les trois tests horaires/installation réussis, ainsi que les suites employé/e-mails, syntaxe PHP, mappings, lint Twig (34 templates), conteneurs et `git diff --check`. Chrome a été contrôlé à 390 et 1440 px sur semaines complète, fermée/à plusieurs créneaux, vide et partielle, sans débordement. La suite du tableau de bord n’avait pas été relancée lors de ce lot faute de MongoDB local ; sa procédure reste en section 11.3. Ces résultats historiques ne sont pas une nouvelle exécution pendant la consolidation. Restent le chargement des horaires réels choisis sur les bases de travail/Render et la vérification des autres navigateurs.

### 11.8 Recette et état du code au 15 novembre

| Périmètre | Résultat et source | Contrôle restant |
|---|---|---|
| Accueil, catalogue, fiche, footer | Vérification directe sur Chrome ordinateur et Render. Photo de table, texte de présentation agrandi, deux menus mis en avant, huit cartes catalogue, fiche classique avec galerie et footer au bas de la connexion. | Vérifier sur Safari/Firefox et sur un téléphone réel. |
| Formulaires et responsive | Les comptes rendus locaux indiquent des essais à 390 et 1440 px, clavier, filtrage GET et JavaScript, états vides, sans photo et sans stock. Les tests d’accueil et de fiche sur SQLite utilisent des données contrôlées. | Rejouer sur la base Render les parcours client, employé et gérant. |
| E-mails | Réinitialisation et notification de création d’employé reçues sur Render selon les essais de Jessy. La notification employé est arrivée dans les indésirables. Les autres types de mail ont leur propre recette. | Vérifier les autres envois réels et leurs liens avant la remise. |
| Avis | Dépôt, modération et publication déjà éprouvés localement. Le formulaire en carte et la vue de modération figurent dans Git. | Vérifier la finition du formulaire, puis déposer et modérer un avis de démonstration sur Render et vérifier son affichage public. |

## 12. Couverture de l’énoncé

| Pages | Attendu | Situation |
|---|---|---|
| 3 | Accueil : entreprise, professionnalisme, avis validés | Accueil présenté et stylisé avec accès catalogue et avis approuvés. Photo de table et menus du moment en ligne. Contact et vérification sur téléphone réel à compléter. |
| 4 | Navigation et footer, sept jours, légal/CGV | Navigation et footer en ligne. Horaires lus depuis PostgreSQL et testés localement, valeurs validées pour le projet. Chargement Render, contact et destinations légales à terminer. |
| 4–6 | Catalogue, détail, cinq filtres dynamiques, commande | Lecture/filtres et parcours de commande réalisés ; finition et recette globale restantes |
| 5 | Inscription, critères du mot de passe et bienvenue | Parcours local réalisé/testé |
| 5 | Connexion et lien de réinitialisation | Réinitialisation confirmée sur Render par Jessy. Recette complémentaire ouverte. |
| 7 | Commande, prix, remise, livraison et confirmation | Transaction et mail testés localement. Géocodage et calcul de livraison validés en ligne pour deux adresses. Confirmation complète et concurrence à tester en ligne. |
| 7 | Profil, commandes personnelles, modifications et avis | Liste/détail/historique, annulation et modification client testés ; profil et dépôt d’avis testés |
| 8 | Gestion employé, statuts, matériel, modération | Liste/détail, suivi normal et matériel testés ; filtres, pagination, actions après contact et modération réalisés. Échéance et retards à traiter. |
| 9 | Administration, employés, statistiques NoSQL/CA | Création d’employés, graphique NoSQL et chiffre d’affaires filtrable validés. Notification reçue sur Render. Désactivation à réaliser. |
| 9 | Contact par email, mise en ligne, accessibilité | Mise en ligne de recette réalisée. Contact et recette d’accessibilité à traiter. |
| 10 | Base SQL et base NoSQL | PostgreSQL et MongoDB Atlas opérationnels. Synchronisation automatique et rattrapage CLI disponibles. |
| 11–12 | Dépôt, documents, schémas, exports et liens | Partiellement préparés ; voir checklist suivante |

## 13. Checklist des livrables

| Livrable demandé | État / action restante |
|---|---|
| Copie Studi renommée ECF_TPDeveloppeurWebEtWebMobile_copiearendre_NOM_Prenom | Brouillon Word renseigné dans le modèle fourni. Relire, ajouter la date de naissance et les accès administrateur de démonstration. |
| Lien GitHub PUBLIC | Lien connu ; visibilité publique à contrôler |
| Liens application(s) déployée(s) | https://vite-gourmand-recette.onrender.com/ — disponibilité à vérifier pour la remise |
| Lien gestion de projet | Le point du 15 novembre couvre les écrans, les vérifications et les livrables. |
| Bonnes pratiques Git | Fusion de feature/gestion-commandes dans develop vérifiée. Render suit develop. Relever l’état de main et le SHA livré. |
| README installation locale | README versionné. Rejouer la procédure depuis un clone vierge et y reporter l’import optionnel du catalogue. |
| SQL création et intégration de données | Les scripts 01 et 02 initialisent la base. Le 03 ajoute le catalogue de démonstration. Appliquer la migration de révision selon la procédure du dépôt. |
| Manuel utilisateur PDF avec identifiants de démonstration | À rédiger quand les parcours seront implémentés ; aucun secret réel à publier |
| Charte graphique PDF, palette/polices | `Charte Graphique.pdf` exporté et vérifié. |
| Wireframes et mockups, trois vues ordinateur et trois mobiles | `WireFrames.pdf` vérifié pour les six wireframes. Le PDF couleur contenant les trois vues ordinateur et trois vues mobiles figure dans `docs/`. |
| Documentation gestion de projet | Première version dans les sections 2–3 de ce document |
| Documentation technique : choix et environnement | Première version dans les sections 4–9 |
| MCD ou diagramme de classes | Diagramme conceptuel préparé dans FigJam. Comparaison finale avec le SQL, export PDF et intégration au dépôt à terminer. |
| Diagramme de cas d’utilisation | À produire en cohérence avec les rôles et parcours |
| Diagramme de séquence | À produire sur un parcours représentatif réellement implémenté |
| Documentation de déploiement | Déploiement et transport Brevo décrits ci-dessous. Recette finale et exploitation à compléter. |

### Trame du futur manuel utilisateur

Présenter l’application et les comptes de démonstration ; expliquer consultation et filtres, inscription/connexion/reset, commande, suivi/modification/annulation autorisées, avis, gestion employé, employés et statistiques administrateur. Ajouter des captures de la version finale et un résultat attendu pour chaque parcours. Cette trame n’est pas le manuel PDF demandé.

### README d’installation

Le README à la racine couvre les dépendances, PostgreSQL, les migrations, MongoDB, Mailpit, le serveur et les comptes de démonstration. Compléter l’installation optionnelle de `03_menus_demo.sql` et tester l’ensemble sur un environnement neuf.

<a id="deploiement-render"></a>

### Déploiement Render : PostgreSQL, Atlas et Brevo

Le service web Docker suit `develop` sur Render. L’image contient PHP 8.4, Apache avec `public/` comme racine HTTP, `pdo_pgsql`, `mongodb` 2.5.7 et les dépendances Composer de production. Le service utilise l’URL PostgreSQL interne ; pgAdmin et PHP depuis le PC utilisent l’URL externe. La version PostgreSQL explicite dans `when@prod` a corrigé l’erreur Doctrine de version de plateforme vide. Les valeurs doivent correspondre à l’environnement effectivement ciblé.

1. Pour une nouvelle base uniquement, exécuter `sql/01_creation.sql` puis `sql/02_donnees.sql` (README). Sur une base existante, ne pas rejouer ces fichiers complets. Les horaires ont leur bloc autonome en section 8.18.
2. Renseigner les variables du service de la section 4.3 : `APP_ENV=prod`, `APP_DEBUG=0`, `APP_SECRET`, `DATABASE_URL`, `MONGODB_URI`, `MONGODB_DATABASE`, `MAILER_DSN`, `MAILER_FROM_EMAIL`, `DEFAULT_URI`, ainsi que `ORS_API_KEY` et le réglage de port requis par l’hébergement. Garder toutes les valeurs sensibles hors Git.
3. Pour Atlas, autoriser les adresses de sortie du service Render, vérifier l’utilisateur et ses droits de lecture/écriture sur la base choisie. Pour Brevo, utiliser la clé API dans le DSN HTTPS, valider l’expéditeur/domaine et remplacer l’adresse fictive locale.
4. Dans l’environnement applicatif de la base ciblée, appliquer la migration additive **avant** la première projection et le code qui dépend de la révision :

```powershell
php bin/console doctrine:migrations:migrate --no-interaction --env=prod --no-debug
php bin/console doctrine:migrations:status --env=prod --no-debug
php bin/console cache:clear --env=prod --no-debug
php bin/console lint:container --env=prod --no-debug
```

5. Effectuer l’import initial avec la commande décrite ci-dessous, puis vérifier `/admin/statistiques` et une création/transition de commande. Contrôler un envoi autorisé de chaque type de mail, l’expéditeur, le destinataire et les liens HTTPS ; vérifier également les indésirables. Aucun worker Messenger n’est à démarrer pour le Mailer actuel.

La recette déjà consignée indique qu’Atlas est connecté, que deux commandes ont été importées initialement, que la migration a été appliquée sur PostgreSQL Render et que création/terminaison actualisent les statistiques. La réception du reset et de la notification employé est consignée en section 11.8. Ces observations historiques ne remplacent pas une recette de la dernière version déployée. Relever la disponibilité et l’échéance de la base Render, les sauvegardes et le SHA déployé avant la remise.

### Import et rattrapage MongoDB

La même commande assure l’import initial et le rattrapage après panne ou arrêt entre commit SQL et projection :

```powershell
# Local : avec la configuration PostgreSQL/MongoDB locale.
php bin/console app:stats:sync
# Render : dans l’environnement applicatif configuré pour PostgreSQL Render et Atlas.
php bin/console app:stats:sync --env=prod --no-debug
```

Elle lit PostgreSQL sans le modifier et affiche les nombres de commandes lues, de documents créés, modifiés et inchangés. Code retour **0** en cas de succès, **1** si l’extension manque, si MongoDB est inaccessible/refuse une opération ou si la lecture SQL échoue. Une base SQL sans commande reste un succès si MongoDB est accessible. Un échec peut laisser une projection partielle : corriger la connexion puis relancer ; les garanties de la section 6.5 empêchent doublons et régression de révision. Cette commande ne renvoie **aucun e-mail** et ne rattrape pas les suppressions SQL.

Pour contrôler un import, relancer la commande sans modifier PostgreSQL : le second passage doit compter zéro création et zéro modification, avec le même nombre de documents. Les tests de la section 11.3 couvrent également une modification de statut et le refus d’une écriture ancienne.

<a id="depannage-services"></a>

### Dépannage des statistiques et des e-mails

| Symptôme | Vérifications et action |
|---|---|
| Extension MongoDB absente ou Composer refuse la plateforme | `php --ini`, `php --ri mongodb`, compatibilité DLL PHP/TS/NTS/x64 ; vérifier aussi le PHP web. Pour Docker, reconstruire l’image puis contrôler l’extension et `composer check-platform-reqs --no-dev` (section 11.3). |
| Lecture SQL impossible ou colonne `version_statistiques` absente | Vérifier `DATABASE_URL`, la base ciblée et `doctrine:migrations:status` ; appliquer la migration avec la procédure de déploiement. Ne pas lancer une synchronisation destructrice du schéma. |
| CLI en échec ou page statistiques 503 | Démarrage/santé du Compose local ou URI Atlas, réseau et liste d’accès Atlas, utilisateur/droits, `MONGODB_DATABASE` et extension du PHP servant la page. Les messages applicatifs n’affichent pas l’URI authentifiée. |
| Statistiques en retard après une action réussie | Vérifier le message fixe « Statistiques de commande non actualisées », corriger l’accès MongoDB puis lancer `app:stats:sync`. L’action reste dans PostgreSQL. Un document orphelin après suppression SQL n’est pas purgé par cette commande. |
| Compteur/CA différents de l’attendu | Vérifier l’environnement/base de projection, la date de **création**, le statut `terminee`, les bornes Paris, la remise et l’exclusion de la livraison. Le graphique reste global lorsque le CA est filtré. Ne pas confondre résultat vide 200, filtre invalide 400 et panne 503. |
| Mail local absent | Démarrer Mailpit **avant** l’action ; contrôler le DSN SMTP et l’interface `http://127.0.0.1:8025/`. Redémarrer Mailpit ne rejoue pas un message échoué. |
| Mail Brevo absent ou refusé | Vérifier `MAILER_DSN` de type `brevo+api`, clé **API** et encodage URI, accès HTTPS, `MAILER_FROM_EMAIL` autorisé, domaine et indésirables. Ne pas attendre un worker ; l’envoi est direct. |
| Mauvais expéditeur ou lien local dans un mail | Contrôler `MAILER_FROM_EMAIL`, `DEFAULT_URI` et le contexte HTTP de l’URL publique ; reconstruire le cache prod après changement de configuration. |
| Employé créé mais notification échouée | Conserver le compte, lire l’avertissement et informer l’employé ; ne pas recréer son compte pour réessayer l’envoi. Ne jamais transmettre le mot de passe dans le mail de notification. |

Pour inspecter la configuration sans recopier les valeurs privées dans un compte rendu :

```powershell
php bin/console debug:router app_admin_statistiques
php bin/console debug:config framework mailer --env=prod --no-debug
php bin/console debug:container http_client --env=prod --no-debug
```

Consulter les journaux locaux ou du service Render en masquant les secrets avant partage. La journalisation expurgée de l’actualisation statistique et de la création d’employé ne doit pas être généralisée aux autres services : plusieurs anciens appels mail journalisent l’objet exception. Pour un reset, l’erreur transport peut encore interrompre la requête (section 7.4). Aucun de ces parcours ne fournit une file de réessai persistante.

## 14. Mise à jour et historique

À chaque fonctionnalité validée : mettre à jour son état, décrire la décision et son intérêt, noter les essais réussis/échoués, corriger les écarts, puis actualiser la carte Trello. Les captures et commandes finales doivent correspondre au code livré. Les diagrammes et le dictionnaire doivent évoluer avec le schéma réel.

| Version | Date | Évolution |
|---|---|---|
| 1.4 | 18/11/2026 | Documentation technique des statistiques, e-mails et horaires centralisée : configuration locale/Render, migration, projection/rattrapage, règles du CA, initialisation SQL des horaires, tests et dépannage, relus avec le code actuel. |
| 1.3 | 18/11/2026 | Horaires choisis, affichage local livré et testé selon Codex, regroupement dans 02_donnees.sql demandé et chargement Render en attente. |
| 1.2 | 18/11/2026 | MCD référencé et expliqué, point sur la modération et lot horaires en cours. |
| 1.1 | 15/11/2026 | Interface publique et privée, huit menus illustrés, README, exports graphiques, e-mails Brevo et préparation de la copie Studi. |
| 1.0 | 12/11/2026 | Statistiques MongoDB Atlas, chiffre d’affaires filtrable, synchronisation automatique et migration Render. Charte et six wireframes vérifiés. |
| 0.9 | 11/11/2026 | Déploiement Docker/Render et import PostgreSQL, accueil et boutons, création d’employés, profils de démonstration et validation de deux adresses de livraison. |
| 0.8 | 08/11/2026 | Filtres et pagination de gestion, actions d’équipe après contact, dépôt et modération des avis, accueil public, invitation Mailpit et état des livrables. |
| 0.7 | 29/10/2026 | Détail équipe, suivi et terminaison, prêt/attente/email/restitution et tests, branche gestion, règle LF et navigation ; nettoyage local confirmé ; Trello actualisé |
| 0.6 | 26/10/2026 | Profil et navigation ; rôles et compte employé de test ; liste/acceptation transactionnelle, historique et tests de renvoi/annulation depuis ancienne page ; deux commits/push confirmés, SHA non fournis |
| 0.5 | 25/10/2026 | Modification client, session et empreinte, transaction et historique sans transition, frais selon adresse, correctif Twig, renvoi et refus après acceptation ; commit/push confirmés ; suivi Trello actualisé |
| 0.4 | 25/10/2026 | Parcours adresse/session, remplacement IGN itinéraire par ORS, transaction/idempotence, mail et panne SMTP, liste/détail/historique, annulation et tests ; décisions/limites, guide et trame de copie actualisés |
| 0.2 | 19/10/2026 | Ajout des entités commande/historique, service de prix et essais, formulaire/contrôleur protégés, préremplissage, options minimum/délai et état Git 2671a66 ; limites de validation et enregistrement précisées |
| 0.1 | 18/10/2026 | Relecture des 12 pages de l’énoncé ; synthèse des travaux jusqu’à l’authentification/reset ; état Git, gestion de projet, architecture, sécurité, modèle, recette, exigences et livrables ; prochaines étapes identifiées |

La documentation technique des statistiques, des e-mails et des horaires est désormais regroupée ici. Les versions antérieures de ce tableau restent des repères de recette ; les procédures courantes sont dans les sections thématiques ci-dessus.

## 15. Préparation de la copie à rendre Studi

Ces éléments sont une aide de rédaction à relire et reformuler par Jessy, pas une copie officiellement complétée ni une déclaration de fonctionnalités terminées. Le modèle fourni exige nom/prénom/date de naissance et quatre informations : lien Git, lien de gestion, lien de déploiement, login/mot de passe administrateur de démonstration. Il annonce un rejet si ces éléments manquent. Ne pas publier un mot de passe réel dans le dépôt public ; créer des accès de démonstration adaptés à la remise.

### 15.1 Partie 1, question 1 — Proposition de résumé (200 à 250 mots)

Vite & Gourmand est un projet d’application web pour une entreprise de traiteur située à Bordeaux. Son objectif est de présenter les menus et de permettre aux clients de préparer puis de suivre leurs commandes. Le catalogue propose des filtres de prix, de thème, de régime alimentaire et de nombre de convives. Chaque menu présente ses conditions, son minimum de personnes, ses plats et ses allergènes.

Le projet distingue visiteurs, clients, employés et administrateur. Les clients disposent d’une inscription, d’une connexion et d’une réinitialisation de mot de passe. Ils peuvent commander, consulter leurs commandes, les modifier et les annuler avant acceptation. Les clients peuvent donner leur avis après terminaison. L’équipe traite les commandes après contact et modère les avis avant leur publication. Le gérant peut créer un employé et consulter les statistiques MongoDB. La notification de création a été reçue sur Render. La désactivation reste à compléter.

L’application utilise PHP avec Symfony et Twig. PostgreSQL conserve les données relationnelles, et Doctrine relie les tables aux objets PHP. Les montants sont calculés en centimes côté serveur. Une remise de dix pour cent s’applique lorsque l’effectif atteint le minimum du menu augmenté de cinq personnes. Les frais de livraison hors Bordeaux reposent sur une distance routière calculée par OpenRouteService après confirmation de l’adresse.

L’enregistrement réunit commande, stock et historique dans une transaction. Les contrôles de propriété protègent les commandes personnelles. Les notifications sont testées avec Mailpit. Le travail est suivi dans Trello et versionné avec Git. La suite comprend notamment la base non relationnelle, le déploiement, la recette globale et les livrables finaux.

Recompter les mots du résumé après la dernière adaptation à l’application livrée.

### 15.2 Partie 1, question 2 — Spécifications fonctionnelles

Reprendre les acteurs de la section 2 et la couverture de la section 12 ; exprimer pour chaque fonction son déclencheur, ses entrées, ses règles, ses résultats et ses erreurs. Exemple réalisé : le client choisit un menu disponible, respecte minimum/délai, confirme une adresse et le total ; la validation crée une commande en attente et son historique, retire une disponibilité et tente le mail. L’annulation est réservée au propriétaire en attente ; après acceptation, il contacte l’équipe. Distinguer ces exigences de leurs états de réalisation. Inclure aussi les exigences non encore développées : reste de la gestion, avis, échéance/retards du matériel, NoSQL et déploiement.

### 15.3 Partie 2, questions 1–2 — Technologies et environnement

La section 4 fournit le choix et sa justification, plutôt qu’une liste de noms. Exemples à reformuler : Symfony structure les routes, formulaires et services ; Doctrine fournit le mapping/repositories ; SQL conserve ses contraintes ; Twig permet le rendu serveur et fetch actualise les filtres. Les services de prix, livraison et écriture ont des responsabilités séparées. ORS a remplacé un appel externe défaillant sans modifier la règle tarifaire ; son offre sans activation de facturation répond au choix pédagogique de Jessy. Windows et les outils déjà disponibles facilitent le travail local ; Mailpit isole les essais de messagerie.

Le README versionné détaille les versions et extensions, Composer, PostgreSQL, MongoDB, Mailpit, le serveur et la préparation des comptes. La prochaine vérification consiste à rejouer la procédure depuis un clone vierge.

### 15.4 Partie 2, question 3 — Sécurité effectivement mise en place

Présenter des exemples précis : validation serveur au-delà des attributs HTML ; paramètres Doctrine liés ; échappement Twig ; hachage et contraintes de mot de passe ; CSRF de connexion et formulaires/POST ; ROLE_CLIENT et recherche id+utilisateur ; clé ORS côté serveur ; jetons de session liés au compte et expiration ; jeton SQL UNIQUE ; relecture/verrous et transaction. Montrer les essais à deux comptes et le refus après acceptation depuis un ancien écran. Ne pas affirmer avoir réalisé un audit complet ni tous les tests CSRF/concurrence. Les conditions d’une mise en production sécurisée restent à valider.

### 15.5 Partie 2, question 4 — Veille sur une vulnérabilité

Exemple de source primaire relue le 25 octobre : [avis Symfony CVE-2024-50345](https://symfony.com/blog/cve-2024-50345-open-redirect-via-browser-sanitized-urls), publié le 6 novembre 2024. Une divergence d’interprétation d’URI entre Request et navigateur pouvait permettre une redirection ouverte. L’avis indique HttpFoundation avant 5.4.46, les branches 6 avant 6.4.14 et 7 avant 7.1.7 ; correctifs dans ces trois versions. Notre branche Symfony 7.4 annoncée est hors de ces plages, mais la version exacte du composant et les autres avis doivent être vérifiés. Cette lecture ne prouve pas l’absence de toute vulnérabilité.

Actions à réaliser et à conserver avant remise : relever `composer show symfony/http-foundation`, puis `composer audit --locked`, interpréter chaque résultat et corriger/tester si nécessaire. Aucun résultat de ces deux commandes n’a encore été communiqué pour cette fiche. Utiliser les routes internes nommées évite des redirections arbitraires dans nos actions, sans remplacer les mises à jour. Consulter régulièrement les avis officiels et noter date, composant, impact, décision et vérification. [Commande audit de Composer](https://getcomposer.org/doc/03-cli.md#audit).

### 15.6 Partie 3 — Recherche sur une source anglophone

Situation réellement rencontrée : appeler un géocodeur puis un service d’itinéraire depuis Symfony. La recherche a porté sur l’injection du client HTTP, la conversion JSON, les paramètres et la gestion des erreurs. Source : [Symfony 7.4 — HTTP Client, Basic Usage](https://symfony.com/doc/7.4/http_client.html#basic-usage), relue le 25 octobre. Dans le projet, HttpClientInterface est injectée dans les services ; les scripts PHP autonomes utilisaient HttpClient::create(). L’option json construit le corps POST ORS et toArray lit sa réponse ; les délais limitent l’attente.

Court extrait anglais : « In the Symfony framework, this class is available as the `http_client` service. »

Traduction : « Dans le framework Symfony, cette classe est disponible sous la forme du service `http_client`. »

Expliquer avec son propre code pourquoi un service reçoit HttpClientInterface, comment Autowire injecte la configuration et pourquoi la clé n’est pas envoyée au navigateur. Ne pas recopier une longue page de documentation.

### 15.7 Ressources et informations complémentaires

- [Doctrine : transactions et concurrence](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/transactions-and-concurrency.html).
- [Symfony 7.4 : Mailer](https://symfony.com/doc/7.4/mailer.html), notamment TemplatedEmail et les erreurs de transport.
- [Pilote PHP MongoDB : releases officielles](https://github.com/mongodb/mongo-php-driver/releases) et [mises à jour MongoDB par pipeline](https://www.mongodb.com/docs/manual/tutorial/update-documents-with-aggregation-pipeline/).
- [API transactionnelle Brevo](https://developers.brevo.com/docs/send-a-transactional-email) et [expéditeurs/domaines](https://developers.brevo.com/docs/getting-started-with-senders-and-domains).
- [Render : connexions PostgreSQL externes](https://render.com/docs/postgresql-creating-connecting#external-connections).
- [IGN : géocodage](https://cartes.gouv.fr/aide/fr/guides-utilisateur/utiliser-les-services-de-la-geoplateforme/geocodage/).
- [HeiGIT : changement d’adresse des API](https://ask.openrouteservice.org/t/deprecating-api-openrouteservice-org-in-favour-of-api-heigit-org/7912).
- [ORS : structure des requêtes et réponses](https://giscience.github.io/openrouteservice/v9.10.0/api-reference/endpoints/directions/requests-and-return-types).

Décrire honnêtement l’accompagnement utilisé : recherche documentaire, explications, préparation de code, adaptations et essais manuels réalisés par Jessy. Préparer des captures de la version finale et des comptes de démonstration. La création d’employés, le tableau de bord NoSQL et la mise en ligne ont fait l’objet de vérifications. La recette complète de la version remise reste à consigner.
