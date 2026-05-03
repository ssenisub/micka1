<?php
/**
 * Modèle de gestion des conversations et messages
 */

class Conversation {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crée une nouvelle conversation
     */
    public function create(string $userId, string $title = null): string {
        $id = $this->generateUuid();
        $title = $title ?? 'Nouvelle conversation';
        
        $stmt = $this->db->prepare("
            INSERT INTO conversations (id, user_id, title, created_at, updated_at)
            VALUES (:id, :user_id, :title, NOW(), NOW())
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':title' => $title,
        ]);
        
        return $id;
    }
    
    /**
     * Récupère une conversation par son ID
     */
    public function getById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM conversations WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Récupère toutes les conversations d'un utilisateur
     */
    public function getByUser(string $userId, int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT * FROM conversations 
            WHERE user_id = :user_id 
            ORDER BY updated_at DESC 
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Met à jour le titre d'une conversation
     */
    public function updateTitle(string $id, string $title): void {
        $stmt = $this->db->prepare("
            UPDATE conversations 
            SET title = :title, updated_at = NOW() 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
        ]);
    }
    
    /**
     * Supprime une conversation et ses messages
     */
    public function delete(string $id): void {
        $this->db->beginTransaction();
        
        try {
            // Supprimer les messages
            $stmt = $this->db->prepare("DELETE FROM messages WHERE conversation_id = :id");
            $stmt->execute([':id' => $id]);
            
            // Supprimer la conversation
            $stmt = $this->db->prepare("DELETE FROM conversations WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Génère un UUID v4
     */
    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

class Message {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Ajoute un message à une conversation
     */
    public function create(string $conversationId, string $role, string $content, int $tokens = null): string {
        $id = $this->generateUuid();
        
        $stmt = $this->db->prepare("
            INSERT INTO messages (id, conversation_id, role, content, tokens, created_at)
            VALUES (:id, :conversation_id, :role, :content, :tokens, NOW())
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':conversation_id' => $conversationId,
            ':role' => $role,
            ':content' => $content,
            ':tokens' => $tokens,
        ]);
        
        // Mettre à jour updated_at de la conversation
        $stmt = $this->db->prepare("
            UPDATE conversations SET updated_at = NOW() WHERE id = :id
        ");
        $stmt->execute([':id' => $conversationId]);
        
        return $id;
    }
    
    /**
     * Récupère tous les messages d'une conversation
     */
    public function getByConversation(string $conversationId, int $limit = 100): array {
        $stmt = $this->db->prepare("
            SELECT * FROM messages 
            WHERE conversation_id = :conversation_id 
            ORDER BY created_at ASC 
            LIMIT :limit
        ");
        
        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère les derniers messages pour le contexte
     */
    public function getForContext(string $conversationId, int $maxTokens = 3000): array {
        $messages = $this->getByConversation($conversationId, 200);
        $context = [];
        $totalTokens = 0;
        
        // Parcourir à l'envers pour prendre les messages les plus récents
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $estimatedTokens = ceil(strlen($messages[$i]['content']) / 4);
            
            if ($totalTokens + $estimatedTokens > $maxTokens) {
                break;
            }
            
            $context[] = [
                'role' => $messages[$i]['role'],
                'content' => $messages[$i]['content'],
            ];
            
            $totalTokens += $estimatedTokens;
        }
        
        // Inverser pour avoir l'ordre chronologique
        return array_reverse($context);
    }
    
    /**
     * Estime le nombre de tokens dans un texte
     */
    public function estimateTokens(string $text): int {
        return ceil(strlen($text) / 4);
    }
    
    /**
     * Génère un UUID v4
     */
    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
