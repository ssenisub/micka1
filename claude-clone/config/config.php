<?php
/**
 * Configuration principale de l'application Claude Clone
 * Adapté pour utiliser l'API Mistral avec Laragon (PHP/MySQL)
 */

// Chemins de base
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'claude_clone');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'API Mistral
define('MISTRAL_API_KEY', 'votre_cle_api_mistral_ici');
define('MISTRAL_API_URL', 'https://api.mistral.ai/v1');
define('MISTRAL_MODEL', 'mistral-large-latest');
define('MISTRAL_MAX_TOKENS', 4096);

// Configuration de la session
define('SESSION_LIFETIME', 3600); // 1 heure en secondes

// Configuration du rate limiting
define('RATE_LIMIT_REQUESTS', 10); // Nombre de requêtes max
define('RATE_LIMIT_WINDOW', 60);   // Fenêtre de temps en secondes

// Configuration de sécurité
define('INPUT_MAX_LENGTH', 10000); // Longueur max d'un message utilisateur
define('OUTPUT_MAX_LENGTH', 50000); // Longueur max de réponse

// Configuration du streaming SSE
define('SSE_RETRY', 1000); // Délai de reconnexion en ms
define('SSE_TIMEOUT', 120); // Timeout en secondes

// Logs
define('LOG_PATH', BASE_PATH . '/logs');
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// System Prompt par défaut
define('DEFAULT_SYSTEM_PROMPT', "Tu es un assistant IA utile, respectueux et honnête. Tu réponds aux questions de manière claire et précise. Si tu ne connais pas la réponse, tu le dis honnêtement.");
