<?php
/**
 * HEADER.PHP - En-tête
 * Saveur Kaolack
 */

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['PHP_SELF'];
if ($currentPage == 'index') $currentPage = '/';

// Détecter si on est sur une page dashboard (restaurant ou admin)
// Normaliser le chemin pour Windows (backslashes → forward slashes)
$normalizedPath = str_replace('\\', '/', $currentPath);
$isDashboardPage = (
    // Pages restaurant
    strpos($normalizedPath, 'dashboard_resto.php') !== false ||
    strpos($normalizedPath, '/menu.php') !== false ||
    strpos($normalizedPath, '/mes_plats.php') !== false ||
    strpos($normalizedPath, '/modifier_plat.php') !== false ||
    strpos($normalizedPath, '/modifier_restaurant.php') !== false ||
    strpos($normalizedPath, '/profil.php') !== false ||
    // Pages admin
    strpos($normalizedPath, '/admin/') !== false ||
    // Page commandes pour restaurant (mais pas pour client)
    ($currentPage == 'commandes' && !empty($_SESSION['role']) && $_SESSION['role'] !== 'client')
);

// Navigation publique (pour visiteurs et clients)
$links = [
    ['to' => 'restaurants', 'label' => 'Restaurants'],
    ['to' => 'plats', 'label' => 'Plats'],
    ['to' => 'suivi', 'label' => 'Suivre ma commande'],
    ['to' => 'partenaire', 'label' => 'Devenir partenaire'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Saveur Kaolack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,900&family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --background: 38 44% 96%;
            --foreground: 20 30% 14%;
            --card: 36 50% 98%;
            --card-foreground: 20 30% 14%;
            --primary: 14 72% 46%;
            --primary-foreground: 38 60% 97%;
            --secondary: 36 78% 92%;
            --secondary-foreground: 20 40% 18%;
            --muted: 36 30% 92%;
            --muted-foreground: 25 15% 42%;
            --accent: 142 38% 32%;
            --accent-foreground: 38 60% 97%;
            --saffron: 38 92% 52%;
            --saffron-foreground: 20 50% 14%;
            --destructive: 0 75% 48%;
            --destructive-foreground: 38 60% 97%;
            --border: 30 25% 86%;
            --input: 30 25% 86%;
            --ring: 14 72% 46%;
            --radius: 1rem;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: hsl(38 44% 96%);
            color: hsl(20 30% 14%);
        }
        
        h1, h2, h3, h4 {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .font-display {
            font-family: 'Fraunces', Georgia, serif;
        }
        
        .bg-gradient-warm {
            background: linear-gradient(135deg, hsl(14 72% 46%), hsl(28 88% 56%));
        }
        
        .bg-gradient-sun {
            background: linear-gradient(180deg, hsl(38 92% 60%), hsl(18 85% 55%));
        }
        
        .shadow-warm {
            box-shadow: 0 20px 50px -20px hsl(14 72% 35% / 0.35);
        }
        
        .shadow-soft {
            box-shadow: 0 8px 24px -12px hsl(20 40% 20% / 0.18);
        }
        
        .shadow-card {
            box-shadow: 0 2px 12px -4px hsl(20 40% 20% / 0.1);
        }
        
        .text-balance {
            text-wrap: balance;
        }
    </style>
</head>
<body class="min-h-screen bg-[hsl(38_44%_96%)]">
    <?php if ($isDashboardPage): ?>
    <!-- Header minimal pour dashboard (restaurant/admin) -->
    <header class="sticky top-0 z-50 border-b border-[hsl(30_25%_86%)]/60 bg-[hsl(38_44%_96%)]/85 backdrop-blur-md">
        <div class="container mx-auto px-4 max-w-7xl flex h-16 items-center justify-between">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>" class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-warm shadow-warm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a9 9 0 00-9 9v1a3 3 0 003 3h12a3 3 0 003-3v-1a9 9 0 00-9-9zM12 2v6m0 0l-3-3m3 3l3-3" />
                    </svg>
                </div>
                <div class="leading-tight">
                    <div class="font-display text-lg font-bold text-[hsl(20_30%_14%)]">Saveur</div>
                    <div class="-mt-1 text-[10px] font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Kaolack</div>
                </div>
            </a>

            <!-- Seulement Déconnexion pour dashboard -->
            <a href="<?php echo BASE_URL; ?>deconnexion.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Déconnexion
            </a>
        </div>
    </header>
    <?php if (($_SESSION['role'] ?? '') === 'restaurant' && isset($pdo) && $pdo instanceof PDO):
        assurerSchemaEssai($pdo);
        $stmt_bandeau = $pdo->prepare("SELECT essai_debut, abonnement_jusquau FROM restaurants WHERE utilisateur_id = ? LIMIT 1");
        $stmt_bandeau->execute([$_SESSION['id']]);
        $restaurant_bandeau = $stmt_bandeau->fetch(PDO::FETCH_ASSOC);
        if ($restaurant_bandeau) echo bandeauEssaiRestaurant($restaurant_bandeau);
    endif; ?>
    <?php else: ?>
    <!-- Header complet pour visiteurs et clients -->
    <header class="sticky top-0 z-50 border-b border-[hsl(30_25%_86%)]/60 bg-[hsl(38_44%_96%)]/85 backdrop-blur-md">
        <div class="container mx-auto px-4 max-w-7xl flex h-16 items-center justify-between">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>" class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-warm shadow-warm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a9 9 0 00-9 9v1a3 3 0 003 3h12a3 3 0 003-3v-1a9 9 0 00-9-9zM12 2v6m0 0l-3-3m3 3l3-3" />
                    </svg>
                </div>
                <div class="leading-tight">
                    <div class="font-display text-lg font-bold text-[hsl(20_30%_14%)]">Saveur</div>
                    <div class="-mt-1 text-[10px] font-semibold uppercase tracking-widest text-[hsl(14_72%_46%)]">Kaolack</div>
                </div>
            </a>

            <!-- Navigation Desktop -->
            <?php if (empty($_SESSION['role']) || $_SESSION['role'] === 'client'): ?>
            <!-- Liens publics pour visiteurs et clients -->
            <nav class="hidden items-center gap-7 text-sm font-medium md:flex">
                <?php foreach ($links as $link):
                    $active = $currentPage === $link['to'];
                ?>
                    <a href="<?php echo BASE_URL; ?><?php echo $link['to']; ?>.php"
                       class="<?php echo $active ? 'text-[hsl(20_30%_14%)]' : 'text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)]'; ?> transition-colors">
                        <?php echo $link['label']; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php else: ?>
            <!-- Navigation dashboard pour restaurant/admin -->
            <nav class="hidden items-center gap-5 text-sm font-medium md:flex">
                <?php if ($_SESSION['role'] === 'restaurant'): ?>
                    <a href="<?php echo BASE_URL; ?>dashboard_resto.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>commandes.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Commandes</a>
                    <a href="<?php echo BASE_URL; ?>menu.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Mon Menu</a>
                    <a href="<?php echo BASE_URL; ?>profil.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Profil</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Admin</a>
                    <a href="<?php echo BASE_URL; ?>admin/restaurants.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Restaurants</a>
                    <a href="<?php echo BASE_URL; ?>admin/commandes.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Commandes</a>
                    <a href="<?php echo BASE_URL; ?>admin/clients.php" class="text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">Clients</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <?php if (!empty($_SESSION['id'])): ?>
                    <!-- Utilisateur connecté -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/index.php" class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-purple-600 hover:bg-purple-50 rounded-md transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            Admin
                        </a>
                    <?php elseif ($_SESSION['role'] === 'client'): ?>
                        <a href="<?php echo BASE_URL; ?>profil_client.php" class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)]/10 rounded-md transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Mon Profil
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>deconnexion.php" class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Déconnexion">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden lg:inline">Déconnexion</span>
                    </a>
                <?php else: ?>
                    <!-- Non connecté -->
                    <a href="<?php echo BASE_URL; ?>connexion.php" class="hidden md:inline-flex p-2 text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>
                <?php endif; ?>
                <?php if (empty($_SESSION['role']) || $_SESSION['role'] === 'client'): ?>
                <a href="<?php echo BASE_URL; ?>panier.php"
                   class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 bg-[hsl(14_72%_46%)] text-[hsl(38_60%_97%)] rounded-md text-sm font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>Panier</span>
                </a>
                <?php endif; ?>
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 text-[hsl(25_15%_42%)] hover:text-[hsl(20_30%_14%)] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-[hsl(30_25%_86%)]/60 bg-[hsl(38_44%_96%)]">
            <nav class="container mx-auto px-4 max-w-7xl flex flex-col py-3">
                <?php if (empty($_SESSION['role']) || $_SESSION['role'] === 'client'): ?>
                    <!-- Liens publics pour visiteurs et clients -->
                    <?php foreach ($links as $link): ?>
                        <a href="<?php echo BASE_URL; ?><?php echo $link['to']; ?>.php"
                           class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">
                            <?php echo $link['label']; ?>
                        </a>
                    <?php endforeach; ?>
                <?php elseif ($_SESSION['role'] === 'restaurant'): ?>
                    <!-- Liens dashboard pour restaurant -->
                    <a href="<?php echo BASE_URL; ?>dashboard_resto.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>commandes.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Commandes</a>
                    <a href="<?php echo BASE_URL; ?>menu.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Mon Menu</a>
                    <a href="<?php echo BASE_URL; ?>profil.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Profil</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <!-- Liens admin -->
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Admin</a>
                    <a href="<?php echo BASE_URL; ?>admin/restaurants.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Restaurants</a>
                    <a href="<?php echo BASE_URL; ?>admin/commandes.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Commandes</a>
                    <a href="<?php echo BASE_URL; ?>admin/clients.php" class="py-2.5 text-sm font-medium text-[hsl(20_30%_14%)]">Clients</a>
                <?php endif; ?>
                <div class="mt-2 flex gap-2 border-t border-[hsl(30_25%_86%)]/60 pt-3">
                    <?php if (!empty($_SESSION['id'])): ?>
                        <!-- Lien spécifique selon le rôle -->
                        <?php if ($_SESSION['role'] === 'client'): ?>
                            <a href="<?php echo BASE_URL; ?>profil_client.php" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 border border-[hsl(30_25%_86%)] rounded-md text-sm font-medium text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)]/10 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Mon Profil
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>deconnexion.php" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 border border-red-200 rounded-md text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>connexion.php" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 border border-[hsl(30_25%_86%)] rounded-md text-sm font-medium text-[hsl(20_30%_14%)] hover:bg-[hsl(36_30%_92%)] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Connexion
                        </a>
                    <?php endif; ?>
                    <?php if (empty($_SESSION['role']) || $_SESSION['role'] === 'client'): ?>
                    <a href="<?php echo BASE_URL; ?>panier.php" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-[hsl(14_72%_46%)] text-[hsl(38_60%_97%)] rounded-md text-sm font-medium hover:bg-[hsl(14_72%_40%)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Panier
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
    <?php endif; ?>
