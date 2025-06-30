<?php
/**
 * Système de traduction automatique avec LibreTranslate
 * Utilise l'API gratuite de LibreTranslate pour traduire le contenu
 */

class TranslationManager {
    private $apiUrl = 'https://libretranslate.com/translate';
    private $sourceLang = 'fr';
    private $targetLang = 'en';
    private $cacheDir = 'cache/translations/';
    
    public function __construct() {
        // Créer le dossier de cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Traduit un texte du français vers l'anglais
     */
    public function translate($text, $from = 'fr', $to = 'en') {
        // Nettoyer le texte
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }
        
        // Vérifier le cache
        $cacheKey = $this->getCacheKey($text, $from, $to);
        $cachedTranslation = $this->getCachedTranslation($cacheKey);
        
        if ($cachedTranslation !== false) {
            return $cachedTranslation;
        }
        
        // Appeler l'API
        $translation = $this->callTranslationAPI($text, $from, $to);
        
        if ($translation !== false) {
            // Mettre en cache
            $this->cacheTranslation($cacheKey, $translation);
            return $translation;
        }
        
        // En cas d'échec, retourner le texte original
        return $text;
    }
    
    /**
     * Appelle l'API LibreTranslate
     */
    private function callTranslationAPI($text, $from, $to) {
        $data = [
            'q' => $text,
            'source' => $from,
            'target' => $to,
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
            $result = file_get_contents($this->apiUrl, false, $context);
            
            if ($result === false) {
                return false;
            }
            
            $response = json_decode($result, true);
            
            if (isset($response['translatedText'])) {
                return $response['translatedText'];
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur de traduction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Génère une clé de cache pour le texte
     */
    private function getCacheKey($text, $from, $to) {
        return md5($text . $from . $to);
    }
    
    /**
     * Récupère une traduction du cache
     */
    private function getCachedTranslation($cacheKey) {
        $cacheFile = $this->cacheDir . $cacheKey . '.txt';
        
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = json_decode($content, true);
            
            // Cache valide pendant 30 jours
            if ($data && isset($data['timestamp']) && (time() - $data['timestamp']) < 2592000) {
                return $data['translation'];
            }
        }
        
        return false;
    }
    
    /**
     * Met en cache une traduction
     */
    private function cacheTranslation($cacheKey, $translation) {
        $cacheFile = $this->cacheDir . $cacheKey . '.txt';
        $data = [
            'translation' => $translation,
            'timestamp' => time()
        ];
        
        file_put_contents($cacheFile, json_encode($data));
    }
    
    /**
     * Traduit une page complète
     */
    public function translatePage($content) {
        // Détecter si l'utilisateur veut la version anglaise
        if (!isset($_GET['lang']) || $_GET['lang'] !== 'en') {
            return $content;
        }
        
        // Extraire les textes à traduire (simplifié)
        $patterns = [
            '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/s',
            '/<p[^>]*>(.*?)<\/p>/s',
            '/<span[^>]*>(.*?)<\/span>/s',
            '/<div[^>]*>(.*?)<\/div>/s',
            '/<li[^>]*>(.*?)<\/li>/s',
            '/<a[^>]*>(.*?)<\/a>/s',
            '/<button[^>]*>(.*?)<\/button>/s',
            '/<label[^>]*>(.*?)<\/label>/s',
            '/placeholder="([^"]*)"/',
            '/title="([^"]*)"/',
            '/alt="([^"]*)"/'
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function($matches) {
                if (isset($matches[1])) {
                    $translated = $this->translate($matches[1]);
                    return str_replace($matches[1], $translated, $matches[0]);
                }
                return $matches[0];
            }, $content);
        }
        
        return $content;
    }
}

// Fonction utilitaire pour traduire rapidement
function translate($text, $from = 'fr', $to = 'en') {
    static $translator = null;
    
    if ($translator === null) {
        $translator = new TranslationManager();
    }
    
    return $translator->translate($text, $from, $to);
}

// Fonction pour ajouter le sélecteur de langue
function getLanguageSelector() {
    $currentLang = isset($_GET['lang']) ? $_GET['lang'] : 'fr';
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Nettoyer l'URL des paramètres existants
    $urlParts = parse_url($currentUrl);
    $path = $urlParts['path'];
    $query = [];
    
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $query);
    }
    
    $html = '<div class="language-selector dropdown">';
    $html .= '<button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">';
    $html .= '<i class="fas fa-globe me-1"></i>';
    $html .= ($currentLang === 'fr') ? 'Français' : 'English';
    $html .= '</button>';
    $html .= '<ul class="dropdown-menu">';
    
    // Français
    $query['lang'] = 'fr';
    $frenchUrl = $path . '?' . http_build_query($query);
    $html .= '<li><a class="dropdown-item' . ($currentLang === 'fr' ? ' active' : '') . '" href="' . $frenchUrl . '">Français</a></li>';
    
    // Anglais
    $query['lang'] = 'en';
    $englishUrl = $path . '?' . http_build_query($query);
    $html .= '<li><a class="dropdown-item' . ($currentLang === 'en' ? ' active' : '') . '" href="' . $englishUrl . '">English</a></li>';
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}
?> 