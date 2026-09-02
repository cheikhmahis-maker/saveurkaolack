/**
 * CHATBOT.JS - Assistant virtuel Saveur Kaolack
 * Widget flottant avec interface de chat
 */

document.addEventListener('DOMContentLoaded', function() {
    // Créer le widget du chatbot
    const chatbotHTML = `
        <div id="chatbot-widget" class="fixed bottom-4 right-4 z-50 flex flex-col items-end">
            <!-- Bouton toggle -->
            <button id="chatbot-toggle" aria-label="Ouvrir l'assistant virtuel" class="h-14 w-14 rounded-full bg-gradient-to-r from-[hsl(14_72%_46%)] to-[hsl(38_92%_52%)] text-white shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>
            
            <!-- Fenêtre de chat -->
            <div id="chatbot-window" class="hidden mb-3 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-[hsl(30_25%_86%)] overflow-hidden flex flex-col" style="max-height: 500px;">
                <!-- Header -->
                <div class="bg-gradient-to-r from-[hsl(14_72%_46%)] to-[hsl(38_92%_52%)] p-4 text-white">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold">Assistant Saveur</div>
                            <div class="text-xs text-white/80 flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
                                En ligne
                            </div>
                        </div>
                    </div>
                    <button id="chatbot-close" aria-label="Fermer l'assistant virtuel" class="absolute top-4 right-4 text-white/80 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Messages -->
                <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-[hsl(36_78%_92%)]/30" style="max-height: 350px;">
                    <div class="flex items-start gap-2">
                        <div class="h-8 w-8 rounded-full bg-[hsl(14_72%_46%)] flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm max-w-[80%] text-sm text-[hsl(20_30%_14%)]">
                            <p class="font-medium mb-1">👋 Bonjour !</p>
                            <p>Je suis votre assistant virtuel. Je peux vous aider à :</p>
                            <ul class="mt-2 space-y-1 text-xs">
                                <li>🍗 Trouver des plats (poulet, poisson...)</li>
                                <li>🏪 Voir les restaurants ouverts</li>
                                <li>💰 Chercher par budget</li>
                                <li>📦 Suivre votre commande</li>
                            </ul>
                            <p class="mt-2 text-xs text-[hsl(25_15%_42%)]">Tapez "aide" pour tout voir</p>
                        </div>
                    </div>
                </div>
                
                <!-- Suggestions rapides -->
                <div class="px-4 py-2 bg-white border-t border-[hsl(30_25%_86%)]">
                    <div class="flex gap-2 overflow-x-auto pb-2 text-xs">
                        <button class="suggestion-btn whitespace-nowrap px-3 py-1.5 rounded-full bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)] hover:text-white transition-colors">
                            🍗 Poulet
                        </button>
                        <button class="suggestion-btn whitespace-nowrap px-3 py-1.5 rounded-full bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)] hover:text-white transition-colors">
                            🏪 Restaurants
                        </button>
                        <button class="suggestion-btn whitespace-nowrap px-3 py-1.5 rounded-full bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)] hover:text-white transition-colors">
                            💰 Moins 3000F
                        </button>
                        <button class="suggestion-btn whitespace-nowrap px-3 py-1.5 rounded-full bg-[hsl(14_72%_46%)]/10 text-[hsl(14_72%_46%)] hover:bg-[hsl(14_72%_46%)] hover:text-white transition-colors">
                            ⭐ Populaires
                        </button>
                    </div>
                </div>
                
                <!-- Input -->
                <div class="p-4 bg-white border-t border-[hsl(30_25%_86%)]">
                    <form id="chatbot-form" class="flex gap-2">
                        <input
                            type="text"
                            id="chatbot-input"
                            aria-label="Votre message"
                            placeholder="Écrivez votre message..."
                            class="flex-1 h-11 rounded-xl border border-[hsl(30_25%_86%)] px-4 text-sm focus:border-[hsl(14_72%_46%)] focus:outline-none"
                            autocomplete="off"
                        >
                        <button type="submit" class="h-11 w-11 rounded-xl bg-[hsl(14_72%_46%)] text-white hover:bg-[hsl(14_72%_40%)] transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Insérer le widget dans le body
    const div = document.createElement('div');
    div.innerHTML = chatbotHTML;
    document.body.appendChild(div);
    
    // Références
    const toggle = document.getElementById('chatbot-toggle');
    const window_ = document.getElementById('chatbot-window');
    const close = document.getElementById('chatbot-close');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const messages = document.getElementById('chatbot-messages');
    const suggestionBtns = document.querySelectorAll('.suggestion-btn');
    
    // Toggle
    toggle.addEventListener('click', () => {
        window_.classList.toggle('hidden');
        if (!window_.classList.contains('hidden')) {
            input.focus();
        }
    });
    
    // Close
    close.addEventListener('click', () => {
        window_.classList.add('hidden');
    });
    
    // Suggestions rapides
    suggestionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.textContent.trim().replace(/^[🍗🏪💰⭐] /, '');
            input.value = text;
            form.dispatchEvent(new Event('submit'));
        });
    });
    
    // Form submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        if (message.length > 300) {
            addMessage("Votre message est trop long (max 300 caractères). ✂️", 'bot');
            return;
        }
        
        // Afficher message utilisateur
        addMessage(message, 'user');
        input.value = '';
        
        // Afficher indicateur de frappe
        const typingId = addTypingIndicator();
        
        try {
            // Appel API
            const response = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            
            const data = await response.json();
            
            // Supprimer indicateur et afficher réponse
            removeTypingIndicator(typingId);
            addMessage(data.reply, 'bot');
            
        } catch (error) {
            removeTypingIndicator(typingId);
            addMessage("Désolé, je rencontre un problème technique. Réessayez plus tard ! 🛠️", 'bot');
        }
    });
    
    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2 animate-fade-in';
        
        if (sender === 'user') {
            div.innerHTML = `
                <div class="flex-1 flex justify-end">
                    <div class="bg-[hsl(14_72%_46%)] text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-sm max-w-[80%] text-sm whitespace-pre-line">
                        ${escapeHtml(text)}
                    </div>
                </div>
                <div class="h-8 w-8 rounded-full bg-[hsl(36_30%_92%)] flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[hsl(25_15%_42%)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            `;
        } else {
            div.innerHTML = `
                <div class="h-8 w-8 rounded-full bg-[hsl(14_72%_46%)] flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm max-w-[80%] text-sm text-[hsl(20_30%_14%)] whitespace-pre-line">
                    ${formatMessage(text)}
                </div>
            `;
        }
        
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
    
    function addTypingIndicator() {
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'flex items-start gap-2';
        div.innerHTML = `
            <div class="h-8 w-8 rounded-full bg-[hsl(14_72%_46%)] flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-[hsl(25_15%_42%)] animate-bounce" style="animation-delay: 0s"></span>
                <span class="h-2 w-2 rounded-full bg-[hsl(25_15%_42%)] animate-bounce" style="animation-delay: 0.2s"></span>
                <span class="h-2 w-2 rounded-full bg-[hsl(25_15%_42%)] animate-bounce" style="animation-delay: 0.4s"></span>
            </div>
        `;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return id;
    }
    
    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatMessage(text) {
        // Convertir les liens markdown en HTML
        let formatted = escapeHtml(text);
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, texte, url) => {
            const urlSafe = url.startsWith('/') || url.startsWith('http') ? url : '#';
            return `<a href="${urlSafe}" class="text-[hsl(14_72%_46%)] hover:underline">${texte}</a>`;
        });
        return formatted;
    }
    
    // Animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
});
