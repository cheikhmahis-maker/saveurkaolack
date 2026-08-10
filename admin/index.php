<?php
/**
 * ADMIN/INDEX.PHP - Dashboard Administrateur
 */

// Démarrer la session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté et est admin
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/fonctions.php';

// ─── REQUÊTES SQL POUR LE DASHBOARD ADMIN ───
// Initialiser toutes les variables par défaut
$nb_restos_actifs = $nb_restos_attente = $nb_clients = $nouveaux_clients_mois = 0;
$nb_cmd_jour = $montant_jour = $revenus_mois = $nb_cmd_mois = $nb_cmd_annulees = 0;
$nb_avis_attente = 0;
$graphique_data = $top_restaurants = $dernieres_commandes = $restos_attente = [];

try {
    $pdo = getDB();
    
    // CARTE 1 — Restaurants actifs + en attente
    $nb_restos_actifs = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE statut = 'actif'")->fetchColumn();
    $nb_restos_attente = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE statut = 'en_attente'")->fetchColumn();
    
    // CARTE 2 — Clients inscrits + nouveaux ce mois
    $nb_clients = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'")->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client' AND MONTH(created_at) = MONTH(NOW())");
    $stmt->execute();
    $nouveaux_clients_mois = $stmt->fetchColumn();
    
    // CARTE 3 — Commandes aujourd'hui
    $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total), 0) FROM commandes WHERE DATE(created_at) = CURDATE() AND statut != 'annulee'");
    $stmt->execute();
    $cmd_aujourdhui = $stmt->fetch(PDO::FETCH_ASSOC);
    $nb_cmd_jour = $cmd_aujourdhui['COUNT(*)'] ?? 0;
    $montant_jour = $cmd_aujourdhui['COALESCE(SUM(total), 0)'] ?? 0;
    
    // CARTE 4 — Revenus ce mois
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee' AND MONTH(created_at) = MONTH(NOW())");
    $stmt->execute();
    $revenus_mois = $stmt->fetchColumn();
    
    // CARTE 5 — Total commandes ce mois + annulées
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE MONTH(created_at) = MONTH(NOW()) AND statut != 'annulee'");
    $stmt->execute();
    $nb_cmd_mois = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE MONTH(created_at) = MONTH(NOW()) AND statut = 'annulee'");
    $stmt->execute();
    $nb_cmd_annulees = $stmt->fetchColumn();
    
    // CARTE 6 — Avis à modérer (avec protection si table inexistante)
    try {
        $nb_avis_attente = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'")->fetchColumn();
    } catch (PDOException $e) {
        $nb_avis_attente = 0; // Table avis n'existe pas
    }
    
    // GRAPHIQUE — Données 7 derniers jours
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as jour, COUNT(*) as nb_commandes, COALESCE(SUM(total), 0) as revenus
        FROM commandes
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND statut != 'annulee'
        GROUP BY DATE(created_at)
        ORDER BY jour ASC
    ");
    $stmt->execute();
    $graphique_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TOP 5 RESTAURANTS DU MOIS
    $stmt = $pdo->prepare("
        SELECT r.id, r.nom, COUNT(c.id) as nb_commandes, COALESCE(SUM(c.total), 0) as revenus
        FROM restaurants r
        LEFT JOIN commandes c ON r.id = c.restaurant_id AND MONTH(c.created_at) = MONTH(NOW()) AND c.statut != 'annulee'
        WHERE r.statut = 'actif'
        GROUP BY r.id
        ORDER BY nb_commandes DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DERNIÈRES COMMANDES
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.total, c.statut, c.created_at,
                   r.nom as resto_nom,
                   'Client' as nom_client,
                   'membre' as type_client
            FROM commandes c
            JOIN restaurants r ON c.restaurant_id = r.id
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $dernieres_commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $dernieres_commandes = [];
    }
    
    // RESTAURANTS EN ATTENTE DE VALIDATION
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.nom, r.adresse, r.quartier, r.created_at,
                   '-' as prenom, '-' as nom_user, r.email, r.telephone,
                   cat.nom as categorie_nom
            FROM restaurants r
            LEFT JOIN categories cat ON r.categorie_id = cat.id
            WHERE r.statut = 'en_attente'
            ORDER BY r.created_at ASC
        ");
        $stmt->execute();
        $restos_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $restos_attente = [];
    }
    
} catch (PDOException $e) {
    $erreur = 'Erreur BDD : ' . $e->getMessage();
}

$pageTitle = 'Dashboard Admin';
require_once '../includes/header.php';

