-- =====================================================
-- SAVEUR KAOLACK — Schéma complet + données de test
-- Généré depuis la BDD réelle
-- ⚠️  Ne contient aucune donnée personnelle réelle
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- ─── Suppression des tables existantes ───────────────
DROP TABLE IF EXISTS `avis`;
DROP TABLE IF EXISTS `commande_details`;
DROP TABLE IF EXISTS `commandes`;
DROP TABLE IF EXISTS `plat_photos`;
DROP TABLE IF EXISTS `plats`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `restaurants`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `utilisateurs`;

-- ─── TABLE : utilisateurs ────────────────────────────
CREATE TABLE `utilisateurs` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `nom`        varchar(100) NOT NULL,
  `prenom`     varchar(100) DEFAULT NULL,
  `email`      varchar(100) NOT NULL,
  `telephone`  varchar(20)  DEFAULT NULL,
  `adresse`    text         DEFAULT NULL,
  `quartier`   varchar(50)  DEFAULT NULL,
  `password`   varchar(255) NOT NULL,
  `role`       enum('client','restaurant','admin') DEFAULT 'client',
  `statut`     enum('actif','inactif','suspendu')  DEFAULT 'actif',
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : categories ──────────────────────────────
CREATE TABLE `categories` (
  `id`              int(11)     NOT NULL AUTO_INCREMENT,
  `nom`             varchar(50) NOT NULL,
  `icone`           varchar(10) DEFAULT NULL,
  `ordre_affichage` int(11)     DEFAULT 0,
  `description`     text        DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : restaurants ─────────────────────────────
CREATE TABLE `restaurants` (
  `id`                  int(11)       NOT NULL AUTO_INCREMENT,
  `nom`                 varchar(100)  NOT NULL,
  `description`         text          DEFAULT NULL,
  `telephone`           varchar(20)   DEFAULT NULL,
  `email`               varchar(100)  DEFAULT NULL,
  `adresse`             varchar(255)  DEFAULT NULL,
  `quartier`            varchar(50)   DEFAULT NULL,
  `categorie_id`        int(11)       DEFAULT NULL,
  `photo_banniere`      varchar(255)  DEFAULT NULL,
  `photo_resto`         varchar(255)  DEFAULT NULL,
  `logo`                varchar(255)  DEFAULT NULL,
  `heure_ouverture`     time          DEFAULT '08:00:00',
  `heure_fermeture`     time          DEFAULT '22:00:00',
  `delai_livraison_min` int(11)       DEFAULT 20,
  `delai_livraison_max` int(11)       DEFAULT 45,
  `frais_livraison`     int(11)       DEFAULT 1000,
  `commande_minimum`    int(11)       DEFAULT 3000,
  `note_moyenne`        decimal(2,1)  DEFAULT 4.5,
  `nb_avis`             int(11)       DEFAULT 0,
  `est_nouveau`         tinyint(1)    DEFAULT 0,
  `statut`              enum('actif','inactif','ferme','en_attente','refuse') DEFAULT 'en_attente',
  `created_at`          timestamp     NOT NULL DEFAULT current_timestamp(),
  `utilisateur_id`      int(11)       DEFAULT NULL,
  `wave_api_key`        varchar(255)  DEFAULT NULL,
  `telegram_bot_token`  varchar(200)  DEFAULT NULL,
  `telegram_chat_id`    varchar(100)  DEFAULT NULL,
  `latitude`            decimal(10,8) DEFAULT NULL,
  `longitude`           decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categorie_id`   (`categorie_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`categorie_id`)   REFERENCES `categories`   (`id`),
  CONSTRAINT `restaurants_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : plats ───────────────────────────────────
CREATE TABLE `plats` (
  `id`            int(11)      NOT NULL AUTO_INCREMENT,
  `nom`           varchar(100) NOT NULL,
  `description`   text         DEFAULT NULL,
  `prix`          int(11)      NOT NULL,
  `categorie`     varchar(50)  DEFAULT NULL,
  `photo`         varchar(255) DEFAULT NULL,
  `restaurant_id` int(11)      DEFAULT NULL,
  `disponible`    tinyint(1)   DEFAULT 1,
  `est_populaire` tinyint(1)   DEFAULT 0,
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `plats_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : plat_photos ─────────────────────────────
CREATE TABLE `plat_photos` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `plat_id`      int(11)      NOT NULL,
  `filename`     varchar(255) NOT NULL,
  `ordre`        int(11)      DEFAULT 0,
  `is_principal` tinyint(1)   DEFAULT 0,
  `created_at`   timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_plat_id` (`plat_id`),
  KEY `idx_ordre`   (`ordre`),
  CONSTRAINT `plat_photos_ibfk_1` FOREIGN KEY (`plat_id`) REFERENCES `plats` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : commandes ───────────────────────────────
CREATE TABLE `commandes` (
  `id`                  int(11)      NOT NULL AUTO_INCREMENT,
  `numero_tracking`     varchar(20)  DEFAULT NULL,
  `numero`              varchar(20)  DEFAULT NULL,
  `client_id`           int(11)      DEFAULT NULL,
  `restaurant_id`       int(11)      DEFAULT NULL,
  `statut`              enum('en_attente','confirmee','en_preparation','en_route','livree','annulee') DEFAULT 'en_attente',
  `raison_annulation`   varchar(255) DEFAULT NULL,
  `client_info`         text         DEFAULT NULL,
  `mode_paiement`       enum('especes','wave','orange_money','free_money') DEFAULT 'especes',
  `total_plats`         int(11)      DEFAULT NULL,
  `frais_livraison`     int(11)      DEFAULT 1000,
  `total`               int(11)      DEFAULT NULL,
  `adresse_livraison`   text         DEFAULT NULL,
  `telephone_livraison` varchar(20)  DEFAULT NULL,
  `notes`               text         DEFAULT NULL,
  `created_at`          timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at`          timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero`          (`numero`),
  UNIQUE KEY `numero_tracking` (`numero_tracking`),
  KEY `client_id`     (`client_id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `idx_statut`    (`statut`),
  CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`client_id`)     REFERENCES `utilisateurs` (`id`),
  CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : commande_details ────────────────────────
