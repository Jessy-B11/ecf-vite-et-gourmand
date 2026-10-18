# Vite & Gourmand — Documentation du projet

**Auteur : Jessy Boehm**  
**ECF : TP Développeur Web et Web Mobile — Studi**  
**Version : 0.1 — 18 octobre 2026**  
**Remise annoncée : 19 novembre 2026**

> Document de travail évolutif. Les sections « Réalisé » reposent sur les extraits de code et les essais partagés pendant le développement. Le dépôt complet n’a pas fait l’objet d’un audit indépendant. Une fonctionnalité prévue ou représentée dans une maquette n’est pas présentée comme développée. Les versions indiquées sont celles de l’environnement utilisé, sans affirmation sur les dernières versions disponibles.

## 1. Objet et sources

Ce document commence les deux documentations demandées pages 11–12 de l’énoncé : la gestion de projet et la documentation technique. Elles sont regroupées ici pour faciliter les mises à jour ; elles pourront être séparées avant la remise.

Sources : énoncé « enoncer ECF.pdf », 12 pages ; dictionnaire de données V1 du 14 octobre ; scripts et extraits PHP/Twig/JS communiqués ; résultats des essais locaux ; état des branches confirmé le 17 octobre ; tableau Trello consulté le 18 octobre. Le dictionnaire V1 précède l’ajout du mécanisme de réinitialisation et devra être actualisé.

Ce document ne remplace ni la copie à rendre Studi, ni le README d’installation, ni le manuel utilisateur PDF, ni les exports graphiques.

| Ressource | Lien ou emplacement prévu |
|---|---|
| Dépôt GitHub | https://github.com/Jessy-B11/ecf-vite-et-gourmand |
| Gestion de projet | https://trello.com/b/zi6AeD7k/projet-vite-gourmand |
| Figma | https://www.figma.com/design/t3Ibv9fml9omqR1O9QEzZq/Sans-titre |
| Documentation évolutive | docs/documentation-projet.md |
| Dictionnaire | docs/dictionnaire-donnees.md |
| Création et données SQL | sql/01_creation.sql ; sql/02_donnees.sql |
| Application publique | À déployer ; aucune URL de production confirmée |

## 2. Présentation et objectifs

Vite & Gourmand est une entreprise bordelaise de Julie et José, active depuis 25 ans. Le projet doit rendre ses menus accessibles au public et permettre aux clients de commander. Les équipes doivent gérer le catalogue, les commandes, les horaires et les avis. L’administrateur dispose en plus de la gestion des employés et de statistiques.

Le périmètre comprend une base relationnelle et une base non relationnelle, toutes deux obligatoires. L’application doit être déployée et accessible conformément aux exigences du sujet. Le choix de Symfony, de PostgreSQL ou d’un hébergeur particulier n’est pas imposé par l’énoncé.

### Acteurs et droits attendus

| Acteur | Fonctions attendues | État au 18 octobre |
|---|---|---|
| Visiteur | Consulter accueil/menus/détails, filtrer, contacter, créer un compte | Catalogue et filtres réalisés ; accueil/contact à développer |
| Client connecté | Commander, consulter ses commandes, modifier son profil, modifier/annuler avant acceptation, suivre et donner un avis | Comptes réalisés ; espace client et commandes à développer |
| Employé | Gérer menus/plats/horaires, suivre et gérer commandes après contact, modérer avis | Interfaces et autorisations à développer |
| Administrateur | Droits employé, créer/désactiver employés, statistiques | À développer ; création admin hors inscription publique |

Une table utilisateur commune est retenue. La séparation des droits doit être effectuée côté serveur : avoir des tables séparées ne garantirait pas la sécurité. La propriété des commandes doit être contrôlée en complément du rôle.

## 3. Gestion de projet

### 3.1 Méthode de travail

