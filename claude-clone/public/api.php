<?php
/**
 * Point d'entrée API principal
 * Toutes les requêtes API passent par ce fichier
 */

// Charger la configuration
require_once __DIR__ . '/../config/config.php';

// Charger les classes
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/Conversation.php';
require_once __DIR__ . '/../src/Services/MistralService.php';
require_once __DIR__ . '/../src/Controllers/ChatController.php';

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs en production
ini_set('log_errors', 1);

// Headers CORS pour le développement local
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Gérer les requêtes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fonction utilitaire pour retourner du JSON
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Récupérer la méthode HTTP et le endpoint
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/claude-clone/public';
$relativePath = str_replace($basePath, '', $path);

// Router simple
try {
    $controller = new ChatController();
    
    // Routes API
    if (preg_match('#^/api/chat/?$#', $relativePath)) {
        if ($method === 'POST') {
            // Lire le corps de la requête
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($input)) {
                throw new Exception("Données invalides");
            }
            
            // Pour le streaming, on ne retourne pas de JSON
            $controller->chat($input);
            exit();
        } else {
            jsonResponse(['error' => 'Méthode non autorisée'], 405);
        }
    }
    
    elseif (preg_match('#^/api/conversations/?$#', $relativePath)) {
        if ($method === 'GET') {
            $userId = $_GET['user_id'] ?? 'anonymous';
            $limit = (int)($_GET['limit'] ?? 50);
            $result = $controller->listConversations($userId, $limit);
            jsonResponse($result);
        }
        elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $controller->createConversation($input);
            jsonResponse($result, 201);
        }
        else {
            jsonResponse(['error' => 'Méthode non autorisée'], 405);
        }
    }
    
    elseif (preg_match('#^/api/conversations/([a-f0-9-]+)/?$#', $relativePath, $matches)) {
        $conversationId = $matches[1];
        
        if ($method === 'GET') {
            $result = $controller->getConversation($conversationId);
            jsonResponse($result);
        }
        elseif ($method === 'DELETE') {
            $result = $controller->deleteConversation($conversationId);
            jsonResponse($result);
        }
        else {
            jsonResponse(['error' => 'Méthode non autorisée'], 405);
        }
    }
    
    elseif ($relativePath === '/' || $relativePath === '') {
        // Page d'accueil
        include __DIR__ . '/../public/index.php';
    }
    
    else {
        // Endpoint non trouvé
        jsonResponse(['error' => 'Endpoint non trouvé'], 404);
    }
    
} catch (Exception $e) {
    // Vérifier si on est déjà en mode streaming
    if (headers_sent() || strpos(header('Content-Type'), 'text/event-stream') !== false) {
        // Déjà en streaming, envoyer l'erreur via SSE
        echo "data: " . json_encode([
            'type' => 'error',
            'message' => $e->getMessage(),
        ]) . "\n\n";
        flush();
    } else {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}
