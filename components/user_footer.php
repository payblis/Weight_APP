    </div><!-- Fin du .user-content -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom scripts -->
    <script src="/assets/js/user.js"></script>

    <script>
    // Fonction pour afficher les toasts
    function showToast(title, message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // Fonction debounce pour limiter les appels
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Vérification des notifications
    function checkNotifications() {
        fetch('/api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const count = data.notifications.length;
                    const badge = document.getElementById('notificationCount');
                    const list = document.getElementById('notificationList');
                    
                    // Mise à jour du compteur
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline' : 'none';
                    
                    // Mise à jour de la liste
                    list.innerHTML = '';
                    if (count > 0) {
                        data.notifications.forEach(notif => {
                            const item = document.createElement('a');
                            item.className = 'dropdown-item';
                            item.href = notif.link || '#';
                            item.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-${notif.icon} me-2"></i>
                                    <div>
                                        <div class="small text-muted">${notif.time}</div>
                                        ${notif.message}
                                    </div>
                                </div>
                            `;
                            list.appendChild(item);
                        });
                    } else {
                        const item = document.createElement('div');
                        item.className = 'dropdown-item text-muted';
                        item.textContent = 'Aucune notification';
                        list.appendChild(item);
                    }
                }
            })
            .catch(error => console.error('Erreur:', error));
    }

    // Vérification initiale des notifications
    checkNotifications();
    
    // Vérification périodique des notifications
    setInterval(checkNotifications, 60000); // Toutes les minutes
    </script>
</body>
</html> 