Le travail avance par fonctionnalités, avec une première conception, une implémentation limitée, un contrôle local puis un commit. L’accompagnement est progressif : le développeur réalise les manipulations dans son environnement et vérifie les résultats. Les décisions doivent pouvoir être expliquées à l’évaluation, y compris lorsque des outils d’assistance ont aidé à préparer du code ou des documents.

Trello sert de tableau Kanban : Backlog, À faire, En cours, À vérifier et Terminé. Les cartes contiennent l’objectif, le travail déjà réalisé, les critères de réussite et le reste à faire. Les cartes séparatrices ne représentent pas des fonctionnalités. Certaines cartes concernent le traitement serveur et d’autres son écran ; elles ne doivent pas être comptées comme autant de fonctionnalités indépendantes.

« Terminé » signifie que le périmètre local décrit a été testé, et non que tout le domaine a été audité. La carte de sécurité, les tests globaux et la validation en production restent distincts. La création d’une table ne suffit pas à terminer la fonctionnalité qui l’utilise.

### 3.2 Git et organisation des branches

Le sujet demande une branche principale, une branche de développement et des branches de fonctionnalité issues de develop. Après validation, une fonctionnalité rejoint develop ; main recevra une version testée.

État confirmé le 17 octobre 2026 :

| Branche | Dernier commit confirmé | Situation |
|---|---|---|
| main | 1110f34 | Initialisation Symfony ; intégration finale non faite |
| develop | 7029f37 | Travail réalisé jusqu’à la réinitialisation du mot de passe |
| feature/base-de-donnees | 7029f37 | Branche historique ayant aussi accueilli catalogue et comptes |
| feature/commandes | 7029f37 | Nouvelle branche active, créée depuis develop et poussée |

L’ancienne branche regroupait plusieurs fonctionnalités. Son contenu a été intégré à develop sans perte d’historique. Pour la suite, les branches seront plus ciblées. Les identifiants et secrets locaux restent hors Git, notamment dans .env.local. La visibilité publique du dépôt devra être vérifiée avant remise.

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
| Symfony Mailer + Mailpit local | Tester les messages sans les envoyer aux destinataires réels | Un transport mail de production reste à choisir et tester |
| SymfonyCasts ResetPasswordBundle | Parcours de jeton de réinitialisation avec expiration et usage unique | Configuration et tests des cas d’erreur à compléter |
| MongoDB envisagé | Séparer les statistiques non relationnelles demandées du stockage opérationnel SQL | Choix non encore implémenté ; structure et synchronisation à définir |

Ces justifications expliquent les choix actuels du projet ; elles ne constituent pas un classement général des technologies.

### 4.2 Poste de développement

Windows, VS Code, PowerShell intégré ; projet dans `C:\Env\Workspace\ECF\App ECF`.

| Composant | Configuration constatée |
|---|---|
| PHP | 8.4.15, C:\Env\Wamp\bin\php\php8.4.15 |
| Configuration PHP CLI | C:\Env\Wamp\bin\php\php8.4.15\php.ini |
| Extension PostgreSQL | pdo_pgsql activée |
| Symfony | 7.4.20 |
| PostgreSQL | 18.3, localhost:5432 |
| Base de travail | vite_et_gourmand |
| Base de reconstruction antérieure | vite_et_gourmand_test_sql |
| Gestion des dépendances | Composer et composer.lock versionné |
| Serveur local | PHP intégré, 127.0.0.1:8000, racine public/ |
| Capture des mails | Mailpit : SMTP 127.0.0.1:1025, interface 127.0.0.1:8025 |

Le port de l’interface pgAdmin est distinct du port PostgreSQL. Le serveur PHP intégré sert au développement local ; l’hébergement de production reste à configurer.

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

