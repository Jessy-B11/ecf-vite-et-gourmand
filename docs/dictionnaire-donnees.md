# Dictionnaire de données — Vite & Gourmand

Version de travail 1 — 14 octobre 2026  
Projet ECF — Symfony, PostgreSQL et une base NoSQL à prévoir pour les statistiques.

## 1. Objectif et périmètre

Ce document décrit les données principales, leurs relations et les règles décidées pendant la conception. Il sert de référence pour le schéma SQL, les entités Symfony et les validations côté serveur.

Il couvre le catalogue, les utilisateurs, les commandes, leur historique, les avis et les horaires. Il ne constitue pas encore un schéma SQL exécutable ni la totalité du modèle final : les mécanismes de réinitialisation du mot de passe, les statistiques NoSQL et les traces d'envoi d'emails restent à préciser.

Le texte de l’énoncé fait référence. Les choix ajoutés pour rendre l’application cohérente sont identifiés comme tels.

## 2. Lire les types et les contraintes

| Terme | Signification |
|---|---|
| Clé primaire / PK | Identifie une ligne de manière unique. |
| Clé étrangère / FK | Référence une ligne existante dans une autre table. |
| NOT NULL | La valeur est obligatoire dans la base. |
| NULL autorisé | La valeur peut être absente ; ce n’est pas la même chose qu’une chaîne vide. |
| UNIQUE | Empêche les doublons sur un champ ou un couple de champs. |
| CHECK | Vérifie une condition sur les valeurs d’une ligne. |
| INTEGER | Nombre entier. |
| VARCHAR(n) | Texte limité à n caractères ; ces limites sont des choix de conception. |
| TEXT | Texte de longueur variable, adapté aux descriptions. |
| BOOLEAN | Vrai ou faux. |
| DATE / TIME | Date seule / heure seule. |
| TIMESTAMP WITH TIME ZONE | Un instant, affiché dans le fuseau choisi par l’application. |
| JSON | Donnée structurée, utilisée ici pour la liste des rôles. |

Pour chaque table possédant un identifiant simple, `id` est un entier généré automatiquement, par exemple avec une colonne PostgreSQL d’identité. Les tables de liaison ont une clé primaire composée.

Les montants sont exprimés en **centimes entiers** : 53 400 signifie 534 €. Les calculs de remise et de prix proportionnels doivent prévoir un arrondi explicite au centime ; aucun montant envoyé par le navigateur ne doit être considéré comme fiable.

## 3. Utilisateurs

### Table utilisateur

Une table commune regroupe les clients, employés et administrateurs. Leur séparation repose sur les contrôles d’autorisation côté serveur.

| Champ | Type | Contraintes / rôle |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| nom | VARCHAR(100) | NULL autorisé dans la base ; obligatoire à l’inscription client. |
| prenom | VARCHAR(100) | Même règle que nom. |
| email | VARCHAR(254) | NOT NULL, UNIQUE ; identifiant de connexion. |
| mot_de_passe | VARCHAR(255) | NOT NULL ; contient le hachage, jamais le mot de passe en clair. |
| telephone | VARCHAR(20) | NULL autorisé ; obligatoire pour un client. |
| adresse | VARCHAR(255) | NULL autorisé ; obligatoire pour un client. |
| code_postal | VARCHAR(20) | NULL autorisé ; obligatoire pour un client. |
| ville | VARCHAR(100) | NULL autorisé ; obligatoire pour un client. |
| roles | JSON | NOT NULL ; liste des rôles attribuée côté serveur. |
| actif | BOOLEAN | NOT NULL, défaut true ; permet la désactivation d’un employé. |
| date_creation | TIMESTAMP WITH TIME ZONE | NOT NULL ; instant de création. |

