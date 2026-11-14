-- Exécuter après 01_creation.sql et 02_donnees.sql, dans la base ciblée.
-- Les prix sont ceux du menu entier pour le nombre minimum de convives.
BEGIN;

-- Sérialise l'ajout par nom sans imposer un nouvel index au catalogue.
LOCK TABLE menu IN SHARE ROW EXCLUSIVE MODE;

INSERT INTO allergene (libelle)
VALUES ('Gluten'), ('Moutarde'), ('Noix')
ON CONFLICT (libelle) DO NOTHING;

CREATE TEMP TABLE demo_menus (
    nom text PRIMARY KEY,
    description text NOT NULL,
    theme text NOT NULL,
    regime text NOT NULL,
    minimum integer NOT NULL,
    prix_centimes integer NOT NULL,
    delai integer NOT NULL,
    stock integer NOT NULL,
    fichier text NOT NULL,
    alt text NOT NULL
) ON COMMIT DROP;

INSERT INTO demo_menus VALUES
    ('Menu festif de Noël',
     'Une entrée de saison, un chapon rôti aux châtaignes et une bûche au chocolat.',
     'Noël', 'Classique', 6, 53400, 5, 5, 'festif-noel.webp',
     'Chapon doré aux herbes, entouré de châtaignes et de carottes.'),
    ('Menu printanier de Pâques',
     'Un repas de printemps autour de l’agneau rôti, des légumes verts et d’un dessert aux fraises.',
     'Pâques', 'Classique', 4, 28800, 3, 6, 'printanier-paques.webp',
     'Tranches d’agneau rôti, asperges, petits pois et pommes de terre nouvelles.'),
    ('Menu jardin provençal',
     'Un menu végétarien aux légumes du soleil, avec tian, polenta et dessert au citron.',
     'Classique', 'Végétarien', 4, 22000, 3, 7, 'jardin-provencal.webp',
     'Tian de tomates, d’aubergines et de courgettes avec une portion de polenta.'),
    ('Menu green événement',
     'Un repas végétal pour les grandes tablées, avec courge rôtie, lentilles et dessert fruité.',
     'Événement', 'Vegan', 10, 48000, 5, 4, 'green-evenement.webp',
     'Quartiers de courge rôtie, lentilles et jeunes pousses.'),
    ('Menu terroir bordelais',
     'Une salade aux noix, du canard confit et des canelés pour un repas du Sud-Ouest.',
     'Classique', 'Classique', 6, 36000, 4, 5, 'terroir-bordelais.webp',
     'Cuisse de canard confite et pommes de terre dorées au persil.'),
    ('Menu buffet méditerranéen',
     'Un buffet végétarien à partager, avec houmous, falafels et salade d’oranges.',
     'Événement', 'Végétarien', 8, 32000, 4, 6, 'buffet-mediterraneen.webp',
     'Falafels dorés, légumes rôtis, boulgour aux herbes et quartiers de citron.');

CREATE TEMP TABLE demo_plats (
    menu_nom text NOT NULL REFERENCES demo_menus(nom),
    nom text NOT NULL,
    description text NOT NULL,
    type text NOT NULL,
    allergenes text[] NOT NULL DEFAULT ARRAY[]::text[],
    PRIMARY KEY (menu_nom, type)
) ON COMMIT DROP;

