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

-- DEBUT COMPOSITION MENU NOEL VEGAN
-- Bloc rejouable seul dans une transaction, après création du menu.
-- Une recette est identifiée par nom, type et description : les variantes déjà présentes sont conservées.
-- Les allergènes dépendent des ingrédients exacts des produits utilisés.
LOCK TABLE menu, plat, menu_plat, allergene, plat_allergene IN SHARE ROW EXCLUSIVE MODE;

DO $composition_vegan$
DECLARE
    menu_cible integer;
    plat_cible integer;
    gluten_id integer;
    recette record;
BEGIN
    IF (SELECT count(*) FROM menu WHERE nom = 'Menu de Noël vegan') <> 1 THEN
        RAISE EXCEPTION 'Un unique Menu de Noël vegan doit exister avant de compléter sa composition.';
    END IF;
    SELECT id INTO menu_cible FROM menu WHERE nom = 'Menu de Noël vegan';

    -- Réutilise le libellé du catalogue ; nécessaire aussi sur une installation sans 03_menus_demo.sql.
    INSERT INTO allergene (libelle) VALUES ('Gluten') ON CONFLICT (libelle) DO NOTHING;
    SELECT id INTO gluten_id FROM allergene WHERE libelle = 'Gluten';

    FOR recette IN
        SELECT * FROM (VALUES
            ('Velouté de courge au lait de coco',
             'Courge, oignon, lait de coco et huile d’olive.', 'entree', FALSE),
            ('Feuilleté aux champignons et lentilles',
             'Pâte feuilletée végétale au blé, sans beurre, lait ni œufs, champignons, lentilles, oignon et herbes, accompagné de courge rôtie.',
             'plat_principal', TRUE),
            ('Poires pochées à la vanille',
             'Poires, sucre, vanille et citron.', 'dessert', FALSE)
        ) AS plats(nom, description, type, contient_gluten)
    LOOP
        SELECT id INTO plat_cible FROM plat
        WHERE nom = recette.nom AND type = recette.type AND description = recette.description
        ORDER BY id LIMIT 1;

        IF plat_cible IS NULL THEN
            INSERT INTO plat (nom, description, type)
            VALUES (recette.nom, recette.description, recette.type)
            RETURNING id INTO plat_cible;
        END IF;

        INSERT INTO menu_plat (menu_id, plat_id) VALUES (menu_cible, plat_cible)
        ON CONFLICT (menu_id, plat_id) DO NOTHING;

        IF recette.contient_gluten THEN
            INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (plat_cible, gluten_id)
            ON CONFLICT (plat_id, allergene_id) DO NOTHING;
        END IF;
        -- Aucun allergène ajouté pour le velouté et les poires ; les associations existantes sont conservées.
    END LOOP;
END;
$composition_vegan$;
-- FIN COMPOSITION MENU NOEL VEGAN

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
