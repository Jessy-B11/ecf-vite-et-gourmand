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