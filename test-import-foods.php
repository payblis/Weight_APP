<?php
require_once 'includes/functions.php';

// Définir le chemin du fichier CSV
$csv_file = __DIR__ . '/aliments.csv';

// Fonction pour lire le fichier CSV
function readCSV($file, $lines = null) {
    if (!file_exists($file)) {
        throw new Exception("Le fichier CSV n'existe pas : " . $file);
    }
    
    $foods = [];
    $row = 0;
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Lire l'en-tête
        $header = fgetcsv($handle);
        
        // Vérifier que l'en-tête contient les colonnes nécessaires
        if (!$header || count($header) < 5) {
            throw new Exception("Le fichier CSV n'a pas le bon format. Il doit contenir : nom, calories, protéines, glucides, lipides");
        }
        
        // Lire les lignes de données
        while (($data = fgetcsv($handle)) !== FALSE && ($lines === null || $row < $lines)) {
            // Vérifier si la colonne calories existe et n'est pas vide
            if (isset($data[1]) && !empty($data[1])) {
                $foods[] = [
                    'name' => $data[0],
                    'calories' => (int)$data[1],
                    'protein' => (float)str_replace('g', '', $data[2]),
                    'carbs' => (float)str_replace('g', '', $data[3]),
                    'fat' => (float)str_replace('g', '', $data[4]),
                    'description' => $data[0] // Utiliser le nom comme description
                ];
            }
            $row++;
        }
        fclose($handle);
    } else {
        throw new Exception("Impossible d'ouvrir le fichier CSV");
    }
    return $foods;
}

// Fonction pour obtenir la catégorie d'un aliment via ChatGPT
function getFoodCategory($food_name) {
    $prompt = "Quelle est la catégorie de l'aliment suivant ? Répondre uniquement avec le numéro de la catégorie correspondante :\n";
    $prompt .= "1. Fruits et légumes\n";
    $prompt .= "2. Viandes et poissons\n";
    $prompt .= "3. Céréales et féculents\n";
    $prompt .= "4. Produits laitiers\n";
    $prompt .= "5. Boissons\n";
    $prompt .= "6. Snacks et desserts\n";
    $prompt .= "7. Autres\n\n";
    $prompt .= "Aliment : " . $food_name;

    $response = callChatGPTAPI($prompt, 'gpt-3.5-turbo');
    
    // Nettoyer la réponse pour obtenir uniquement le numéro
    $category_id = (int) preg_replace('/[^0-9]/', '', $response);
    
    // Vérifier que la catégorie est valide
    if ($category_id < 1 || $category_id > 7) {
        return 7; // Catégorie "Autres" par défaut
    }
    
    return $category_id;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $isTestImport = isset($_POST['test_import']);
        $foods = readCSV($csv_file, $isTestImport ? 5 : null);
        $results = [];
        $total = count($foods);
        $processed = 0;
        $start_time = microtime(true);
        
        foreach ($foods as $food) {
            // Vérifier si l'aliment existe déjà
            $sql = "SELECT id FROM foods WHERE name = ?";
            $existing = fetchOne($sql, [$food['name']]);
            
            if (!$existing) {
                // Obtenir la catégorie via ChatGPT
                $category_id = getFoodCategory($food['name']);
                
                // Insérer l'aliment avec les macronutriments
                $sql = "INSERT INTO foods (name, description, calories, protein, carbs, fat, category_id, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $params = [
                    $food['name'],
                    $food['description'],
                    $food['calories'],
                    $food['protein'],
                    $food['carbs'],
                    $food['fat'],
                    $category_id
                ];
                
                insert($sql, $params);
                $results[] = [
                    'status' => 'success',
                    'message' => "Aliment ajouté : {$food['name']}",
                    'data' => $food
                ];
            } else {
                $results[] = [
                    'status' => 'warning',
                    'message' => "L'aliment {$food['name']} existe déjà",
                    'data' => $food
                ];
            }
            $processed++;
            
            // Si c'est un test import, on s'arrête après 5 aliments
            if ($isTestImport && $processed >= 5) {
                break;
            }
        }
        
        $end_time = microtime(true);
        $total_time = round($end_time - $start_time, 2);
        
        $_SESSION['import_results'] = [
            'results' => $results,
            'total' => $total,
            'processed' => $processed,
            'is_test' => $isTestImport,
            'total_time' => $total_time
        ];
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }
    
    redirect('test-import-foods.php');
}