**Explications**
- L’énoncé demande uniquement un email et un mot de passe à la création d’un employé. Les informations de profil peuvent donc être nulles dans la base, mais doivent être exigées pour l’inscription client.
- Les rôles envisagés sont ROLE_CLIENT, ROLE_EMPLOYE et ROLE_ADMIN. Le visiteur est une personne non connectée, sans ligne obligatoire dans cette table.
- Un nom peut être partagé par plusieurs personnes ; l’email de connexion doit identifier un compte unique.
- Nous normaliserons les emails selon une règle commune avant leur enregistrement et leur recherche, afin de gérer les variantes de saisie de façon cohérente.
- Téléphone et code postal sont du texte : conserver les zéros initiaux et accepter une éventuelle notation internationale.
- L’inscription publique attribue uniquement le rôle client. Aucun rôle fourni par le navigateur ne doit être accepté librement.
- L’administrateur est créé hors du formulaire public. Un compte inactif doit être refusé par les contrôles d’accès.
- Le mot de passe choisi doit respecter les exigences de l’énoncé : au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.

## 4. Catalogue

### Table theme

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| libelle | VARCHAR(100) | NOT NULL, UNIQUE. |

Exemples : Noël, Pâques, classique, événement.

### Table regime

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| libelle | VARCHAR(100) | NOT NULL, UNIQUE. |

Exemples : classique, végétarien, vegan. Centraliser ces valeurs évite les variantes qui perturbent les filtres.

### Table menu

| Champ | Type | Contraintes / rôle |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| nom | VARCHAR(150) | NOT NULL ; titre affiché. |
| description | TEXT | NOT NULL ; présentation. |
| minimum_personnes | INTEGER | NOT NULL, CHECK > 0. |
| prix_minimum_centimes | INTEGER | NOT NULL, CHECK > 0 ; prix pour le minimum de personnes. |
| conditions | TEXT | NOT NULL ; délai, conservation et autres précautions. |
| delai_minimum_jours | INTEGER | NOT NULL, CHECK >= 0 ; règle exploitable pour le délai. |
| stock_commandes | INTEGER | NOT NULL, CHECK >= 0. |
| theme_id | INTEGER | NOT NULL, FK vers theme(id). |
| regime_id | INTEGER | NOT NULL, FK vers regime(id). |

**Explications**
- Chaque menu référence un thème et un régime ; chacun peut être partagé par plusieurs menus.
- On ne stocke pas séparément le prix affiché par personne : il découle du prix minimum et du minimum de personnes.
- L’énoncé, page 4, définit le stock en **commandes possibles**, pas en portions. Une commande pour 12 personnes retire une unité de stock.
- Le délai numérique est un ajout de conception. Nous retenons des jours calendaires et un contrôle sur les dates : prestation >= date de commande + délai. Ce choix doit être visible dans les conditions ; il ne signifie pas un délai exact de 72 heures.
- Le prix proportionnel au nombre de personnes est une interprétation de calcul retenue pour le projet.
- Les conditions rédigées et le délai numérique doivent rester cohérents.

### Table plat

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| nom | VARCHAR(150) | NOT NULL. |
| description | TEXT | NULL autorisé. |
| type | VARCHAR(20) | NOT NULL ; CHECK parmi entree, plat_principal, dessert. |

Un plat peut appartenir à plusieurs menus. Son nom n’est pas nécessairement unique : différentes recettes peuvent avoir le même nom.

### Table allergene

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| libelle | VARCHAR(100) | NOT NULL, UNIQUE. |

Les allergènes sont associés aux plats. Les allergènes d’un menu sont obtenus à partir de ses plats, sans enregistrer une deuxième liste indépendante.

### Table menu_plat

| Champ | Type | Contraintes |
|---|---|---|
| menu_id | INTEGER | NOT NULL, FK vers menu(id). |
| plat_id | INTEGER | NOT NULL, FK vers plat(id). |

Clé primaire composée : **(menu_id, plat_id)**. Trois plats pour un menu correspondent à trois lignes. Le même couple ne peut pas être ajouté deux fois.

### Table plat_allergene

