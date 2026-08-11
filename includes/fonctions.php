<?php
/**
 * =====================================================
 * INCLUDES/FONCTIONS.PHP — Fonctions utilitaires
 * Saveur Kaolack
 * =====================================================
 * 
 * Fonctions d'affichage d'images, formatage, etc.
 * Inclure APRES config.php et db.php
 */

// Vérifier que config.php est inclus
if (!defined('BASE_URL')) {
    die('Erreur : config.php doit être inclus avant fonctions.php');
}

/**
 * =====================================================
 * FONCTIONS D'AFFICHAGE D'IMAGES
 * =====================================================
 */

/**
 * Affiche une image de plat avec fallback automatique
 * 
 * @param string|null $nom_fichier Nom du fichier dans uploads/plats/
 * @param string $alt Texte alternatif pour l'accessibilité
 * @param string $classe Classes CSS (ex: "img-fluid rounded")
 * @param string $style Styles CSS inline (optionnel)
 * @return string HTML complet de la balise <img>
 * 
 * Exemple d'utilisation :
 * echo afficherImagePlat($plat['photo'], $plat['nom'], 'img-fluid');
 */
function afficherImagePlat(?string $nom_fichier, string $alt = '', string $classe = '', string $style = ''): string {
    // Nettoyer le nom de fichier
    $nom_fichier = $nom_fichier ? basename($nom_fichier) : '';
    
    // Vérifier si le fichier existe physiquement
    $chemin_complet = PLATS_PATH . $nom_fichier;
    $fichier_existe = !empty($nom_fichier) && file_exists($chemin_complet) && is_file($chemin_complet);
    
    // URL finale
    $url = $fichier_existe ? PLATS_URL . urlencode($nom_fichier) : IMG_PLAT_DEFAUT;
    
    // Construire la balise HTML
    $html = '<img src="' . $url . '"';
    $html .= ' alt="' . htmlspecialchars($alt ?: 'Image du plat', ENT_QUOTES, 'UTF-8') . '"';
    $html .= ' loading="lazy"'; // Chargement différé pour performance
    
    if (!empty($classe)) {
        $html .= ' class="' . htmlspecialchars($classe, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    if (!empty($style)) {
        $html .= ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    // Fallback JavaScript si l'image échoue quand même
    $html .= ' onerror="this.onerror=null;this.src=\'' . IMG_PLAT_DEFAUT . '\'"';
    
    $html .= '>';
    
    return $html;
}

/**
 * Affiche une image de restaurant (bannière/logos)
 * 
 * @param string|null $nom_fichier Nom du fichier
 * @param string $alt Texte alternatif
 * @param string $classe Classes CSS
 * @param string $type Type d'image : 'banniere', 'logo', ou 'default'
 * @return string HTML de la balise <img>
 */
function afficherImageResto(?string $nom_fichier, string $alt = '', string $classe = '', string $type = 'default'): string {
    $nom_fichier = $nom_fichier ? basename($nom_fichier) : '';
    
    // Déterminer le chemin et l'URL selon le type
    switch ($type) {
        case 'banniere':
            $chemin_complet = BANNIERES_PATH . $nom_fichier;
            $url_base = BANNIERES_URL;
            $url_defaut = IMG_BANNIERE_DEFAUT;
            break;
        case 'logo':
            $chemin_complet = LOGOS_PATH . $nom_fichier;
            $url_base = LOGOS_URL;
            $url_defaut = IMG_LOGO_DEFAUT;
            break;
        default:
            $chemin_complet = RESTOS_PATH . $nom_fichier;
            $url_base = RESTOS_URL;
            $url_defaut = IMG_RESTO_DEFAUT;
    }
    
    $fichier_existe = !empty($nom_fichier) && file_exists($chemin_complet) && is_file($chemin_complet);
    $url = $fichier_existe ? $url_base . urlencode($nom_fichier) : $url_defaut;
    
    // Construire le HTML
    $html = '<img src="' . $url . '"';
    $html .= ' alt="' . htmlspecialchars($alt ?: 'Image du restaurant', ENT_QUOTES, 'UTF-8') . '"';
    $html .= ' loading="lazy"';
    
    if (!empty($classe)) {
        $html .= ' class="' . htmlspecialchars($classe, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    $html .= ' onerror="this.onerror=null;this.src=\'' . $url_defaut . '\'"';
    $html .= '>';
    
    return $html;
}

/**
 * Affiche une bannière de restaurant (grand format)
 * 
 * @param string|null $nom_fichier Nom du fichier dans uploads/restaurants/bannieres/
 * @param string $alt Texte alternatif
 * @param string $classe Classes CSS
 * @return string HTML de la balise <img>
 */
function afficherBanniere(?string $nom_fichier, string $alt = '', string $classe = ''): string {
    return afficherImageResto($nom_fichier, $alt, $classe, 'banniere');
}

/**
 * Affiche un logo de restaurant
 * 
 * @param string|null $nom_fichier Nom du fichier dans uploads/restaurants/logos/
 * @param string $alt Texte alternatif
 * @param string $classe Classes CSS
 * @return string HTML de la balise <img>
 */
function afficherLogo(?string $nom_fichier, string $alt = '', string $classe = ''): string {
    return afficherImageResto($nom_fichier, $alt, $classe, 'logo');
}

/**
 * Retourne juste l'URL d'une image (pour CSS background)
 * 
 * @param string|null $nom_fichier Nom du fichier
 * @param string $type Type : 'plat', 'resto', 'banniere', 'logo'
 * @return string URL complète
 */
function urlImage(?string $nom_fichier, string $type = 'plat'): string {
    $nom_fichier = $nom_fichier ? basename($nom_fichier) : '';
    
    switch ($type) {
        case 'plat':
            $chemin = PLATS_PATH . $nom_fichier;
            return file_exists($chemin) ? PLATS_URL . urlencode($nom_fichier) : IMG_PLAT_DEFAUT;
        case 'resto':
            $chemin = RESTOS_PATH . $nom_fichier;
            return file_exists($chemin) ? RESTOS_URL . urlencode($nom_fichier) : IMG_RESTO_DEFAUT;
        case 'banniere':
            $chemin = BANNIERES_PATH . $nom_fichier;
            return file_exists($chemin) ? BANNIERES_URL . urlencode($nom_fichier) : IMG_BANNIERE_DEFAUT;
        case 'logo':
            $chemin = LOGOS_PATH . $nom_fichier;
            return file_exists($chemin) ? LOGOS_URL . urlencode($nom_fichier) : IMG_LOGO_DEFAUT;
        default:
            return IMG_PLAT_DEFAUT;
    }
}

/**
 * =====================================================
 * FONCTIONS DE FORMATAGE
 * =====================================================
 */

/**
 * Affiche une note en étoiles Bootstrap Icons
 * 
 * @param float $note Note de 0 à 5 (ex: 4.5)
 * @param string $taille Taille des étoiles : 'sm', 'md', 'lg'
 * @return string HTML des étoiles
 * 
 * Exemple : afficherEtoiles(4.5) retourne 4 étoiles pleines + 1 demi
 */
function afficherEtoiles(float $note, string $taille = 'md'): string {
    $note = max(0, min(5, $note)); // Limiter entre 0 et 5
    
    // Taille CSS
    $sizeClass = match($taille) {
        'sm' => 'fs-6',
        'lg' => 'fs-4',
        default => 'fs-5'
    };
    
    $html = '<span class="etoiles ' . $sizeClass . '" style="color: #F7C948;">'; // Couleur jaune/or
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $note) {
            // Étoile pleine
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($i - 0.5 <= $note) {
            // Demi-étoile
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            // Étoile vide
            $html .= '<i class="bi bi-star" style="color: #dee2e6;"></i>';
        }
    }
    
    $html .= '</span>';
    return $html;
}

/**
 * Formate un montant en FCFA
 * 
 * @param float|int $montant Montant en FCFA
 * @param bool $symbole Afficher le symbole FCFA
 * @return string Montant formaté (ex: "2 500 FCFA")
 */
function formaterPrix(float|int $montant, bool $symbole = true): string {
    $montant = max(0, $montant);
    $formate = number_format($montant, 0, ',', ' '); // Espace comme séparateur de milliers
    return $symbole ? $formate . ' FCFA' : $formate;
}

/**
 * Formate une date MySQL en français
 * 
 * @param string $date Date format MySQL (2024-03-15 14:30:00)
 * @param bool $heure Afficher l'heure
 * @return string Date formatée (15 mars 2024 à 14h30)
 */
function formaterDate(string $date, bool $heure = true): string {
    $timestamp = strtotime($date);
    
    if ($timestamp === false) {
        return 'Date invalide';
    }
    
    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];
    
    $jour = date('j', $timestamp);
    $mois_nom = $mois[(int)date('n', $timestamp)];
    $annee = date('Y', $timestamp);
    
    $resultat = $jour . ' ' . $mois_nom . ' ' . $annee;
    
    if ($heure) {
        $h = date('H', $timestamp);
        $m = date('i', $timestamp);
        $resultat .= ' à ' . $h . 'h' . $m;
    }
    
    return $resultat;
}

/**
 * Retourne un badge Bootstrap coloré selon le statut
 * 
 * @param string $statut Statut de commande
 * @return string HTML du badge Bootstrap
 */
function statutBadge(string $statut): string {
    $statuts = [
        'recue' => ['class' => 'bg-warning text-dark', 'label' => 'Reçue'],
        'preparation' => ['class' => 'bg-info', 'label' => 'En préparation'],
        'prete' => ['class' => 'bg-primary', 'label' => 'Prête'],
        'livraison' => ['class' => 'bg-purple', 'label' => 'En livraison'],
        'livree' => ['class' => 'bg-success', 'label' => 'Livrée'],
        'annulee' => ['class' => 'bg-danger', 'label' => 'Annulée'],
        'en_attente' => ['class' => 'bg-secondary', 'label' => 'En attente'],
        'confirme' => ['class' => 'bg-success', 'label' => 'Confirmée'],
    ];
    
    $statut = strtolower($statut);
    $config = $statuts[$statut] ?? ['class' => 'bg-secondary', 'label' => ucfirst($statut)];
    
    return '<span class="badge ' . $config['class'] . '">' . $config['label'] . '</span>';
}

/**
 * =====================================================
 * FONCTIONS DE SÉCURITÉ
 * =====================================================
 */

/**
 * Nettoie une chaîne pour éviter XSS
 * 
 * @param string $texte Texte à nettoyer
 * @return string Texte sécurisé
 */
function e(string $texte): string {
    return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige vers une URL
 * 
 * @param string $url URL de destination
 * @return void
 */
function rediriger(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Affiche un message flash
 * 
 * @param string $type Type : success, danger, warning, info
 * @param string $message Message à afficher
 * @return string HTML de l'alerte
 */
function alerte(string $type, string $message): string {
    $classes = [
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $classes[$type] ?? 'alert-info';
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">' 
         . e($message) 
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

/**
 * Vérifie si un restaurant est ouvert selon ses horaires
 * 
 * @param string $heureOuverture Heure d'ouverture (format HH:MM)
 * @param string $heureFermeture Heure de fermeture (format HH:MM)
 * @return bool True si le restaurant est ouvert, false sinon
 */
function estRestaurantOuvert(string $heureOuverture, string $heureFermeture): bool {
    $now = new DateTime();

    // Convertir en minutes pour comparaison facile
    $currentMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');
    
    list($openH, $openM) = explode(':', $heureOuverture);
    list($closeH, $closeM) = explode(':', $heureFermeture);

    $openMinutes = (int)$openH * 60 + (int)$openM;
    $closeMinutes = (int)$closeH * 60 + (int)$closeM;

    // Heures identiques = ouvert en continu (24h/24)
    if ($openMinutes === $closeMinutes) {
        return true;
    }

    // Gérer le cas où la fermeture est après minuit
    if ($closeMinutes < $openMinutes) {
        // Le restaurant ferme après minuit
        return $currentMinutes >= $openMinutes || $currentMinutes <= $closeMinutes;
    }

    return $currentMinutes >= $openMinutes && $currentMinutes <= $closeMinutes;
}

/**
 * Vérifie qu'un numéro de téléphone contient un nombre de chiffres raisonnable
 * (accepte les espaces, tirets, "+", avec ou sans indicatif +221).
 */
function telephoneValide(string $telephone): bool {
    $chiffres = preg_replace('/\D/', '', $telephone);
    return strlen($chiffres) >= 7 && strlen($chiffres) <= 15;
}

/**
 * Normalise un numéro pour un lien tel:, en ajoutant l'indicatif +221
 * s'il est absent (numéro enregistré sans indicatif).
 */
function formatTelLien(string $telephone): string {
    $chiffres = preg_replace('/\D/', '', $telephone);
    if (!str_starts_with($chiffres, '221')) {
        $chiffres = '221' . $chiffres;
    }
    return '+' . $chiffres;
}

/**
 * =====================================================
 * ESSAI GRATUIT & ABONNEMENT RESTAURANT
 * =====================================================
 * Un restaurant a 45 jours gratuits à partir du jour où il ajoute
 * son premier plat. Passé ce délai, il faut un abonnement actif
 * (activé manuellement par l'admin) pour continuer à opérer.
 */

const DUREE_ESSAI_JOURS = 45;

/** Crée les colonnes essai_debut / abonnement_jusquau si elles n'existent pas encore (déploiement). */
function assurerSchemaEssai(PDO $pdo): void {
    try {
        if (empty($pdo->query("SHOW COLUMNS FROM restaurants LIKE 'essai_debut'")->fetchAll())) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN essai_debut DATE DEFAULT NULL");
        }
        if (empty($pdo->query("SHOW COLUMNS FROM restaurants LIKE 'abonnement_jusquau'")->fetchAll())) {
            $pdo->exec("ALTER TABLE restaurants ADD COLUMN abonnement_jusquau DATE DEFAULT NULL");
        }
    } catch (PDOException $e) {
        // Non bloquant — colonnes peut-être déjà présentes
    }
}

/**
 * Un restaurant est "en règle" s'il n'a pas encore commencé son essai,
 * s'il est encore dans ses 45 jours gratuits, ou s'il a un abonnement en cours.
 */
function restaurantEnRegle(array $restaurant): bool {
    if (empty($restaurant['essai_debut'])) {
        return true; // pas encore ajouté de plat : accès complet pour se lancer
    }

    $finEssai = strtotime($restaurant['essai_debut'] . ' +' . DUREE_ESSAI_JOURS . ' days');
    if (time() <= $finEssai) {
        return true;
    }

    if (!empty($restaurant['abonnement_jusquau']) && time() <= strtotime($restaurant['abonnement_jusquau'] . ' 23:59:59')) {
        return true;
    }

    return false;
}

/** Nombre de jours restants d'essai gratuit. Null si l'essai n'a pas commencé. 0 si terminé. */
function joursRestantsEssai(array $restaurant): ?int {
    if (empty($restaurant['essai_debut'])) {
        return null;
    }
    $finEssai = strtotime($restaurant['essai_debut'] . ' +' . DUREE_ESSAI_JOURS . ' days');
    return max(0, (int) ceil(($finEssai - time()) / 86400));
}

/** Bandeau HTML à afficher en haut du tableau de bord restaurant (vide si rien à signaler). */
function bandeauEssaiRestaurant(array $restaurant): string {
    if (empty($restaurant['essai_debut'])) {
        return '';
    }

    $abonneActif = !empty($restaurant['abonnement_jusquau']) && time() <= strtotime($restaurant['abonnement_jusquau'] . ' 23:59:59');

    if ($abonneActif) {
        $dateFmt = date('d/m/Y', strtotime($restaurant['abonnement_jusquau']));
        return '<div class="bg-green-50 border-b border-green-200 px-4 py-2 text-center text-sm text-green-700">✓ Abonnement actif jusqu\'au ' . htmlspecialchars($dateFmt) . '</div>';
    }

    if (!restaurantEnRegle($restaurant)) {
        return '<div class="bg-red-50 border-b border-red-200 px-4 py-2 text-center text-sm text-red-700 font-medium">⛔ Votre essai gratuit est terminé. Contactez l\'administrateur pour activer votre abonnement et reprendre la gestion de votre restaurant.</div>';
    }

    $jours = joursRestantsEssai($restaurant);
    return '<div class="bg-amber-50 border-b border-amber-200 px-4 py-2 text-center text-sm text-amber-700">🎁 Essai gratuit — il vous reste <strong>' . $jours . ' jour' . ($jours > 1 ? 's' : '') . '</strong>.</div>';
}

/**
 * =====================================================
 * FONCTIONS CSRF - Protection contre les attaques CSRF
 * =====================================================
 */

/**
 * Génère un token CSRF et le stocke en session
 * @return string Token CSRF
 */
function genererTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le token CSRF soumis est valide
 * @param string $token Token soumis
 * @return bool True si valide, false sinon
 */
function verifierTokenCSRF(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un champ hidden avec le token CSRF
 * @return string HTML du champ hidden
 */
function champTokenCSRF(): string {
    $token = genererTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * =====================================================
 * FONCTIONS DE SECURITE UPLOAD
 * =====================================================
 */

/**
 * Upload et redimensionne une image de maniere securisee
 * 
 * @param array $file Tableau $_FILES['nom_du_champ']
 * @param string $destination Dossier de destination (ex: 'uploads/plats/')
 * @param int $maxWidth Largeur maximale en pixels
 * @param int $maxHeight Hauteur maximale en pixels  
 * @param int $maxSize Taille maximale en octets (defaut: 2Mo)
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function uploadImageSecurise(array $file, string $destination, int $maxWidth = 1200, int $maxHeight = 900, int $maxSize = 2097152): array {
    // Extensions autorisees
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
    $typesMimeAutorises = ['image/jpeg', 'image/png', 'image/webp'];
    
    // Verifier si un fichier a ete upload
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'filename' => null, 'error' => 'Aucun fichier selectionne'];
    }
    
    // Verifier les erreurs d'upload PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $erreurs = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier depasse la taille autorisee par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier depasse la taille autorisee par le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier a ete partiellement upload',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a ete upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Erreur d\'ecriture sur le disque'
        ];
        return ['success' => false, 'filename' => null, 'error' => $erreurs[$file['error']] ?? 'Erreur d\'upload inconnue'];
    }
    
    // Verifier la taille du fichier
    if ($file['size'] > $maxSize) {
        $maxSizeMo = round($maxSize / 1048576, 1);
        return ['success' => false, 'filename' => null, 'error' => "Le fichier est trop lourd (max {$maxSizeMo} Mo)"];
    }
    
    // Recuperer l'extension reelle
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Verifier l'extension
    if (!in_array($extension, $extensionsAutorisees)) {
        return ['success' => false, 'filename' => null, 'error' => 'Extension non autorisee (jpg, jpeg, png, webp uniquement)'];
    }
    
    // Verifier le type MIME reel (plus fiable que l'extension)
    $typeMime = mime_content_type($file['tmp_name']);
    if (!in_array($typeMime, $typesMimeAutorises)) {
        return ['success' => false, 'filename' => null, 'error' => 'Type de fichier invalide'];
    }
    
    // Verifier les magic bytes (signature de fichier) pour securite supplementaire
    $fichier = fopen($file['tmp_name'], 'rb');
    $bytes = fread($fichier, 8);
    fclose($fichier);
    
    // Signatures magiques des images autorisees
    $signatures = [
        'jpg' => [0xFF, 0xD8, 0xFF],
        'jpeg' => [0xFF, 0xD8, 0xFF],
        'png' => [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A],
        'webp' => [0x52, 0x49, 0x46, 0x46]
    ];
    
    $signatureValide = false;
    foreach ($signatures[$extension] as $i => $octet) {
        if (!isset($bytes[$i]) || ord($bytes[$i]) !== $octet) {
            $signatureValide = false;
            break;
        }
        $signatureValide = true;
    }
    
    if (!$signatureValide) {
        return ['success' => false, 'filename' => null, 'error' => 'Signature de fichier invalide - ce n\'est pas une vraie image'];
    }
    
    // Creer le dossier s'il n'existe pas
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return ['success' => false, 'filename' => null, 'error' => 'Impossible de creer le dossier de destination'];
        }
    }
    
    // Generer un nom de fichier unique et aleatoire
    $nouveauNom = uniqid('img_', true) . '.' . $extension;
    $cheminComplet = rtrim($destination, '/') . '/' . $nouveauNom;
    
    // Vérifier si GD est disponible pour le redimensionnement
    if (extension_loaded('gd')) {
        // Redimensionner l'image si necessaire
        if (!redimensionnerImage($file['tmp_name'], $cheminComplet, $maxWidth, $maxHeight, $extension)) {
            return ['success' => false, 'filename' => null, 'error' => 'Erreur lors du traitement de l\'image'];
        }
    } else {
        // GD non disponible : déplacer l'image sans redimensionnement
        if (!move_uploaded_file($file['tmp_name'], $cheminComplet)) {
            return ['success' => false, 'filename' => null, 'error' => 'Erreur lors de l\'upload du fichier'];
        }
    }
    
    return ['success' => true, 'filename' => $nouveauNom, 'error' => null];
}

/**
 * Redimensionne une image en conservant le ratio
 * 
 * @param string $source Chemin de l'image source
 * @param string $destination Chemin de destination
 * @param int $maxWidth Largeur maximale
 * @param int $maxHeight Hauteur maximale
 * @param string $extension Extension de l'image
 * @return bool True si succes, false si echec
 */
function redimensionnerImage(string $source, string $destination, int $maxWidth, int $maxHeight, string $extension): bool {
    // Recuperer les dimensions de l'image originale
    $dimensions = getimagesize($source);
    if ($dimensions === false) {
        return false;
    }
    
    list($width, $height) = $dimensions;
    
    // Calculer les nouvelles dimensions en conservant le ratio
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
    } else {
        // L'image est deja assez petite, on la copie telle quelle
        return copy($source, $destination);
    }
    
    // Creer l'image source selon le format
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $srcImage = imagecreatefromjpeg($source);
            break;
        case 'png':
            $srcImage = imagecreatefrompng($source);
            break;
        case 'webp':
            $srcImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$srcImage) {
        return false;
    }
    
    // Creer la nouvelle image vide
    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserver la transparence pour PNG et WebP
    if ($extension === 'png' || $extension === 'webp') {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }
    
    // Redimensionner
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Sauvegarder selon le format
    $result = false;
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $result = imagejpeg($dstImage, $destination, 85); // Qualite 85%
            break;
        case 'png':
            $result = imagepng($dstImage, $destination, 8); // Compression 8
            break;
        case 'webp':
            $result = imagewebp($dstImage, $destination, 85); // Qualite 85%
            break;
    }
    
    // Liberer la memoire
    imagedestroy($srcImage);
    imagedestroy($dstImage);
    
    return $result;
}

/**
 * Envoie un email de confirmation de commande
 * 
 * @param string $emailDestinataire Email du client
 * @param string $prenom Prenom du client
 * @param string $numeroTracking Numero de commande
 * @param float $total Montant total
 * @param string $restaurantNom Nom du restaurant
 * @param array $items Articles commandes
 * @return bool True si envoye, false sinon
 */
function envoyerEmailConfirmation(string $emailDestinataire, string $prenom, string $numeroTracking, float $total, string $restaurantNom, array $items): bool {
    // Ne pas envoyer si pas d'email
    if (empty($emailDestinataire) || !filter_var($emailDestinataire, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Construire le resume des articles
    $articlesTexte = "";
    foreach ($items as $item) {
        $articlesTexte .= "- {$item['nom']} x{$item['quantite']} = " . number_format($item['prix'] * $item['quantite'], 0, ',', ' ') . " FCFA\n";
    }
    
    // URL de suivi
    $urlSuivi = BASE_URL . "suivi.php?token=" . urlencode($numeroTracking);
    
    // Sujet
    $sujet = "Confirmation commande #{$numeroTracking} - Saveur Kaolack";
    
    // Formater le total pour l'email
    $totalFormate = number_format($total, 0, ',', ' ');
    
    // Message HTML
    $messageHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirmation de commande</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #d35400;">🍽️ Saveur Kaolack</h2>
        <p>Bonjour <strong>{$prenom}</strong>,</p>
        <p>Votre commande est <strong style="color: green;">confirmée !</strong></p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>📦 N° de commande :</strong> {$numeroTracking}</p>
            <p><strong>🍽️ Restaurant :</strong> {$restaurantNom}</p>
            <p><strong>💰 Total :</strong> {$totalFormate} FCFA</p>
        </div>
        
        <h3>Détail de votre commande :</h3>
        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">{$articlesTexte}</pre>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$urlSuivi}" style="background: #d35400; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                📍 SUIVRE MA COMMANDE
            </a>
        </div>
        
        <p style="font-size: 12px; color: #666;">
            Gardez ce email, il contient votre numéro de commande pour suivre votre livraison.<br>
            Si vous avez des questions, contactez-nous au 77 123 45 67.
        </p>
        
        <p>Merci de votre confiance !<br>
        <strong>L'équipe Saveur Kaolack</strong></p>
    </div>
</body>
</html>
HTML;
    
    // Message texte simple (fallback)
    $messageTexte = "Bonjour {$prenom},\n\n";
    $messageTexte .= "Votre commande est confirmée !\n\n";
    $messageTexte .= "N° de commande : {$numeroTracking}\n";
    $messageTexte .= "Restaurant : {$restaurantNom}\n";
    $messageTexte .= "Total : " . number_format($total, 0, ',', ' ') . " FCFA\n\n";
    $messageTexte .= "Détail :\n{$articlesTexte}\n";
    $messageTexte .= "Suivre votre commande : {$urlSuivi}\n\n";
    $messageTexte .= "Gardez ce email pour retrouver votre numéro de commande.\n";
    $messageTexte .= "Questions ? 77 123 45 67\n\n";
    $messageTexte .= "Merci !\nSaveur Kaolack";
    
    // Essayer d'envoyer via PHPMailer si disponible
    $phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailerPath)) {
        try {
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('SMTP_EMAIL')    ? SMTP_EMAIL    : '';
            $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(defined('SMTP_EMAIL') ? SMTP_EMAIL : 'noreply@saveurkaolack.sn', 'Saveur Kaolack');
            $mail->addAddress($emailDestinataire);
            $mail->Subject = $sujet;
            $mail->Body = $messageHtml;
            $mail->AltBody = $messageTexte;
            $mail->isHTML(true);

            return $mail->send();
        } catch (PHPMailer\PHPMailer\Exception $e) {
            error_log("Erreur envoi email : " . $e->getMessage());
            return false;
        }
    }
    
    // Fallback : mail() natif PHP (moins fiable)
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Saveur Kaolack <noreply@saveurkaolack.sn>" . "\r\n";
    
    return mail($emailDestinataire, $sujet, $messageHtml, $headers);
}

/**
 * Envoie un email simple (utilisé pour réinitialisation MDP)
 * 
 * @param string $destinataire Email du destinataire
 * @param string $sujet Sujet de l'email
 * @param string $messageHtml Contenu HTML
 * @return bool True si envoyé
 */
function envoyerEmailSimple(string $destinataire, string $sujet, string $messageHtml): bool {
    // Essayer PHPMailer d'abord
    $phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailerPath)) {
        try {
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('SMTP_EMAIL')    ? SMTP_EMAIL    : '';
            $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(defined('SMTP_EMAIL') ? SMTP_EMAIL : 'noreply@saveurkaolack.sn', 'Saveur Kaolack');
            $mail->addAddress($destinataire);
            $mail->Subject = $sujet;
            $mail->Body = $messageHtml;
            $mail->isHTML(true);

            return $mail->send();
        } catch (PHPMailer\PHPMailer\Exception $e) {
            error_log("Erreur envoi email simple : " . $e->getMessage());
        }
    }
    
    // Fallback mail()
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Saveur Kaolack <noreply@saveurkaolack.sn>" . "\r\n";
    
    return mail($destinataire, $sujet, $messageHtml, $headers);
}