// Date du jour formatée
$date_jour = date('d/m/Y');
$mois_fr = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$mois_actuel = $mois_fr[date('n') - 1] . ' ' . date('Y');
?>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- ─── LAYOUT ADMIN AVEC SIDEBAR ─── -->
<div class="min-h-screen bg-[hsl(38_44%_96%)]">
    
    <!-- SECTION 1 — SIDEBAR -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-[hsl(20_30%_14%)] text-white z-50 overflow-y-auto">
        <div class="p-6 border-b border-white/10">
            <div class="font-display text-xl font-bold text-[hsl(14_72%_46%)]">Saveur Kaolack</div>
            <div class="text-xs text-white/60 mt-1">Panel Administration</div>
        </div>
        
        <nav class="p-4 space-y-1">
            <!-- Dashboard (actif) -->
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[hsl(14_72%_46%)] text-white transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            
            <!-- Validation Restaurants -->
            <a href="restaurants.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Demandes en attente
                </div>
                <?php if ($nb_restos_attente > 0): ?>
                <span class="animate-pulse bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $nb_restos_attente; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Restaurants -->
            <a href="restaurants.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Restaurants
                </div>
                <?php if ($nb_restos_attente > 0): ?>
                <span class="animate-pulse bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $nb_restos_attente; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Commandes -->
            <a href="commandes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Commandes
            </a>
            
            <!-- Utilisateurs -->
            <a href="utilisateurs.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Clients
            </a>
            
            <!-- Plats -->
            <a href="plats.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Plats
            </a>
            
            <!-- Avis avec badge -->
            <a href="avis.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Avis
                </div>
                <?php if ($nb_avis_attente > 0): ?>
                <span class="animate-pulse bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $nb_avis_attente; ?></span>
                <?php endif; ?>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/10">
                <a href="../index.php" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Voir le site
                </a>
                
                <a href="../deconnexion.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/20 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Déconnexion
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- ─── CONTENU PRINCIPAL ─── -->
    <main class="ml-64">
        
        <!-- SECTION 2 — TOPBAR -->
        <header class="bg-white border-b border-[hsl(30_25%_86%)] px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Dashboard</h1>
                    <p class="text-sm text-[hsl(25_15%_42%)]"><?php echo $date_jour; ?></p>
                </div>
                
                <div class="flex items-center gap-4">
                    <?php if ($nb_restos_attente > 0): ?>
                    <a href="restaurants.php" class="flex items-center gap-2 bg-orange-100 text-orange-700 px-4 py-2 rounded-xl text-sm font-medium animate-pulse hover:bg-orange-200 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <?php echo $nb_restos_attente; ?> restaurant(s) en attente
                    </a>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-3 pl-4 border-l border-[hsl(30_25%_86%)]">
                        <div class="h-10 w-10 rounded-full bg-[hsl(14_72%_46%)] text-white flex items-center justify-center font-medium">
                            <?php echo substr($_SESSION['prenom'] ?? 'A', 0, 1); ?>
                        </div>
                        <div class="text-sm">
                            <div class="font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></div>
                            <div class="text-[hsl(25_15%_42%)]">Administrateur</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- ─── CONTENU DU DASHBOARD ─── -->
        <div class="p-8 space-y-8">
            
            <?php if (isset($erreur)): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl"><?php echo $erreur; ?></div>
            <?php endif; ?>
            
            <!-- SECTION 3 — 6 CARTES STATISTIQUES -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- CARTE 1 — Restaurants -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[hsl(20_30%_14%)]">Restaurants actifs</h3>
                        <div class="h-10 w-10 rounded-xl bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)] flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[hsl(20_30%_14%)]"><?php echo $nb_restos_actifs; ?></div>
                    <?php if ($nb_restos_attente > 0): ?>
                    <div class="mt-2 text-sm text-orange-600"><?php echo $nb_restos_attente; ?> en attente de validation</div>
                    <?php endif; ?>
                </div>
                
                <!-- CARTE 2 — Clients -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[hsl(20_30%_14%)]">Clients inscrits</h3>
                        <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[hsl(20_30%_14%)]"><?php echo $nb_clients; ?></div>
                    <div class="mt-2 text-sm text-green-600">+<?php echo $nouveaux_clients_mois; ?> ce mois</div>
                </div>
                
                <!-- CARTE 3 — Commandes aujourd'hui -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[hsl(20_30%_14%)]">Commandes aujourd'hui</h3>
                        <div class="h-10 w-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[hsl(20_30%_14%)]"><?php echo $nb_cmd_jour; ?></div>
                    <div class="mt-2 text-sm text-[hsl(25_15%_42%)]"><?php echo number_format($montant_jour, 0, ',', ' '); ?> FCFA</div>
                </div>
                
                <!-- CARTE 4 — Revenus ce mois -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[hsl(20_30%_14%)]">Revenus ce mois</h3>
                        <div class="h-10 w-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[hsl(14_72%_46%)]"><?php echo number_format($revenus_mois, 0, ',', ' '); ?> <span class="text-lg">FCFA</span></div>
                    <div class="mt-2 text-sm text-[hsl(25_15%_42%)]">tous restaurants confondus</div>
                </div>
                
                <!-- CARTE 5 — Total commandes mois -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[hsl(20_30%_14%)]">Commandes ce mois</h3>
                        <div class="h-10 w-10 rounded-xl bg-yellow-100 text-yellow-600 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[hsl(20_30%_14%)]"><?php echo $nb_cmd_mois; ?></div>
                    <?php if ($nb_cmd_annulees > 0): ?>
                    <div class="mt-2 text-sm text-red-500"><?php echo $nb_cmd_annulees; ?> annulées</div>
                    <?php endif; ?>
                </div>
                
                <!-- CARTE 6 — Avis à modérer -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-6 shadow-soft">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[hsl(20_30%_14%)]">Avis à modérer</h3>
                        <div class="h-10 w-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[hsl(20_30%_14%)]"><?php echo $nb_avis_attente; ?></div>
                    <a href="avis.php" class="mt-2 text-sm text-[hsl(14_72%_46%)] hover:underline">Modérer les avis →</a>
                </div>
            </div>
            
            <!-- SECTION 4 — GRAPHIQUE + TOP RESTAURANTS (empilés verticalement) -->
            <div class="grid grid-cols-1 gap-6">
                
                <!-- GRAPHIQUE COMPACT -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-4 shadow-soft">
                    <h3 class="font-semibold text-[hsl(20_30%_14%)] mb-2 text-sm">Activité des 7 derniers jours</h3>
                    <div class="h-40">
                        <canvas id="chartCommandes"></canvas>
                    </div>
                </div>
                
                <!-- TOP 5 RESTAURANTS EN LIGNE HORIZONTAL -->
                <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] p-4 shadow-soft">
                    <h3 class="font-semibold text-[hsl(20_30%_14%)] mb-3 text-sm">Top 5 restaurants — <?php echo $mois_actuel; ?></h3>
                    
                    <?php if (!empty($top_restaurants)): ?>
                    <div class="grid grid-cols-5 gap-3">
                        <?php foreach ($top_restaurants as $index => $resto): ?>
                        <div class="text-center p-3 rounded-xl <?php echo $index === 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-[hsl(36_50%_98%)]'; ?>">
                            <div class="h-10 w-10 rounded-full <?php echo $index === 0 ? 'bg-yellow-400 text-white' : 'bg-[hsl(30_25%_86%)] text-[hsl(25_15%_42%)]'; ?> flex items-center justify-center font-bold text-sm mx-auto mb-2">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="font-medium text-[hsl(20_30%_14%)] text-xs truncate" title="<?php echo htmlspecialchars($resto['nom']); ?>"><?php echo htmlspecialchars($resto['nom']); ?></div>
                            <div class="text-xs text-[hsl(25_15%_42%)] mt-1"><?php echo $resto['nb_commandes']; ?> cmd</div>
                            <div class="font-bold text-[hsl(14_72%_46%)] text-xs mt-1"><?php echo number_format($resto['revenus'], 0, ',', ' '); ?> F</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-[hsl(25_15%_42%)] py-4 text-sm">Aucune donnée ce mois</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- SECTION 5 — TABLEAU DERNIÈRES COMMANDES -->
            <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] shadow-soft overflow-hidden">
                <div class="px-6 py-4 border-b border-[hsl(30_25%_86%)] flex items-center justify-between">
                    <h3 class="font-semibold text-[hsl(20_30%_14%)]">10 dernières commandes</h3>
                    <a href="commandes.php" class="text-sm text-[hsl(14_72%_46%)] hover:underline">Voir toutes →</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[hsl(36_50%_98%)] text-[hsl(25_15%_42%)]">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium">N° SK</th>
                                <th class="px-6 py-3 text-left font-medium">Client</th>
                                <th class="px-6 py-3 text-left font-medium">Type</th>
                                <th class="px-6 py-3 text-left font-medium">Restaurant</th>
                                <th class="px-6 py-3 text-left font-medium">Total</th>
                                <th class="px-6 py-3 text-left font-medium">Statut</th>
                                <th class="px-6 py-3 text-left font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[hsl(30_25%_86%)]">
                            <?php foreach ($dernieres_commandes as $cmd): ?>
                            <tr class="hover:bg-[hsl(36_50%_98%)]/50">
                                <td class="px-6 py-3 font-medium text-[hsl(20_30%_14%)]">#<?php echo $cmd['id']; ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($cmd['nom_client']); ?></td>
                                <td class="px-6 py-3">
                                    <?php if ($cmd['type_client'] === 'membre'): ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">Membre</span>
                                    <?php else: ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 rounded-full">Invité</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3 text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($cmd['resto_nom']); ?></td>
                                <td class="px-6 py-3 font-medium"><?php echo number_format($cmd['total'], 0, ',', ' '); ?> F</td>
                                <td class="px-6 py-3">
                                    <?php
                                    $badge_class = match($cmd['statut']) {
                                        'en_attente' => 'bg-red-100 text-red-700',
                                        'en_preparation' => 'bg-blue-100 text-blue-700',
                                        'en_livraison' => 'bg-purple-100 text-purple-700',
                                        'livree' => 'bg-green-100 text-green-700',
                                        'annulee' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                    $statut_label = match($cmd['statut']) {
                                        'en_attente' => 'Reçue',
                                        'en_preparation' => 'Préparation',
                                        'en_livraison' => 'Livraison',
                                        'livree' => 'Livrée',
                                        'annulee' => 'Annulée',
                                        default => $cmd['statut']
                                    };
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium <?php echo $badge_class; ?> rounded-full"><?php echo $statut_label; ?></span>
                                </td>
                                <td class="px-6 py-3 text-[hsl(25_15%_42%)]"><?php echo date('d/m H:i', strtotime($cmd['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- SECTION 6 — RESTAURANTS EN ATTENTE (affiché seulement s'il y en a) -->
            <?php if (!empty($restos_attente)): ?>
            <div id="section-restos-attente" class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] shadow-soft overflow-hidden">
                <div class="px-6 py-4 border-b border-[hsl(30_25%_86%)] bg-orange-50">
                    <h3 class="font-semibold text-orange-800 flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Restaurants en attente de validation (<?php echo count($restos_attente); ?>)
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[hsl(36_50%_98%)] text-[hsl(25_15%_42%)]">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium">Restaurant</th>
                                <th class="px-6 py-3 text-left font-medium">Catégorie</th>
                                <th class="px-6 py-3 text-left font-medium">Quartier</th>
                                <th class="px-6 py-3 text-left font-medium">Gérant</th>
                                <th class="px-6 py-3 text-left font-medium">Contact</th>
                                <th class="px-6 py-3 text-left font-medium">Date demande</th>
                                <th class="px-6 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[hsl(30_25%_86%)]" id="tbody-restos-attente">
                            <?php foreach ($restos_attente as $resto): ?>
                            <tr class="hover:bg-[hsl(36_50%_98%)]/50" data-restaurant-id="<?php echo $resto['id']; ?>">
                                <td class="px-6 py-3 font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($resto['nom']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($resto['categorie_nom'] ?? 'Non catégorisé'); ?></td>
                                <td class="px-6 py-3 text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($resto['quartier']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($resto['prenom'] . ' ' . $resto['nom_user']); ?></td>
                                <td class="px-6 py-3 text-[hsl(25_15%_42%)]">
                                    <div><?php echo $resto['telephone']; ?></div>
                                    <div class="text-xs"><?php echo $resto['email']; ?></div>
                                </td>
                                <td class="px-6 py-3 text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y', strtotime($resto['created_at'])); ?></td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <button onclick="validerRestaurant(<?php echo $resto['id']; ?>, 'valider')" class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                                            Valider
                                        </button>
                                        <button onclick="voirRestaurant('<?php echo htmlspecialchars($resto['nom']); ?>', '<?php echo htmlspecialchars($resto['adresse']); ?>', '<?php echo htmlspecialchars($resto['quartier']); ?>', '<?php echo htmlspecialchars($resto['telephone']); ?>', '<?php echo htmlspecialchars($resto['email']); ?>', '<?php echo htmlspecialchars($resto['categorie_nom']); ?>', '<?php echo date('d/m/Y', strtotime($resto['created_at'])); ?>')" class="px-3 py-1.5 bg-[hsl(30_25%_86%)] text-[hsl(25_15%_42%)] text-xs font-medium rounded-lg hover:bg-[hsl(36_30%_92%)] transition-colors">
                                            Voir
                                        </button>
                                        <button onclick="validerRestaurant(<?php echo $resto['id']; ?>, 'rejeter')" class="px-3 py-1.5 bg-red-100 text-red-600 text-xs font-medium rounded-lg hover:bg-red-200 transition-colors">
                                            Rejeter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </main>
</div>

<!-- Données pour le graphique Chart.js -->
<script>
window.chartData = <?php echo json_encode($graphique_data); ?>;
</script>

<script src="../assets/js/dashboard_admin.js"></script>

<?php require_once '../includes/footer.php'; ?>