| Champ | Type | Contraintes |
|---|---|---|
| plat_id | INTEGER | NOT NULL, FK vers plat(id). |
| allergene_id | INTEGER | NOT NULL, FK vers allergene(id). |

Clé primaire composée : **(plat_id, allergene_id)**. Un plat peut avoir plusieurs allergènes ; un allergène peut concerner plusieurs plats.

### Table image_menu

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| menu_id | INTEGER | NOT NULL, FK vers menu(id). |
| chemin | VARCHAR(255) | NOT NULL ; chemin ou clé du fichier stocké. |
| texte_alternatif | VARCHAR(255) | NOT NULL pour nos photos informatives de plats. |
| position | INTEGER | NOT NULL, CHECK > 0. |

Contrainte UNIQUE sur **(menu_id, position)**. Deux menus peuvent avoir une image en position 1 ; un même menu ne peut pas avoir deux images à cette position.

Les images elles-mêmes sont stockées hors des tables, dans des fichiers ou un service de stockage. Le texte alternatif décrit utilement le contenu ; sa qualité ne peut pas être garantie par une simple contrainte SQL.

## 5. Commandes

### Table commande

| Champ | Type | Contraintes / rôle |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| utilisateur_id | INTEGER | NOT NULL, FK vers utilisateur(id). |
| menu_id | INTEGER | NOT NULL, FK vers menu(id). |
| nombre_personnes | INTEGER | NOT NULL, CHECK > 0. |
| date_creation | TIMESTAMP WITH TIME ZONE | NOT NULL. |
| date_prestation | DATE | NOT NULL. |
| heure_livraison | TIME | NOT NULL ; heure locale souhaitée pour la prestation. |
| adresse_livraison | VARCHAR(255) | NOT NULL ; copie de l’adresse utilisée. |
| code_postal_livraison | VARCHAR(20) | NOT NULL. |
| ville_livraison | VARCHAR(100) | NOT NULL. |
| telephone_contact | VARCHAR(20) | NOT NULL ; copie du numéro utilisé. |
| montant_menu_centimes | INTEGER | NOT NULL, CHECK >= 0 ; avant remise. |
| montant_remise_centimes | INTEGER | NOT NULL, CHECK >= 0 ; défaut 0. |
| frais_livraison_centimes | INTEGER | NOT NULL, CHECK >= 0. |
| montant_total_centimes | INTEGER | NOT NULL, CHECK >= 0. |
| statut | VARCHAR(40) | NOT NULL ; défaut en_attente ; valeurs limitées. |
| materiel_prete | BOOLEAN | NOT NULL ; défaut false. |
| date_retour_materiel | TIMESTAMP WITH TIME ZONE | NULL autorisé jusqu’à restitution. |

**Relations et conservation des informations**
- Une commande appartient à un utilisateur et porte sur un menu.
- Les montants, l’adresse et le téléphone sont enregistrés dans la commande : une modification ultérieure du profil ou du tarif général ne les modifie pas automatiquement.
- Une modification autorisée de la commande peut en revanche changer ces données et recalculer ses montants.
- Les champs précédents ne conservent pas encore une copie de tous les éléments du menu. La conservation du tarif de référence, du minimum applicable et de la composition lors de changements du catalogue reste à préciser avant le SQL final.

**Règles de calcul**
- Nombre demandé >= minimum du menu.
- Seuil de remise : nombre demandé >= minimum + 5. Minimum 6 : remise à partir de 11 ; minimum 4 : à partir de 9.
- Remise de 10 % sur le menu, hors livraison : choix de calcul retenu, à documenter.
- Total = montant menu − remise + frais de livraison.
- La remise ne peut pas dépasser le montant du menu.
- Hors Bordeaux, l’énoncé indique 5 € plus 0,59 € par kilomètre. Nous retenons l’absence de frais à Bordeaux ; l’origine, le calcul et l’arrondi de la distance restent à préciser.
- Tous les calculs et validations sont effectués côté serveur.

