-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 30 avr. 2026 à 23:19
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `saveur_kaolack`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) NOT NULL DEFAULT 1,
  `commande_id` int(11) DEFAULT NULL,
  `note` tinyint(1) NOT NULL DEFAULT 5,
  `commentaire` text DEFAULT NULL,
  `statut` enum('en_attente','approuve','rejete') DEFAULT 'en_attente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `utilisateur_id`, `restaurant_id`, `commande_id`, `note`, `commentaire`, `statut`, `created_at`) VALUES
(1, NULL, 1, 1, 4, 'machallah', 'approuve', '2026-04-25 02:58:06');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `icone` varchar(10) DEFAULT '???????',
  `ordre_affichage` int(11) DEFAULT 0,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `icone`, `ordre_affichage`, `description`) VALUES
(1, 'Traditionnel', '', 1, 'Cuisine senegalaise authentique'),
(2, 'Grillades', '', 2, 'Viandes et poissons grillars'),
(3, 'Fast-Food', '', 3, 'Restauration rapide'),
(4, 'Patisserie', '', 4, 'Gateaux et douceurs'),
(5, 'Boissons', '', 5, 'Jus et boissons fraiches');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `numero_tracking` varchar(20) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `statut` enum('en_attente','confirmee','en_preparation','en_route','livree','annulee') DEFAULT 'en_attente',
  `raison_annulation` varchar(255) DEFAULT NULL,
  `client_info` text DEFAULT NULL,
  `mode_paiement` enum('especes','wave','orange_money','free_money') DEFAULT 'especes',
  `total_plats` int(11) DEFAULT NULL,
  `frais_livraison` int(11) DEFAULT 1000,
  `total` int(11) DEFAULT NULL,
  `adresse_livraison` text DEFAULT NULL,
  `telephone_livraison` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `numero_tracking`, `numero`, `client_id`, `restaurant_id`, `statut`, `raison_annulation`, `client_info`, `mode_paiement`, `total_plats`, `frais_livraison`, `total`, `adresse_livraison`, `telephone_livraison`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SK-260425-3EW2', NULL, NULL, 1, 'livree', NULL, '{\"prenom\":\"fatou ndi\",\"telephone\":\"77526598\",\"adresse\":\"sasol\",\"notes\":\"\"}', 'especes', NULL, 1000, 4200, NULL, NULL, NULL, '2026-04-25 02:53:57', '2026-04-25 02:57:23'),
(2, 'SK-260427-R8EN', NULL, NULL, 1, 'en_attente', NULL, '{\"prenom\":\"fatou ndi\",\"telephone\":\"77526598\",\"adresse\":\"dass\",\"notes\":\"\"}', 'especes', NULL, 1000, 4200, NULL, NULL, NULL, '2026-04-27 21:35:42', '2026-04-27 21:35:42'),
(3, 'SK-260429-7XA8', NULL, NULL, 5, 'livree', NULL, '{\"prenom\":\"amadou\",\"telephone\":\"785216568\",\"adresse\":\"ndakarou\",\"notes\":\"\"}', 'especes', NULL, 1000, 3500, NULL, NULL, NULL, '2026-04-29 01:49:17', '2026-04-29 01:52:31'),
(4, 'SK-260430-AHDU', NULL, NULL, 5, 'en_attente', NULL, '{\"prenom\":\"fatou ndi\",\"telephone\":\"77526598\",\"email\":\"client@test.sn\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 3500, NULL, NULL, NULL, '2026-04-30 13:44:44', '2026-04-30 13:44:44'),
(5, 'SK-260430-LMEP', NULL, NULL, 1, 'en_preparation', NULL, '{\"prenom\":\"fatou ndi\",\"telephone\":\"77526598\",\"email\":\"niangprogrammeur@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 4200, NULL, NULL, NULL, '2026-04-30 13:47:02', '2026-04-30 13:51:01'),
(6, 'SK-260430-2BRN', NULL, NULL, 2, 'en_attente', NULL, '{\"prenom\":\"Fatou\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar sicap mbao\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 14:54:46', '2026-04-30 14:54:46'),
(7, 'SK-260430-DRPZ', NULL, NULL, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar sicap mbao\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 14:59:21', '2026-04-30 14:59:21'),
(8, 'SK-260430-9S84', NULL, NULL, 2, 'en_attente', NULL, '{\"prenom\":\"Manager\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar sicap\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 15:17:02', '2026-04-30 15:17:02'),
(9, 'SK-260430-CRLK', NULL, NULL, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar sicap\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 15:19:01', '2026-04-30 15:19:01'),
(10, 'SK-260430-XJ2C', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"passoire\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 16:17:43', '2026-04-30 16:17:43'),
(11, 'SK-260430-34KM', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:05:19', '2026-04-30 17:05:19'),
(12, 'SK-260430-FEJQ', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77456958\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:10:06', '2026-04-30 17:10:06'),
(13, 'SK-260430-QAJ6', NULL, 9, 2, 'en_attente', NULL, '{\"prenom\":\"Fatou\",\"telephone\":\"776426838\",\"email\":\"fatou@gmail.com\",\"adresse\":\"parcelles assainies\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:29:22', '2026-04-30 17:29:22'),
(14, 'SK-260430-5M2X', NULL, 9, 2, 'en_attente', NULL, '{\"prenom\":\"Fatou\",\"telephone\":\"785216568\",\"email\":\"client@test.sn\",\"adresse\":\"parcelle\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:38:30', '2026-04-30 17:38:30'),
(15, 'SK-260430-GQ5S', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77456958\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:41:03', '2026-04-30 17:41:03'),
(16, 'SK-260430-GSUK', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77456958\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dkar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:42:29', '2026-04-30 17:42:29'),
(17, 'SK-260430-2PXU', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77456958\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:45:35', '2026-04-30 17:45:35'),
(18, 'SK-260430-QEDM', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77456958\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 17:51:32', '2026-04-30 17:51:32'),
(19, 'SK-260430-6KCY', NULL, 9, 2, 'en_preparation', NULL, '{\"prenom\":\"Fatou\",\"telephone\":\"77456958\",\"email\":\"client@test.sn\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 20:29:32', '2026-04-30 20:30:15'),
(20, 'SK-260430-U8C4', NULL, 9, 2, 'en_attente', NULL, '{\"prenom\":\"Fatou\",\"telephone\":\"77456958\",\"email\":\"client@test.sn\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 20:40:03', '2026-04-30 20:40:03'),
(21, 'SK-260430-9GJ3', NULL, 9, 2, 'annulee', NULL, '{\"prenom\":\"Fatou\",\"telephone\":\"785216568\",\"email\":\"client@test.sn\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 20:46:02', '2026-04-30 21:00:46'),
(22, 'SK-260430-6GJ9', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"785216568\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 20:56:58', '2026-04-30 20:56:58'),
(23, 'SK-260430-9VH8', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77526598\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 20:59:57', '2026-04-30 20:59:57'),
(24, 'SK-260430-E28L', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77526598\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 21:01:04', '2026-04-30 21:01:04'),
(25, 'SK-260430-MBQ6', NULL, 10, 2, 'en_attente', NULL, '{\"prenom\":\"mouhamed\",\"telephone\":\"77526598\",\"email\":\"mouhamedsy3002@gmail.com\",\"adresse\":\"dakar\",\"notes\":\"\"}', 'especes', NULL, 1000, 5500, NULL, NULL, NULL, '2026-04-30 21:06:33', '2026-04-30 21:06:33');

-- --------------------------------------------------------

--
-- Structure de la table `commande_details`
--

CREATE TABLE `commande_details` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) DEFAULT NULL,
  `plat_id` int(11) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `prix_unitaire` int(11) DEFAULT NULL,
  `sous_total` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commande_details`
--

INSERT INTO `commande_details` (`id`, `commande_id`, `plat_id`, `quantite`, `prix_unitaire`, `sous_total`) VALUES
(1, 1, 6, 1, 3200, NULL),
(2, 2, 6, 1, 3200, NULL),
(3, 3, 46, 1, 2500, NULL),
(4, 4, 46, 1, 2500, NULL),
(5, 5, 6, 1, 3200, NULL),
(6, 6, 12, 1, 4500, NULL),
(7, 7, 12, 1, 4500, NULL),
(8, 8, 12, 1, 4500, NULL),
(9, 9, 12, 1, 4500, NULL),
(10, 10, 12, 1, 4500, NULL),
(11, 11, 12, 1, 4500, NULL),
(12, 12, 12, 1, 4500, NULL),
(13, 13, 12, 1, 4500, NULL),
(14, 14, 12, 1, 4500, NULL),
(15, 15, 12, 1, 4500, NULL),
(16, 16, 12, 1, 4500, NULL),
(17, 17, 12, 1, 4500, NULL),
(18, 18, 12, 1, 4500, NULL),
(19, 19, 12, 1, 4500, NULL),
(20, 20, 12, 1, 4500, NULL),
(21, 21, 12, 1, 4500, NULL),
(22, 22, 12, 1, 4500, NULL),
(23, 23, 12, 1, 4500, NULL),
(24, 24, 12, 1, 4500, NULL),
(25, 25, 12, 1, 4500, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `plats`
--

CREATE TABLE `plats` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` int(11) NOT NULL,
  `categorie` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `est_populaire` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `plats`
--

INSERT INTO `plats` (`id`, `nom`, `description`, `prix`, `categorie`, `photo`, `restaurant_id`, `disponible`, `est_populaire`, `created_at`) VALUES
(1, 'Thieboudienne Classique', 'Riz au poisson avec legumes (chou, carotte, aubergine, manioc) et sauce tomate', 3500, 'Plats', 'plat_01_thieboudienne.jpg', 1, 1, 1, '2026-04-18 23:26:55'),
(2, 'Yassa Poulet', 'Poulet marines aux oignons et citron, accompagne de riz', 3000, 'Plats', 'plat_02_yassa.jpg', 1, 1, 1, '2026-04-18 23:26:55'),
(3, 'Maffe Boeuf', 'Boeuf sauce arachide avec riz blanc', 4000, 'Plats', 'plat_03_mafe.jpg', 1, 1, 0, '2026-04-18 23:26:55'),
(4, 'Soupe Kandia', 'Gombo sauce avec riz et poisson fumer', 2500, 'Plats', 'plat_04_soupoukandia.jpg', 1, 1, 0, '2026-04-18 23:26:55'),
(5, 'Thiebou Yapp', 'Riz a la viande (mouton ou boeuf) avec legumes', 3800, 'Plats', 'plat_05_thiebouyapp.jpg', 1, 1, 1, '2026-04-18 23:26:55'),
(6, 'Boulettes Viande en Sauce', '', 3200, '', 'plat_06_ceebujen.jpg', 1, 1, 0, '2026-04-18 23:26:55'),
(7, 'Boulettes de Poisson', 'Boulettes de poisson sauce tomate avec riz', 2800, 'Plats', 'plat_07_boulettes.jpg', 1, 1, 0, '2026-04-18 23:26:55'),
(8, 'Salade boeuf aux herbes', 'Feuilles de manioc pilles avec huile de palme et poisson fumes', 2500, '', 'plat_08_sakasaka.jpg', 1, 1, 0, '2026-04-18 23:26:55'),
(9, 'Pizza', 'Allocos (bananes plantain frites)', 7999, '', '69e7b27b26e18.jpg', 1, 1, 0, '2026-04-18 23:26:55'),
(10, 'Poulet Sauce Tomate', 'Semoule de manioc avec poisson griller', 3500, '', 'plat_10_attiekepoisson.jpg', 1, 1, 1, '2026-04-18 23:26:55'),
(11, 'Mbolo Bowl', 'Poke bowl revisites a la senegalaise : riz, thon, avocat, mangue, cacahuetes', 5500, 'Bowls', 'plat_11_mbolobowl.jpg', 2, 1, 1, '2026-04-18 23:26:55'),
(12, 'Burger', 'Burger gourmet sauce yassa avec poulet griller', 4500, '', 'plat_12_yassaburger.jpg', 2, 1, 1, '2026-04-18 23:26:55'),
(13, 'Tacos', 'Wrap thieboudienne version street food', 3500, '', 'plat_13_thiebwrap.jpg', 2, 1, 0, '2026-04-18 23:26:55'),
(14, 'Salade Kaolack', 'Salade compose avec poulet griller, avocat, mangue, noix de cajou', 4000, 'Salades', 'plat_14_saladekaolack.jpg', 2, 1, 0, '2026-04-18 23:26:55'),
(15, 'Poulet DG Mbolo', 'Poulet DG revisiter avec bananes plantain croustillantes', 6000, 'Plats', 'plat_15_pouletdg.jpg', 2, 1, 1, '2026-04-18 23:26:55'),
(16, 'MuniGateaux', 'Dessert fusion tiramisu au bissap maison', 2500, '', 'plat_16_tiramisubissap.jpg', 2, 1, 1, '2026-04-18 23:26:55'),
(17, 'Jus de Cocacola', 'Boisson coco', 1500, 'Boissons', 'plat_17_jusgingembre.jpg', 2, 1, 0, '2026-04-18 23:26:55'),
(18, 'Cafe Touba Latte', 'Cafe Touba version mouride', 2000, 'Boissons', 'plat_18_cafetouba.jpg', 2, 1, 0, '2026-04-18 23:26:55'),
(19, 'Thioff Poisson Entier', 'Thioff grilles entier avec legumes et riz', 8000, 'Poissons', 'plat_19_thioffentier.jpg', 3, 1, 1, '2026-04-18 23:26:55'),
(20, 'Sole Griller', 'Sole grille avec sauce moutarde', 7000, 'Poissons', 'plat_20_sole.jpg', 3, 1, 0, '2026-04-18 23:26:55'),
(21, 'Thioff Filet', 'Filets de thioff grilles sans aretes', 6000, 'Poissons', 'plat_21_thiofffilet.jpg', 3, 1, 1, '2026-04-18 23:26:55'),
(22, 'Crevettes Jumbo', 'Crevettes geantes grilles (6 pieces)', 9000, 'Fruits de Mer', 'plat_22_crevettesjumbo.jpg', 3, 1, 1, '2026-04-18 23:26:55'),
(23, 'Calamars Farcis', 'Calamars farcis et grilles', 7500, 'Fruits de Mer', 'plat_23_calamars.jpg', 3, 1, 0, '2026-04-18 23:26:55'),
(24, 'Langoustes', 'Langoustes grilles selon arrivage', 12000, 'Fruits de Mer', 'plat_24_langoustes.jpg', 3, 1, 0, '2026-04-18 23:26:55'),
(25, 'Poutine', 'Poutine', 6000, '', 'img_69ecdb0837b869.78088022.jpg', 3, 1, 1, '2026-04-18 23:26:55'),
(26, 'Poulet du Jour', 'Poulet', 5999, '', 'img_69ecdb6a8f3456.04926451.jpg', 3, 1, 0, '2026-04-18 23:26:55'),
(27, 'Dibi Chievre', 'Chevre grillie avec attieke et oignons', 5000, 'Grillades', 'plat_27_dibichevre.jpg', 4, 1, 1, '2026-04-18 23:26:55'),
(28, 'Dibi Mouton', 'Mouton grilles avec attieke et oignons', 4500, 'Grillades', 'plat_28_dibimouton.jpg', 4, 1, 1, '2026-04-18 23:26:55'),
(29, 'Dibi Mixte', 'Assortiment chievre et mouton', 7000, 'Grillades', 'plat_29_dibimixte.jpg', 4, 1, 1, '2026-04-18 23:26:55'),
(30, 'Choukouya', 'Viande grille epices (piquante)', 4000, 'Grillades', 'plat_30_choukouya.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(31, 'Brochettes Mouton', '6 brochettes de mouton', 3500, 'Grillades', 'plat_31_brochettesmouton.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(32, 'Brochettes Boeuf', '6 brochettes de boeuf', 4000, 'Grillades', 'plat_32_brochettesboeuf.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(33, 'Poulet Braises Entier', 'Poulet braises complet', 8000, 'Grillades', 'plat_33_pouletentier.jpg', 4, 1, 1, '2026-04-18 23:26:55'),
(34, 'Poulet Braises 1/2', 'Demi poulet braises', 4500, 'Grillades', 'plat_34_pouletdemi.jpg', 4, 1, 1, '2026-04-18 23:26:55'),
(35, 'Gambas Grillies', '10 gambas grillies', 6000, '', 'plat_35_gambas.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(36, 'Attieke Seul', 'Attieke nature', 1000, 'Accompagnements', 'plat_36_attieke.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(37, 'Frites de Plantain', 'Allocos frites', 1500, 'Accompagnements', 'plat_37_allocos.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(38, 'Salade d Oignons', 'Oignons vinaigr??s avec moutarde', 500, 'Accompagnements', 'plat_38_saladoignons.jpg', 4, 1, 0, '2026-04-18 23:26:55'),
(39, 'Tacos', 'Tacos', 2500, '', 'plat_39_sandwichthieb.jpg', 5, 1, 1, '2026-04-18 23:26:55'),
(40, 'Burger Dibi', 'Burger au dibi avec sauce sp??ciale', 3500, 'Burgers', 'plat_40_burgerdibi.jpg', 5, 1, 1, '2026-04-18 23:26:55'),
(41, 'Tacos Yassa', 'Tacos garni au poulet yassa', 3000, 'Tacos', 'plat_41_tacosyassa.jpg', 5, 1, 1, '2026-04-18 23:26:55'),
(42, 'Shawarma Senegalais', 'Shawarma revisit?? ?? la sauce s??n??galaise', 3000, 'Sandwiches', 'plat_42_shawarma.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(43, 'Hot Dog Kaolack', 'Hot dog avec attieke et oignons', 2000, 'Sandwiches', 'plat_43_hotdog.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(44, 'Pizza Mbolo', 'Pizza locale aux saveurs kaolackoises', 5000, 'Pizzas', 'plat_44_pizza.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(45, 'Sangl?? Frites', 'Frites maison avec sauce fromag??re', 2000, 'Accompagnements', 'plat_45_sanglefrites.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(46, 'Brochette', '', 2500, '', 'img_69ecd940dcc9c3.16813457.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(47, 'Soda', 'Coca, Fanta, Sprite', 500, 'Boissons', 'plat_47_soda.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(48, 'Eau', 'Eau min??rale 50cl', 300, 'Boissons', 'plat_48_eau.jpg', 5, 1, 0, '2026-04-18 23:26:55'),
(49, 'Baguette Tradition', 'Baguette française croustillante', 150, '', 'plat_49_baguette.jpg', 6, 1, 1, '2026-04-18 23:26:55'),
(50, 'Pain de Mie', 'Pain de mie moelleux', 400, 'Pains', 'plat_50_paindemie.jpg', 6, 1, 0, '2026-04-18 23:26:55'),
(51, 'Croissant', 'Croissant au beurre pur', 500, 'Viennoiseries', 'plat_51_croissant.jpg', 6, 1, 1, '2026-04-18 23:26:55'),
(52, 'Pain au Chocolat', 'Pain au chocolat maison', 600, 'Viennoiseries', 'plat_52_painauchocolat.jpg', 6, 1, 1, '2026-04-18 23:26:55'),
(53, 'Chausson aux Pommes', 'Chausson pomme caram??lis??e', 700, 'Viennoiseries', 'plat_53_chaussonpommes.jpg', 6, 1, 0, '2026-04-18 23:26:55'),
(54, 'Gateau Yaourt', 'Gateau au yaourt individuel', 1800, '', 'plat_54_gateauyaourt.jpg', 6, 1, 0, '2026-04-18 23:26:55'),
(55, 'Tarte aux Pommes', 'Part de tarte tatin', 1000, 'P??tisseries', 'plat_55_tartepommes.jpg', 6, 1, 1, '2026-04-18 23:26:55'),
(56, 'Mille-feuille', 'Mille-feuille creme vanille', 1200, '', 'plat_56_millefeuille.jpg', 6, 1, 1, '2026-04-18 23:26:55'),
(57, 'Gateaux', 'Gateaux chocolat', 4999, '', 'img_69ecda3d8a4eb3.59764990.jpg', 6, 1, 0, '2026-04-18 23:26:55'),
(58, 'Tarte au Citron', 'Tarte citron meringue', 1500, '', 'plat_58_tartecitron.jpg', 6, 1, 0, '2026-04-18 23:26:55'),
(59, 'Cafee Croissant', 'Formule petit dejeuner', 1000, '', 'plat_59_formulecafe.jpg', 6, 1, 1, '2026-04-18 23:26:55'),
(60, 'Jus Baguette', 'Formule dejeuner rapide', 800, '', 'plat_60_formulejus.jpg', 6, 1, 0, '2026-04-18 23:26:55'),
(73, 'Fataya', '', 696, '', '69e7b342bf49c.jpg', 1, 1, 0, '2026-04-19 19:27:01'),
(74, 'Shawarma', 'Poulet mariné aux oignons', 2000, '', '69e7b3976e214.jpg', 1, 1, 0, '2026-04-19 19:27:01'),
(75, 'Crepes Sucre', '1 pieces =500', 500, '', '69e7b3e97e22d.jpg', 1, 1, 0, '2026-04-19 19:27:01'),
(76, 'glace artisanal', 'Jus de baobab frais', 1000, 'Boissons', '69e7b1db7fbc9.jpg', 1, 1, 0, '2026-04-19 19:27:01'),
(78, 'Burger', '', 3000, 'Fast-Food', NULL, 1, 1, 0, '2026-04-25 03:53:42'),
(79, 'Dibi Viande', '', 4999, 'Fast-Food', NULL, 1, 1, 0, '2026-04-25 03:53:47'),
(80, 'Tacos', '', 3000, 'Fast-Food', 'img_69ec3b0bec7430.26672691.jpg', 1, 1, 0, '2026-04-25 03:54:51'),
(81, 'jus', '', 2498, 'Boissons', 'img_69ec3d03423c41.52271239.jpg', 1, 1, 0, '2026-04-25 04:03:15');

-- --------------------------------------------------------

--
-- Structure de la table `plat_photos`
--

CREATE TABLE `plat_photos` (
  `id` int(11) NOT NULL,
  `plat_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `ordre` int(11) DEFAULT 0,
  `is_principal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `plat_photos`
--

INSERT INTO `plat_photos` (`id`, `plat_id`, `filename`, `ordre`, `is_principal`, `created_at`) VALUES
(1, 9, 'img_69ec39eb9a67e7.34923996.jpg', 0, 1, '2026-04-25 03:50:03'),
(2, 80, 'img_69ec3b0bec7430.26672691.jpg', 0, 1, '2026-04-25 03:54:51'),
(3, 78, 'img_69ec3b31b99c22.17944831.jpg', 0, 1, '2026-04-25 03:55:29'),
(4, 79, 'img_69ec3bd0cf6d77.91654885.jpg', 0, 1, '2026-04-25 03:58:09'),
(5, 79, 'img_69ec3c75caae54.37546203.jpg', 1, 0, '2026-04-25 04:00:54'),
(6, 81, 'img_69ec3d03423c41.52271239.jpg', 0, 1, '2026-04-25 04:03:15');

-- --------------------------------------------------------

--
-- Structure de la table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `quartier` varchar(50) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `photo_banniere` varchar(255) DEFAULT NULL,
  `photo_resto` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `heure_ouverture` time DEFAULT '08:00:00',
  `heure_fermeture` time DEFAULT '22:00:00',
  `delai_livraison_min` int(11) DEFAULT 20,
  `delai_livraison_max` int(11) DEFAULT 45,
  `frais_livraison` int(11) DEFAULT 1000,
  `commande_minimum` int(11) DEFAULT 3000,
  `note_moyenne` decimal(2,1) DEFAULT 4.5,
  `nb_avis` int(11) DEFAULT 0,
  `est_nouveau` tinyint(1) DEFAULT 0,
  `statut` enum('actif','inactif','ferme') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `utilisateur_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `restaurants`
--

INSERT INTO `restaurants` (`id`, `nom`, `description`, `telephone`, `email`, `adresse`, `quartier`, `categorie_id`, `photo_banniere`, `photo_resto`, `logo`, `heure_ouverture`, `heure_fermeture`, `delai_livraison_min`, `delai_livraison_max`, `frais_livraison`, `commande_minimum`, `note_moyenne`, `nb_avis`, `est_nouveau`, `statut`, `created_at`, `utilisateur_id`) VALUES
(1, 'Belle Vue', 'La meilleure cuisine locale de Kaolack. Thieboudienne prepare avec amour selon la recette traditionnelle de la grand-mere Aminata. Specialites : thieb, yassa poulet, mafe.', '+221 77 123 45 67', 'contact@bellevue.sn', 'Rue du Marche Central coeur de ville et Niatty Gouy', 'Centre-ville', 1, 'chez_aminata.jpg', NULL, NULL, '00:00:00', '23:59:59', 25, 40, 1000, 1000, 4.7, 128, 0, 'actif', '2026-04-18 23:26:55', 2),
(2, 'Rof-Top', 'tacos frais tous les jours, viennoiseries et Pizza maison. Un classique de Kaolack depuis 3ans .', '+221 77 234 56 78', 'Keurdeville@.sn', 'Keur de ville', 'coeur de ville', 1, 'mbolo_house.jpg', NULL, NULL, '00:00:00', '23:59:59', 20, 40, 700, 1000, 4.5, 89, 1, 'actif', '2026-04-18 23:26:55', 3),
(3, 'La Farisse', 'Poissons frais grilles et fruits de mer directement du port de Kaolack. La fraicheur garantie!', '+221 77 345 67 89', 'lafarisse.@email.sn', 'marche en face polise ', 'en face police marche ', 2, 'grillade_port.jpg', NULL, NULL, '00:00:00', '23:59:59', 20, 35, 1000, 1000, 4.6, 156, 0, 'actif', '2026-04-18 23:26:55', 4),
(4, 'Beckaye ', 'Le roi du dibi kaolackois! tacos et mouton grilles ?? la perfection avec butger et oignons.', '+221 77 456 78 90', 'beckaye@.sn', 'Angle toucouleur', 'angle toucouleur', 2, 'dibi_express.jpg', NULL, NULL, '00:00:00', '23:59:59', 15, 25, 800, 1000, 4.8, 203, 0, 'actif', '2026-04-18 23:26:55', 5),
(5, 'Resto Merveille', 'Fast-food local avec une touche kaolackoise. Burgers, sandwiches et tacos revisites.', '+221 77 567 89 01', 'merveille@.sn', 'leona', 'leona', 3, 'sangle_sandwich.jpg', NULL, NULL, '00:00:00', '23:59:59', 15, 30, 1000, 100, 4.2, 67, 1, 'actif', '2026-04-18 23:26:55', 6),
(6, 'Alimentation Keur Baye', 'Basé à Kaolack, notre service de repas vous propose des plats délicieux, préparés avec soin et à base d’ingrédients frais. Nous mettons à votre disposition une variété de spécialités sénégalaises et de repas rapides, accessibles à tous.', '+221 77 678 90 12', 'keurbaye@.sn', 'Medina Baye', 'medina', 4, 'pain_dore.jpg', NULL, NULL, '00:00:00', '23:59:59', 10, 20, 500, 1000, 4.4, 234, 0, 'actif', '2026-04-18 23:26:55', 7),
(8, 'Restaurant Test', NULL, '770000000', 'test@resto.sn', 'Rue principale', 'Centre ville', 1, NULL, NULL, NULL, '08:00:00', '22:00:00', 20, 45, 1000, 3000, 4.5, 0, 0, '', '2026-04-19 17:51:04', NULL),
(9, 'Restaurant Demo', NULL, '771234567', 'demo@resto.sn', 'Avenue principale', 'Centre ville', 1, NULL, NULL, NULL, '08:00:00', '22:00:00', 20, 45, 1000, 3000, 4.5, 0, 0, '', '2026-04-19 18:03:01', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `quartier` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','restaurant','admin') DEFAULT 'client',
  `statut` enum('actif','inactif','suspendu') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `telephone`, `adresse`, `quartier`, `password`, `role`, `statut`, `created_at`) VALUES
(1, 'Admin', 'System', 'admin@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif', '2026-04-18 23:26:55'),
(2, 'Belle Vue', 'Manager', 'bellevue@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$hycFbgGFalHXb8DZSJvWDOoh4DY/EThsmqGkUPOetqthIFmI0vm7u', 'restaurant', 'actif', '2026-04-19 03:06:46'),
(3, 'Rof-Top', 'Manager', 'roftop@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$.itE8USnYYhrfBtlcw3PCePzGjxyIMz49H3LyzOESwiOWxHLK8tl2', 'restaurant', 'actif', '2026-04-19 03:06:46'),
(4, 'La Farisse', 'Manager', 'lafaris@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$.itE8USnYYhrfBtlcw3PCePzGjxyIMz49H3LyzOESwiOWxHLK8tl2', 'restaurant', 'actif', '2026-04-19 03:06:46'),
(5, 'Beckaye', 'Manager', 'bekaye@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$RaEG9q4IzE33S/JbJWyLJeJy4nmygqrCn5YQoUguFiwk.utU.vhfu', 'restaurant', 'actif', '2026-04-19 03:06:46'),
(6, 'Resto Merveille', 'Manager', 'merveille@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$.itE8USnYYhrfBtlcw3PCePzGjxyIMz49H3LyzOESwiOWxHLK8tl2', 'restaurant', 'actif', '2026-04-19 03:06:46'),
(7, 'Keur Baye', 'Manager', 'keurbaye@saveurkaolack.sn', '+221 78 521 65 68', NULL, NULL, '$2y$10$.itE8USnYYhrfBtlcw3PCePzGjxyIMz49H3LyzOESwiOWxHLK8tl2', 'restaurant', 'actif', '2026-04-19 03:06:46'),
(9, 'Diallo', 'Fatou', 'client@test.sn', '77 987 65 43', 'Médina, Kaolack', NULL, '$2y$10$t0Qdbjb.EVDQaauuSYtiY.9Yvad/cJJ/.Piz.PkUImouPJeW9uKwO', 'client', 'actif', '2026-04-21 15:21:40'),
(10, 'sy', 'mouhamed', 'mouhamedsy3002@gmail.com', '785216568', NULL, NULL, '$2y$12$QZglSoF65eZEbBgvVrcMOOLyd8qvi9nTjSb9UMZ0QgVi1i7KRhe6m', 'client', 'actif', '2026-04-30 14:58:24');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avis_commande_fk` (`commande_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `numero_tracking` (`numero_tracking`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Index pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `plat_id` (`plat_id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `token_index` (`token`);

--
-- Index pour la table `plats`
--
ALTER TABLE `plats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Index pour la table `plat_photos`
--
ALTER TABLE `plat_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plat_id` (`plat_id`),
  ADD KEY `idx_ordre` (`ordre`);

--
-- Index pour la table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `commande_details`
--
ALTER TABLE `commande_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `plats`
--
ALTER TABLE `plats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT pour la table `plat_photos`
--
ALTER TABLE `plat_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_commande_fk` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`);

--
-- Contraintes pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD CONSTRAINT `commande_details_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_details_ibfk_2` FOREIGN KEY (`plat_id`) REFERENCES `plats` (`id`);

--
-- Contraintes pour la table `plats`
--
ALTER TABLE `plats`
  ADD CONSTRAINT `plats_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `plat_photos`
--
ALTER TABLE `plat_photos`
  ADD CONSTRAINT `plat_photos_ibfk_1` FOREIGN KEY (`plat_id`) REFERENCES `plats` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `restaurants_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
