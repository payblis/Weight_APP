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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Générer une suggestion
                            </button>
                        </form>

                        <?php if (!empty($suggestions)): ?>
                            <div class="list-group">
                                <?php foreach ($suggestions as $suggestion): ?>
                                    <?php $data = json_decode($suggestion['content'], true); ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($data['nom_du_repas']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="mt-2">
                                            <a href="create-foods-from-suggestion.php?id=<?php echo $suggestion['id']; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-plus"></i> Ajouter à mon journal
                                            </a>
                                        </div>
                                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('generateSuggestionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('generate-suggestions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'alimentation'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la génération de la suggestion');
            });
        });
    </script>
</body>
</html> 