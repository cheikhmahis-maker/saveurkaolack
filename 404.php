<?php
http_response_code(404);
$pageTitle = 'Page non trouvée';
require_once 'includes/header.php';
?>

<section class="flex min-h-[60vh] items-center justify-center">
    <div class="text-center">
        <h1 class="mb-4 font-display text-6xl font-bold text-[hsl(20_30%_14%)]">404</h1>
        <p class="mb-6 text-xl text-[hsl(25_15%_42%)]">Oops! Page non trouvée</p>
        <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center gap-2 rounded-2xl px-6 py-3 bg-gradient-warm text-[hsl(38_60%_97%)] font-medium shadow-warm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Retour à l'accueil
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
