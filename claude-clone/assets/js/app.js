/**
 * Application JavaScript pour Claude Clone
 * Gère l'interface utilisateur et la communication avec l'API via SSE
 */

class ChatApp {
    constructor() {
        this.currentConversationId = null;
        this.systemPrompt = DEFAULT_SYSTEM_PROMPT;
        this.isLoading = false;
        
        // Éléments DOM
        this.elements = {
            messagesContainer: document.getElementById('messages-container'),
            messageInput: document.getElementById('message-input'),
            chatForm: document.getElementById('chat-form'),
            sendBtn: document.getElementById('send-btn'),
            conversationsList: document.getElementById('conversations-list'),
            conversationTitle: document.getElementById('conversation-title'),
            newChatBtn: document.getElementById('new-chat-btn'),
            deleteConversationBtn: document.getElementById('delete-conversation-btn'),
            welcomeMessage: document.getElementById('welcome-message'),
            settingsModal: document.getElementById('settings-modal'),
            systemPromptInput: document.getElementById('system-prompt'),
            saveSettingsBtn: document.getElementById('save-settings'),
            closeSettingsBtn: document.getElementById('close-settings'),
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadConversations();
        this.autoResizeTextarea();
    }
    
    bindEvents() {
        // Envoi du formulaire
        this.elements.chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Touche Entrée
        this.elements.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Redimensionnement automatique du textarea
        this.elements.messageInput.addEventListener('input', () => {
            this.autoResizeTextarea();
        });
        
        // Nouveau chat
        this.elements.newChatBtn.addEventListener('click', () => {
            this.startNewConversation();
        });
        
        // Supprimer la conversation
        this.elements.deleteConversationBtn.addEventListener('click', () => {
            this.deleteCurrentConversation();
        });
        
        // Paramètres
        this.elements.saveSettingsBtn.addEventListener('click', () => {
            this.saveSettings();
        });
        
        this.elements.closeSettingsBtn.addEventListener('click', () => {
            this.closeSettings();
        });
    }
    
    autoResizeTextarea() {
        const textarea = this.elements.messageInput;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
    }
    
    async loadConversations() {
        try {
            const response = await fetch('/claude-clone/public/api.php/api/conversations');
            const data = await response.json();
            
            this.renderConversationsList(data.conversations);
        } catch (error) {
            console.error('Erreur lors du chargement des conversations:', error);
        }
    }
    
    renderConversationsList(conversations) {
        this.elements.conversationsList.innerHTML = '';
        
        if (conversations.length === 0) {
            this.elements.conversationsList.innerHTML = `
                <div style="padding: 20px; text-align: center; color: var(--text-secondary);">
                    Aucune conversation
                </div>
            `;
            return;
        }
        
        conversations.forEach(conv => {
            const item = document.createElement('div');
            item.className = 'conversation-item';
            if (conv.id === this.currentConversationId) {
                item.classList.add('active');
            }
            item.textContent = conv.title;
            item.addEventListener('click', () => {
                this.loadConversation(conv.id);
            });
            this.elements.conversationsList.appendChild(item);
        });
    }
    
    async loadConversation(conversationId) {
        try {
            const response = await fetch(`/claude-clone/public/api.php/api/conversations/${conversationId}`);
            const data = await response.json();
            
            this.currentConversationId = conversationId;
            this.elements.conversationTitle.textContent = data.conversation.title;
            this.elements.deleteConversationBtn.style.display = 'block';
            this.elements.welcomeMessage.style.display = 'none';
            
            this.renderMessages(data.messages);
            this.updateActiveConversation();
        } catch (error) {
            console.error('Erreur lors du chargement de la conversation:', error);
            this.showError('Impossible de charger la conversation');
        }
    }
    
    renderMessages(messages) {
        // Effacer les messages existants (sauf le message de bienvenue)
        const existingMessages = this.elements.messagesContainer.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());
        
        // Afficher les messages
        messages.forEach(msg => {
            this.appendMessage(msg.role, msg.content, false);
        });
        
        this.scrollToBottom();
    }
    
    appendMessage(role, content, animate = true) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;
        
        const avatar = role === 'user' ? '👤' : '🤖';
        
        messageDiv.innerHTML = `
            <div class="message-avatar">${avatar}</div>
            <div class="message-content">${this.formatMessage(content)}</div>
        `;
        
        this.elements.messagesContainer.appendChild(messageDiv);
        
