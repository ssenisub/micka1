<?php
/**
 * Contrôleur principal pour la gestion du chat
 */

class ChatController {
    private MistralService $mistral;
    private Conversation $conversationModel;
    private Message $messageModel;
    
    public function __construct() {
        $this->mistral = new MistralService();
        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
    }
    
    /**
     * Gère une requête de chat avec streaming SSE
     */
    public function chat(array $request): void {
        try {
            // Validation des entrées
            $this->validateRequest($request);
            
            $conversationId = $request['conversation_id'] ?? null;
            $message = trim($request['message']);
            $systemPrompt = $request['system_prompt'] ?? DEFAULT_SYSTEM_PROMPT;
            
            // Créer une nouvelle conversation si nécessaire
            if (!$conversationId) {
                // Générer un titre à partir du premier message
                $title = mb_substr($message, 0, 50) . (mb_strlen($message) > 50 ? '...' : '');
                $conversationId = $this->conversationModel->create('anonymous', $title);
            }
            
            // Vérifier que la conversation existe
            $conversation = $this->conversationModel->getById($conversationId);
            if (!$conversation) {
                throw new Exception("Conversation non trouvée");
            }
            
            // Sauvegarder le message utilisateur
            $userTokens = $this->messageModel->estimateTokens($message);
            $this->messageModel->create($conversationId, 'user', $message, $userTokens);
            
            // Récupérer le contexte de la conversation
            $contextMessages = $this->messageModel->getForContext($conversationId);
            
            // Démarrer le streaming SSE
            $this->mistral->chatStream($contextMessages, $systemPrompt);
            
            // Note: La réponse sera sauvegardée par le frontend ou via un callback
            // Pour une implémentation complète, on pourrait utiliser un webhook
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
    
    /**
     * Crée une nouvelle conversation
     */
    public function createConversation(array $request): array {
        $title = $request['title'] ?? 'Nouvelle conversation';
        $userId = $request['user_id'] ?? 'anonymous';
        
        $id = $this->conversationModel->create($userId, $title);
        
        return [
            'success' => true,
            'conversation_id' => $id,
            'title' => $title,
        ];
    }
    
    /**
     * Récupère une conversation avec ses messages
     */
    public function getConversation(string $id): array {
        $conversation = $this->conversationModel->getById($id);
        
        if (!$conversation) {
            throw new Exception("Conversation non trouvée");
        }
        
        $messages = $this->messageModel->getByConversation($id);
        
        return [
            'conversation' => $conversation,
            'messages' => $messages,
        ];
    }
    
    /**
     * Liste toutes les conversations d'un utilisateur
     */
    public function listConversations(string $userId = 'anonymous', int $limit = 50): array {
        $conversations = $this->conversationModel->getByUser($userId, $limit);
        
        return [
            'conversations' => $conversations,
            'total' => count($conversations),
        ];
    }
    
    /**
     * Supprime une conversation
     */
    public function deleteConversation(string $id): array {
        $this->conversationModel->delete($id);
        
        return [
            'success' => true,
            'message' => 'Conversation supprimée',
        ];
    }
    
    /**
     * Valide la requête entrante
     */
    private function validateRequest(array $request): void {
        if (empty($request['message'])) {
            throw new Exception("Le message est requis");
        }
        
        if (strlen($request['message']) > INPUT_MAX_LENGTH) {
            throw new Exception("Message trop long (maximum " . INPUT_MAX_LENGTH . " caractères)");
        }
        
        // Validation optionnelle de conversation_id si fourni
        if (!empty($request['conversation_id'])) {
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $request['conversation_id'])) {
                throw new Exception("ID de conversation invalide");
            }
        }
    }
    
    /**
     * Envoie une erreur au format SSE
     */
    private function sendError(string $message): void {
        header('Content-Type: text/event-stream');
        echo "data: " . json_encode([
            'type' => 'error',
            'message' => $message,
        ]) . "\n\n";
        flush();
    }
}
