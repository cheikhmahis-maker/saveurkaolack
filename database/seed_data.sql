-- =====================================================
-- SEED_DATA.SQL - Données initiales Saveur Kaolack
-- =====================================================
-- Restaurants et plats authentiques de Kaolack

-- =====================================================
-- 1. STRUCTURE DES TABLES (si elles n'existent pas)
-- =====================================================

-- Table des catégories de restaurants
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    icone VARCHAR(10) DEFAULT '🍽️',
    ordre_affichage INT DEFAULT 0,
    description TEXT
);

-- Table des restaurants
CREATE TABLE IF NOT EXISTS restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    telephone VARCHAR(20),
    email VARCHAR(100),
    adresse VARCHAR(255),
    quartier VARCHAR(50),
    categorie_id INT,
    photo_banniere VARCHAR(255),
    photo_resto VARCHAR(255),
    logo VARCHAR(255),
    heure_ouverture TIME DEFAULT '08:00:00',
    heure_fermeture TIME DEFAULT '22:00:00',
    delai_livraison_min INT DEFAULT 20,
    delai_livraison_max INT DEFAULT 45,
    frais_livraison INT DEFAULT 1000,
    commande_minimum INT DEFAULT 3000,
    note_moyenne DECIMAL(2,1) DEFAULT 4.5,
    nb_avis INT DEFAULT 0,
    est_nouveau BOOLEAN DEFAULT FALSE,
    statut ENUM('actif', 'inactif', 'ferme') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id)
);

-- Table des plats
CREATE TABLE IF NOT EXISTS plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix INT NOT NULL,
    categorie VARCHAR(50),
    photo VARCHAR(255),
    restaurant_id INT,
    disponible BOOLEAN DEFAULT TRUE,
    est_populaire BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    quartier VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role ENUM('client', 'restaurant', 'admin') DEFAULT 'client',
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) UNIQUE,
    client_id INT,
    restaurant_id INT,
    statut ENUM('en_attente', 'confirmee', 'en_preparation', 'en_route', 'livree', 'annulee') DEFAULT 'en_attente',
    mode_paiement ENUM('especes', 'wave', 'orange_money', 'free_money') DEFAULT 'especes',
    total_plats INT,
    frais_livraison INT DEFAULT 1000,
    total INT,
    adresse_livraison TEXT,
    telephone_livraison VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

-- Table des détails de commande
CREATE TABLE IF NOT EXISTS commande_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT,
    plat_id INT,
    quantite INT,
    prix_unitaire INT,
    sous_total INT,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (plat_id) REFERENCES plats(id)
);

-- =====================================================
-- 2. INSERTION DES DONNÉES
-- =====================================================

-- Catégories de restaurants
INSERT INTO categories (nom, icone, ordre_affichage, description) VALUES
('Traditionnel', '🥘', 1, 'Cuisine sénégalaise authentique'),
('Grillades', '🍖', 2, 'Viandes et poissons grillés'),
('Fast-Food', '🍔', 3, 'Restauration rapide'),
('Pâtisserie', '🥐', 4, 'Gâteaux et douceurs'),
('Boissons', '🥤', 5, 'Jus et boissons fraîches')
ON DUPLICATE KEY UPDATE ordre_affichage=VALUES(ordre_affichage);

-- Restaurants traditionnels de Kaolack
INSERT INTO restaurants (nom, description, telephone, email, adresse, quartier, categorie_id, 
    heure_ouverture, heure_fermeture, delai_livraison_min, delai_livraison_max, 
    frais_livraison, commande_minimum, note_moyenne, nb_avis, est_nouveau, statut) 
VALUES
('Chez Aminata', 'La meilleure cuisine locale de Kaolack. Thieboudienne préparée avec amour selon la recette traditionnelle de la grand-mère Aminata. Spécialités : thieb, yassa poulet, mafé.', 
    '+221 77 123 45 67', 'contact@chezaminata.sn', 'Rue du Marché Central', 'Centre-ville', 1,
    '09:00:00', '22:00:00', 25, 40, 1000, 3000, 4.7, 128, FALSE, 'actif'),

('Le Mbolo House', 'Restaurant moderne revisitant la cuisine sénégalaise avec une touche contemporaine. Ambiance chic et décontractée.', 
    '+221 77 234 56 78', 'mbolo@house.sn', 'Avenue Blaise Diagne', 'Médina', 1,
    '11:00:00', '23:00:00', 30, 50, 1500, 5000, 4.5, 89, TRUE, 'actif'),

