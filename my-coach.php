<?php
session_start();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$sql = "SELECT u.*, p.name as program_name, p.description as program_description 
        FROM users u 
        LEFT JOIN user_programs up ON u.id = up.user_id AND up.status = 'actif'
        LEFT JOIN programs p ON up.program_id = p.id 
        WHERE u.id = ?";
$user = fetchOne($sql, [$user_id]);

if (!$user) {
    $_SESSION['error_message'] = "Erreur : Utilisateur non trouvé.";
    redirect('login.php');
}

// Récupérer les suggestions d'IA
$sql = "SELECT id, content, created_at FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type = 'alimentation' 
        ORDER BY created_at DESC";
$suggestions = fetchAll($sql, [$user_id]);

// Récupérer les catégories d'aliments
$sql = "SELECT * FROM food_categories ORDER BY name";
$categories = fetchAll($sql);

// Récupérer les messages de succès/erreur
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Nettoyer les messages après les avoir récupérés
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Coach - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Mon Coach Personnel</h1>
                <p class="text-muted">Bienvenue <?php echo htmlspecialchars($user['username']); ?> !</p>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Suggestion de Repas</h5>
                    </div>
                    <div class="card-body">
                        <form id="generateSuggestionForm" class="mb-3">
                            <input type="hidden" name="type" value="alimentation">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Générer une suggestion
                            </button>
                        </form>

                        <?php if (!empty($suggestions)): ?>
                            <div class="list-group">
                                <?php foreach ($suggestions as $suggestion): ?>
                                    <?php 
                                    $data = json_decode($suggestion['content'], true);
                                    if (json_last_error() === JSON_ERROR_NONE): 
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($data['nom_du_repas']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-info me-2" 
                                                    onclick="showSuggestionDetails(<?php echo htmlspecialchars(json_encode($data)); ?>)">
                                                <i class="fas fa-eye"></i> Voir les détails
                                            </button>
                                            <a href="create-meal-from-suggestion.php?id=<?php echo $suggestion['id']; ?>" 
                                               class="btn btn-sm btn-success me-2">
                                                <i class="fas fa-plus"></i> Créer un repas
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteSuggestion(<?php echo $suggestion['id']; ?>)">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucune suggestion générée pour le moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher les détails de la suggestion -->
    <div class="modal fade" id="suggestionDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="suggestionDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher les détails d'une suggestion
        function showSuggestionDetails(data) {
            const modal = new bootstrap.Modal(document.getElementById('suggestionDetailsModal'));
            const content = document.getElementById('suggestionDetailsContent');
            
            let html = `
                <h4>${data.nom_du_repas}</h4>
                <div class="mt-3">
                    <h5>Ingrédients :</h5>
                    <ul class="list-group">
                        ${data.ingredients.map(ing => `
                            <li class="list-group-item">
                                <strong>${ing.nom}</strong> - ${ing.quantite}
                                <br>
                                <small class="text-muted">
                                    Calories: ${ing.calories} | 
                                    Protéines: ${ing.proteines}g | 
                                    Glucides: ${ing.glucides}g | 
                                    Lipides: ${ing.lipides}g
                                </small>
                            </li>
                        `).join('')}
                    </ul>
                </div>
                <div class="mt-3">
                    <h5>Valeurs nutritionnelles totales :</h5>
                    <ul class="list-group">
                        <li class="list-group-item">
                            Calories: ${data.valeurs_nutritionnelles.calories}
                        </li>
                        <li class="list-group-item">
                            Protéines: ${data.valeurs_nutritionnelles.proteines}g
                        </li>
                        <li class="list-group-item">
                            Glucides: ${data.valeurs_nutritionnelles.glucides}g
                        </li>
                        <li class="list-group-item">
                            Lipides: ${data.valeurs_nutritionnelles.lipides}g
                        </li>
                    </ul>
                </div>
            `;
            
            content.innerHTML = html;
            modal.show();
        }

        // Fonction pour supprimer une suggestion
        function deleteSuggestion(suggestionId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette suggestion ?')) {
                fetch('delete-suggestion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: suggestionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Gestion du formulaire de génération de suggestion
            const generateForm = document.getElementById('generateSuggestionForm');
            if (generateForm) {
                generateForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Désactiver le bouton pendant la génération
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Génération en cours...';
                    
                    // Récupérer le type de suggestion
                    const suggestionType = this.querySelector('input[name="type"]').value;
                    
                    // Préparer les données à envoyer
                    const data = {
                        type: suggestionType
                    };
                    
                    // Envoyer la requête AJAX
                    fetch('generate-suggestions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Afficher le message de succès
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success alert-dismissible fade show';
                            alertDiv.innerHTML = `
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            generateForm.insertAdjacentElement('beforebegin', alertDiv);
                            
                            // Recharger la page après 2 secondes
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            throw new Error(data.message || 'Une erreur est survenue');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        // Afficher le message d'erreur
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            Erreur: ${error.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        generateForm.insertAdjacentElement('beforebegin', alertDiv);
                    })
                    .finally(() => {
                        // Réactiver le bouton
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    });
                });
            }
        });
    </script>
</body>
</html> 