/**
 * DASHBOARD_ADMIN.JS - JavaScript du panel administrateur
 * Saveur Kaolack
 * 
 * Gère :
 * - Initialisation du graphique Chart.js
 * - Validation/Rejet des restaurants en AJAX
 * - Mise à jour des badges
 */

// ─── INITIALISATION QUAND LE DOM EST PRÊT ───
document.addEventListener('DOMContentLoaded', function() {
    initChartCommandes();
    initRafraichissementAuto();
});

// ─── GRAPHIQUE CHART.JS ───
function initChartCommandes() {
    const ctx = document.getElementById('chartCommandes');
    if (!ctx || !window.chartData) return;
    
    const data = window.chartData;
    
    // Préparer les données pour les 7 derniers jours
    const jours = [];
    const commandes = [];
    const revenus = [];
    
    // Générer les 7 derniers jours
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        const jourLabel = date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
        
        // Chercher si on a des données pour ce jour
        const jourData = data.find(d => d.jour === dateStr);
        
        jours.push(jourLabel);
        commandes.push(jourData ? parseInt(jourData.nb_commandes) : 0);
        revenus.push(jourData ? parseInt(jourData.revenus) / 1000 : 0); // En milliers
    }
    
    // Créer le graphique
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: jours,
            datasets: [
                {
                    label: 'Commandes',
                    data: commandes,
                    backgroundColor: 'hsl(14, 72%, 46%)', // Orange Saveur Kaolack
                    borderColor: 'hsl(14, 72%, 40%)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Revenus (milliers F)',
                    data: revenus,
                    backgroundColor: 'hsl(142, 76%, 36%)', // Vert
                    borderColor: 'hsl(142, 76%, 30%)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.yAxisID === 'y1') {
                                label += context.parsed.y + 'k FCFA';
                            } else {
                                label += context.parsed.y + ' commandes';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Nombre de commandes'
                    },
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenus (k FCFA)'
                    },
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// ─── VALIDATION/REJET D'UN RESTAURANT EN AJAX ───
function validerRestaurant(restaurantId, action) {
    // Confirmation avant rejet
    if (action === 'rejeter') {
        if (!confirm('Êtes-vous sûr de vouloir rejeter ce restaurant ?')) {
            return;
        }
    }
    
    // Désactiver les boutons pendant le traitement
    const row = document.querySelector(`tr[data-restaurant-id="${restaurantId}"]`);
    if (row) {
        const buttons = row.querySelectorAll('button');
        buttons.forEach(btn => btn.disabled = true);
    }
    
    // Envoyer la requête AJAX
    fetch('ajax/valider_restaurant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `restaurant_id=${restaurantId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Supprimer la ligne du tableau avec animation
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    row.remove();
                    
                    // Vérifier s'il reste des restaurants en attente
                    const tbody = document.getElementById('tbody-restos-attente');
                    if (tbody && tbody.children.length === 0) {
                        // Cacher toute la section
                        const section = document.getElementById('section-restos-attente');
                        if (section) {
                            section.style.transition = 'all 0.3s ease';
                            section.style.opacity = '0';
                            setTimeout(() => section.remove(), 300);
                        }
                    }
                }, 300);
            }
            
            // Mettre à jour les badges
            updateBadges(data.nb_restos_attente);
            
            // Afficher notification
            showNotification(data.message, 'success');
            
        } else {
            // Réactiver les boutons en cas d'erreur
            if (row) {
                const buttons = row.querySelectorAll('button');
                buttons.forEach(btn => btn.disabled = false);
            }
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        if (row) {
            const buttons = row.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = false);
        }
        showNotification('Erreur de connexion', 'error');
    });
}

