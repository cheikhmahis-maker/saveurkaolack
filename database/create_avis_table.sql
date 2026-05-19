-- Création de la table 'avis' pour les avis clients
-- Saveur Kaolack

CREATE TABLE IF NOT EXISTS `avis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `note` tinyint(1) NOT NULL COMMENT 'Note de 1 à 5 étoiles',
  `commentaire` text DEFAULT NULL,
  `statut` enum('en_attente','approuve','rejete') DEFAULT 'en_attente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `avis_utilisateur_fk` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avis_restaurant_fk` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de test (optionnel)
-- INSERT INTO `avis` (`utilisateur_id`, `restaurant_id`, `note`, `commentaire`, `statut`) VALUES
-- (1, 1, 5, 'Excellent restaurant, je recommande !', 'approuve'),
-- (2, 1, 4, 'Très bon, mais livraison un peu longue', 'en_attente'),
-- (3, 2, 5, 'Le meilleur thieboudienne de Kaolack', 'approuve');