CREATE TABLE `commande_details` (
  `id`            int(11) NOT NULL AUTO_INCREMENT,
  `commande_id`   int(11) DEFAULT NULL,
  `plat_id`       int(11) DEFAULT NULL,
  `quantite`      int(11) DEFAULT NULL,
  `prix_unitaire` int(11) DEFAULT NULL,
  `sous_total`    int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  KEY `plat_id`     (`plat_id`),
  CONSTRAINT `commande_details_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commande_details_ibfk_2` FOREIGN KEY (`plat_id`)     REFERENCES `plats`     (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE : avis ────────────────────────────────────
CREATE TABLE `avis` (
  `id`             int(11)    NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11)    DEFAULT NULL,
  `restaurant_id`  int(11)    NOT NULL DEFAULT 1,
  `commande_id`    int(11)    DEFAULT NULL,
  `note`           tinyint(1) NOT NULL DEFAULT 5,
  `commentaire`    text       DEFAULT NULL,
  `statut`         enum('en_attente','approuve','rejete') DEFAULT 'en_attente',
  `created_at`     timestamp  NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `avis_commande_fk` (`commande_id`),
  CONSTRAINT `avis_commande_fk` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── TABLE : login_attempts ──────────────────────────
CREATE TABLE `login_attempts` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `ip`         varchar(45)  NOT NULL,
  `email`      varchar(255) NOT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_email`   (`ip`, `email`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─── TABLE : password_resets ─────────────────────────
CREATE TABLE `password_resets` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `email`      varchar(255) NOT NULL,
  `token`      varchar(255) NOT NULL,
  `expires_at` datetime     NOT NULL,
  `created_at` datetime     DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ─── DONNÉES DE TEST ─────────────────────────────────

-- Compte admin
-- ⚠️  Changer le mot de passe après installation via :
--     http://localhost/saveur-php/changer_mdp_admin.php
INSERT INTO `utilisateurs` (`nom`, `prenom`, `email`, `password`, `role`, `statut`) VALUES
('Admin', 'System', 'admin@saveurkaolack.sn', '$2y$12$lHdw3CJurxSVNAEbFa7wcu5LrCwTYXD8.Hyftu3T7gWAbrEr.6gKi', 'admin', 'actif');

-- Catégories
INSERT INTO `categories` (`nom`, `icone`, `ordre_affichage`) VALUES
('Plats locaux', '🍛', 1),
('Grillades',    '🔥', 2),
('Poissons',     '🐟', 3),
('Sandwichs',    '🥪', 4),
('Boissons',     '🥤', 5);
