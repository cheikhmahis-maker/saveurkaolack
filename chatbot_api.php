<?php
/**
 * CHATBOT_API.PHP - API pour l'assistant virtuel
 * Répond aux questions basées sur les données du site
 */

session_start();
header('Content-Type: application/json');

// Récupérer le message
$input = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));

// Initialiser l'historique des conversations si pas existant
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

if (empty($message)) {
    $reponse = 'Bonjour ! Comment puis-je vous aider ?';
    // Sauvegarder dans l'historique
    $_SESSION['chat_history'][] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $input['message'] ?? '',
        'bot' => $reponse
    ];

    // Limiter l'historique aux 20 derniers messages
    if (count($_SESSION['chat_history']) > 20) {
        $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
    }
    echo json_encode(['reply' => $reponse]);
    exit;
}

// Connexion BDD
try {
    $pdo = new PDO('mysql:host=localhost;dbname=saveur_kaolack;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['reply' => 'Désolé, je ne peux pas accéder aux informations pour le moment.']);
    exit;
}

$reponse = '';

// === SALUTATIONS ===
if (preg_match('/\b(bonjour|salut|hello|hey|coucou|bonsoir)\b/', $message)) {
    $reponse = "Bonjour ! 🌟 Je suis votre assistant Saveur Kaolack.\n\nJe peux vous aider à :\n• Trouver un restaurant\n• Découvrir des plats\n• Répondre à vos questions\n• Suivre votre commande\n\nQue souhaitez-vous faire ?";
}

// === AIDE / QUESTIONS ===
elseif (preg_match('/\b(aide|help|comment|peux-tu|que fais|qui es-tu)\b/', $message)) {
    $reponse = "Je suis l'assistant virtuel de Saveur Kaolack ! 🤖\n\nVoici ce que je peux faire :\n\n🍽️ **Culinaire**\n• 'J'ai envie de poulet' → Plats au poulet\n• 'Qu'est-ce qui est épicé ?' → Plats épicés\n• 'Menu pour 2 personnes' → Suggestions\n\n🏪 **Restaurants**\n• 'Restaurants ouverts' → Liste actuelle\n• 'Quartier Médina' → Restos du quartier\n\n📦 **Commandes**\n• 'Suivre ma commande' → Page de suivi\n• 'Temps de livraison' → Informations\n\n💰 **Budget**\n• 'Plats à moins de 3000F' → Filtre par prix";
}

// === POULET ===
elseif (preg_match('/\b(poulet|chicken|yassa)\b/', $message)) {
    $stmt = $pdo->query("
        SELECT p.id as plat_id, p.nom, p.prix, r.id as restaurant_id, r.nom as restaurant, p.description
        FROM plats p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE (p.nom LIKE '%poulet%' OR p.nom LIKE '%yassa%' OR p.description LIKE '%poulet%')
        AND p.disponible = 1 AND r.statut = 'actif'
        ORDER BY p.prix
        LIMIT 5
    ");
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($plats) {
        $reponse = "🍗 **Plats au poulet disponibles :**\n\n";
        foreach ($plats as $p) {
            $reponse .= "• **{$p['nom']}**\n";
            $reponse .= "  📍 [Voir chez {$p['restaurant']}](restaurant.php?id={$p['restaurant_id']})\n";
            $reponse .= "  💰 " . number_format($p['prix'], 0, ',', ' ') . " F\n";
            $reponse .= "  🛒 [Ajouter au panier](panier.php?add={$p['plat_id']})\n";
            if ($p['description']) {
                $reponse .= "  📝 " . substr($p['description'], 0, 40) . "...\n";
            }
            $reponse .= "\n";
        }
        $reponse .= "Cliquez sur les liens pour voir le restaurant ou ajouter au panier ! 🛒";
    } else {
        $reponse = "Désolé, aucun plat au poulet n'est disponible actuellement. Essayez 'poisson' ou 'viande' ! 🍽️";
    }
}

// === POISSON ===
elseif (preg_match('/\b(poisson|poissons|thieb|thiebou|filet|merlu|sole)\b/', $message)) {
    $stmt = $pdo->query("
        SELECT p.id as plat_id, p.nom, p.prix, r.id as restaurant_id, r.nom as restaurant
        FROM plats p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE (p.nom LIKE '%poisson%' OR p.nom LIKE '%thieb%' OR p.nom LIKE '%thiebou%' OR p.categorie = 'Poissons')
        AND p.disponible = 1 AND r.statut = 'actif'
        ORDER BY p.prix
        LIMIT 5
    ");
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($plats) {
        $reponse = "🐟 **Plats au poisson disponibles :**\n\n";
        foreach ($plats as $p) {
            $reponse .= "• **{$p['nom']}**\n";
            $reponse .= "  📍 [Voir chez {$p['restaurant']}](restaurant.php?id={$p['restaurant_id']})\n";
            $reponse .= "  💰 " . number_format($p['prix'], 0, ',', ' ') . " F\n";
            $reponse .= "  🛒 [Ajouter au panier](panier.php?add={$p['plat_id']})\n\n";
        }
    } else {
        $reponse = "Pas de poisson disponible actuellement. 🎣";
    }
}

// === ÉPICÉ / PIMENTÉ ===
elseif (preg_match('/\b(épicé|epice|piment|pimenté|fort)\b/', $message)) {
    $stmt = $pdo->query("
        SELECT p.id as plat_id, p.nom, p.prix, r.id as restaurant_id, r.nom as restaurant
        FROM plats p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE (p.nom LIKE '%yassa%' OR p.nom LIKE '%mala%' OR p.nom LIKE '%saka%' OR p.description LIKE '%piment%' OR p.description LIKE '%épicé%')
        AND p.disponible = 1 AND r.statut = 'actif'
        LIMIT 5
    ");
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($plats) {
        $reponse = "🌶️ **Plats épicés recommandés :**\n\n";
        foreach ($plats as $p) {
            $reponse .= "• **{$p['nom']}** - 🔥 Épicé\n";
            $reponse .= "  📍 [Voir chez {$p['restaurant']}](restaurant.php?id={$p['restaurant_id']})\n";
            $reponse .= "  💰 " . number_format($p['prix'], 0, ',', ' ') . " F\n";
            $reponse .= "  🛒 [Ajouter au panier](panier.php?add={$p['plat_id']})\n\n";
        }
        $reponse .= "Attention : certains peuvent être très pimentés ! 🥵";
    } else {
        $reponse = "Je n'ai pas trouvé de plats épicés. Essayez de chercher 'Yassa' ! 🌶️";
    }
}

// === BUDGET / PRIX ===
elseif (preg_match('/\b(moins de|pas cher|budget|([0-9]+).*[fFcC])\b/', $message, $matches)) {
    $budget = isset($matches[2]) ? intval($matches[2]) : 3000;
    
    $stmt = $pdo->prepare("
        SELECT p.id as plat_id, p.nom, p.prix, r.id as restaurant_id, r.nom as restaurant
        FROM plats p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE p.prix <= ? AND p.disponible = 1 AND r.statut = 'actif'
        ORDER BY p.prix DESC
        LIMIT 5
    ");
    $stmt->execute([$budget]);
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($plats) {
        $reponse = "💰 **Plats à moins de " . number_format($budget, 0, ',', ' ') . " F :**\n\n";
        foreach ($plats as $p) {
            $reponse .= "• **{$p['nom']}**\n";
            $reponse .= "  📍 [Voir chez {$p['restaurant']}](restaurant.php?id={$p['restaurant_id']})\n";
            $reponse .= "  💵 " . number_format($p['prix'], 0, ',', ' ') . " F\n";
            $reponse .= "  🛒 [Ajouter au panier](panier.php?add={$p['plat_id']})\n\n";
        }
    } else {
        $reponse = "Aucun plat trouvé sous ce budget. Augmentez un peu ! 💸";
    }
}

// === RESTAURANTS OUVERTS ===
elseif (preg_match('/\b(restaurant|restos|ouvert|ouvré|disponible)\b/', $message)) {
    $stmt = $pdo->query("
        SELECT nom, quartier, delai_livraison_min, delai_livraison_max, frais_livraison
        FROM restaurants
        WHERE statut = 'actif'
        ORDER BY nom
        LIMIT 8
    ");
    $restos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($restos) {
        $reponse = "🏪 **Restaurants actuellement ouverts :**\n\n";
        foreach ($restos as $r) {
            $reponse .= "• **{$r['nom']}**\n";
            $reponse .= "  📍 {$r['quartier']}\n";
            $reponse .= "  ⏱️ {$r['delai_livraison_min']}-{$r['delai_livraison_max']} min\n";
            $reponse .= "  🚚 Livraison: " . number_format($r['frais_livraison'], 0, ',', ' ') . " F\n\n";
        }
    } else {
        $reponse = "Aucun restaurant ouvert actuellement. 😔";
    }
}

// === QUARTIER ===
elseif (preg_match('/\b(médina|medina|quartier|zone)\b/', $message)) {
    $stmt = $pdo->prepare("
        SELECT nom, quartier, categorie_id
        FROM restaurants
        WHERE quartier LIKE ? AND statut = 'actif'
    ");
    $stmt->execute(['%' . (strpos($message, 'médina') !== false || strpos($message, 'medina') !== false ? 'Médina' : '') . '%']);
    $restos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($restos) {
        $reponse = "📍 **Restaurants à Médina :**\n\n";
        foreach ($restos as $r) {
            $reponse .= "• **{$r['nom']}**\n";
        }
    } else {
        $reponse = "Aucun restaurant trouvé dans ce quartier. Essayez un autre ! 🗺️";
    }
}

// === SUIVI COMMANDE ===
elseif (preg_match('/\b(suivre|commande|numéro|code|tracking|où est)\b/', $message)) {
    $reponse = "📦 **Suivi de commande**\n\n";
    $reponse .= "Pour suivre votre commande, vous avez besoin de votre numéro de suivi.\n\n";
    $reponse .= "Le format est : **SK-XXXXXX-XXXX**\n\n";
    $reponse .= "👉 [Cliquez ici pour suivre votre commande](suivi.php)\n\n";
    $reponse .= "Si vous n'avez pas votre numéro, vérifiez votre SMS ou email de confirmation. 📱";
}

// === TEMPS DE LIVRAISON ===
elseif (preg_match('/\b(temps|livraison|délai|duree|durée|minute|heure|combien de temps)\b/', $message)) {
    $stmt = $pdo->query("SELECT AVG(delai_livraison_min) as min_avg, AVG(delai_livraison_max) as max_avg FROM restaurants WHERE statut = 'actif'");
    $delai = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $reponse = "🚚 **Temps de livraison**\n\n";
    $reponse .= "En moyenne à Kaolack :\n";
    $reponse .= "⏱️ **" . round($delai['min_avg']) . " - " . round($delai['max_avg']) . " minutes**\n\n";
    $reponse .= "Cela dépend du :\n";
    $reponse .= "• Restaurant choisi\n";
    $reponse .= "• Votre quartier (Médina, Niary Taly, etc.)\n";
    $reponse .= "• Trafic et disponibilité\n\n";
    $reponse .= "Vous recevrez une notification quand le livreur part ! 📱";
}

// === PAIEMENT ===
elseif (preg_match('/\b(payer|paiement|carte|espèce|wave|orange|paye)\b/', $message)) {
    $reponse = "💳 **Modes de paiement**\n\n";
    $reponse .= "Nous acceptons :\n";
    $reponse .= "💵 **Espèces** à la livraison\n";
    $reponse .= "📱 **Wave** - Paiement mobile\n";
    $reponse .= "📱 **Orange Money**\n";
    $reponse .= "💳 **Carte bancaire** (bientôt)\n\n";
    $reponse .= "Le paiement se fait au moment de la livraison. Sécurisé et pratique ! 🔒";
}

// === MENU / SUGGESTION ===
elseif (preg_match('/\b(menu|suggestion|suggère|recommande|quoi manger|je veux manger)\b/', $message)) {
    $stmt = $pdo->query("
        SELECT p.id as plat_id, p.nom, p.prix, r.id as restaurant_id, r.nom as restaurant, p.est_populaire
        FROM plats p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE p.disponible = 1 AND r.statut = 'actif'
        ORDER BY p.est_populaire DESC, RAND()
        LIMIT 5
    ");
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $reponse = "🍽️ **Suggestions du moment :**\n\n";
    foreach ($plats as $i => $p) {
        $icon = $p['est_populaire'] ? '⭐' : '🍽️';
        $reponse .= "$icon **{$p['nom']}**\n";
        $reponse .= "   📍 [Voir chez {$p['restaurant']}](restaurant.php?id={$p['restaurant_id']})\n";
        $reponse .= "   💰 " . number_format($p['prix'], 0, ',', ' ') . " F\n";
        $reponse .= "   🛒 [Ajouter au panier](panier.php?add={$p['plat_id']})\n\n";
    }
    $reponse .= "Cliquez sur les liens pour commander ! 😋";
}

// === BEST SELLERS / POPULAIRE ===
elseif (preg_match('/\b(populaire|best|tendance|top|favori|aimé)\b/', $message)) {
    $stmt = $pdo->query("
        SELECT p.id as plat_id, p.nom, p.prix, r.id as restaurant_id, r.nom as restaurant
        FROM plats p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE p.est_populaire = 1 AND p.disponible = 1 AND r.statut = 'actif'
        ORDER BY p.prix
        LIMIT 5
    ");
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($plats) {
        $reponse = "🔥 **Best-sellers de la semaine :**\n\n";
        foreach ($plats as $p) {
            $reponse .= "⭐ **{$p['nom']}**\n";
            $reponse .= "   📍 [Voir chez {$p['restaurant']}](restaurant.php?id={$p['restaurant_id']})\n";
            $reponse .= "   💰 " . number_format($p['prix'], 0, ',', ' ') . " F\n";
            $reponse .= "   🛒 [Ajouter au panier](panier.php?add={$p['plat_id']})\n\n";
        }
    } else {
        $reponse = "Les clients adorent la **Thieboudienne** et le **Yassa Poulet** ! 🔥";
    }
}

// === CONTACT / AIDE HUMAINE ===
elseif (preg_match('/\b(contact|téléphone|appeler|humain|opérateur|service client)\b/', $message)) {
    $reponse = "📞 **Besoin d'aide ?**\n\n";
    $reponse .= "Notre équipe est disponible :\n";
    $reponse .= "📱 **33 XXX XX XX**\n";
    $reponse .= "⏰ Lundi-Dimanche : 11h - 23h\n\n";
    $reponse .= "Ou envoyez un email à : contact@saveurkaolack.sn\n\n";
    $reponse .= "Je reste là si vous avez d'autres questions ! 😊";
}

// === SUIVI DE COMMANDE ===
elseif (preg_match('/\b(suivi|tracking|où.*commande|ma commande|numéro|statut.*commande|commande #?SK)\b/i', $message)) {
    // Chercher un numéro de tracking dans le message
    preg_match('/SK?-?\d{6,}/i', $message, $matches);
    
    if (!empty($matches)) {
        $tracking = strtoupper($matches[0]);
        // Normaliser le format
        if (preg_match('/^SK\d{6}$/', $tracking)) {
            $tracking = 'SK-' . substr($tracking, 2);
        }
        
        // Rechercher dans la base
        $stmt = $pdo->prepare("SELECT c.*, r.nom as restaurant_nom 
                              FROM commandes c 
                              LEFT JOIN restaurants r ON c.restaurant_id = r.id 
                              WHERE c.numero_tracking = ?");
        $stmt->execute([$tracking]);
        $commande = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($commande) {
            $statut_emoji = [
                'en_attente' => '⏳',
                'preparation' => '👨‍🍳',
                'en_livraison' => '🚗',
                'livree' => '✅',
                'annulee' => '❌'
            ];
            $emoji = $statut_emoji[$commande['statut']] ?? '📦';
            
            $reponse = "🔍 **Suivi Commande #{$tracking}**\n\n";
            $reponse .= "{$emoji} **Statut :** " . ucfirst(str_replace('_', ' ', $commande['statut'])) . "\n";
            $reponse .= "🍽️ **Restaurant :** {$commande['restaurant_nom']}\n";
            $reponse .= "💰 **Total :** " . number_format($commande['total'], 0, ',', ' ') . " FCFA\n";
            $reponse .= "📅 **Date :** " . date('d/m/Y H:i', strtotime($commande['created_at'])) . "\n\n";
            $reponse .= "📱 [Voir les détails complets](suivi.php?track={$tracking})";
        } else {
            $reponse = "❌ Commande **#{$tracking}** non trouvée.\n\n";
            $reponse .= "Vérifiez votre numéro de tracking.\n";
            $reponse .= "Format attendu : **SK-123456**\n\n";
            $reponse .= "📱 [Page de suivi](suivi.php)";
        }
    } else {
        $reponse = "🔍 **Suivi de commande**\n\n";
        $reponse .= "Donnez-moi votre numéro de tracking (ex: **SK-123456**)\n\n";
        $reponse .= "Ou allez sur la [page de suivi](suivi.php)";
    }
}

// === AU REVOIR ===
elseif (preg_match('/\b(au revoir|bye|ciao|merci|à plus)\b/', $message)) {
    $reponses = [
        "Au revoir ! 👋 Bon appétit et à bientôt sur Saveur Kaolack !",
        "Merci ! 🌟 Profitez de votre repas !",
        "À la prochaine ! 🍽️ Passez une bonne journée !"
    ];
    $reponse = $reponses[array_rand($reponses)];
}

// === RÉPONSE PAR DÉFAUT ===
else {
    $reponses_defaut = [
        "Je n'ai pas bien compris... 🤔\n\nEssayez :\n• 'Poulet' pour les plats au poulet\n• 'Restaurants ouverts' pour la liste\n• 'Moins de 3000F' pour votre budget\n• 'Aide' pour voir tout ce que je peux faire",
        "Hmm, je ne suis pas sûr de comprendre. 😅\n\nVous pouvez me demander :\n• Des suggestions de plats\n• Les restaurants disponibles\n• Le temps de livraison\n• Ou tapez 'aide' pour la liste complète"
    ];
    $reponse = $reponses_defaut[array_rand($reponses_defaut)];
}

echo json_encode(['reply' => $reponse]);