Variables locales : DATABASE_URL vers PostgreSQL et MAILER_DSN vers Mailpit. Aucun mot de passe, jeton de réinitialisation ou secret réel ne doit être copié dans ce document. La procédure complète de clonage, configuration et exécution des scripts SQL doit encore être validée et intégrée au README.

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
| src/Form/ | Formulaires d’inscription et réinitialisation |
| src/Security/UtilisateurChecker.php | Refus d’une connexion pour compte inactif |
| src/Service/EmailBienvenue.php | Message de bienvenue |
| templates/ | Pages Twig, gabarit commun et contenu du mail de reset |
| public/css/style.css | Styles communs et cartes/formulaires |
| public/js/menu-filters.js | Soumission/réinitialisation dynamique des filtres |
| public/images/menus/ | Illustration de démonstration |
| config/packages/security.yaml | Provider, hachage, firewall et connexion/déconnexion |

Les futurs calculs et transitions des commandes devront être isolés dans des services métier plutôt que dispersés dans les templates. Ce découpage n’est pas encore implémenté.

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

Le détail des champs et contraintes figure dans le dictionnaire V1. Le diagramme MCD ou de classes final, demandé par le sujet, reste à produire ; cette table ne le remplace pas. L’annexe graphique doit aussi être comparée au modèle final.

### 6.2 Mappings et précautions

Les six entités catalogue Theme, Regime, Allergene, Plat, Menu et ImageMenu sont mappées et lues avec leurs relations. Utilisateur et ResetPasswordRequest ont été ajoutées pour les comptes. Les autres tables métier ne sont pas encore entièrement mappées ; ne pas lancer de synchronisation destructrice fondée sur un mapping incomplet.

Les noms de colonnes explicitent notamment minimum_personnes, prix_minimum_centimes, theme_id, regime_id, texte_alternatif et mot_de_passe. Les dates utilisateur ont été ajustées à une précision de secondes après une erreur de lecture liée aux fractions de seconde. Le trait de reset utilise des dates sans fuseau ; les colonnes proposées pour cette table sont requested_at et expires_at en TIMESTAMP(0) WITHOUT TIME ZONE. Vérifier la cohérence de chaque type lors de la reconstruction.

### 6.3 Décisions encore nécessaires

- Conserver le tarif de référence, le minimum et la composition commandés si le catalogue change ; archiver un menu utilisé plutôt que détruire son historique.
- Tracer les modifications de commande sans changement de statut.
- Définir distance de livraison, arrondis et calendrier des jours ouvrés.
- Prévoir les transactions du stock, de l’annulation et de l’historique.
- Définir les documents NoSQL et leur synchronisation ; SQL reste la référence des commandes.
- Actualiser le dictionnaire pour les comptes/reset et les décisions finales.

## 7. Fonctionnalités réalisées

### 7.1 Catalogue et détail

La route GET /menus, nommée app_menu, affiche les données lues depuis PostgreSQL : nom, description, minimum de personnes, prix correspondant, prix indicatif par personne, thème, régime, stock et lien de détail. /menus/{id}, app_menu_show, présente les informations du menu, plats/allergènes et image ; un identifiant absent renvoie une 404.

La galerie de démonstration affiche poulet-roti.png. L’illustration a été générée par IA ; elle doit être identifiée comme telle et ne prouve pas l’existence d’une photo de prestation réelle. Les conditions et délais doivent encore être contrôlés visuellement et le bouton de commande ajouté.

### 7.2 Filtres dynamiques

Les paramètres GET prix_min, prix_max, theme, regime et nombre_personnes sont traités côté serveur. Le prix filtré est celui du menu pour son minimum de personnes, pas le prix par personne. L’effectif filtre les menus dont le minimum est inférieur ou égal au nombre de convives prévu ; c’est l’interprétation fonctionnelle retenue et libellée dans l’interface.

Les montants sont convertis depuis une chaîne décimale en centimes entiers. Le repository combine les conditions avec paramètres liés, puis trie par prix et identifiant. Une fourchette inversée affiche une erreur dans la page ; certains paramètres malformés peuvent encore provoquer une réponse 400 à améliorer.

