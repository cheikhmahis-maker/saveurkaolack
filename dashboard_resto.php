<?php
/**
 * DASHBOARD RESTAURANT - Tableau de bord restaurateur
 */

// Démarrer la session en premier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// DEBUG - Afficher les infos session
if (isset($_GET['debug'])) {
    echo "<pre>DEBUG SESSION:\n";
    echo "ID: " . ($_SESSION['id'] ?? 'NON') . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'NON') . "\n";
    echo "Nom: " . ($_SESSION['nom'] ?? 'NON') . "\n";
    echo "</pre>";
}

// Vérifier que l'utilisateur est connecté et est un restaurant
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'restaurant') {
    header('Location: connexion.php');
    exit();
}

// Initialiser les variables statistiques
$total_commandes = 0;
$commandes_en_attente = 0;
$total_plats = 0;
$commandes_recentes = [];
$erreur = '';

try {
    $pdo = getDB();
    
    // Récupérer le restaurant associé à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si pas de restaurant lié, essayer de lier par email
    if (!$restaurant) {
        $user_email = $_SESSION['email'] ?? '';
        if ($user_email) {
            $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE email = ? AND (utilisateur_id IS NULL OR utilisateur_id = 0) LIMIT 1");
            $stmt->execute([$user_email]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($restaurant) {
                // Lier le restaurant à cet utilisateur
                $stmt = $pdo->prepare("UPDATE restaurants SET utilisateur_id = ? WHERE id = ?");
                $stmt->execute([$_SESSION['id'], $restaurant['id']]);
            }
        }
    }
    
    if (!$restaurant) {
        $erreur = "Aucun restaurant associé à votre compte. Veuillez contacter l'administrateur.";
    } else {
        // Statistiques
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE restaurant_id = ?");
        $stmt->execute([$restaurant['id']]);
        $total_commandes = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE restaurant_id = ? AND statut = 'en_attente'");
        $stmt->execute([$restaurant['id']]);
        $commandes_en_attente = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM plats WHERE restaurant_id = ?");
        $stmt->execute([$restaurant['id']]);
        $total_plats = $stmt->fetchColumn();
        
        // Commandes récentes
        $stmt = $pdo->prepare("
            SELECT c.*, 'Client' as client_nom, '' as client_prenom, '' as telephone
            FROM commandes c
            WHERE c.restaurant_id = ?
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$restaurant['id']]);
        $commandes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Plats du restaurant
        $stmt = $pdo->prepare("SELECT * FROM plats WHERE restaurant_id = ? ORDER BY categorie, nom");
        $stmt->execute([$restaurant['id']]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Avis des clients
        $stmt = $pdo->prepare("
            SELECT a.*, c.numero_tracking 
            FROM avis a 
            LEFT JOIN commandes c ON a.commande_id = c.id
            WHERE a.restaurant_id = ? 
            ORDER BY a.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$restaurant['id']]);
        $avis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Note moyenne
        $stmt = $pdo->prepare("SELECT AVG(note) FROM avis WHERE restaurant_id = ? AND statut = 'approuve'");
        $stmt->execute([$restaurant['id']]);
        $note_moyenne = $stmt->fetchColumn() ?? 0;
    }
    
} catch (PDOException $e) {
    $erreur = 'Erreur : ' . $e->getMessage();
}

$pageTitle = 'Tableau de bord - ' . ($restaurant['nom'] ?? 'Restaurant');
require_once 'includes/header.php';
?>

