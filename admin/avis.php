<?php
/**
 * ADMIN/AVIS.PHP - Gestion des avis
 * Saveur Kaolack
 */

session_start();

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/fonctions.php';

$avis = [];
$stats = ['total' => 0, 'en_attente' => 0, 'approuve' => 0];
$erreur = '';
$succes = '';

// Traitement approbation / rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avis_id'], $_POST['action'])) {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? '')) {
        $erreur = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $avis_id = (int) $_POST['avis_id'];
        $action  = $_POST['action'];

        if ($avis_id > 0 && in_array($action, ['approuver', 'rejeter'])) {
            try {
                $pdo = getDB();
                $nouveau_statut = ($action === 'approuver') ? 'approuve' : 'rejete';

                $stmt = $pdo->prepare("UPDATE avis SET statut = ? WHERE id = ?");
                $stmt->execute([$nouveau_statut, $avis_id]);

                // Recalculer la note moyenne du restaurant
                if ($action === 'approuver') {
                    $stmt = $pdo->prepare("
                        UPDATE restaurants r
                        SET note_moyenne = (
                                SELECT ROUND(AVG(note), 1) FROM avis
                                WHERE restaurant_id = r.id AND statut = 'approuve'
                            ),
                            nb_avis = (
                                SELECT COUNT(*) FROM avis
                                WHERE restaurant_id = r.id AND statut = 'approuve'
                            )
                        WHERE r.id = (SELECT restaurant_id FROM avis WHERE id = ?)
                    ");
                    $stmt->execute([$avis_id]);
                }

                $succes = $action === 'approuver' ? "Avis approuvé et publié." : "Avis rejeté.";
            } catch (PDOException $e) {
                $erreur = "Erreur : " . $e->getMessage();
            }
        }
    }
}

try {
    $pdo = getDB();
    
    // Verifier si la table existe
    $tables = $pdo->query("SHOW TABLES LIKE 'avis'")->fetchAll();
    if (empty($tables)) {
        $erreur = "La table 'avis' n'existe pas encore dans la base de données.";
    } else {
        // Stats
        $stats['total'] = $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn();
        $stats['en_attente'] = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'")->fetchColumn();
        $stats['approuve'] = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'approuve'")->fetchColumn();
        
        // Avis avec details
        $stmt = $pdo->query("
            SELECT a.id, a.note, a.commentaire, a.statut, a.created_at,
                   COALESCE(u.prenom, 'Invité') as prenom,
                   COALESCE(u.nom, '') as nom,
                   r.nom as resto_nom
            FROM avis a
            LEFT JOIN utilisateurs u ON a.utilisateur_id = u.id
            LEFT JOIN restaurants r ON a.restaurant_id = r.id
            ORDER BY
                CASE a.statut WHEN 'en_attente' THEN 1 ELSE 2 END,
                a.created_at DESC
            LIMIT 50
        ");
        $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $erreur = 'Erreur BDD : ' . $e->getMessage();
}

$pageTitle = 'Gestion Avis';
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
            
            <a href="utilisateurs.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                Clients
            </a>
            
            <a href="plats.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13"/></svg>
                Plats
            </a>
            
            <a href="avis.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[hsl(14_72%_46%)] text-white transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                Avis
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
                    <h1 class="font-display text-2xl font-bold text-[hsl(20_30%_14%)]">Gestion des Avis</h1>
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
            <div class="bg-orange-100 border border-orange-200 text-orange-700 px-4 py-3 rounded-xl"><?php echo htmlspecialchars($erreur); ?></div>
            <?php endif; ?>

            <?php if ($succes): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl"><?php echo htmlspecialchars($succes); ?></div>
            <?php endif; ?>
            
            <?php if (empty($erreur)): ?>
            <!-- STATS -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-[hsl(14_72%_46%)]"><?php echo $stats['total']; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Total avis</div>
                </div>
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-orange-500"><?php echo $stats['en_attente']; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">À modérer</div>
                </div>
                <div class="bg-white rounded-xl border border-[hsl(30_25%_86%)] p-4 shadow-soft text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['approuve']; ?></div>
                    <div class="text-sm text-[hsl(25_15%_42%)]">Approuvés</div>
                </div>
            </div>
            
            <!-- TABLEAU -->
            <div class="bg-white rounded-2xl border border-[hsl(30_25%_86%)] shadow-soft overflow-hidden">
                <div class="px-6 py-4 border-b border-[hsl(30_25%_86%)]">
                    <h3 class="font-semibold text-[hsl(20_30%_14%)]">Liste des avis</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[hsl(36_50%_98%)] text-[hsl(25_15%_42%)]">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Client</th>
                                <th class="px-4 py-3 text-left font-medium">Restaurant</th>
                                <th class="px-4 py-3 text-left font-medium">Note</th>
                                <th class="px-4 py-3 text-left font-medium">Commentaire</th>
                                <th class="px-4 py-3 text-left font-medium">Statut</th>
                                <th class="px-4 py-3 text-left font-medium">Date</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[hsl(30_25%_86%)]">
                            <?php foreach ($avis as $a): ?>
                            <tr class="hover:bg-[hsl(36_50%_98%)]/50">
                                <td class="px-4 py-3"><?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?></td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]"><?php echo htmlspecialchars($a['resto_nom']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="h-4 w-4 <?php echo $i <= $a['note'] ? 'fill-current' : 'text-gray-300'; ?>" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)] max-w-xs truncate" title="<?php echo htmlspecialchars($a['commentaire']); ?>"><?php echo htmlspecialchars($a['commentaire'] ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $badge_class = match($a['statut']) {
                                        'en_attente' => 'bg-orange-100 text-orange-700 animate-pulse',
                                        'approuve' => 'bg-green-100 text-green-700',
                                        'rejete' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium <?php echo $badge_class; ?> rounded-full capitalize"><?php echo str_replace('_', ' ', $a['statut']); ?></span>
                                </td>
                                <td class="px-4 py-3 text-[hsl(25_15%_42%)]"><?php echo date('d/m/Y', strtotime($a['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($a['statut'] === 'en_attente'): ?>
                                    <div class="flex gap-2">
                                        <form method="POST" class="inline">
                                            <?php echo champTokenCSRF(); ?>
                                            <input type="hidden" name="avis_id" value="<?php echo $a['id']; ?>">
                                            <input type="hidden" name="action" value="approuver">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-green-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-600 transition-colors">
                                                ✓ Approuver
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Rejeter cet avis ?')">
                                            <?php echo champTokenCSRF(); ?>
                                            <input type="hidden" name="avis_id" value="<?php echo $a['id']; ?>">
                                            <input type="hidden" name="action" value="rejeter">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-red-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600 transition-colors">
                                                ✗ Rejeter
                                            </button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-xs text-[hsl(25_15%_42%)]">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($avis)): ?>
                <div class="text-center py-12 text-[hsl(25_15%_42%)]">
                    <svg class="h-12 w-12 mx-auto mb-4 text-[hsl(30_25%_86%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    Aucun avis enregistré
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
