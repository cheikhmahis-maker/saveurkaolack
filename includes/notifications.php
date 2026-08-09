<?php
/**
 * NOTIFICATIONS.PHP — Notifications restaurant : email + Telegram
 */

if (!defined('BASE_URL')) {
    die('Erreur : config.php doit être inclus avant notifications.php');
}

/**
 * Ajoute les colonnes Telegram à la table restaurants si absentes
 */
function assurerSchemaTelegram(PDO $pdo): void {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'telegram_bot_token'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN telegram_bot_token VARCHAR(200) DEFAULT NULL AFTER wave_api_key");
        }
        $cols2 = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'telegram_chat_id'")->fetchAll();
        if (empty($cols2)) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN telegram_chat_id VARCHAR(100) DEFAULT NULL AFTER telegram_bot_token");
        }
    } catch (PDOException) {
        // Non bloquant — colonnes peut-être déjà présentes
    }
}

/**
 * Envoie un email de notification de nouvelle commande au restaurant
 */
function envoyerEmailRestaurant(
    array  $restaurant,
    string $prenom,
    string $telephone,
    string $adresse,
    string $quartier,
    string $notes,
    float  $total,
    string $mode_paiement,
    string $numero_tracking,
    array  $items
): bool {
    $email_resto = $restaurant['email'] ?? '';
    if (empty($email_resto) || !filter_var($email_resto, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $nom_resto = $restaurant['nom'] ?? 'Restaurant';

    $lignes_plats = '';
    foreach ($items as $item) {
        $sous_total    = number_format(($item['prix'] ?? 0) * ($item['quantite'] ?? 1), 0, ',', ' ');
        $nom_plat      = htmlspecialchars($item['nom'] ?? '');
        $lignes_plats .= "<tr>
            <td style='padding:7px 12px;border-bottom:1px solid #f0e8df;'>{$nom_plat}</td>
            <td style='padding:7px 12px;border-bottom:1px solid #f0e8df;text-align:center;'>" . intval($item['quantite'] ?? 1) . "</td>
            <td style='padding:7px 12px;border-bottom:1px solid #f0e8df;text-align:right;'>{$sous_total} F</td>
        </tr>";
    }

    $total_fmt      = number_format($total, 0, ',', ' ');
    $paiement_label = ($mode_paiement === 'wave') ? 'Wave (paiement en ligne)' : 'Espèces à la livraison';
    $quartier_txt   = $quartier ? " — " . htmlspecialchars($quartier) : '';
    $notes_html     = $notes
        ? "<p style='background:#fff8e1;padding:8px 12px;border-left:3px solid #f59e0b;border-radius:4px;margin:12px 0 0;'>💬 <strong>Note client :</strong> " . htmlspecialchars($notes) . "</p>"
        : '';
    $url_dashboard  = BASE_URL . 'commandes.php';
    $sujet          = "🔔 Nouvelle commande #{$numero_tracking} — " . htmlspecialchars($nom_resto);

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:0;background:#f9f5f0;">
<div style="max-width:600px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e8d5c4;">

    <div style="background:#c0392b;padding:20px 24px;">
        <h2 style="margin:0;color:#fff;font-size:22px;">🔔 Nouvelle commande !</h2>
        <p style="margin:4px 0 0;color:#fff;opacity:.85;font-size:14px;">#{$numero_tracking} — {$nom_resto}</p>
    </div>

    <div style="padding:24px;">
        <h3 style="margin:0 0 10px;font-size:16px;color:#7f1d0b;border-bottom:1px solid #f0e8df;padding-bottom:8px;">👤 Client</h3>
        <p style="margin:6px 0;font-size:18px;font-weight:bold;">{$prenom}</p>
        <p style="margin:6px 0;">
            <a href="tel:{$telephone}" style="color:#c0392b;text-decoration:none;font-size:22px;font-weight:bold;letter-spacing:1px;">
                📞 {$telephone}
            </a>
        </p>
        <p style="margin:6px 0;color:#555;">📍 {$adresse}{$quartier_txt}</p>
        {$notes_html}

        <h3 style="margin:24px 0 10px;font-size:16px;color:#7f1d0b;border-bottom:1px solid #f0e8df;padding-bottom:8px;">🛒 Commande</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f9f5f0;">
                    <th style="padding:8px 12px;text-align:left;color:#7f1d0b;font-weight:600;">Plat</th>
                    <th style="padding:8px 12px;text-align:center;color:#7f1d0b;font-weight:600;">Qté</th>
                    <th style="padding:8px 12px;text-align:right;color:#7f1d0b;font-weight:600;">Sous-total</th>
                </tr>
            </thead>
            <tbody>{$lignes_plats}</tbody>
        </table>

        <div style="text-align:right;margin-top:12px;padding:14px 16px;background:#fff8f5;border-radius:8px;border:1px solid #f0d0c0;">
            <div style="font-size:22px;font-weight:bold;color:#c0392b;">{$total_fmt} FCFA</div>
            <div style="font-size:13px;color:#888;margin-top:2px;">💳 Paiement : {$paiement_label}</div>
        </div>

        <div style="text-align:center;margin:28px 0 0;">
            <a href="{$url_dashboard}" style="background:#c0392b;color:#fff;padding:14px 36px;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;display:inline-block;">
                Gérer la commande →
            </a>
        </div>
    </div>

    <div style="padding:12px 24px;background:#f9f5f0;border-top:1px solid #f0e8df;text-align:center;font-size:12px;color:#aaa;">
        Saveur Kaolack — Notification automatique
    </div>
</div>
</body>
</html>
HTML;

    $phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailerPath)) {
        try {
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
            $mail             = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('SMTP_EMAIL')    ? SMTP_EMAIL    : '';
            $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('noreply@saveurkaolack.sn', 'Saveur Kaolack');
            $mail->addAddress($email_resto, $nom_resto);
            $mail->Subject    = $sujet;
            $mail->isHTML(true);
            $mail->Body       = $html;
            return $mail->send();
        } catch (Exception $e) {
            error_log("Email restaurant erreur: " . $e->getMessage());
        }
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Saveur Kaolack <noreply@saveurkaolack.sn>\r\n";
    return mail($email_resto, $sujet, $html, $headers);
}

/**
 * Envoie un email de confirmation de demande de partenariat au restaurant
 */
function envoyerEmailConfirmationPartenaire(
    string $nom_resto,
    string $proprietaire,
    string $email_resto
): bool {
    if (empty($email_resto) || !filter_var($email_resto, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $nom_safe   = htmlspecialchars($nom_resto);
    $prop_safe  = htmlspecialchars($proprietaire);

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:0;background:#f9f5f0;">
<div style="max-width:600px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e8d5c4;">

    <div style="background:#c0392b;padding:20px 24px;">
        <h2 style="margin:0;color:#fff;font-size:22px;">Saveur Kaolack</h2>
        <p style="margin:4px 0 0;color:#fff;opacity:.85;font-size:14px;">Demande de partenariat reçue</p>
    </div>

    <div style="padding:24px;">
        <p style="font-size:16px;">Bonjour <strong>{$prop_safe}</strong>,</p>
        <p>Nous avons bien reçu votre demande de partenariat pour <strong>{$nom_safe}</strong>.</p>

        <div style="background:#fff8f5;border-left:4px solid #c0392b;padding:14px 18px;border-radius:0 8px 8px 0;margin:20px 0;">
            <p style="margin:0;font-size:15px;font-weight:bold;color:#c0392b;">Et maintenant ?</p>
            <p style="margin:8px 0 0;color:#555;">Notre équipe va examiner votre dossier. Vous recevrez vos identifiants de connexion par email dès que votre restaurant sera validé, généralement sous 24h.</p>
        </div>

        <p style="color:#555;">Si vous avez des questions, n'hésitez pas à nous contacter.</p>
        <p style="color:#555;margin-bottom:0;">L'équipe <strong>Saveur Kaolack</strong></p>
    </div>

    <div style="padding:12px 24px;background:#f9f5f0;border-top:1px solid #f0e8df;text-align:center;font-size:12px;color:#aaa;">
        Saveur Kaolack — Notification automatique
    </div>
</div>
</body>
</html>
HTML;

    $sujet = "Votre demande a bien été reçue — Saveur Kaolack";

    $phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailerPath)) {
        try {
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
            $mail             = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('SMTP_EMAIL')    ? SMTP_EMAIL    : '';
            $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('noreply@saveurkaolack.sn', 'Saveur Kaolack');
            $mail->addAddress($email_resto, $nom_resto);
            $mail->Subject    = $sujet;
            $mail->isHTML(true);
            $mail->Body       = $html;
            return $mail->send();
        } catch (Exception $e) {
            error_log("Email confirmation partenaire erreur: " . $e->getMessage());
        }
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Saveur Kaolack <noreply@saveurkaolack.sn>\r\n";
    return mail($email_resto, $sujet, $html, $headers);
}

/**
 * Envoie un message Telegram via Bot API
 * Utilise TELEGRAM_BOT_TOKEN de config.php si $bot_token est vide
 */
function envoyerNotificationTelegram(string $bot_token, string $chat_id, string $message): bool {
    // Utiliser le token global (admin) si aucun token spécifique fourni
    if (empty($bot_token) && defined('TELEGRAM_BOT_TOKEN')) {
        $bot_token = TELEGRAM_BOT_TOKEN;
    }

    if (empty($bot_token) || empty($chat_id)) {
        return false;
    }

    $url  = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    $data = http_build_query([
        'chat_id'    => $chat_id,
        'text'       => $message,
        'parse_mode' => 'HTML',
    ]);

    $opts = [
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content'       => $data,
            'timeout'       => 5,
            'ignore_errors' => true,
        ],
    ];

    $ctx    = stream_context_create($opts);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) {
        return false;
    }
    $json = json_decode($result, true);
    return !empty($json['ok']);
}

