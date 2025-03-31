<?php
session_start();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$errors = [];

// Nettoyer les messages de la session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Récupérer les suggestions de repas
$sql = "SELECT id, content, created_at FROM ai_suggestions 
        WHERE user_id = ? AND suggestion_type IN ('repas', 'alimentation') 
        ORDER BY created_at DESC";
$suggestions = fetchAll($sql, [$user_id]);
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

    <!-- Contenu principal -->
    <div class="container py-4">
        <!-- Section de débogage -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0">Debug Information</h5>
            </div>
            <div class="card-body">
                <?php
                // Debug: Afficher les informations de session
                echo "<h6>Session Information:</h6>";
                echo "<pre>";
                print_r($_SESSION);
                echo "</pre>";

                // Debug: Afficher les suggestions brutes de la base de données
                echo "<h6>Suggestions brutes de la base de données:</h6>";
                $sql = "SELECT * FROM ai_suggestions WHERE user_id = ? AND suggestion_type IN ('repas', 'alimentation') ORDER BY created_at DESC";
                error_log("SQL Query pour récupérer les suggestions: " . $sql);
                error_log("User ID: " . $_SESSION['user_id']);
                $raw_suggestions = fetchAll($sql, [$_SESSION['user_id']]);
                error_log("Résultats de la requête: " . print_r($raw_suggestions, true));
                echo "<pre>";
                print_r($raw_suggestions);
                echo "</pre>";

                // Debug: Afficher le contenu JSON de chaque suggestion
                echo "<h6>Contenu JSON de chaque suggestion:</h6>";
                foreach ($raw_suggestions as $suggestion) {
                    echo "<h6>Suggestion ID: " . $suggestion['id'] . "</h6>";
                    echo "<pre>";
                    $json_data = json_decode($suggestion['content'], true);
                    print_r($json_data);
                    echo "</pre>";
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo "Erreur JSON: " . json_last_error_msg() . "<br>";
                    }
                }

                // Debug: Afficher les suggestions formatées
                echo "<h6>Suggestions formatées:</h6>";
                $formatted_suggestions = getMealSuggestions($_SESSION['user_id']);
                error_log("Suggestions formatées: " . print_r($formatted_suggestions, true));
                echo "<pre>";
                print_r($formatted_suggestions);
                echo "</pre>";
                ?>
            </div>
        </div>

        <h1 class="mb-4">Mon Coach</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulaire de génération de suggestion -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Générer une suggestion de repas</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="generate-suggestions.php">
                            <input type="hidden" name="suggestion_type" value="alimentation">
                            <p>Générez une suggestion de repas personnalisée basée sur vos objectifs et préférences.</p>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-robot me-1"></i>Générer une suggestion
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Liste des suggestions -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Suggestions de repas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suggestions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Aucune suggestion disponible. Utilisez le formulaire pour en générer.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="suggestionsAccordion">
                                <?php foreach ($suggestions as $index => $suggestion): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse<?php echo $index; ?>" 
                                                    aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                                    aria-controls="collapse<?php echo $index; ?>">
                                                Suggestion du <?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" 
                                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                             aria-labelledby="heading<?php echo $index; ?>" 
                                             data-bs-parent="#suggestionsAccordion">
                                            <div class="accordion-body">
                                                <?php
                                                $data = json_decode($suggestion['content'], true);
                                                if ($data): ?>
                                                    <h6><?php echo htmlspecialchars($data['nom_du_repas'] ?? 'Suggestion de repas'); ?></h6>
                                                    
                                                    <div class="mb-3">
                                                        <h6>Valeurs nutritionnelles :</h6>
                                                        <ul class="list-unstyled">
                                                            <li>Calories : <?php echo $data['valeurs_nutritionnelles']['calories'] ?? 0; ?> kcal</li>
                                                            <li>Protéines : <?php echo $data['valeurs_nutritionnelles']['proteines'] ?? 0; ?> g</li>
                                                            <li>Glucides : <?php echo $data['valeurs_nutritionnelles']['glucides'] ?? 0; ?> g</li>
                                                            <li>Lipides : <?php echo $data['valeurs_nutritionnelles']['lipides'] ?? 0; ?> g</li>
                                                        </ul>
                                                    </div>

                                                    <div class="mb-3">
                                                        <h6>Ingrédients :</h6>
                                                        <ul class="list-unstyled">
                                                            <?php foreach ($data['ingredients'] as $ingredient): ?>
                                                                <li>
                                                                    <?php echo htmlspecialchars($ingredient['nom'] ?? 'Ingrédient inconnu'); ?> 
                                                                    (<?php echo htmlspecialchars($ingredient['quantite'] ?? 'Quantité non spécifiée'); ?>)
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>

                                                    <div class="mb-3">
                                                        <h6>Instructions :</h6>
                                                        <ol>
                                                            <?php foreach ($data['instructions'] ?? [] as $instruction): ?>
                                                                <li><?php echo htmlspecialchars($instruction); ?></li>
                                                            <?php endforeach; ?>
                                                        </ol>
                                                    </div>

                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a href="create-meal-from-suggestion.php?id=<?php echo $suggestion['id']; ?>" 
                                                           class="btn btn-success">
                                                            <i class="fas fa-plus me-1"></i>Créer le repas
                                                        </a>
                                                        <a href="create-foods-from-suggestion.php?id=<?php echo $suggestion['id']; ?>" 
                                                           class="btn btn-primary">
                                                            <i class="fas fa-apple-alt me-1"></i>Créer les aliments
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Format de suggestion invalide
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 