**Stock et annulation**
- Une commande enregistrée réserve une unité de stock.
- Vérifier et diminuer le stock dans une transaction, avec protection contre deux réservations concurrentes du dernier exemplaire.
- L’annulation restitue une unité une seule fois : règle de conception retenue, non détaillée par l’énoncé.
- Une commande déjà annulée ne doit pas restituer à nouveau son stock.
- La modification du nombre de personnes ne change pas le nombre d’unités de stock réservé.

**Statuts envisagés**

| Valeur technique | Sens |
|---|---|
| en_attente | Commande reçue, pas encore acceptée. |
| acceptee | Validée par l’équipe. |
| en_preparation | Préparation en cuisine. |
| en_livraison | Livraison en cours. |
| livree | Livraison effectuée. |
| attente_retour_materiel | Matériel à restituer. |
| terminee | Livraison sans prêt ou matériel restitué. |
| annulee | Commande annulée. |

Les transitions autorisées seront définies séparément ; autoriser une valeur ne signifie pas autoriser tout passage entre deux statuts.

**Droits et matériel**
- Le client consulte uniquement ses commandes. Il peut modifier ou annuler avant acceptation ; le choix du menu reste non modifiable.
- L’équipe doit contacter le client avant de modifier ou annuler la commande. Une annulation nécessite aussi son motif.
- Sans matériel prêté, une commande livrée peut être terminée. Avec prêt, elle attend la restitution.
- Le passage en attente de retour déclenche l’email prévu par l’énoncé, avec le délai de 10 jours ouvrés et les frais de 600 € annoncés. Le calendrier de calcul et l’éventuel traitement des frais restent à préciser ; ce document n’autorise pas un prélèvement automatique.

### Table historique_commande

| Champ | Type | Contraintes / rôle |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| commande_id | INTEGER | NOT NULL, FK vers commande(id). |
| statut | VARCHAR(40) | NOT NULL ; mêmes valeurs autorisées que commande.statut. |
| date_changement | TIMESTAMP WITH TIME ZONE | NOT NULL. |
| auteur_id | INTEGER | FK vers utilisateur(id), NULL autorisé pour une action automatique. |
| mode_contact | VARCHAR(20) | NULL autorisé ; telephone ou email si renseigné. |
| motif | TEXT | NULL autorisé ; requis pour l’annulation par l’équipe. |

Chaque ligne appartient à une commande. Une commande possède plusieurs lignes, dont son état initial. Le changement de statut et l’ajout à l’historique doivent être atomiques.

Le mode de contact est exigé pour les modifications et annulations par l’équipe. Ces obligations dépendent de l’action et de l’auteur : Symfony les vérifie. Les champs restent nullables pour les événements où ils ne s’appliquent pas.

**Limite à traiter :** cette table décrit les changements de statut. Une modification d’adresse ou de date sans changement de statut doit aussi laisser une trace ; un type d’événement et le détail des changements pourront compléter ce modèle.

## 6. Avis

### Table avis

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| commande_id | INTEGER | NOT NULL, FK vers commande(id), UNIQUE. |
| note | INTEGER | NOT NULL, CHECK entre 1 et 5. |
| commentaire | TEXT | NOT NULL ; contenu non vide exigé par le serveur. |
| statut_moderation | VARCHAR(20) | NOT NULL ; en_attente, valide ou refuse ; défaut en_attente. |
| date_creation | TIMESTAMP WITH TIME ZONE | NOT NULL. |

Choix retenu : une commande possède zéro ou un avis. La clé étrangère garantit que la commande existe ; UNIQUE empêche deux avis pour cette commande, même lors de requêtes concurrentes.

Le serveur vérifie que la commande appartient au client et qu’elle est terminée. Seuls les avis validés sont publiés sur l’accueil. Le client est retrouvé via la commande, sans recopier son identifiant dans avis.

## 7. Horaires