Le JavaScript intercepte la soumission et la réinitialisation, appelle fetch, extrait le bloc des résultats, met à jour l’URL et annonce le résultat. Le catalogue s’actualise sans rechargement complet. Un état de chargement évite les soumissions simultanées. Le formulaire conserve son fonctionnement GET sans JavaScript.

### 7.3 Comptes et connexion

L’inscription collecte nom, prénom, téléphone, email et adresse. Les validations du formulaire imposent les champs requis et les critères du mot de passe : au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial. Le mot de passe est haché avant enregistrement dans mot_de_passe. Le rôle ROLE_CLIENT est attribué côté serveur et le compte est actif ; ROLE_USER est ajouté par l’entité.

L’email est unique dans la base et contrôlé par UniqueEntity pour donner un message compréhensible. La connexion utilise email/mot de passe, form_login et un jeton CSRF. Le compte inactif est refusé par un user checker. La déconnexion est prise en charge par le firewall. La révocation d’une session déjà ouverte lors d’une désactivation reste à vérifier.

### 7.4 Emails et réinitialisation

Après une inscription valide, EmailBienvenue envoie le message ; une erreur de transport est journalisée sans annuler le compte créé. En local, Mailpit capture les messages. L’adresse d’expéditeur en .example est une donnée de démonstration à remplacer pour la production.

Le reset comporte une demande par email, une confirmation générique, un lien avec jeton, une saisie répétée du nouveau mot de passe puis un retour vers la connexion. Le jeton est stocké en session après ouverture pour être retiré de l’URL. Le mot de passe est haché et la demande de reset supprimée lors de la validation. Les quatre templates request/check_email/email/reset sont traduits ; vérifier la langue globale des messages de durée.

Réception du mail, changement, connexion avec le nouveau mot de passe et refus du lien déjà utilisé ont été confirmés. L’ancien mot de passe n’a pas été retesté car il avait été oublié. L’expiration effective, les erreurs SMTP et la réponse pour une adresse inconnue restent à contrôler.

## 8. Commandes et règles à implémenter

Cette section décrit la cible, pas une fonctionnalité déjà livrée.

| Règle | Référence / décision et état |
|---|---|
| Menu choisi et compte | Bouton depuis le détail ; connexion/inscription exigée pour un visiteur ; menu prérempli — pages 5–7 |
| Coordonnées et prestation | Nom/prénom/email/GSM préremplis ; adresse, date et heure demandées — page 7 |
| Minimum | Quantité au moins égale au minimum du menu ; contrôle serveur |
| Délai | Respecter les conditions ; délai numérique en jours calendaires retenu pour chaque menu, pas une règle universelle de trois jours |
| Prix | Calcul proportionnel aux convives, en centimes, arrondi défini ; interprétation retenue à expliciter |
| Remise | Dès minimum +5 personnes, 10 % ; exemple minimum 4 : remise dès 9 — page 7. Hors livraison : choix retenu |
| Livraison | Hors Bordeaux : 5 € +0,59 €/km — page 7. Gratuité Bordeaux retenue ; origine et méthode de distance à définir |
| Stock | Nombre de commandes possibles — page 4. Une réservation consomme une unité quel que soit le nombre de convives |
| Validation | Récapitulatif menu/remise/livraison/total avant confirmation ; calcul autoritatif côté serveur |
| Transaction | Création commande + réservation stock + état initial/historique atomiques ; protéger le dernier stock contre la concurrence |
| Client | Consulter uniquement ses commandes ; modifier/annuler avant acceptation ; menu non modifiable — page 7 |
| Équipe | Contact préalable avant modification/annulation ; mode de contact et motif d’annulation tracés — page 8 |
| Annulation | Restituer le stock une seule fois ; décision de conception à tester |
| Suivi | États datés/horodatés ; transitions autorisées à définir et contrôler |
| Matériel | Email au passage en attente de retour : délai de 10 jours ouvrés et frais de 600 € annoncés — page 8 ; aucune automatisation de prélèvement décidée |
| Avis | Commande terminée et appartenant au client, note 1–5/commentaire, un avis maximum ; publication après validation |