('Grillade du Port', 'Poissons frais grillés et fruits de mer directement du port de Kaolack. La fraîcheur garantie!', 
    '+221 77 345 67 89', 'grillade.port@email.sn', 'Quai du Port', 'Port', 2,
    '10:00:00', '22:00:00', 20, 35, 1000, 4000, 4.6, 156, FALSE, 'actif'),

('Dibi Express', 'Le roi du dibi kaolackois! Chèvre et mouton grillés à la perfection avec attieke et oignons.', 
    '+221 77 456 78 90', 'dibi@express.sn', 'Route de Darou', 'Darou', 2,
    '18:00:00', '02:00:00', 15, 25, 800, 2500, 4.8, 203, FALSE, 'actif'),

('Sanglé Sandwich', 'Fast-food local avec une touche kaolackoise. Burgers, sandwiches et tacos revisités.', 
    '+221 77 567 89 01', 'sangle@sandwich.sn', 'Avenue Abdoulaye Wade', 'Nioro', 3,
    '10:00:00', '00:00:00', 15, 30, 1000, 2000, 4.2, 67, TRUE, 'actif'),

('Boulangerie Pain Doré', 'Pain frais tous les jours, viennoiseries et gâteaux maison. Un classique de Kaolack depuis 1985.', 
    '+221 77 678 90 12', 'pain.dore@email.sn', 'Rue du Commerce', 'Centrale', 4,
    '06:00:00', '20:00:00', 10, 20, 500, 1000, 4.4, 234, FALSE, 'actif'),

('Jus Naturels Ndiawar', 'Jus de fruits frais pressés minute, bissap, gingembre, bouye et délices locaux.', 
    '+221 77 789 01 23', 'jus.ndiawar@email.sn', 'Marché Ndiawar', 'Ndiawar', 5,
    '08:00:00', '20:00:00', 10, 15, 500, 1500, 4.6, 112, FALSE, 'actif')
ON DUPLICATE KEY UPDATE telephone=VALUES(telephone);

