    <footer class="border-t border-[hsl(30_25%_86%)]/60 bg-[hsl(36_78%_92%)]/30">
        <div class="container mx-auto px-4 max-w-7xl grid gap-8 py-12 md:grid-cols-4">
            <div>
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-warm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(38_60%_97%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a9 9 0 00-9 9v1a3 3 0 003 3h12a3 3 0 003-3v-1a9 9 0 00-9-9zM12 2v6m0 0l-3-3m3 3l3-3" />
                        </svg>
                    </div>
                    <div class="font-display text-lg font-bold text-[hsl(20_30%_14%)]">Saveur Kaolack</div>
                </div>
                <p class="mt-3 text-sm text-[hsl(25_15%_42%)]">
                    La saveur du Saloum, à portée de clic.
                </p>
            </div>
            <div>
                <div class="mb-3 text-sm font-semibold text-[hsl(20_30%_14%)]">Découvrir</div>
                <ul class="space-y-2 text-sm text-[hsl(25_15%_42%)]">
                    <li><a href="<?php echo BASE_URL; ?>restaurants.php" class="hover:text-[hsl(20_30%_14%)] transition-colors">Restaurants</a></li>
                    <li><a href="<?php echo BASE_URL; ?>plats.php" class="hover:text-[hsl(20_30%_14%)] transition-colors">Plats populaires</a></li>
                    <li><a href="<?php echo BASE_URL; ?>suivi.php" class="hover:text-[hsl(20_30%_14%)] transition-colors">Suivre une commande</a></li>
                </ul>
            </div>
            <div>
                <div class="mb-3 text-sm font-semibold text-[hsl(20_30%_14%)]">Pro</div>
                <ul class="space-y-2 text-sm text-[hsl(25_15%_42%)]">
                    <li><a href="<?php echo BASE_URL; ?>partenaire.php" class="hover:text-[hsl(20_30%_14%)] transition-colors">Devenir partenaire</a></li>
                    <li><a href="<?php echo BASE_URL; ?>connexion.php" class="hover:text-[hsl(20_30%_14%)] transition-colors">Espace restaurant</a></li>
                </ul>
            </div>
            <div>
                <div class="mb-3 text-sm font-semibold text-[hsl(20_30%_14%)]">Contact</div>
                <ul class="space-y-2 text-sm text-[hsl(25_15%_42%)]">
                    <li>Kaolack, Sénégal</li>
                    <li>+221 78 521 65 68</li>
                    <li>mouhamedsy3002@gmail.com</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-[hsl(30_25%_86%)]/60 py-5 text-center text-xs text-[hsl(25_15%_42%)]">
            &copy; <?php echo date('Y'); ?> Saveur Kaolack — Fait avec amour au Saloum.
        </div>
    </footer>
    
    <!-- Assistant Virtuel -->
    <script src="<?php echo BASE_URL; ?>assets/js/chatbot.js"></script>
</body>
</html>
