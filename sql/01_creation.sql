CREATE TABLE theme (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE regime (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE allergene (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE plat (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    type VARCHAR(20) NOT NULL
        CHECK (type IN ('entree', 'plat_principal', 'dessert'))
);

CREATE TABLE plat_allergene (
    plat_id INTEGER NOT NULL,
    allergene_id INTEGER NOT NULL,

    PRIMARY KEY (plat_id, allergene_id),

    FOREIGN KEY (plat_id) REFERENCES plat(id),
    FOREIGN KEY (allergene_id) REFERENCES allergene(id)
);

CREATE TABLE menu (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,

    minimum_personnes INTEGER NOT NULL
        CHECK (minimum_personnes > 0),

    prix_minimum_centimes INTEGER NOT NULL
        CHECK (prix_minimum_centimes > 0),

    conditions TEXT NOT NULL,

    delai_minimum_jours INTEGER NOT NULL
        CHECK (delai_minimum_jours >= 0),

    stock_commandes INTEGER NOT NULL
        CHECK (stock_commandes >= 0),

    theme_id INTEGER NOT NULL REFERENCES theme(id),
    regime_id INTEGER NOT NULL REFERENCES regime(id)
);

CREATE TABLE menu_plat (
    menu_id INTEGER NOT NULL REFERENCES menu(id),
    plat_id INTEGER NOT NULL REFERENCES plat(id),

    PRIMARY KEY (menu_id, plat_id)
);

CREATE TABLE image_menu (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    menu_id INTEGER NOT NULL REFERENCES menu(id),
    chemin VARCHAR(255) NOT NULL,
    texte_alternatif VARCHAR(255) NOT NULL,
    position INTEGER NOT NULL CHECK (position > 0),

    UNIQUE (menu_id, position)
);

CREATE TABLE utilisateur (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(254) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse VARCHAR(255),
    code_postal VARCHAR(20),
    ville VARCHAR(100),
    roles JSON NOT NULL DEFAULT '["ROLE_CLIENT"]',
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_creation TIMESTAMP(0) WITH TIME ZONE NOT NULL
        DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE commande (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    utilisateur_id INTEGER NOT NULL REFERENCES utilisateur(id),
    menu_id INTEGER NOT NULL REFERENCES menu(id),

    nombre_personnes INTEGER NOT NULL
        CHECK (nombre_personnes > 0),

    date_creation TIMESTAMP WITH TIME ZONE NOT NULL
        DEFAULT CURRENT_TIMESTAMP,
    date_prestation DATE NOT NULL,
    heure_livraison TIME NOT NULL,

    adresse_livraison VARCHAR(255) NOT NULL,
    code_postal_livraison VARCHAR(20) NOT NULL,
    ville_livraison VARCHAR(100) NOT NULL,
    telephone_contact VARCHAR(20) NOT NULL,

    montant_menu_centimes INTEGER NOT NULL
        CHECK (montant_menu_centimes >= 0),
    montant_remise_centimes INTEGER NOT NULL DEFAULT 0
        CHECK (montant_remise_centimes >= 0),
    frais_livraison_centimes INTEGER NOT NULL
        CHECK (frais_livraison_centimes >= 0),
    montant_total_centimes INTEGER NOT NULL
        CHECK (montant_total_centimes >= 0),

    statut VARCHAR(40) NOT NULL DEFAULT 'en_attente'
        CHECK (statut IN (
            'en_attente',
            'acceptee',
            'en_preparation',
            'en_livraison',
            'livree',
            'attente_retour_materiel',
            'terminee',
            'annulee'
        )),

    materiel_prete BOOLEAN NOT NULL DEFAULT FALSE,
    date_retour_materiel TIMESTAMP WITH TIME ZONE,

    CHECK (montant_remise_centimes <= montant_menu_centimes),

    CHECK (
        montant_total_centimes =
        montant_menu_centimes
        - montant_remise_centimes
        + frais_livraison_centimes
    )
);

CREATE TABLE historique_commande (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    commande_id INTEGER NOT NULL REFERENCES commande(id),

    statut VARCHAR(40) NOT NULL
        CHECK (statut IN (
            'en_attente',
            'acceptee',
            'en_preparation',
            'en_livraison',
            'livree',
            'attente_retour_materiel',
            'terminee',
            'annulee'
        )),

    date_changement TIMESTAMP WITH TIME ZONE NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    auteur_id INTEGER REFERENCES utilisateur(id),

    mode_contact VARCHAR(20)
        CHECK (mode_contact IN ('telephone', 'email')),

    motif TEXT
);

CREATE TABLE avis (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    commande_id INTEGER NOT NULL UNIQUE REFERENCES commande(id),

    note INTEGER NOT NULL
        CHECK (note BETWEEN 1 AND 5),

    commentaire TEXT NOT NULL
        CHECK (length(trim(commentaire)) > 0),

    statut_moderation VARCHAR(20) NOT NULL DEFAULT 'en_attente'
        CHECK (statut_moderation IN (
            'en_attente',
            'valide',
            'refuse'
        )),

    date_creation TIMESTAMP WITH TIME ZONE NOT NULL
        DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE horaire_jour (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    jour_semaine INTEGER NOT NULL UNIQUE
        CHECK (jour_semaine BETWEEN 1 AND 7),

    ferme BOOLEAN NOT NULL
);

CREATE TABLE creneau_horaire (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    horaire_jour_id INTEGER NOT NULL
        REFERENCES horaire_jour(id),

    heure_ouverture TIME NOT NULL,
    heure_fermeture TIME NOT NULL,

    CHECK (heure_fermeture > heure_ouverture)
);