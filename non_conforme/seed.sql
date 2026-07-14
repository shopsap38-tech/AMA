-- Données initiales reprises du fichier NON_CONFORME.xlsx (au 2026-07-14)
USE non_conforme;

INSERT INTO nc_semi_fini
    (numero_article, description_article, quantite, nbr_palettes, poids_total_kg, motif, decision_sq) VALUES
    ('80010073', 'BAÑO MATIC Océanique Sac 9 Kgs',        480, 8, 4320.00, 'colmaté', 'a recycler'),
    ('80010002', 'MIO MATIC 3*5Kg Sachet',                240, 6, 1200.00, 'colmaté', 'a recycler'),
    ('80010054', 'ZEN MATIC S.M 750 Grs',                 200, 4, 2400.00, 'colmaté', 'a recycler'),
    ('80010059', 'A+ MATIC Océanique Sac 9 Kgs',          210, 3, 1890.00, 'colmaté', 'a recycler'),
    ('80000038', 'MIO Semi-auto 1 Kg Rose - Sac 10P',     150, 3, 1500.00, 'colmaté', 'a recycler');

INSERT INTO nc_desinfectant
    (type_article, numero_article, description_article, stock_mag, date_expiration, code_um, nbr_palettes, prix_unitaire, poids_par_carton_kg, date_blocage, motif, responsable, decision_cq) VALUES
    ('SOLUTION H.A', '80110004', 'MIO SOLUTION H.A DESINFECTANT 100ml / ETHANOL',            1984, NULL,         'Carton 24', 4, 131.9200,  2.8500,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110018', 'Pack MIO AL70% 50ml MIO X 2 + MIO AL70% 50ml GRATUIT',     3294, NULL,         'Unité',     3,  16.9167,  0.1820,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('SOLUTION H.A', '80110006', 'MIO SOLUTION H.A DESINFECTANT 5L',                          218, NULL,         'Carton 3',  4, 339.5000, 14.6650,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('SOLUTION H.A', '80110007', 'MIO SOLUTION H.A DESINFECTANT 500ml',                       169, '2025-08-05', 'Carton 9',  2, 135.3150,  4.4463,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110009', 'MIO ALCOOL 70° DESINFECTANT SPRAY 250ml',                   105, '2026-09-25', 'Carton 25', 2, 212.1875,  6.3500,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110010', 'MIO ALCOOL 70° DESINFECTANT SPRAY 50ml',                     40, NULL,         'Carton 56', 1, 217.2800,  3.3956,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110023', 'FAYZ SOLUTION ALCO. DESINFECTANT 250ml',                     38, NULL,         'Carton 25', 1, 404.1667,  6.3500,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('SOLUTION H.A', '80110008', 'MIO SOLUTION H.A DESINFECTANT 1L',                           37, NULL,         'Carton 6',  1, 167.3250,  6.6495,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110026', 'FAYZ SOLUTION ALCO. DESINFECTANT 100ml',                     36, NULL,         'Carton 25', 1, 404.1667,  2.8500,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110025', 'FAYZ SOLUTION ALCO. DESINFECTANT 1L',                        24, NULL,         'Carton 6',  1, 315.2500,  6.6495,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('ALCOOL',       '80110024', 'FAYZ SOLUTION ALCO. DESINFECTANT 500ml',                     11, NULL,         'Carton 9',  1, 258.2625,  4.4463,  NULL, 'produit expiré', 'Service qualité', NULL),
    ('GEL',          '80110019', 'FAYZ GEL MAINS DESINFECTANT 100ml',                          29, NULL,         'Carton 24', 1, 223.1000,  2.8500,  NULL, 'produit expiré', 'Service qualité', NULL);

INSERT INTO nc_big_bag (date_nc, total_big_bag, tonnage) VALUES
    ('2026-07-14', 52, 34840.00);
