<?php
/**
 * CONFIGURATION EMAIL - Saveur Kaolack
 * 
 * Ce fichier explique comment configurer l'envoi d'emails
 */

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Email - Saveur Kaolack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-3xl font-bold text-orange-600 mb-6">📧 Configuration Email</h1>
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
            <p class="text-green-800 font-medium">✅ Fonctionnalité installée avec succès !</p>
            <p class="text-green-700 text-sm mt-1">L'email de confirmation est maintenant actif sur votre site.</p>
        </div>
        
        <h2 class="text-xl font-bold text-gray-800 mb-4">🔧 Étape 1 : Créer un compte Gmail</h2>
        <div class="space-y-2 text-gray-600 mb-6">
            <p>1. Allez sur <a href="https://gmail.com" class="text-orange-600 underline">gmail.com</a></p>
            <p>2. Créez un compte dédié (ex: saveurkaolack@gmail.com)</p>
            <p>3. Ou utilisez un compte existant</p>
        </div>
        
        <h2 class="text-xl font-bold text-gray-800 mb-4">🔐 Étape 2 : Activer le mot de passe d'application</h2>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
            <p class="text-yellow-800 text-sm">
                ⚠️ <strong>Important :</strong> Gmail nécessite un "mot de passe d'application" spécial
            </p>
        </div>
        <div class="space-y-2 text-gray-600 mb-6">
            <p>1. Allez sur <a href="https://myaccount.google.com/security" class="text-orange-600 underline">myaccount.google.com/security</a></p>
            <p>2. Activez "Validation en 2 étapes" (obligatoire)</p>
            <p>3. Cherchez "Mots de passe d'application"</p>
            <p>4. Cliquez sur "Sélectionner l'application" → "Autre"</p>
            <p>5. Nommez : "Saveur Kaolack"</p>
            <p>6. Copiez le mot de passe généré (16 caractères)</p>
        </div>
        
        <h2 class="text-xl font-bold text-gray-800 mb-4">⚙️ Étape 3 : Configurer le fichier</h2>
        <div class="bg-gray-900 rounded-xl p-4 mb-6 text-sm font-mono text-green-400 overflow-x-auto">
<pre>// Ouvrir : includes/fonctions.php
// Ligne 667-668, remplacer :

$mail->Username = 'saveurkaolack@gmail.com';    // Votre email
$mail->Password = 'xxxx xxxx xxxx xxxx';        // Mot de passe d'app</pre>
        </div>
        
        <h2 class="text-xl font-bold text-gray-800 mb-4">🧪 Étape 4 : Tester</h2>
        <div class="space-y-2 text-gray-600 mb-6">
            <p>1. Faites une commande test sur votre site</p>
            <p>2. Entrez votre email dans le champ "Email"</p>
            <p>3. Validez la commande</p>
            <p>4. Vérifiez votre boîte mail (et spam) dans les 30 secondes</p>
        </div>
        
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <p class="text-blue-800 font-medium">💡 Astuce :</p>
            <p class="text-blue-700 text-sm mt-1">
                Si l'email n'arrive pas, vérifiez le fichier error_log dans XAMPP.
                La commande sera toujours créée même si l'email échoue.
            </p>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="inline-block bg-orange-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-orange-700 transition">
                Retour au site
            </a>
        </div>
    </div>
</body>
</html>