// ─── MISE À JOUR DES BADGES ───
function updateBadges(nbRestosAttente) {
    // Mettre à jour le badge dans la sidebar
    const sidebarBadges = document.querySelectorAll('aside nav a[href="restaurants.php"] span');
    sidebarBadges.forEach(badge => {
        if (nbRestosAttente > 0) {
            badge.textContent = nbRestosAttente;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    });
    
    // Mettre à jour l'alerte dans la topbar
    const topbarAlert = document.querySelector('header .animate-pulse');
    if (topbarAlert) {
        if (nbRestosAttente > 0) {
            topbarAlert.innerHTML = `
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                ${nbRestosAttente} restaurant(s) en attente
            `;
        } else {
            topbarAlert.remove();
        }
    }
}

// ─── NOTIFICATION ───
function showNotification(message, type) {
    // Créer l'élément de notification
    const notif = document.createElement('div');
    notif.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-xl shadow-lg transform transition-all duration-300 translate-x-full ${
        type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
    }`;
    notif.innerHTML = `
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                ${type === 'success' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
                }
            </svg>
            ${message}
        </div>
    `;
    
    document.body.appendChild(notif);
    
    // Animation d'entrée
    setTimeout(() => {
        notif.classList.remove('translate-x-full');
    }, 100);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notif.classList.add('translate-x-full');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// ─── RAFRAÎCHISSEMENT AUTO DES COMMANDES ───
function initRafraichissementAuto() {
    // Rafraîchir le compteur de commandes toutes les 60 secondes
    setInterval(() => {
        rafraichirCommandesAujourdhui();
    }, 60000);
}

function rafraichirCommandesAujourdhui() {
    fetch('../api/commandes_aujourdhui.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage si les données ont changé
                console.log('Commandes aujourd\'hui mises à jour:', data);
            }
        })
        .catch(error => console.error('Erreur rafraîchissement:', error));
}

// ─── AFFICHER LES DÉTAILS D'UN RESTAURANT ───
function voirRestaurant(nom, adresse, quartier, telephone, email, categorie, date) {
    // Créer le modal
    const modal = document.createElement('div');
    modal.id = 'modal-restaurant';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all scale-100 overflow-hidden">
            <div class="bg-gradient-to-r from-[hsl(14,72%,46%)] to-[hsl(25,85%,45%)] px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">${nom}</h3>
                    <button onclick="fermerModalRestaurant()" class="text-white/80 hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-[hsl(14,72%,46%)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Adresse</p>
                        <p class="text-gray-600">${adresse}</p>
                        <p class="text-sm text-gray-500">${quartier}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-[hsl(14,72%,46%)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Téléphone</p>
                        <p class="text-gray-600">${telephone}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-[hsl(14,72%,46%)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Email</p>
                        <p class="text-gray-600">${email}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-[hsl(14,72%,46%)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Catégorie</p>
                        <p class="text-gray-600">${categorie || 'Non spécifiée'}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-[hsl(14,72%,46%)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-gray-900">Date d'inscription</p>
                        <p class="text-gray-600">${date}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
                <button onclick="fermerModalRestaurant()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Fermer
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Animation d'entrée
    setTimeout(() => {
        modal.querySelector('div').classList.add('scale-100');
    }, 10);
}

function fermerModalRestaurant() {
    const modal = document.getElementById('modal-restaurant');
    if (modal) {
        modal.remove();
    }
}

// ─── CHANGER STATUT RESTAURANT (ACTIVER/SUSPENDRE) ───
function changerStatut(restaurantId, nouveauStatut) {
    // Si le nouveau statut est 'actif', on veut ACTIVER (action = 'activer')
    // Si le nouveau statut est 'inactif', on veut SUSPENDRE (action = 'suspendre')
    const action = nouveauStatut === 'actif' ? 'activer' : 'suspendre';
    const message = action === 'activer'
        ? 'Activer ce restaurant ?' 
        : 'Suspendre ce restaurant ? Il ne sera plus visible.';
    console.log('Action:', action, 'Restaurant ID:', restaurantId);
    
    if (!confirm(message)) return;
    
    fetch('ajax/restaurant_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=${action}&id=${restaurantId}`
    })
    .then(r => {
        console.log('Réponse serveur:', r.status, r.statusText);
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text().then(text => {
            console.log('Corps réponse:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e, 'Texte:', text);
                throw new Error('Réponse invalide du serveur');
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    })
    .catch(err => {
        console.error('Erreur fetch:', err);
        alert('Erreur lors de l\'opération. Voir la console (F12) pour détails.');
    });
}

// ─── SUPPRIMER UN RESTAURANT ───
function supprimerRestaurant(restaurantId, nomRestaurant) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer définitivement "${nomRestaurant}" ?\n\nCette action est irréversible et supprimera tous les plats et commandes associés.`)) {
        return;
    }
    
    fetch('ajax/restaurant_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=supprimer&id=${restaurantId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur lors de la suppression');
    });
}
