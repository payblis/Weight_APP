<?php
/**
 * Système de traduction automatique avec LibreTranslate
 * Utilise l'API gratuite de LibreTranslate pour traduire le contenu
 */

require_once 'translations.php';

class TranslationManager {
    private $apiUrl = 'https://libretranslate.com/translate';
    private $sourceLang = 'fr';
    private $targetLang = 'en';
    private $cacheDir = 'cache/translations/';
    private $cacheExpiry = 86400; // 24 heures
    
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
        // Si c'est la même langue, retourner le texte original
        if ($from === $to) {
            return $text;
        }
        
        // Essayer d'abord les traductions manuelles
        $manualTranslation = $this->getManualTranslation($text, $from, $to);
        if ($manualTranslation !== false) {
            return $manualTranslation;
        }
        
        // Vérifier le cache
        $cachedTranslation = $this->getCachedTranslation($text, $from, $to);
        if ($cachedTranslation !== false) {
            return $cachedTranslation;
        }
        
        // Appeler l'API
        $translation = $this->callTranslationAPI($text, $from, $to);
        
        if ($translation !== false) {
            // Mettre en cache
            $this->cacheTranslation($text, $from, $to, $translation);
            return $translation;
        }
        
        // Si tout échoue, retourner le texte original
        return $text;
    }
    
    /**
     * Appelle l'API LibreTranslate
     */
    private function callTranslationAPI($text, $from, $to) {
        // Essayer d'abord LibreTranslate
        $translation = $this->callLibreTranslate($text, $from, $to);
        
        if ($translation !== false) {
            return $translation;
        }
        
        // Si LibreTranslate échoue, essayer Google Translate
        return $this->callGoogleTranslate($text, $from, $to);
    }
    
    /**
     * Appelle LibreTranslate API
     */
    private function callLibreTranslate($text, $from, $to) {
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
            error_log("Erreur LibreTranslate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Appelle Google Translate API (alternative)
     */
    private function callGoogleTranslate($text, $from, $to) {
        $url = 'https://translate.googleapis.com/translate_a/single';
        $params = [
            'client' => 'gtx',
            'sl' => $from,
            'tl' => $to,
            'dt' => 't',
            'q' => urlencode($text)
        ];
        
        $fullUrl = $url . '?' . http_build_query($params);
        
        try {
            $result = file_get_contents($fullUrl);
            
            if ($result === false) {
                return false;
            }
            
            $response = json_decode($result, true);
            
            if (isset($response[0][0][0])) {
                return $response[0][0][0];
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur Google Translate: " . $e->getMessage());
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
    private function getCachedTranslation($text, $from, $to) {
        $cacheKey = $this->getCacheKey($text, $from, $to);
        $cacheFile = $this->cacheDir . $cacheKey . '.txt';
        
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = json_decode($content, true);
            
            // Cache valide pendant 24 heures
            if ($data && isset($data['timestamp']) && (time() - $data['timestamp']) < $this->cacheExpiry) {
                return $data['translation'];
            }
        }
        
        return false;
    }
    
    /**
     * Met en cache une traduction
     */
    private function cacheTranslation($text, $from, $to, $translation) {
        $cacheKey = $this->getCacheKey($text, $from, $to);
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
    public function translatePage($content, $from = 'fr', $to = 'en') {
        if ($from === $to) {
            return $content;
        }
        
        // Essayer d'abord la traduction manuelle du contenu
        $translatedContent = translateContent($content, $to);
        
        // Si le contenu a été traduit manuellement, le retourner
        if ($translatedContent !== $content) {
            return $translatedContent;
        }
        
        // Sinon, utiliser la traduction par API
        return $this->translate($content, $from, $to);
    }
    
    /**
     * Obtient une traduction manuelle
     */
    private function getManualTranslation($text, $from, $to) {
        global $translations;
        
        if (!isset($translations[$from]) || !isset($translations[$to])) {
            return false;
        }
        
        // Chercher la clé correspondante
        foreach ($translations[$from] as $key => $frenchText) {
            if (trim($text) === trim($frenchText)) {
                return $translations[$to][$key];
            }
        }
        
        return false;
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
    $html .= '<button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
    $html .= '<i class="fas fa-globe me-1"></i>';
    $html .= ($currentLang === 'fr') ? 'FR' : 'EN';
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