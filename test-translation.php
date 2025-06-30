<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/translation.php';

// Test simple de traduction
$testText = "Bonjour, comment allez-vous ?";
$translatedText = translate($testText, 'fr', 'en');

echo "<h1>Test de Traduction</h1>";
echo "<p><strong>Texte original :</strong> " . $testText . "</p>";
echo "<p><strong>Traduction :</strong> " . $translatedText . "</p>";
echo "<p><strong>Langue demandée :</strong> " . (isset($_GET['lang']) ? $_GET['lang'] : 'fr') . "</p>";

// Test de l'API
echo "<h2>Test de l'API LibreTranslate</h2>";
$apiUrl = 'https://libretranslate.com/translate';
$data = [
    'q' => 'Bonjour le monde',
    'source' => 'fr',
    'target' => 'en',
    'format' => 'text'
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data),
        'timeout' => 10
    ]
];

$context = stream_context_create($options);

try {
    $result = file_get_contents($apiUrl, false, $context);
    if ($result !== false) {
        $response = json_decode($result, true);
        echo "<p><strong>Réponse API :</strong> " . print_r($response, true) . "</p>";
    } else {
        echo "<p><strong>Erreur API :</strong> Impossible de contacter l'API</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Exception :</strong> " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
?> 