Les emails de confirmation de commande, de retour matériel et d’invitation à déposer un avis sont encore à développer. Le traitement d’un échec d’email devra éviter toute création de commande en double.

## 9. Sécurité, données personnelles et accessibilité

### 9.1 Mesures mises en place

Paramètres de requête liés par Doctrine ; contraintes SQL ; validation serveur des formulaires et filtres ; échappement de sortie Twig dans les rendus ordinaires ; mots de passe hachés ; CSRF connexion et formulaires Symfony ; unicité email ; refus des comptes inactifs à la connexion ; lien reset déjà utilisé refusé ; configuration sensible locale exclue de Git.

Ces mesures ne constituent pas un audit de sécurité. Il reste notamment les contrôles d’autorisation/propriété, les tentatives répétées, la normalisation des emails, la cohérence entre limites des mots de passe et hacheur, les erreurs mail, les uploads de gestion, les secrets de production, HTTPS/cookies et les logs sans données sensibles. Les durées de conservation, informations RGPD, mentions légales et CGV sont à formaliser.

### 9.2 Accessibilité et interface

Déjà travaillés : langue française, viewport, titres, labels associés, focus visible, texte alternatif, essais initiaux à 320 px, messages d’erreur et annonces des filtres dynamiques. Aucun audit RGAA complet n’a été réalisé ; aucune conformité globale n’est revendiquée.

À vérifier : clavier et ordre du focus sur tous les parcours, contrastes de la charte, erreurs associées aux champs, annonces de chargement/résultats, zoom et redistribution, images, boutons et formulaires. Consigner les critères testés et les corrections plutôt que déclarer une conformité sans preuve.

## 10. Conception graphique

Trois écrans de référence, accueil/catalogue/commande, ont été conçus en ordinateur et mobile : wireframes Figma et six prototypes HTML. La page d’accueil a été raccourcie. Le logo et une charte V1 sont préparés. Les exports PDF sont différés et restent à produire.

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

| Test | Résultat au 18 octobre | Limite / suite |
|---|---|---|
| PHP/Doctrine accèdent à la bonne base et aux thèmes | Confirmé | Maintenir la configuration locale |
| Reconstruction SQL dans une base de test | Confirmée sur une version antérieure | Refaire après image, second menu, ajustement date et reset |
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
| Commandes, stock concurrent, droits, avis, gestion | Non testables à ce stade | Développer puis recette négative et positive |
| NoSQL, production, accessibilité globale | Non validés | Intégration, déploiement et recette nécessaires |

Aucune suite complète de tests automatisés n’est livrée à ce stade. Prévoir des tests utiles sur calculs, seuils, transitions, propriété et concurrence ; les compléter avec les parcours manuels et leurs résultats datés.

## 12. Couverture de l’énoncé

| Pages | Attendu | Situation |
|---|---|---|
| 3 | Accueil : entreprise, professionnalisme, avis validés | Maquettes préparées ; page réelle à développer |
| 4 | Navigation et footer, sept jours, légal/CGV | Structure partielle ; contenu et pages à compléter |
| 4–6 | Catalogue, détail, cinq filtres dynamiques, commande | Lecture/filtres réalisés ; commande et finition à faire |
| 5 | Inscription, critères du mot de passe et bienvenue | Parcours local réalisé/testé |
| 5 | Connexion et lien de réinitialisation | Parcours local réalisé/testé, recette complémentaire ouverte |
| 7 | Commande, prix, remise, livraison et confirmation | À développer |
| 7 | Profil, commandes personnelles, modifications et avis | À développer |
| 8 | Gestion employé, statuts, matériel, modération | À développer |
| 9 | Administration, employés, statistiques NoSQL/CA | À développer |
| 9 | Contact par email, mise en ligne, accessibilité | À développer/valider ; premiers éléments d’accessibilité en place |
| 10 | Base SQL et base NoSQL | SQL en place ; NoSQL à réaliser |
| 11–12 | Dépôt, documents, schémas, exports et liens | Partiellement préparés ; voir checklist suivante |