INSERT INTO demo_plats VALUES
    ('Menu festif de Noël', 'Velouté de potimarron', 'Potimarron, oignon et crème.', 'entree', ARRAY['Lait']),
    ('Menu festif de Noël', 'Chapon rôti aux châtaignes', 'Chapon aux herbes, châtaignes et carottes.', 'plat_principal', DEFAULT),
    ('Menu festif de Noël', 'Bûche au chocolat', 'Génoise au blé et ganache au chocolat et à la crème.', 'dessert', ARRAY['Gluten', 'Oeufs', 'Lait']),
    ('Menu printanier de Pâques', 'Salade de radis', 'Radis, jeunes pousses et vinaigrette à la moutarde.', 'entree', ARRAY['Moutarde']),
    ('Menu printanier de Pâques', 'Agneau rôti printanier', 'Agneau rôti, asperges, petits pois et pommes de terre nouvelles.', 'plat_principal', DEFAULT),
    ('Menu printanier de Pâques', 'Fraises et chantilly', 'Fraises fraîches et crème fouettée.', 'dessert', ARRAY['Lait']),
    ('Menu jardin provençal', 'Salade de tomates et chèvre', 'Tomates, basilic et fromage de chèvre.', 'entree', ARRAY['Lait']),
    ('Menu jardin provençal', 'Tian provençal et polenta', 'Tomates, courgettes et aubergines rôties, servies avec une polenta au beurre.', 'plat_principal', ARRAY['Lait']),
    ('Menu jardin provençal', 'Tarte au citron', 'Pâte sablée au blé et crème au citron.', 'dessert', ARRAY['Gluten', 'Oeufs', 'Lait']),
    ('Menu green événement', 'Salade de quinoa croquante', 'Quinoa, concombre, citron et herbes fraîches.', 'entree', DEFAULT),
    ('Menu green événement', 'Courge rôtie et lentilles', 'Courge butternut, lentilles et jeunes pousses, assaisonnées au citron.', 'plat_principal', DEFAULT),
    ('Menu green événement', 'Poires pochées à la vanille', 'Poires pochées dans un sirop léger à la vanille.', 'dessert', DEFAULT),
    ('Menu terroir bordelais', 'Salade aux noix', 'Jeunes pousses, noix et vinaigrette à la moutarde.', 'entree', ARRAY['Noix', 'Moutarde']),
    ('Menu terroir bordelais', 'Canard confit et pommes sarladaises', 'Cuisse de canard confite, pommes de terre et persil.', 'plat_principal', DEFAULT),
    ('Menu terroir bordelais', 'Canelés bordelais', 'Petits gâteaux au blé, lait, œufs et vanille.', 'dessert', ARRAY['Gluten', 'Oeufs', 'Lait']),
    ('Menu buffet méditerranéen', 'Houmous citronné', 'Pois chiches, citron et huile d’olive, servis avec des bâtonnets de légumes.', 'entree', DEFAULT),
    ('Menu buffet méditerranéen', 'Falafels et boulgour aux herbes', 'Falafels de pois chiches, légumes rôtis et boulgour de blé.', 'plat_principal', ARRAY['Gluten']),
    ('Menu buffet méditerranéen', 'Oranges à la fleur d’oranger', 'Oranges fraîches, menthe et fleur d’oranger.', 'dessert', DEFAULT);

DO $$
DECLARE
    menu_demo record;
    plat_demo record;
    nouveau_menu_id integer;
    nouveau_plat_id integer;
BEGIN
    FOR menu_demo IN SELECT * FROM demo_menus ORDER BY nom LOOP
        -- Un menu déjà présent conserve ses plats, son tarif et son stock.
        CONTINUE WHEN EXISTS (SELECT 1 FROM menu WHERE nom = menu_demo.nom);

        INSERT INTO menu (
            nom, description, minimum_personnes, prix_minimum_centimes,
            conditions, delai_minimum_jours, stock_commandes, theme_id, regime_id
        ) VALUES (
            menu_demo.nom, menu_demo.description, menu_demo.minimum, menu_demo.prix_centimes,
            format('Commander au moins %s jours calendaires avant la prestation.', menu_demo.delai),
            menu_demo.delai, menu_demo.stock,
            (SELECT id FROM theme WHERE libelle = menu_demo.theme),
            (SELECT id FROM regime WHERE libelle = menu_demo.regime)
        ) RETURNING id INTO nouveau_menu_id;

        INSERT INTO image_menu (menu_id, chemin, texte_alternatif, position)
        VALUES (nouveau_menu_id, '/images/menus/' || menu_demo.fichier, menu_demo.alt, 1);

        FOR plat_demo IN SELECT * FROM demo_plats WHERE menu_nom = menu_demo.nom LOOP
            INSERT INTO plat (nom, description, type)
            VALUES (plat_demo.nom, plat_demo.description, plat_demo.type)
            RETURNING id INTO nouveau_plat_id;

            INSERT INTO menu_plat (menu_id, plat_id)
            VALUES (nouveau_menu_id, nouveau_plat_id);

            INSERT INTO plat_allergene (plat_id, allergene_id)
            SELECT nouveau_plat_id, a.id
            FROM allergene AS a
            WHERE a.libelle = ANY (plat_demo.allergenes);
        END LOOP;
    END LOOP;
END;
$$;

-- Complète uniquement la première image du menu vegan d'origine, si elle manque.
INSERT INTO image_menu (menu_id, chemin, texte_alternatif, position)
SELECT id, '/images/menus/noel-vegan.webp',
       'Feuilleté vegan aux champignons et lentilles, servi avec de la courge rôtie.', 1
FROM menu
WHERE nom = 'Menu de Noël vegan'
ON CONFLICT (menu_id, position) DO NOTHING;

COMMIT;
