<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claude Clone - Assistant IA</title>
    <link rel="stylesheet" href="/claude-clone/assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar pour l'historique -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🤖 Claude Clone</h1>
                <button id="new-chat-btn" class="btn btn-primary">
                    <span>+</span> Nouveau chat
                </button>
            </div>
            
            <div class="conversations-list" id="conversations-list">
                <!-- Les conversations seront chargées ici -->
            </div>
            
            <div class="sidebar-footer">
                <small>Propulsé par Mistral AI</small>
            </div>
        </aside>
        
        <!-- Zone principale de chat -->
        <main class="chat-main">
            <header class="chat-header">
                <h2 id="conversation-title">Nouvelle conversation</h2>
                <div class="header-actions">
                    <button id="delete-conversation-btn" class="btn btn-danger" style="display: none;">
                        🗑️ Supprimer
                    </button>
                </div>
            </header>
            
            <!-- Zone des messages -->
            <div class="messages-container" id="messages-container">
                <div class="welcome-message" id="welcome-message">
                    <div class="welcome-icon">👋</div>
                    <h3>Bienvenue sur Claude Clone!</h3>
                    <p>Je suis un assistant IA propulsé par Mistral AI.</p>
                    <p>Comment puis-je vous aider aujourd'hui?</p>
                </div>
                
                <!-- Les messages seront ajoutés ici dynamiquement -->
            </div>
            
            <!-- Zone de saisie -->
            <div class="input-container">
                <form id="chat-form" class="chat-form">
                    <textarea 
                        id="message-input" 
                        placeholder="Écrivez votre message..." 
                        rows="1"
                        maxlength="10000"
                    ></textarea>
                    <button type="submit" id="send-btn" class="btn btn-send">
                        <span>Envoyer</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
                <div class="input-footer">
                    <small>Appuyez sur Entrée pour envoyer, Maj+Entrée pour une nouvelle ligne</small>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal de configuration -->
    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <h3>Paramètres</h3>
            <div class="form-group">
                <label for="system-prompt">System Prompt:</label>
                <textarea id="system-prompt" rows="4" placeholder="Instructions pour l'assistant..."></textarea>
            </div>
            <div class="modal-actions">
                <button id="save-settings" class="btn btn-primary">Enregistrer</button>
                <button id="close-settings" class="btn btn-secondary">Fermer</button>
            </div>
        </div>
    </div>

    <script src="/claude-clone/assets/js/app.js"></script>
</body>
</html>
