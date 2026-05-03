# 🤖 Claude Clone - Version PHP/Mistral

Assistant IA conversationnel complet utilisant l'API Mistral, développé en PHP pour fonctionner avec Laragon (Windows).

## 📋 Table des matières

- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [Architecture](#-architecture)
- [Fonctionnalités](#-fonctionnalités)
- [Structure du projet](#-structure-du-projet)

---

## ⚙️ Prérequis

- **Laragon** installé (avec PHP 8.0+ et MySQL 5.7+)
- **Clé API Mistral** (obtenez-la sur https://console.mistral.ai)
- **Navigateur web** moderne (Chrome, Firefox, Edge)

---

## 🚀 Installation

### 1. Cloner/copier le projet

Placez ce dossier dans le répertoire `www` de Laragon :
```
C:\laragon\www\claude-clone\
```

### 2. Créer la base de données

1. Ouvrez **phpMyAdmin** (http://localhost/phpmyadmin)
2. Exécutez le fichier SQL : `database/schema.sql`
3. La base de données `claude_clone` sera créée automatiquement

### 3. Configurer l'API Mistral

Ouvrez le fichier `config/config.php` et modifiez :

```php
define('MISTRAL_API_KEY', 'votre_cle_api_mistral_ici');
```

Remplacez `votre_cle_api_mistral_ici` par votre vraie clé API.

### 4. Ajuster la configuration (optionnel)

Dans `config/config.php`, vous pouvez modifier :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'claude_clone');
define('DB_USER', 'root');
define('DB_PASS', ''); // Laissez vide par défaut sur Laragon

define('MISTRAL_MODEL', 'mistral-large-latest'); // Ou 'mistral-small-latest'
define('MISTRAL_MAX_TOKENS', 4096);
```

---

## 💻 Utilisation

### Démarrage

1. Lancez **Laragon** et cliquez sur **Start All**
2. Accédez à l'application : http://localhost/claude-clone/public/

### Fonctionnalités principales

- 💬 **Chat en temps réel** avec streaming token-par-token
- 📜 **Historique des conversations** sauvegardé en base de données
- 🎨 **Interface moderne** inspirée de Claude/ChatGPT
- ⚙️ **System Prompt personnalisable** via les paramètres
- 🗑️ **Suppression de conversations**
- ➕ **Création de nouveaux chats**
- 📱 **Design responsive** (mobile et desktop)

### Raccourcis clavier

- `Entrée` : Envoyer le message
- `Maj + Entrée` : Nouvelle ligne

---

## 🏗 Architecture

```
┌─────────────────┐
│   Frontend      │  HTML/CSS/JS Vanilla
│   (Interface)   │  SSE pour le streaming
└────────┬────────┘
         │
┌────────▼────────┐
│   API Router    │  public/api.php
│   (Entry Point) │  Routing simple
└────────┬────────┘
         │
┌────────▼────────┐
│  Controllers    │  ChatController
│  (Logique)      │  Validation & Orchestration
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
┌───▼──┐  ┌──▼────────┐
│Models│  │ Services  │
│ DB   │  │ Mistral   │
└──────┘  └───────────┘
```

---

## ✨ Fonctionnalités

### Implémentées (v1.0)

- ✅ Chat avec streaming SSE (Server-Sent Events)
- ✅ Intégration API Mistral
- ✅ Historique des conversations (MySQL)
- ✅ Gestion du contexte conversationnel
- ✅ Interface utilisateur moderne
- ✅ Support Markdown basique
- ✅ System Prompt personnalisable
- ✅ Rate limiting basique
- ✅ Gestion d'erreurs

### À venir (Roadmap)

- 🔲 Authentification utilisateur complète
- 🔲 Upload de documents (RAG)
- 🔲 Recherche dans l'historique
- 🔲 Export des conversations
- 🔲 Thèmes sombre/clair
- 🔲 Multi-langues
- 🔲 Outils (recherche web, exécution de code)

---

## 📁 Structure du projet

```
claude-clone/
├── config/
│   └── config.php              # Configuration générale
│
├── src/
│   ├── Controllers/
│   │   └── ChatController.php  # Logique métier du chat
│   ├── Models/
│   │   ├── Database.php        # Connexion PDO
│   │   └── Conversation.php    # Modèles Conversation & Message
│   ├── Services/
│   │   └── MistralService.php  # Intégration API Mistral
│   └── Middleware/             # (À implémenter)
│
├── public/
│   ├── index.php               # Page d'accueil
│   └── api.php                 # Point d'entrée API
│
├── assets/
│   ├── css/
│   │   └── style.css           # Styles CSS
│   └── js/
│       └── app.js              # JavaScript frontend
│
├── database/
│   └── schema.sql              # Script de création BDD
│
├── logs/                       # Logs applicatifs (à créer)
│
└── README.md                   # Ce fichier
```

---

## 🔧 Dépannage

### Erreur "Conversation non trouvée"

Vérifiez que la base de données est bien créée et que les tables existent.

### Erreur API Mistral

1. Vérifiez votre clé API dans `config/config.php`
2. Assurez-vous d'avoir des crédits sur votre compte Mistral
3. Testez l'API avec curl :
```bash
curl https://api.mistral.ai/v1/models \
  -H "Authorization: Bearer VOTRE_CLE"
```

### Le streaming ne fonctionne pas

1. Vérifiez que `output_buffering` est désactivé dans php.ini
2. Redémarrez Apache/Nginx dans Laragon
3. Désactivez tout proxy ou extension navigateur qui pourrait interférer

### Problèmes de CORS

Les en-têtes CORS sont configurés pour le développement local. Pour la production, ajustez dans `public/api.php`.

---

## 🔒 Sécurité

- **Clé API** : Ne commitez jamais `config/config.php` avec votre vraie clé
- **Input validation** : Tous les inputs sont validés côté serveur
- **SQL Injection** : Requêtes préparées avec PDO
- **XSS** : Échappement des sorties dans le frontend

Pour la production :
- Activez HTTPS
- Mettez en place une authentification
- Configurez un rate limiting plus strict
- Ajoutez des logs d'audit

---

## 📝 Licence

MIT - Voir le fichier LICENSE

---

## 🤝 Contribuer

1. Forkez le projet
2. Créez une branche : `git checkout -b feature/nouvelle-fonctionnalite`
3. Committez : `git commit -m "feat: ajout nouvelle fonctionnalité"`
4. Pushez : `git push origin feature/nouvelle-fonctionnalite`
5. Ouvrez une Pull Request

---

## 📞 Support

Pour toute question ou problème, ouvrez une issue sur le dépôt.

---

**Propulsé par Mistral AI** 🚀