-- Plats traditionnels (Chez Aminata - restaurant_id: 1)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Thieboudienne Classique', 'Riz au poisson avec légumes (chou, carotte, aubergine, manioc) et sauce tomate', 3500, 'Plats', 1, TRUE, TRUE),
('Yassa Poulet', 'Poulet mariné aux oignons et citron, accompagné de riz', 3000, 'Plats', 1, TRUE, TRUE),
('Mafé Boeuf', 'Boeuf sauce arachide avec riz blanc', 4000, 'Plats', 1, TRUE, FALSE),
('Soupe Kandia', 'Gombo sauce avec riz et poisson fumé', 2500, 'Plats', 1, TRUE, FALSE),
('Thiebou Yapp', 'Riz à la viande (mouton ou boeuf) avec légumes', 3800, 'Plats', 1, TRUE, TRUE),
('Ceebu Jen Thiaroye', 'Version thiès/thiaroye du thieb avec plus d huile', 3200, 'Plats', 1, TRUE, FALSE),
('Boulettes de Poisson', 'Boulettes de poisson sauce tomate avec riz', 2800, 'Plats', 1, TRUE, FALSE),
('Saka Saka', 'Feuilles de manioc pilées avec huile de palme et poisson fumé', 2000, 'Accompagnements', 1, TRUE, FALSE),
('Fried Plaintains', 'Allocos (bananes plantain frites)', 1000, 'Accompagnements', 1, TRUE, FALSE),
('Attieke Poisson', 'Semoule de manioc avec poisson grillé', 3500, 'Plats', 1, TRUE, TRUE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Plats Le Mbolo House (restaurant_id: 2)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Mbolo Bowl', 'Poke bowl revisité à la sénégalaise : riz, thon, avocat, mangue, cacahuètes', 5500, 'Bowls', 2, TRUE, TRUE),
('Yassa Burger', 'Burger gourmet sauce yassa avec poulet grillé', 4500, 'Burgers', 2, TRUE, TRUE),
('Thieb Wrap', 'Wrap thieboudienne version street food', 3500, 'Wraps', 2, TRUE, FALSE),
('Salade Kaolack', 'Salade composée avec poulet grillé, avocat, mangue, noix de cajou', 4000, 'Salades', 2, TRUE, FALSE),
('Poulet DG Mbolo', 'Poulet DG revisité avec bananes plantain croustillantes', 6000, 'Plats', 2, TRUE, TRUE),
('Tiramisu Bissap', 'Dessert fusion tiramisu au bissap maison', 2500, 'Desserts', 2, TRUE, TRUE),
('Jus de Gingembre', 'Gingembre frais pressé, citron, menthe', 1500, 'Boissons', 2, TRUE, FALSE),
('Café Touba Latte', 'Café Touba version latte', 2000, 'Boissons', 2, TRUE, FALSE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Plats Grillade du Port (restaurant_id: 3)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Thioff Poisson Entier', 'Thioff grillé entier avec légumes et riz', 8000, 'Poissons', 3, TRUE, TRUE),
('Sole Grillé', 'Sole grillée avec sauce moutarde', 7000, 'Poissons', 3, TRUE, FALSE),
('Thioff Filet', 'Filets de thioff grillés sans arêtes', 6000, 'Poissons', 3, TRUE, TRUE),
('Crevettes Jumbo', 'Crevettes géantes grillées (6 pièces)', 9000, 'Fruits de Mer', 3, TRUE, TRUE),
('Calamars Farcis', 'Calamars farcis et grillés', 7500, 'Fruits de Mer', 3, TRUE, FALSE),
('Langoustes', 'Langoustes grillées selon arrivage', 12000, 'Fruits de Mer', 3, TRUE, FALSE),
('Plateau Fruits de Mer', 'Assortiment pour 2 personnes', 18000, 'Menus', 3, TRUE, TRUE),
('Poisson du Jour', 'Selon arrivage du port', 5000, 'Poissons', 3, TRUE, FALSE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Plats Dibi Express (restaurant_id: 4)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Dibi Chèvre', 'Chèvre grillée avec attieke et oignons', 5000, 'Grillades', 4, TRUE, TRUE),
('Dibi Mouton', 'Mouton grillé avec attieke et oignons', 4500, 'Grillades', 4, TRUE, TRUE),
('Dibi Mixte', 'Assortiment chèvre et mouton', 7000, 'Grillades', 4, TRUE, TRUE),
('Choukouya', 'Viande grillée épicée (piquante)', 4000, 'Grillades', 4, TRUE, FALSE),
('Brochettes Mouton', '6 brochettes de mouton', 3500, 'Grillades', 4, TRUE, FALSE),
('Brochettes Boeuf', '6 brochettes de boeuf', 4000, 'Grillades', 4, TRUE, FALSE),
('Poulet Braisé Entier', 'Poulet braisé complet', 8000, 'Grillades', 4, TRUE, TRUE),
('Poulet Braisé 1/2', 'Demi poulet braisé', 4500, 'Grillades', 4, TRUE, TRUE),
('Gambas Grillées', '10 gambas grillées', 6000, 'Fruits de Mer', 4, TRUE, FALSE),
('Attieke Seul', 'Attieke nature', 1000, 'Accompagnements', 4, TRUE, FALSE),
('Frites de Plantain', 'Allocos frites', 1500, 'Accompagnements', 4, TRUE, FALSE),
('Salade d Oignons', 'Oignons vinaigrés avec moutarde', 500, 'Accompagnements', 4, TRUE, FALSE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Plats Sanglé Sandwich (restaurant_id: 5)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Sandwich Thieb', 'Sandwich au thieboudienne (recette maison)', 2500, 'Sandwiches', 5, TRUE, TRUE),
('Burger Dibi', 'Burger au dibi avec sauce spéciale', 3500, 'Burgers', 5, TRUE, TRUE),
('Tacos Yassa', 'Tacos garni au poulet yassa', 3000, 'Tacos', 5, TRUE, TRUE),
('Shawarma Senegalais', 'Shawarma revisité à la sauce sénégalaise', 3000, 'Sandwiches', 5, TRUE, FALSE),
('Hot Dog Kaolack', 'Hot dog avec attieke et oignons', 2000, 'Sandwiches', 5, TRUE, FALSE),
('Pizza Mbolo', 'Pizza locale aux saveurs kaolackoises', 5000, 'Pizzas', 5, TRUE, FALSE),
('Sanglé Frites', 'Frites maison avec sauce fromagère', 2000, 'Accompagnements', 5, TRUE, FALSE),
('Nems', '6 nems avec sauce aigre-douce', 2500, 'Entrées', 5, TRUE, FALSE),
('Soda', 'Coca, Fanta, Sprite', 500, 'Boissons', 5, TRUE, FALSE),
('Eau', 'Eau minérale 50cl', 300, 'Boissons', 5, TRUE, FALSE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Plats Boulangerie Pain Doré (restaurant_id: 6)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Baguette Tradition', 'Baguette française croustillante', 150, 'Pains', 6, TRUE, TRUE),
('Pain de Mie', 'Pain de mie moelleux', 400, 'Pains', 6, TRUE, FALSE),
('Croissant', 'Croissant au beurre pur', 500, 'Viennoiseries', 6, TRUE, TRUE),
('Pain au Chocolat', 'Pain au chocolat maison', 600, 'Viennoiseries', 6, TRUE, TRUE),
('Chausson aux Pommes', 'Chausson pomme caramélisée', 700, 'Viennoiseries', 6, TRUE, FALSE),
('Gâteau Yaourt', 'Gâteau au yaourt individuel', 800, 'Pâtisseries', 6, TRUE, FALSE),
('Tarte aux Pommes', 'Part de tarte tatin', 1000, 'Pâtisseries', 6, TRUE, TRUE),
('Mille-feuille', 'Mille-feuille crème vanille', 1200, 'Pâtisseries', 6, TRUE, TRUE),
('Éclair au Chocolat', 'Éclair fourré crème chocolat', 1000, 'Pâtisseries', 6, TRUE, FALSE),
('Tarte au Citron', 'Tarte citron meringuée', 1500, 'Pâtisseries', 6, TRUE, FALSE),
('Café Croissant', 'Formule petit déjeuner', 1000, 'Menus', 6, TRUE, TRUE),
('Jus Baguette', 'Formule déjeuner rapide', 800, 'Menus', 6, TRUE, FALSE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Plats Jus Naturels Ndiawar (restaurant_id: 7)
INSERT INTO plats (nom, description, prix, categorie, restaurant_id, disponible, est_populaire) VALUES
('Jus de Bissap', 'Bissap (oseille de Guinée) frais', 500, 'Jus Frais', 7, TRUE, TRUE),
('Jus de Gingembre', 'Gingembre frais, citron, sucre', 500, 'Jus Frais', 7, TRUE, TRUE),
('Jus de Bouye', 'Baobab (bouye) naturel', 600, 'Jus Frais', 7, TRUE, TRUE),
('Jus de Tamarin', 'Tamarin dilué sucré', 500, 'Jus Frais', 7, TRUE, FALSE),
('Jus de Mangue', 'Mangue fraîche mixée', 700, 'Jus Frais', 7, TRUE, TRUE),
('Jus de Papaye', 'Papaye fraîche mixée', 600, 'Jus Frais', 7, TRUE, FALSE),
('Jus Cocktail', 'Mix de 3 fruits au choix', 1000, 'Jus Frais', 7, TRUE, TRUE),
('Lait Caillé', 'Lait fermenté traditionnel', 500, 'Lactés', 7, TRUE, FALSE),
('Ditakh', 'Jus de ditakh (fruit local)', 700, 'Jus Frais', 7, TRUE, FALSE),
('Café Touba', 'Café traditionnel parfumé', 300, 'Boissons Chaudes', 7, TRUE, TRUE),
('Thé à la Menthe', 'Thé vert menthe fraîche', 400, 'Boissons Chaudes', 7, TRUE, FALSE),
('Eau de Coco', 'Noix de coco fraîche', 1500, 'Boissons', 7, TRUE, TRUE)
ON DUPLICATE KEY UPDATE prix=VALUES(prix);

-- Utilisateur admin
INSERT INTO utilisateurs (nom, prenom, email, telephone, password, role, statut) VALUES
('Admin', 'System', 'admin@saveurkaolack.sn', '+221 77 000 00 00', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif')
ON DUPLICATE KEY UPDATE email=email;

-- =====================================================
-- 3. INSTRUCTIONS POUR LES IMAGES
-- =====================================================
-- 
-- Pour que les images s'affichent, vous devez :
--
-- 1. Télécharger des images réelles et les placer dans :
--    - uploads/restaurants/bannieres/  (photos grandes des restaurants)
--    - uploads/restaurants/logos/      (logos des restaurants)
--    - uploads/plats/                  (photos des plats)
--
-- 2. Mettre à jour les restaurants avec les noms de fichiers :
--    UPDATE restaurants SET photo_banniere = 'restaurant1.jpg' WHERE id = 1;
--
-- 3. Les noms des photos des plats sont dans la colonne 'photo' de la table plats
--    UPDATE plats SET photo = 'plat1.jpg' WHERE id = 1;
--
-- Conseil : Utilisez des images libres de droits (Unsplash, Pexels) ou vos propres photos
--
-- =====================================================
