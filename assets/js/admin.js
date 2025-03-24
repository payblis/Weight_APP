// Fonctions utilitaires
function formatDate(date) {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatNumber(number) {
    return new Intl.NumberFormat('fr-FR').format(number);
}

// Gestion des modales
function showModal(modalId) {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function hideModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) {
        modal.hide();
    }
}

// Gestion des formulaires
function handleFormSubmit(form, successCallback) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            
            const data = await response.json();
            
            if (data.success) {
                if (successCallback) {
                    successCallback(data);
                }
                showToast('Succès', 'Opération réussie', 'success');
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
}

// Gestion des notifications
function showToast(title, message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.getElementById('toast-container') || document.body;
    container.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Gestion des confirmations
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Gestion des tableaux
function initDataTable(tableId, options = {}) {
    const defaultOptions = {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
        },
        pageLength: 10,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    };
    
    return new DataTable(`#${tableId}`, { ...defaultOptions, ...options });
}

// Gestion des graphiques
function createLineChart(canvasId, data, options = {}) {
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    };
    
    return new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: data,
        options: { ...defaultOptions, ...options }
    });
}

function createDoughnutChart(canvasId, data, options = {}) {
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };
    
    return new Chart(document.getElementById(canvasId), {
        type: 'doughnut',
        data: data,
        options: { ...defaultOptions, ...options }
    });
}

// Gestion des filtres
function initializeFilters() {
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', debounce((e) => {
            const target = input.dataset.search;
            const value = e.target.value.toLowerCase();
            const items = document.querySelectorAll(`[data-searchable="${target}"]`);
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(value) ? '' : 'none';
            });
        }, 300));
    });
}

// Utilitaire de debounce
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

// Gestion des exports
async function exportData(type, data, filename) {
    switch (type) {
        case 'csv':
            const csv = convertToCSV(data);
            downloadFile(csv, `${filename}.csv`, 'text/csv');
            break;
        case 'pdf':
            await exportToPDF(data, filename);
            break;
        case 'excel':
            await exportToExcel(data, filename);
            break;
    }
}

function convertToCSV(data) {
    if (!data || !data.length) return '';
    
    const headers = Object.keys(data[0]);
    const rows = data.map(obj => headers.map(header => obj[header]));
    
    return [
        headers.join(','),
        ...rows.map(row => row.join(','))
    ].join('\n');
}

function downloadFile(content, filename, type) {
    const blob = new Blob([content], { type });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialiser les filtres
    initializeFilters();
    
    // Gérer la fermeture du sidebar sur mobile
    const sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }
}); 