## 13. Checklist des livrables

| Livrable demandé | État / action restante |
|---|---|
| Copie Studi renommée ECF_TPDeveloppeurWebEtWebMobile_copiearendre_NOM_Prenom | À compléter dans le modèle fourni ; justifier choix et sécurité |
| Lien GitHub PUBLIC | Lien connu ; visibilité publique à contrôler |
| Liens application(s) déployée(s) | À produire ; exigence bloquante de livraison |
| Lien gestion de projet | Trello disponible et actualisé |
| Bonnes pratiques Git | main/develop et branche commandes en place ; intégrations futures après validation |
| README installation locale | À rédiger/valider depuis une installation vierge |
| SQL création et intégration de données | Fichiers présents ; mise à jour et reconstruction finale à vérifier |
| Manuel utilisateur PDF avec identifiants de démonstration | À rédiger quand les parcours seront implémentés ; aucun secret réel à publier |
| Charte graphique PDF, palette/polices | Conception V1 préparée ; export à faire |
| Wireframes et mockups, trois vues ordinateur et trois mobiles | Conceptions préparées ; exports et contrôle final à faire |
| Documentation gestion de projet | Première version dans les sections 2–3 de ce document |
| Documentation technique : choix et environnement | Première version dans les sections 4–9 |
| MCD ou diagramme de classes | Relations décrites ; diagramme à formaliser |
| Diagramme de cas d’utilisation | À produire en cohérence avec les rôles et parcours |
| Diagramme de séquence | À produire sur un parcours représentatif réellement implémenté |
| Documentation de déploiement | Hébergement non choisi ; procédure et vérification à rédiger |

### Trame du futur manuel utilisateur

Présenter l’application et les comptes de démonstration ; expliquer consultation et filtres, inscription/connexion/reset, commande, suivi/modification/annulation autorisées, avis, gestion employé, employés et statistiques administrateur. Ajouter des captures de la version finale et un résultat attendu pour chaque parcours. Cette trame n’est pas le manuel PDF demandé.

### Trame du futur README

Prérequis et versions ; clonage ; composer install ; création des bases et rôle SQL ; exécution des deux fichiers SQL dans l’ordre ; variables d’environnement d’exemple sans secrets ; service mail local ; lancement ; accès/tests de démonstration ; NoSQL ; dépannage courant. Vérifier l’ensemble sur un environnement neuf avant de le présenter comme reproductible.

### Déploiement : éléments à renseigner

Décision d’hébergeur et justification ; services PHP/SQL/NoSQL ; document root public/ ; variables et secrets ; installation des dépendances ; création/évolution des données sans perte ; cache/assets ; domaine et HTTPS ; emails ; stockage persistant ; sauvegarde/restauration ; supervision ; recette après déploiement ; procédure de correction et retour arrière. Aucun de ces éléments n’est annoncé comme déployé dans cette V0.1.

## 14. Mise à jour et historique

À chaque fonctionnalité validée : mettre à jour son état, décrire la décision et son intérêt, noter les essais réussis/échoués, corriger les écarts, puis actualiser la carte Trello. Les captures et commandes finales doivent correspondre au code livré. Les diagrammes et le dictionnaire doivent évoluer avec le schéma réel.

| Version | Date | Évolution |
|---|---|---|
| 0.1 | 18/10/2026 | Relecture des 12 pages de l’énoncé ; synthèse des travaux jusqu’à l’authentification/reset ; état Git, gestion de projet, architecture, sécurité, modèle, recette, exigences et livrables ; prochaines étapes identifiées |

Prochaine mise à jour prévue après la première commande fonctionnelle : champs réels, service de calcul, réservation du stock, historique, droits et essais. À terme, séparer/exporter les documents si cela facilite la remise, sans laisser plusieurs versions contradictoires.