        if (animate) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(10px)';
            setTimeout(() => {
                messageDiv.style.transition = 'all 0.3s ease';
                messageDiv.style.opacity = '1';
                messageDiv.style.transform = 'translateY(0)';
            }, 10);
        }
        
        this.scrollToBottom();
        return messageDiv;
    }
    
    formatMessage(content) {
        // Échapper le HTML
        let formatted = content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        
        // Code en ligne
        formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Blocs de code
        formatted = formatted.replace(/```(\w*)\n([\s\S]*?)```/g, (match, lang, code) => {
            return `<pre><code class="language-${lang}">${code.trim()}</code></pre>`;
        });
        
        // Paragraphes
        formatted = formatted.split('\n\n').map(p => `<p>${p}</p>`).join('');
        
        // Gras et italique
        formatted = formatted.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        return formatted;
    }
    
    async sendMessage() {
        const message = this.elements.messageInput.value.trim();
        
        if (!message || this.isLoading) return;
        
        // Désactiver l'interface
        this.setLoading(true);
        this.elements.messageInput.value = '';
        this.autoResizeTextarea();
        
        // Masquer le message de bienvenue
        this.elements.welcomeMessage.style.display = 'none';
        
        // Afficher le message utilisateur
        this.appendMessage('user', message);
        
        // Créer une nouvelle conversation si nécessaire
        if (!this.currentConversationId) {
            try {
                const response = await fetch('/claude-clone/public/api.php/api/conversations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title: message.substring(0, 50) }),
                });
                const data = await response.json();
                this.currentConversationId = data.conversation_id;
                this.elements.conversationTitle.textContent = data.title;
                this.elements.deleteConversationBtn.style.display = 'block';
                this.loadConversations();
            } catch (error) {
                this.showError('Erreur lors de la création de la conversation');
                this.setLoading(false);
                return;
            }
        }
        
        // Afficher l'indicateur de frappe
        const typingIndicator = this.showTypingIndicator();
        
        // Envoyer la requête avec streaming SSE
        try {
            const response = await fetch('/claude-clone/public/api.php/api/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: this.currentConversationId,
                    message: message,
                    system_prompt: this.systemPrompt,
                }),
            });
            
            // Supprimer l'indicateur de frappe
            typingIndicator.remove();
            
            // Créer le conteneur pour la réponse
            const assistantMessageDiv = this.appendMessage('assistant', '');
            const contentDiv = assistantMessageDiv.querySelector('.message-content');
            let fullResponse = '';
            
            // Lire le stream SSE
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            while (true) {
                const { done, value } = await reader.read();
                
                if (done) break;
                
                const chunk = decoder.decode(value);
                const lines = chunk.split('\n');
                
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.slice(6);
                        
                        if (data === '[DONE]') {
                            break;
                        }
                        
                        try {
                            const parsed = JSON.parse(data);
                            
                            if (parsed.type === 'token') {
                                fullResponse += parsed.content;
                                contentDiv.innerHTML = this.formatMessage(fullResponse);
                                this.scrollToBottom();
                            } else if (parsed.type === 'error') {
                                this.showError(parsed.message);
                            }
                        } catch (e) {
                            // Ignorer les erreurs de parsing
                        }
                    }
                }
            }
            
            // Sauvegarder la réponse complète (optionnel, peut être fait par le backend)
            await this.saveAssistantMessage(fullResponse);
            
        } catch (error) {
            typingIndicator.remove();
            console.error('Erreur lors de l\'envoi du message:', error);
            this.showError('Erreur de connexion au serveur');
        } finally {
            this.setLoading(false);
        }
    }
    
    showTypingIndicator() {
        const indicatorDiv = document.createElement('div');
        indicatorDiv.className = 'message assistant';
        indicatorDiv.innerHTML = `
            <div class="message-avatar">🤖</div>
            <div class="message-content">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        this.elements.messagesContainer.appendChild(indicatorDiv);
        this.scrollToBottom();
        return indicatorDiv;
    }
    
    async saveAssistantMessage(content) {
        // Optionnel: sauvegarder la réponse via une API séparée
        // Pour l'instant, le backend devrait déjà l'avoir fait
    }
    
    startNewConversation() {
        this.currentConversationId = null;
        this.elements.conversationTitle.textContent = 'Nouvelle conversation';
        this.elements.deleteConversationBtn.style.display = 'none';
        this.elements.welcomeMessage.style.display = 'block';
        this.elements.messagesContainer.innerHTML = '';
        this.elements.messagesContainer.appendChild(this.elements.welcomeMessage);
        this.updateActiveConversation();
    }
    
    async deleteCurrentConversation() {
        if (!this.currentConversationId) return;
        
        if (!confirm('Voulez-vous vraiment supprimer cette conversation ?')) {
            return;
        }
        
        try {
            await fetch(`/claude-clone/public/api.php/api/conversations/${this.currentConversationId}`, {
                method: 'DELETE',
            });
            
            this.startNewConversation();
            this.loadConversations();
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            this.showError('Impossible de supprimer la conversation');
        }
    }
    
    updateActiveConversation() {
        const items = this.elements.conversationsList.querySelectorAll('.conversation-item');
        items.forEach(item => {
            item.classList.toggle('active', item.textContent === this.elements.conversationTitle.textContent);
        });
    }
    
    saveSettings() {
        this.systemPrompt = this.elements.systemPromptInput.value.trim() || DEFAULT_SYSTEM_PROMPT;
        this.closeSettings();
    }
    
    closeSettings() {
        this.elements.settingsModal.classList.remove('active');
    }
    
    setLoading(loading) {
        this.isLoading = loading;
        this.elements.sendBtn.disabled = loading;
        this.elements.messageInput.disabled = loading;
        
        if (loading) {
            this.elements.sendBtn.innerHTML = '<span>Envoi...</span>';
        } else {
            this.elements.sendBtn.innerHTML = `
                <span>Envoyer</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            `;
        }
    }
    
    scrollToBottom() {
        this.elements.messagesContainer.scrollTop = this.elements.messagesContainer.scrollHeight;
    }
    
    showError(message) {
        alert('Erreur: ' + message);
    }
}

// Constantes
const DEFAULT_SYSTEM_PROMPT = "Tu es un assistant IA utile, respectueux et honnête. Tu réponds aux questions de manière claire et précise.";

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.chatApp = new ChatApp();
});
