<?php
/**
 * CHATBOT_API.PHP - Assistant virtuel propulsé par Claude AI (Anthropic)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// === RATE LIMITING : 10 messages par minute par IP ===
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$cleRate = 'chatbot_rate_' . md5($ip);

if (!isset($_SESSION[$cleRate]) || (time() - $_SESSION[$cleRate]['debut']) >= 60) {
    $_SESSION[$cleRate] = ['count' => 0, 'debut' => time()];
}
if ($_SESSION[$cleRate]['count'] >= 10) {
    echo json_encode(['reply' => 'Trop de messages envoyés. Attendez une minute avant de continuer. ⏳']);
    exit;
}
$_SESSION[$cleRate]['count']++;

// === RÉCUPÉRER ET VALIDER LE MESSAGE ===
$input = json_decode(file_get_contents('php://input'), true);
$messageOriginal = trim($input['message'] ?? '');

if (mb_strlen($messageOriginal) > 300) {
    echo json_encode(['reply' => 'Votre message est trop long. Essayez en moins de 300 caractères. 😊']);
    exit;
}

// Initialiser la session
if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];

if (empty($messageOriginal)) {
    $rep = 'Bonjour ! Comment puis-je vous aider ?';
    sauvegarderHistorique($messageOriginal, $rep);
    echo json_encode(['reply' => $rep]);
    exit;
}

// === CONNEXION BDD ===
try {
    $pdo = getDB();
    assurerSchemaEssai($pdo);
} catch (PDOException $e) {
    echo json_encode(['reply' => 'Désolé, je ne peux pas accéder aux informations pour le moment.']);
    exit;
}

// ================================================================
// FONCTIONS
// ================================================================

function sauvegarderHistorique(string $user, string $bot): void {
    if (!empty($user)) {
        $_SESSION['chat_history'][] = ['role' => 'user',      'content' => $user];
        $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $bot];
    }
    // Garder les 10 derniers échanges (20 entrées)
    if (count($_SESSION['chat_history']) > 20) {
        $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
    }
}

function construireContexteBDD(PDO $pdo): string {
    $ctx = '';

    try {
        $stmt = $pdo->prepare("
            SELECT id, nom, quartier, delai_livraison_min, delai_livraison_max
            FROM restaurants
            WHERE statut = 'actif'
              AND (essai_debut IS NULL OR DATE_ADD(essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (abonnement_jusquau IS NOT NULL AND abonnement_jusquau >= CURDATE()))
            ORDER BY nom LIMIT 10
        ");
        $stmt->execute();
        $restos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($restos) {
            $ctx .= "\n### Restaurants actifs :\n";
            foreach ($restos as $r) {
                $ctx .= "- **{$r['nom']}** — Quartier {$r['quartier']}, livraison {$r['delai_livraison_min']}-{$r['delai_livraison_max']} min → [menu](restaurant.php?id={$r['id']})\n";
            }
        }
    } catch (PDOException $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT p.id as plat_id, p.nom, p.prix, p.categorie, r.nom as restaurant, r.id as restaurant_id
            FROM plats p
            JOIN restaurants r ON p.restaurant_id = r.id
            WHERE p.disponible = 1 AND r.statut = 'actif'
              AND (r.essai_debut IS NULL OR DATE_ADD(r.essai_debut, INTERVAL 45 DAY) >= CURDATE() OR (r.abonnement_jusquau IS NOT NULL AND r.abonnement_jusquau >= CURDATE()))
            ORDER BY p.est_populaire DESC, p.prix ASC
            LIMIT 20
        ");
        $stmt->execute();
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($plats) {
            $ctx .= "\n### Plats disponibles :\n";
            foreach ($plats as $p) {
                $ctx .= "- **{$p['nom']}** ({$p['categorie']}) chez {$p['restaurant']} — {$p['prix']} FCFA"
                      . " → [commander](panier.php?add={$p['plat_id']})"
                      . " | [resto](restaurant.php?id={$p['restaurant_id']})\n";
            }
        }
    } catch (PDOException $e) {}

    return $ctx;
}

function callClaudeAPI(string $message, array $history, string $contexteBDD): string {
    $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';

    if (empty($apiKey) || str_starts_with($apiKey, 'sk-ant-VOTRE')) {
        return "Je suis en cours de configuration. En attendant, appelez-nous au " . SITE_TEL . " ou écrivez à " . SITE_EMAIL . " ! 📞";
    }

    $siteTel = SITE_TEL;
    $siteEmail = SITE_EMAIL;
    $systemPrompt = <<<SYSTEM
Tu es l'assistant virtuel de **Saveur Kaolack**, une plateforme de livraison de repas à Kaolack, Sénégal.
Tu réponds en français naturel (ou en Wolof si l'utilisateur écrit en Wolof).
Tu es chaleureux, concis, et tu utilises des emojis avec modération.

## Tes missions
1. Aider à trouver des plats par type (poulet, poisson, mafé, benachin…), budget ou préférence
2. Présenter les restaurants disponibles et leur menu
3. Aider au suivi de commande (format : SK-XXXXXX-XXXX) → [page suivi](suivi.php)
4. Informer sur les modes de paiement : espèces, Wave, Orange Money (tous à la livraison)
5. Donner les délais de livraison

## Règles de formatage IMPORTANTES
- Liens TOUJOURS en Markdown relatif : [texte](url-relative)
  Exemples : [Voir le menu](restaurant.php?id=5) | [Commander](panier.php?add=12) | [Suivi](suivi.php)
- Noms de plats et restaurants en **gras**
- Réponses courtes (max 150 mots)
- Listes de plats : 5 maximum

## Contact
- Tél : {$siteTel} | Email : {$siteEmail}
- Horaires : 7j/7, 11h–23h

## Données en temps réel
$contexteBDD

Si l'utilisateur demande le suivi d'une commande sans numéro, demande le format SK-XXXXXX-XXXX ou renvoie vers [la page de suivi](suivi.php).
Si tu ne sais pas quelque chose, dis-le honnêtement et propose une alternative.
SYSTEM;

    // Construire l'historique au format Claude (paires user/assistant valides)
    $messages = [];
    foreach ($history as $msg) {
        if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant'], true)) {
            $messages[] = ['role' => $msg['role'], 'content' => (string) $msg['content']];
        }
    }
    // Le premier message doit être de l'utilisateur
    while (!empty($messages) && $messages[0]['role'] !== 'user') {
        array_shift($messages);
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5',
        'max_tokens' => 512,
        'system'     => $systemPrompt,
        'messages'   => $messages,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT        => 20,
        // En développement XAMPP, désactiver la vérification SSL si nécessaire
        CURLOPT_SSL_VERIFYPEER => (defined('ENVIRONMENT') && ENVIRONMENT === 'production'),
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $raw === false) {
        return "Désolé, problème de connexion au service IA. Réessayez dans un instant ! 🛠️";
    }

    if ($httpCode !== 200) {
        return "Je ne peux pas répondre pour le moment. Appelez-nous au +221 78 521 65 68 ! 📞";
    }

    $data = json_decode($raw, true);

    return $data['content'][0]['text']
        ?? "Je n'ai pas pu générer de réponse. Tapez 'aide' ou appelez le +221 78 521 65 68 ! 😊";
}

// ================================================================
// TRAITEMENT PRINCIPAL
// ================================================================

$contexteBDD = construireContexteBDD($pdo);

// Récupérer et valider l'historique (format Claude : role + content)
$historique = [];
foreach ($_SESSION['chat_history'] as $msg) {
    if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant'], true)) {
        $historique[] = $msg;
    }
}
// Limiter aux 8 derniers échanges (16 messages) pour économiser les tokens
if (count($historique) > 16) {
    $historique = array_slice($historique, -16);
}

$reponse = callClaudeAPI($messageOriginal, $historique, $contexteBDD);

sauvegarderHistorique($messageOriginal, $reponse);
echo json_encode(['reply' => $reponse]);