<div class="container mx-auto px-4 max-w-7xl py-8">
    <div class="grid gap-6 lg:grid-cols-4">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl bg-[hsl(14,72%,46%)] p-4 text-white mb-4">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="overflow-hidden">
                        <p class="font-bold truncate"><?php echo htmlspecialchars($restaurant['nom'] ?? 'Mon Restaurant'); ?></p>
                        <p class="text-xs text-white/70">Dashboard</p>
                    </div>
                </div>
            </div>
            
            <nav class="space-y-1 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-2 shadow-soft">
                <a href="dashboard_resto.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[hsl(14,72%,46%)]/10 text-[hsl(14,72%,46%)] font-medium">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="commandes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[hsl(25_15%_42%)] hover:bg-white/50 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Commandes
                    <?php if ($commandes_en_attente > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $commandes_en_attente; ?></span>
                    <?php endif; ?>
                </a>
                <a href="mes_plats.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[hsl(25_15%_42%)] hover:bg-white/50 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Mes Plats
                </a>
                <a href="profil.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[hsl(25_15%_42%)] hover:bg-white/50 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mon Profil
                </a>
                <a href="changer_mdp.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[hsl(25_15%_42%)] hover:bg-white/50 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Mot de passe
                </a>
                <a href="deconnexion.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnexion
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-[hsl(20_30%_14%)]">
                    Tableau de bord
                </h1>
                <p class="text-[hsl(25_15%_42%)]">Gérez votre restaurant</p>
            </div>

            <?php if (isset($erreur)): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                <?php echo htmlspecialchars($erreur); ?>
            </div>
            <?php endif; ?>

    <?php if ($restaurant): ?>
    <!-- Statistiques -->
    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl bg-gradient-warm p-6 text-white shadow-warm">
            <div class="text-3xl font-bold"><?php echo $total_commandes; ?></div>
            <div class="text-sm opacity-90">Commandes totales</div>
        </div>
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
            <div class="text-3xl font-bold text-[hsl(14_72%_46%)]"><?php echo $commandes_en_attente; ?></div>
            <div class="text-sm text-[hsl(25_15%_42%)]">En attente</div>
        </div>
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
            <div class="text-3xl font-bold text-[hsl(20_30%_14%)]"><?php echo $total_plats; ?></div>
            <div class="text-sm text-[hsl(25_15%_42%)]">Plats au menu</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Commandes récentes -->
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)]">Commandes récentes</h2>
                <a href="commandes.php?id=<?php echo $restaurant['id']; ?>" class="text-sm text-[hsl(14_72%_46%)] hover:underline">Voir tout</a>
            </div>
            
            <?php if (!empty($commandes_recentes)): ?>
            <div class="space-y-3">
                <?php foreach ($commandes_recentes as $cmd): ?>
                <div class="flex items-center justify-between rounded-xl bg-white p-3 border border-[hsl(30_25%_86%)]">
                    <div>
                        <div class="font-medium text-[hsl(20_30%_14%)]">
                            #<?php echo $cmd['id']; ?> - <?php echo htmlspecialchars($cmd['client_prenom'] . ' ' . $cmd['client_nom']); ?>
                        </div>
                        <div class="text-sm text-[hsl(25_15%_42%)]">
                            <?php echo number_format($cmd['total'], 0, ',', ' '); ?> F
                        </div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium <?php 
                        switch($cmd['statut']) {
                            case 'en_attente': echo 'bg-yellow-100 text-yellow-700'; break;
                            case 'en_preparation': echo 'bg-blue-100 text-blue-700'; break;
                            case 'en_livraison': echo 'bg-purple-100 text-purple-700'; break;
                            case 'livree': echo 'bg-green-100 text-green-700'; break;
                            default: echo 'bg-gray-100 text-gray-700';
                        }
                    ?>">
                        <?php 
                        switch($cmd['statut']) {
                            case 'en_attente': echo 'En attente'; break;
                            case 'en_preparation': echo 'En préparation'; break;
                            case 'en_livraison': echo 'En livraison'; break;
                            case 'livree': echo 'Livrée'; break;
                            default: echo $cmd['statut'];
                        }
                        ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-center text-[hsl(25_15%_42%)] py-8">Aucune commande pour le moment</p>
            <?php endif; ?>
        </div>

        <!-- Menu / Plats -->
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)]">Mon menu</h2>
                <a href="mes_plats.php" class="inline-flex items-center gap-1 rounded-xl bg-[hsl(14_72%_46%)] px-4 py-2 text-sm font-medium text-white hover:bg-[hsl(14_72%_40%)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ajouter
                </a>
            </div>
            
            <?php if (!empty($plats)): ?>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                <?php foreach ($plats as $plat): ?>
                <div class="flex items-center gap-3 rounded-xl bg-white p-3 border border-[hsl(30_25%_86%)]">
                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg">
                        <?php echo afficherImagePlat($plat['photo'] ?? null, $plat['nom'], 'h-full w-full object-cover'); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-[hsl(20_30%_14%)] truncate"><?php echo htmlspecialchars($plat['nom']); ?></div>
                        <div class="text-sm text-[hsl(25_15%_42%)]"><?php echo number_format($plat['prix'], 0, ',', ' '); ?> F</div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="rounded-full px-2 py-1 text-xs <?php echo $plat['disponible'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $plat['disponible'] ? 'Dispo' : 'Indispo'; ?>
                        </span>
                        <a href="modifier_plat.php?id=<?php echo $plat['id']; ?>" class="ml-1 p-1.5 text-[hsl(25_15%_42%)] hover:text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)]/10 rounded-lg transition-colors" title="Modifier">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-center text-[hsl(25_15%_42%)] py-8">Aucun plat dans votre menu</p>
            <?php endif; ?>
        </div>
        
        <!-- Avis clients -->
        <div class="rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-6 shadow-soft">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-display text-xl font-bold text-[hsl(20_30%_14%)]">Avis clients</h2>
                    <?php if ($note_moyenne > 0): ?>
                    <div class="flex items-center gap-1 mt-1">
                        <span class="text-yellow-500">★</span>
                        <span class="text-sm font-medium"><?php echo number_format($note_moyenne, 1); ?>/5</span>
                    </div>
                    <?php endif; ?>
                </div>
                <span class="text-sm text-[hsl(25_15%_42%)]"><?php echo count($avis_list); ?> avis</span>
            </div>
            
            <?php if (!empty($avis_list)): ?>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <?php foreach ($avis_list as $avis): ?>
                <div class="rounded-xl bg-white p-3 border border-[hsl(30_25%_86%)]">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex text-yellow-500">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span><?php echo $i <= $avis['note'] ? '★' : '☆'; ?></span>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y', strtotime($avis['created_at'])); ?></span>
                    </div>
                    <?php if (!empty($avis['commentaire'])): ?>
                    <p class="text-sm text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($avis['commentaire']); ?></p>
                    <?php else: ?>
                    <p class="text-sm text-[hsl(25_15%_42%)] italic">(Aucun commentaire)</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-center text-[hsl(25_15%_42%)] py-8">
                Aucun avis pour le moment.<br>
                <span class="text-xs">Les clients peuvent laisser un avis après livraison.</span>
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <a href="restaurant.php?id=<?php echo $restaurant['id']; ?>" class="flex items-center gap-3 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-4 hover:border-[hsl(14_72%_46%)]/30 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </div>
            <div>
                <div class="font-medium text-[hsl(20_30%_14%)]">Voir ma page</div>
                <div class="text-sm text-[hsl(25_15%_42%)]">Public</div>
            </div>
        </a>
        
        <a href="profil.php" class="flex items-center gap-3 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-4 hover:border-[hsl(14_72%_46%)]/30 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <div class="font-medium text-[hsl(20_30%_14%)]">Modifier infos</div>
                <div class="text-sm text-[hsl(25_15%_42%)]">Horaires, photos</div>
            </div>
        </a>
        
        <a href="mes_plats.php" class="flex items-center gap-3 rounded-2xl bg-[hsl(36_50%_98%)] border border-[hsl(30_25%_86%)] p-4 hover:border-[hsl(14_72%_46%)]/30 transition-colors">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <div>
                <div class="font-medium text-[hsl(20_30%_14%)]">Gérer le menu</div>
                <div class="text-sm text-[hsl(25_15%_42%)]">Plats & prix</div>
            </div>
        </a>
    </div>
    
        </div><!-- /lg:col-span-3 -->
    </div><!-- /grid -->
    <?php endif; ?>
</div><!-- /container -->

<?php require_once 'includes/footer.php'; ?>
