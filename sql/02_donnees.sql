BEGIN;
INSERT INTO theme (libelle)
VALUES
    ('Noël'),
    ('Pâques'),
    ('Classique'),
    ('Événement');

INSERT INTO regime (libelle)
VALUES
    ('Classique'),
    ('Végétarien'),
    ('Vegan');

INSERT INTO allergene (libelle)
VALUES
    ('Lait'),
    ('Oeufs'),
    ('Arachides');
    
INSERT INTO plat (nom, description, type)
VALUES (
    'Crème dessert à la vanille',
    'Crème préparée avec du lait et des œufs.',
    'dessert'
);

INSERT INTO plat_allergene (plat_id, allergene_id)
VALUES
    (1, 1),
    (1, 2);

INSERT INTO menu (
    nom,
    description,
    minimum_personnes,
    prix_minimum_centimes,
    conditions,
    delai_minimum_jours,
    stock_commandes,
    theme_id,
    regime_id
)
VALUES (
    'Menu classique découverte',
    'Un menu de démonstration pour tester le catalogue.',
    4,
    12000,
    'Commander au moins 3 jours calendaires avant la prestation.',
    3,
    5,
    3,
    1
)
RETURNING id, nom;

INSERT INTO menu_plat (menu_id, plat_id)
VALUES (1, 1);

INSERT INTO plat (nom, description, type)
VALUES
    (
        'Salade de crudités',
        'Carottes, concombre et salade verte.',
        'entree'
    ),
    (
        'Poulet rôti et riz',
        'Poulet rôti accompagné de riz.',
        'plat_principal'
    )
RETURNING id, nom;

INSERT INTO menu_plat (menu_id, plat_id)
VALUES
    (1, 2),
    (1, 3);

INSERT INTO image_menu (
    menu_id,
    chemin,
    texte_alternatif,
    position
)
VALUES (
    1,
    '/images/menus/poulet-roti.png',
    'Cuisse de poulet rôti accompagnée de riz blanc et de persil.',
    1
);
INSERT INTO menu (
    nom,
    description,
    minimum_personnes,
    prix_minimum_centimes,
    conditions,
    delai_minimum_jours,
    stock_commandes,
    theme_id,
    regime_id
)
VALUES (
    'Menu de Noël vegan',
    'Un menu festif de démonstration entièrement végétal.',
    6,
    18000,
    'Commander au moins 5 jours calendaires avant la prestation.',
    5,
    3,
    1,
    3
)
RETURNING id, nom;
COMMIT;