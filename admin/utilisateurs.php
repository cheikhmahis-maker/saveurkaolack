<?php
/**
 * ADMIN/UTILISATEURS.PHP - Gestion des clients
 * Saveur Kaolack
 */

session_start();

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';

$clients = [];
$stats = ['total' => 0, 'nouveaux' => 0];
$erreur = '';

try {
    $pdo = getDB();
    
    // Stats
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'")->fetchColumn();
    $stats['nouveaux'] = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client' AND MONTH(created_at) = MONTH(NOW())")->fetchColumn();
    
    // Clients
    $stmt = $pdo->query("
        SELECT id, prenom, nom, email, telephone, adresse, quartier, created_at, statut
        FROM utilisateurs
        WHERE role = 'client'
        ORDER BY created_at DESC
    ");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erreur = 'Erreur BDD : ' . $e->getMessage();
}

$pageTitle = 'Gestion Clients';
require_once '../includes/header.php';

$nb_restos_attente = 0;
try {
    $pdo = getDB();
    $nb_restos_attente = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE statut = 'en_attente'")->fetchColumn();
} catch (PDOException $e) {}
?>

<div class="min-h-screen bg-[hsl(38_44%_96%)]">
    
    <aside id="admin-sidebar" class="fixed left-0 top-0 h-full w-64 bg-[hsl(20_30%_14%)] text-white z-50 overflow-y-auto -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="p-6 border-b border-white/10">
            <div class="font-display text-xl font-bold text-[hsl(14_72%_46%)]">Saveur Kaolack</div>
            <div class="text-xs text-white/60 mt-1">Panel Administration</div>
        </div>
        
        <nav class="p-4 space-y-1">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
                Dashboard
            </a>
            
            <a href="restaurants.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    Restaurants
                </div>
                <?php if ($nb_restos_attente > 0): ?>
                <span class="bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $nb_restos_attente; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="commandes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Commandes
            </a>
            
            <a href="utilisateurs.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[hsl(14_72%_46%)] text-white transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                Clients
            </a>
            
            <a href="plats.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13"/></svg>
                Plats
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/10">
                <a href="../index.php" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
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
    <div id="admin-sidebar-overlay" onclick="toggleAdminSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
    <script>
    function toggleAdminSidebar() {
        document.getElementById('admin-sidebar').classList.toggle('-translate-x-full');
        document.getElementById('admin-sidebar-overlay').classList.toggle('hidden');
    }
    </script>

    <main class="lg:ml-64">
        <header class="bg-white border-b border-[hsl(30_25%_86%)] px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button onclick="toggleAdminSidebar()" class="lg:hidden p-2 -ml-2 text-[hsl(20_30%_14%)]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Gestion des Clients</h1>
                    <p class="text-sm text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-[hsl(14_72%_46%)] text-white flex items-center justify-center font-medium">
                        <?php echo substr($_SESSION['prenom'] ?? 'A', 0, 1); ?>
                    </div>
                    <div class="text-sm hidden sm:block">
                        <div class="font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></div>
                        <div class="text-[hsl(25_15%_42%)]">Administrateur</div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8 space-y-6">
            
            <?php if ($erreur): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl"><?php echo $erreur; ?></div>
            <?php endif; ?>
            
            <!-- STATS -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-[hsl(14_72%_46%)]"><?php echo $stats['total']; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Clients inscrits</div>
                </div>
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-green-600">+<?php echo $stats['nouveaux']; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Nouveaux ce mois</div>
                </div>
            </div>
            
            <!-- TABLEAU -->
            <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] shadow-soft overflow-hidden">
                <div class="px-6 py-4 border-b border-[hsl(30_25%_86%)]">
                    <h3 class="font-semibold text-[hsl(20_30%_14%)]">Liste des clients</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[hsl(36_50%_98%)] text-[hsl(25_15%_42%)]">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Nom</th>
                                <th class="px-4 py-3 text-left font-medium">Contact</th>
                                <th class="px-4 py-3 text-left font-medium">Adresse</th>
                                <th class="px-4 py-3 text-left font-medium">Inscrit le</th>
                                <th class="px-4 py-3 text-left font-medium">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[hsl(30_25%_86%)]">
                            <?php foreach ($clients as $c): ?>
                            <tr class="hover:bg-[hsl(36_50%_98%)]/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-[hsl(20_30%_14%)]"><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></div>
                                </td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]">
                                    <div><?php echo $c['email']; ?></div>
                                    <div class="text-xs"><?php echo $c['telephone'] ?? '-'; ?></div>
                                </td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($c['adresse'] ?? '-') . ' - ' . htmlspecialchars($c['quartier'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $badge_class = match($c['statut']) {
                                        'actif' => 'bg-green-100 text-green-700',
                                        'inactif' => 'bg-gray-100 text-gray-600',
                                        'suspendu' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium <?php echo $badge_class; ?> rounded-full capitalize"><?php echo $c['statut']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($clients)): ?>
                <div class="text-center py-12 text-[hsl(25_15%_42%)]">
                    <svg class="h-12 w-12 mx-auto mb-4 text-[hsl(30_25%_86%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                    Aucun client inscrit
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