### Table horaire_jour

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| jour_semaine | INTEGER | NOT NULL, UNIQUE, CHECK de 1 à 7 ; lundi = 1. |
| ferme | BOOLEAN | NOT NULL. |

Prévoir sept lignes pour représenter toute la semaine, y compris les jours fermés. UNIQUE empêche les doublons mais ne garantit pas à lui seul que les sept lignes existent.

### Table creneau_horaire

| Champ | Type | Contraintes |
|---|---|---|
| id | INTEGER | PK, générée automatiquement. |
| horaire_jour_id | INTEGER | NOT NULL, FK vers horaire_jour(id). |
| heure_ouverture | TIME | NOT NULL. |
| heure_fermeture | TIME | NOT NULL, après l’ouverture. |

Un jour peut avoir plusieurs créneaux, par exemple 9 h–12 h et 14 h–19 h. Un jour fermé n’en possède aucun. Un jour ouvert doit en avoir au moins un. Le serveur vérifie cette cohérence et l’absence de chevauchement.

Choix simplificateur : les créneaux ne traversent pas minuit.

## 8. Résumé des relations

| Relation | Cardinalité retenue |
|---|---|
| Utilisateur → commande | Un utilisateur : zéro à plusieurs commandes ; une commande : un utilisateur. |
| Menu → commande | Un menu : zéro à plusieurs commandes ; une commande : un menu. |
| Thème / régime → menu | Un thème ou régime : plusieurs menus possibles ; un menu : un de chaque. |
| Menu ↔ plat | Plusieurs de chaque côté, via menu_plat. |
| Plat ↔ allergène | Plusieurs de chaque côté, via plat_allergene. |
| Menu → image | Plusieurs images possibles ; une image : un menu. |
| Commande → historique | Au moins un événement initial ; chaque ligne : une commande. |
| Commande → avis | Zéro ou un avis ; un avis : une commande. |
| Jour → créneau | Zéro à plusieurs créneaux ; un créneau : un jour. |

## 9. Ce que la base et Symfony vérifient

**Base de données :** existence des références, absence de doublons, valeurs obligatoires, bornes numériques et contraintes locales. Une valeur NOT NULL peut toujours être un texte vide : les contrôles de contenu restent nécessaires.

**Symfony :** authentification, rôles, propriété des commandes, validations selon le type de compte, minimum du menu, délais, calcul des montants, transitions de statut, contact préalable, cohérence des horaires et déclenchement des emails.

**Transactions :** réservation de stock et commande ; annulation et restitution du stock ; statut et historique. Toutes les étapes liées doivent réussir ensemble ou être annulées ensemble.

## 10. Décisions à terminer avant l’implémentation finale

1. Préserver les commandes historiques lorsqu’un menu change : données de référence copiées ou versionnement.
2. Définir les suppressions : ne pas supprimer en cascade les commandes lorsqu’un menu est retiré. Prévoir une désactivation ou un archivage du catalogue référencé.
3. Compléter la trace des modifications sans changement de statut.
4. Fixer les arrondis monétaires, la distance de livraison et le calendrier des jours ouvrés.
5. Décrire les tokens de réinitialisation du mot de passe et leur expiration.
6. Définir les données des statistiques NoSQL et leur synchronisation avec les commandes SQL, qui restent la référence opérationnelle.
7. Définir la gestion des emails et des échecs d’envoi ; l’échec d’un email ne doit pas créer une seconde commande.
8. Décider si le formulaire de contact nécessite une conservation en base : l’énoncé impose son envoi par email, sans imposer une table.
9. Les suggestions vin et sans alcool sont un ajout éditorial choisi pour les maquettes. Leur stockage dans le menu reste à définir ; aucune boisson n’entre dans la commande.
10. Comparer le schéma final au MCD fourni en annexe, puis écrire les fichiers SQL de création et de données demandés, en complément des migrations.

Ce document est une base expliquée à relire et à faire évoluer : il doit refléter les règles réellement implémentées.