/**
 * Construit le texte Telegram formaté pour une nouvelle commande
 */
function buildMessageTelegram(
    string $resto_nom,
    string $prenom,
    string $telephone,
    string $adresse,
    string $quartier,
    string $notes,
    float  $total,
    string $mode_paiement,
    string $numero_tracking,
    array  $items
): string {
    $lignes = '';
    foreach ($items as $item) {
        $lignes .= "  • " . htmlspecialchars($item['nom'] ?? '') . " ×" . intval($item['quantite'] ?? 1) . "\n";
    }

    $total_fmt    = number_format($total, 0, ',', ' ');
    $paiement_ico = ($mode_paiement === 'wave') ? '💳 Wave' : '💵 Espèces';
    $quartier_txt = $quartier ? "\n📍 <b>Quartier :</b> " . htmlspecialchars($quartier) : '';
    $notes_txt    = $notes    ? "\n💬 <b>Note :</b> " . htmlspecialchars($notes)    : '';

    return "🔔 <b>NOUVELLE COMMANDE</b>\n"
         . "<b>" . htmlspecialchars($resto_nom) . "</b>\n\n"
         . "👤 <b>" . htmlspecialchars($prenom) . "</b>\n"
         . "📞 <b>" . htmlspecialchars($telephone) . "</b>\n"
         . "📍 " . htmlspecialchars($adresse) . $quartier_txt . $notes_txt . "\n\n"
         . "🛒 <b>Commande :</b>\n" . $lignes . "\n"
         . "💰 <b>Total : {$total_fmt} FCFA</b>\n"
         . $paiement_ico . "\n"
         . "🏷 <code>#{$numero_tracking}</code>";
}
