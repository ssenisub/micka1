<?php
/**
 * Service d'intégration avec l'API Mistral AI
 * Gère les appels au LLM avec support du streaming SSE
 */

class MistralService {
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $maxTokens;
    
    public function __construct() {
        $this->apiKey = MISTRAL_API_KEY;
        $this->apiUrl = MISTRAL_API_URL;
        $this->model = MISTRAL_MODEL;
        $this->maxTokens = MISTRAL_MAX_TOKENS;
    }
    
    /**
     * Envoie une requête à l'API Mistral et retourne la réponse complète
     */
    public function chat(array $messages, string $systemPrompt = null): array {
        $url = $this->apiUrl . '/chat/completions';
        
        $payload = [
            'model' => $this->model,
            'messages' => [],
            'max_tokens' => $this->maxTokens,
            'stream' => false,
        ];
        
        // Ajouter le system prompt s'il existe
        if ($systemPrompt) {
            $payload['messages'][] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }
        
        // Ajouter les messages
        foreach ($messages as $msg) {
            $payload['messages'][] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erreur cURL: " . $error);
        }
        
        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            throw new Exception("Erreur API Mistral (" . $httpCode . "): " . 
                ($data['message'] ?? 'Erreur inconnue'));
        }
        
        $data = json_decode($response, true);
        
        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'model' => $data['model'] ?? $this->model,
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'stop',
        ];
    }
    
    /**
     * Envoie une requête à l'API Mistral avec streaming SSE
     * Cette méthode ne retourne rien, elle écrit directement dans le flux HTTP
     */
    public function chatStream(array $messages, string $systemPrompt = null): void {
        $url = $this->apiUrl . '/chat/completions';
        
        $payload = [
            'model' => $this->model,
            'messages' => [],
            'max_tokens' => $this->maxTokens,
            'stream' => true,
        ];
        
        // Ajouter le system prompt s'il existe
        if ($systemPrompt) {
            $payload['messages'][] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }
        
        // Ajouter les messages
        foreach ($messages as $msg) {
            $payload['messages'][] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }
        
        // Configuration pour le streaming
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Désactiver le buffering nginx/apache
        
        // Flush initial
        ob_end_clean();
        ob_implicit_flush(true);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: text/event-stream',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => SSE_TIMEOUT,
            CURLOPT_WRITEFUNCTION => function($curl, $data) {
                // Parser les données SSE de l'API Mistral
                $lines = explode("\n", $data);
                
                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $content = substr($line, 6); // Retirer "data: "
                        
                        if ($content === '[DONE]') {
                            // Fin du stream
                            echo "data: [DONE]\n\n";
                            flush();
                            break;
                        }
                        
                        try {
                            $json = json_decode($content, true);
                            if ($json && isset($json['choices'][0]['delta']['content'])) {
                                $token = $json['choices'][0]['delta']['content'];
                                
                                // Envoyer le token au client
                                echo "data: " . json_encode([
                                    'type' => 'token',
                                    'content' => $token,
                                ]) . "\n\n";
                                flush();
                            }
                        } catch (Exception $e) {
                            // Ignorer les erreurs de parsing
                        }
                    }
                }
                
                return strlen($data);
            },
        ]);
        
        curl_exec($ch);
        
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "data: " . json_encode([
                'type' => 'error',
                'message' => "Erreur de streaming: " . $error,
            ]) . "\n\n";
            flush();
        }
        
        // Message de fin
        echo "data: " . json_encode([
            'type' => 'done',
        ]) . "\n\n";
        flush();
    }
    
    /**
     * Estime le nombre de tokens dans un ensemble de messages
     */
    public function estimateTokens(array $messages): int {
        $totalChars = 0;
        
        foreach ($messages as $msg) {
            $totalChars += strlen($msg['content'] ?? '');
        }
        
        // Approximation: 1 token ≈ 4 caractères en français/anglais
        return ceil($totalChars / 4);
    }
    
    /**
     * Valide la configuration de l'API
     */
    public function validateConfig(): bool {
        return !empty($this->apiKey) && 
               $this->apiKey !== 'votre_cle_api_mistral_ici' &&
               filter_var($this->apiUrl, FILTER_VALIDATE_URL);
    }
}