// Récupérer les résultats de l'import si disponibles
$import_results = $_SESSION['import_results'] ?? null;
unset($_SESSION['import_results']);
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Aliments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1000;
        }
        .loader-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .progress {
            width: 300px;
            margin: 20px auto;
        }
        .results-table {
            display: none;
        }
        .timer {
            font-size: 1.2em;
            font-weight: bold;
            margin: 10px 0;
        }
        .time-remaining {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Import Aliments</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!file_exists($csv_file)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                Le fichier CSV n'existe pas à l'emplacement : <?php echo htmlspecialchars($csv_file); ?>
                <br>
                Veuillez placer le fichier aliments.csv dans le même dossier que ce script
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Actions</h5>
                    <div class="btn-group">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="test_import" class="btn btn-primary" onclick="showLoader()">
                                <i class="fas fa-vial"></i> Test Import (5 aliments)
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="full_import" class="btn btn-success" onclick="showLoader()">
                                <i class="fas fa-file-import"></i> Import Complet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($import_results): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        Résultats de l'import <?php echo $import_results['is_test'] ? '(Test)' : '(Complet)'; ?>
                    </h5>
                    <p>
                        Total des aliments traités : <?php echo $import_results['processed']; ?> sur <?php echo $import_results['total']; ?>
                    </p>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Statut</th>
                                    <th>Nom</th>
                                    <th>Calories</th>
                                    <th>Protéines</th>
                                    <th>Glucides</th>
                                    <th>Lipides</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_results['results'] as $result): ?>
                                    <tr class="<?php echo $result['status'] === 'success' ? 'table-success' : 'table-warning'; ?>">
                                        <td>
                                            <?php if ($result['status'] === 'success'): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-exclamation-circle text-warning"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($result['data']['name']); ?></td>
                                        <td><?php echo $result['data']['calories']; ?></td>
                                        <td><?php echo $result['data']['protein']; ?>g</td>
                                        <td><?php echo $result['data']['carbs']; ?>g</td>
                                        <td><?php echo $result['data']['fat']; ?>g</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="timer">
                        <div>Temps écoulé : <span id="elapsed-time">0:00</span></div>
                        <div>Temps restant estimé : <span id="remaining-time" class="time-remaining">Calcul en cours...</span></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Loader -->
    <div class="loader" id="loader">
        <div class="loader-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <h4 class="mt-3">Import en cours...</h4>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
            </div>
            <div class="timer">
                <div>Temps écoulé : <span id="elapsed-time">0:00</span></div>
                <div>Temps restant estimé : <span id="remaining-time" class="time-remaining">Calcul en cours...</span></div>
            </div>
            <p class="mt-2">Veuillez patienter pendant le traitement des données...</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let startTime = new Date().getTime();
        let totalItems = <?php echo isset($_POST['test_import']) ? 5 : 'null'; ?>;
        let processedItems = 0;
        
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        function updateTimer() {
            const currentTime = new Date().getTime();
            const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
            
            // Mettre à jour le temps écoulé
            document.getElementById('elapsed-time').textContent = formatTime(elapsedSeconds);
            
            // Calculer le temps restant estimé
            if (totalItems !== null && processedItems > 0) {
                const timePerItem = elapsedSeconds / processedItems;
                const remainingItems = totalItems - processedItems;
                const estimatedRemainingSeconds = Math.round(timePerItem * remainingItems);
                
                if (estimatedRemainingSeconds > 0) {
                    document.getElementById('remaining-time').textContent = formatTime(estimatedRemainingSeconds);
                } else {
                    document.getElementById('remaining-time').textContent = "Presque terminé...";
                }
            }
        }
        
        function showLoader() {
            document.getElementById('loader').style.display = 'block';
            startTime = new Date().getTime();
            processedItems = 0;
            // Mettre à jour le timer toutes les secondes
            setInterval(updateTimer, 1000);
        }
        
        // Cacher le loader si on revient sur la page avec des résultats
        <?php if ($import_results): ?>
            document.getElementById('loader').style.display = 'none';
            // Afficher le temps total dans les résultats
            const totalTime = <?php echo $import_results['total_time']; ?>;
            const resultsCard = document.querySelector('.card-body');
            if (resultsCard) {
                const timeInfo = document.createElement('p');
                timeInfo.className = 'text-muted';
                timeInfo.innerHTML = `<i class="fas fa-clock"></i> Temps total d'import : ${formatTime(totalTime)}`;
                resultsCard.insertBefore(timeInfo, resultsCard.querySelector('.table-responsive'));
            }
        <?php endif; ?>
    </script>
</body>
</html> 