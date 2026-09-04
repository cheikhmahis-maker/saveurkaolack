<?php
/**
 * MODIFIER RESTAURANT - Modification des infos restaurant
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/wave.php';
require_once 'includes/notifications.php';
require_once 'includes/geo.php';

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

$erreur = '';
$succes = '';

try {
    $pdo = getDB();

    // Crée automatiquement les colonnes Wave et Telegram si elles n'existent pas encore
    assurerSchemaWave($pdo);
    assurerSchemaTelegram($pdo);
    assurerSchemaGeo($pdo);

    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$restaurant) {
        header('Location: dashboard_resto.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier le token CSRF
        if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
            $erreur = "Erreur de sécurité : session invalide. Veuillez réessayer.";
        } elseif (!restaurantEnRegle($restaurant)) {
            $erreur = "Votre essai gratuit est terminé. Contactez l'administrateur pour réactiver votre compte.";
        } else {
        $telephone           = trim($_POST['telephone']           ?? '');
        $email               = trim($_POST['email']               ?? '');
        $adresse             = trim($_POST['adresse']             ?? '');
        $heure_ouverture     = $_POST['heure_ouverture']          ?? '08:00';
        $heure_fermeture     = $_POST['heure_fermeture']          ?? '22:00';
        $wave_api_key        = trim($_POST['wave_api_key']        ?? '');
        $telegram_bot_token  = trim($_POST['telegram_bot_token']  ?? '');
        $telegram_chat_id    = trim($_POST['telegram_chat_id']    ?? '');

        if (!telephoneValide($telephone)) {
            $erreur = "Le numéro de téléphone n'est pas valide. Exemple : +221 77 123 45 67";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "L'adresse email n'est pas valide. C'est cette adresse qui reçoit vos notifications de commande.";
        } else {

        // Construire la requête UPDATE de façon flexible selon ce qui est renseigné
        $set_clauses = "telephone=?, email=?, adresse=?, heure_ouverture=?, heure_fermeture=?";
        $params      = [$telephone, $email, $adresse, $heure_ouverture, $heure_fermeture];

        // N'écraser la clé Wave que si une nouvelle est saisie
        if (!empty($wave_api_key)) {
            $set_clauses .= ", wave_api_key=?";
            $params[]     = $wave_api_key;
        }

        // Telegram : on met à jour dès qu'un champ est envoyé (même vide pour effacer)
        if (isset($_POST['telegram_bot_token'])) {
            $set_clauses .= ", telegram_bot_token=?";
            $params[]     = $telegram_bot_token ?: null;
        }
        if (isset($_POST['telegram_chat_id'])) {
            $set_clauses .= ", telegram_chat_id=?";
            $params[]     = $telegram_chat_id ?: null;
        }

        // Coordonnées GPS
        if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
            $lat_val = $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
            $lng_val = $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
            $set_clauses .= ", latitude=?, longitude=?";
            $params[] = $lat_val;
            $params[] = $lng_val;
        }

        $params[] = $restaurant['id'];
        $stmt = $pdo->prepare("UPDATE restaurants SET {$set_clauses} WHERE id = ?");
        $stmt->execute($params);
        
        $succes = 'Informations mises à jour !';
        
        // Recharger
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? LIMIT 1");
        $stmt->execute([$restaurant['id']]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        }
    }
    
} catch (PDOException $e) {
    $erreur = messageErreurBDD($e, 'modifier_restaurant.php');
}

$pageTitle = 'Modifier - ' . $restaurant['nom'];
require_once 'includes/header.php';
?>

<section class="container mx-auto px-4 max-w-2xl py-8">
    <div class="mb-6">
        <a href="dashboard_resto.php" class="text-sm text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)]">← Retour au dashboard</a>
    </div>

    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)] mb-6">Modifier mes infos</h1>

    <?php if ($erreur): ?>
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"><?php echo $erreur; ?></div>
    <?php endif; ?>
    
    <?php if ($succes): ?>
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700"><?php echo $succes; ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
        <?php echo champTokenCSRF(); ?>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Téléphone</label>
                <input type="tel" name="telephone" pattern="[\d\s\-\+]{7,17}" title="Entrez un numéro valide (ex: +221 77 123 45 67)" placeholder="+221 77 000 00 00" value="<?php echo htmlspecialchars($restaurant['telephone'] ?? ''); ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($restaurant['email'] ?? ''); ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Adresse</label>
            <input type="text" name="adresse" value="<?php echo htmlspecialchars($restaurant['adresse'] ?? ''); ?>"
                   class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Ouverture</label>
                <input type="time" name="heure_ouverture" value="<?php echo $restaurant['heure_ouverture']; ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">Fermeture</label>
                <input type="time" name="heure_fermeture" value="<?php echo $restaurant['heure_fermeture']; ?>"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20">
            </div>
        </div>

        <!-- Clé API Wave -->
        <div id="wave" class="rounded-xl border border-[hsl(30_25%_86%)] bg-[hsl(36_78%_92%)]/20 p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xl">🌊</span>
                <span class="font-medium text-[hsl(20_30%_14%)]">Paiement Wave</span>
                <?php if (!empty($restaurant['wave_api_key'])): ?>
                <span class="ml-auto text-xs bg-green-100 text-green-700 font-medium px-2 py-0.5 rounded-full">✓ Configuré</span>
                <?php else: ?>
                <span class="ml-auto text-xs bg-orange-100 text-orange-700 font-medium px-2 py-0.5 rounded-full">Non configuré</span>
                <?php endif; ?>
            </div>
            <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">
                Clé API Wave
                <span class="text-xs font-normal text-[hsl(25_15%_42%)]"> — depuis developer.wave.com</span>
                <a href="aide_wave.php" class="ml-2 text-xs text-[hsl(14_72%_46%)] hover:underline font-normal">Comment faire ?</a>
            </label>
            <input type="password" name="wave_api_key"
                   placeholder="<?php echo !empty($restaurant['wave_api_key']) ? 'Laisser vide pour conserver la clé actuelle' : 'wave_sn_prod_...'; ?>"
                   autocomplete="off"
                   class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20 font-mono text-sm">
            <p class="mt-1 text-xs text-[hsl(25_15%_42%)]">
                Les clients pourront payer par Wave uniquement si cette clé est renseignée.
                <?php if (!empty($restaurant['wave_api_key'])): ?>
                Laissez vide pour ne pas modifier la clé existante.
                <?php endif; ?>
            </p>
        </div>

        <!-- Localisation sur la carte -->
        <div class="rounded-xl border border-[hsl(30_25%_86%)] bg-green-50/30 p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xl">🗺️</span>
                <span class="font-medium text-[hsl(20_30%_14%)]">Localisation du restaurant</span>
                <?php if (!empty($restaurant['latitude']) && !empty($restaurant['longitude'])): ?>
                <span class="ml-auto text-xs bg-green-100 text-green-700 font-medium px-2 py-0.5 rounded-full">✓ Positionné</span>
                <?php else: ?>
                <span class="ml-auto text-xs bg-gray-100 text-gray-500 font-medium px-2 py-0.5 rounded-full">Non défini</span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-[hsl(25_15%_42%)] mb-3">
                Placez votre restaurant sur la carte — les clients verront exactement où vous êtes.<br>
                Cliquez sur <strong>Détecter ma position</strong> ou faites glisser le marqueur sur la carte.
            </p>

            <button type="button" id="btn-geo-resto"
                    class="mb-3 flex items-center gap-2 rounded-xl bg-[hsl(14_72%_46%)] px-4 py-2 text-sm font-medium text-white hover:bg-[hsl(14_72%_40%)] transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span id="btn-geo-resto-txt">Détecter ma position</span>
            </button>

            <!-- Carte interactive -->
            <div id="map-edit" class="rounded-xl overflow-hidden border border-[hsl(30_25%_86%)]" style="height:280px;"></div>
            <p class="mt-2 text-xs text-[hsl(25_15%_42%)]">Cliquez sur la carte ou faites glisser le marqueur pour ajuster la position.</p>

            <!-- Inputs cachés transmis avec le formulaire -->
            <input type="hidden" name="latitude"  id="input-lat" value="<?php echo htmlspecialchars($restaurant['latitude']  ?? ''); ?>">
            <input type="hidden" name="longitude" id="input-lng" value="<?php echo htmlspecialchars($restaurant['longitude'] ?? ''); ?>">

            <div id="geo-coords" class="mt-2 text-xs text-[hsl(25_15%_42%)] font-mono <?php echo empty($restaurant['latitude']) ? 'hidden' : ''; ?>">
                lat : <span id="disp-lat"><?php echo $restaurant['latitude'] ?? ''; ?></span> —
                lng : <span id="disp-lng"><?php echo $restaurant['longitude'] ?? ''; ?></span>
            </div>
        </div>

        <!-- Notifications Telegram -->
        <div id="telegram" class="rounded-xl border border-[hsl(30_25%_86%)] bg-blue-50/30 p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xl">✈️</span>
                <span class="font-medium text-[hsl(20_30%_14%)]">Notifications Telegram</span>
                <?php if (!empty($restaurant['telegram_chat_id'])): ?>
                <span class="ml-auto text-xs bg-green-100 text-green-700 font-medium px-2 py-0.5 rounded-full">✓ Activé</span>
                <?php else: ?>
                <span class="ml-auto text-xs bg-gray-100 text-gray-500 font-medium px-2 py-0.5 rounded-full">Optionnel</span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-[hsl(25_15%_42%)] mb-3">
                Recevez chaque nouvelle commande en temps réel sur votre téléphone Telegram.<br>
                <strong>1 seule étape :</strong> Ouvrez Telegram → cherchez <strong>@userinfobot</strong> → envoyez n'importe quel message → copiez votre <strong>id</strong> et collez-le ci-dessous.
            </p>
            <div>
                <label class="block text-sm font-medium text-[hsl(20_30%_14%)] mb-1">
                    Votre Chat ID Telegram
                    <span class="text-xs font-normal text-[hsl(25_15%_42%)]"> — trouvé via @userinfobot</span>
                </label>
                <input type="text" name="telegram_chat_id"
                       value="<?php echo htmlspecialchars($restaurant['telegram_chat_id'] ?? ''); ?>"
                       placeholder="123456789"
                       autocomplete="off"
                       class="w-full rounded-xl border border-[hsl(30_25%_86%)] px-4 py-2 focus:border-[hsl(14_72%_46%)] focus:outline-none focus:ring-2 focus:ring-[hsl(14_72%_46%)]/20 font-mono text-sm">
                <p class="mt-1 text-xs text-[hsl(25_15%_42%)]">Laissez vide pour désactiver les notifications Telegram.</p>
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 rounded-xl bg-[hsl(14_72%_46%)] px-6 py-3 text-white font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
                Enregistrer
            </button>
            <a href="dashboard_resto.php" class="rounded-xl border border-[hsl(30_25%_86%)] px-6 py-3 text-[hsl(25_15%_42%)] font-medium hover:bg-[hsl(36_30%_92%)] transition-colors">
                Annuler
            </a>
        </div>
    </form>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    // Coordonnées initiales : restaurant si déjà positionné, sinon centre de Kaolack
    const initLat = <?php echo !empty($restaurant['latitude'])  ? floatval($restaurant['latitude'])  : 14.1652; ?>;
    const initLng = <?php echo !empty($restaurant['longitude']) ? floatval($restaurant['longitude']) : -16.0758; ?>;
    const dejaPositionne = <?php echo (!empty($restaurant['latitude'])) ? 'true' : 'false'; ?>;

    const inputLat  = document.getElementById('input-lat');
    const inputLng  = document.getElementById('input-lng');
    const dispLat   = document.getElementById('disp-lat');
    const dispLng   = document.getElementById('disp-lng');
    const geoCoords = document.getElementById('geo-coords');

    const map = L.map('map-edit', { zoomControl: true }).setView([initLat, initLng], dejaPositionne ? 16 : 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>'
    }).addTo(map);

    // Marqueur rouge déplaçable
    const icon = L.divIcon({
        html: '<div style="width:22px;height:22px;background:#c0392b;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.4);cursor:grab;"></div>',
        iconSize: [22, 22],
        iconAnchor: [11, 11],
        className: '',
    });

    let marker = dejaPositionne
        ? L.marker([initLat, initLng], { icon, draggable: true }).addTo(map)
        : null;

    function mettreAJourCoords(lat, lng) {
        const l = lat.toFixed(7);
        const g = lng.toFixed(7);
        inputLat.value  = l;
        inputLng.value  = g;
        dispLat.textContent = l;
        dispLng.textContent = g;
        geoCoords.classList.remove('hidden');
    }

    // Déplacer le marqueur existant
    if (marker) {
        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            mettreAJourCoords(pos.lat, pos.lng);
        });
    }

    // Cliquer sur la carte pour poser / déplacer le marqueur
    map.on('click', function (e) {
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { icon, draggable: true }).addTo(map);
            marker.on('dragend', function () {
                const pos = marker.getLatLng();
                mettreAJourCoords(pos.lat, pos.lng);
            });
        }
        mettreAJourCoords(e.latlng.lat, e.latlng.lng);
    });

    // Bouton "Détecter ma position"
    const btn    = document.getElementById('btn-geo-resto');
    const btnTxt = document.getElementById('btn-geo-resto-txt');

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Géolocalisation non supportée par votre navigateur');
            return;
        }
        btn.disabled = true;
        btnTxt.textContent = 'Localisation…';

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                map.setView([lat, lng], 17);

                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng], { icon, draggable: true }).addTo(map);
                    marker.on('dragend', function () {
                        const p = marker.getLatLng();
                        mettreAJourCoords(p.lat, p.lng);
                    });
                }
                mettreAJourCoords(lat, lng);
                btn.disabled = false;
                btnTxt.textContent = '✓ Position détectée';
            },
            function () {
                alert('Impossible d\'accéder à votre position — cliquez directement sur la carte.');
                btn.disabled = false;
                btnTxt.textContent = 'Détecter ma position';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
})();
</script>
<?php require_once 'includes/footer.php'; ?>
