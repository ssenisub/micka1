# 🤖 Claude Clone — Architecture & Guide de Développement

> Assistant IA conversationnel inspiré de Claude (Anthropic). Ce dépôt documente l'architecture complète, les choix techniques et les étapes pour construire un assistant IA de bout en bout.

---

## 📋 Table des matières

- [Vue d'ensemble](#-vue-densemble)
- [Architecture](#-architecture)
- [Stack technique recommandée](#-stack-technique-recommandée)
- [Structure du projet](#-structure-du-projet)
- [Couches détaillées](#-couches-détaillées)
  - [1. Interface utilisateur](#1-interface-utilisateur)
  - [2. API Gateway & Authentification](#2-api-gateway--authentification)
  - [3. Orchestrateur (Backend principal)](#3-orchestrateur-backend-principal)
  - [4. Couche IA / LLM](#4-couche-ia--llm)
  - [5. Mémoire & Contexte](#5-mémoire--contexte)
  - [6. Outils & Intégrations](#6-outils--intégrations)
  - [7. Sécurité & Modération](#7-sécurité--modération)
  - [8. Infrastructure](#8-infrastructure)
- [Flux d'une requête](#-flux-dune-requête)
- [Roadmap](#-roadmap)
- [Contribuer](#-contribuer)
- [Licence](#-licence)

---

## 🌐 Vue d'ensemble

Ce projet est une implémentation d'un assistant IA conversationnel full-stack. Il couvre tout le cycle de vie d'une requête utilisateur : de l'interface web jusqu'au modèle de langage, en passant par la gestion du contexte, les outils, et la sécurité.

L'objectif est pédagogique et pratique : comprendre chaque brique d'un assistant IA moderne, et pouvoir en déployer une version fonctionnelle.

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────┐
│              Interface utilisateur                  │
│         Web / Mobile  ·  Chat UI  ·  API REST       │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│           API Gateway & Authentification             │
│       Rate limiting · JWT/OAuth2 · Routage           │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│         Orchestrateur (Backend principal)            │
│    Gestion contexte · Prompt building · SSE          │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│               Couche IA / LLM                        │
│   Inférence · System prompt · Gestion des tokens     │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│             Mémoire & Contexte                       │
│  Session · DB persistante · RAG / Vector store       │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│        Outils & Intégrations (MCP / Tools)           │
│  Recherche web · Exécution code · APIs · Fichiers    │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│           Sécurité & Modération                      │
│    Filtrage I/O · Guardrails · Logging · RGPD        │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│                 Infrastructure                       │
│     Cloud · Docker · CI/CD · Monitoring              │
└─────────────────────────────────────────────────────┘
```

---

## 🛠 Stack technique recommandée

| Couche | Technologies |
|---|---|
| Frontend | React / Next.js, TailwindCSS, WebSocket / SSE |
| API Gateway | Nginx, Kong, ou AWS API Gateway |
| Auth | JWT, OAuth2, Keycloak ou Auth0 |
| Backend | Node.js (Express/Fastify) ou Python (FastAPI) |
| LLM | Anthropic API, OpenAI API, ou modèle local (Ollama) |
| Mémoire court terme | Redis |
| Mémoire long terme | PostgreSQL, MongoDB |
| Vector store / RAG | Pinecone, Qdrant, pgvector |
| Outils | LangChain, LlamaIndex, ou custom MCP |
| Sécurité | OpenAI Moderation API, règles custom, OWASP |
| Infra | Docker, Kubernetes, AWS / GCP / Azure |
| CI/CD | GitHub Actions |
| Monitoring | Prometheus + Grafana, Datadog, ou Langfuse |

---

## 📁 Structure du projet

```
claude-clone/
├── README.md
├── docker-compose.yml
├── .env.example
│
├── frontend/                   # Interface utilisateur
│   ├── src/
│   │   ├── components/
│   │   │   ├── ChatWindow.tsx
│   │   │   ├── MessageBubble.tsx
│   │   │   └── InputBar.tsx
│   │   ├── hooks/
│   │   │   └── useStream.ts    # Hook SSE pour le streaming
│   │   └── pages/
│   │       └── index.tsx
│   └── package.json
│
├── backend/                    # Orchestrateur principal
│   ├── src/
│   │   ├── routes/
│   │   │   ├── chat.ts         # Route principale /api/chat
│   │   │   └── health.ts
│   │   ├── services/
│   │   │   ├── llm.ts          # Appel au modèle LLM
│   │   │   ├── memory.ts       # Gestion du contexte
│   │   │   ├── tools.ts        # Orchestration des outils
│   │   │   └── moderation.ts   # Filtrage sécurité
│   │   ├── middleware/
│   │   │   ├── auth.ts
│   │   │   └── rateLimit.ts
│   │   └── index.ts
│   └── package.json
│
├── memory/                     # Service mémoire & RAG
│   ├── vector_store/
│   │   └── embeddings.py       # Gestion des embeddings
│   ├── session/
│   │   └── redis_store.py      # Historique en session
│   └── long_term/
│       └── postgres_store.py   # Mémoire persistante
│
├── tools/                      # Intégrations & outils
│   ├── web_search.ts
│   ├── code_executor.ts
│   └── file_reader.ts
│
├── security/                   # Sécurité & guardrails
│   ├── input_filter.ts
│   ├── output_filter.ts
│   └── audit_log.ts
│
├── infra/                      # Infrastructure & déploiement
│   ├── docker/
│   │   ├── Dockerfile.frontend
│   │   └── Dockerfile.backend
│   ├── k8s/                    # Manifests Kubernetes
│   └── terraform/              # Infrastructure as Code
│
└── docs/                       # Documentation complémentaire
    ├── architecture.md
    ├── api-reference.md
    └── deployment.md
```

---

## 🔍 Couches détaillées

### 1. Interface utilisateur

Le front-end gère l'affichage des conversations et la communication avec le backend via **Server-Sent Events (SSE)** pour le streaming token-par-token.

**Fonctionnalités clés :**
- Affichage du streaming en temps réel (effet "machine à écrire")
- Historique local de la conversation
- Support du Markdown dans les réponses
- Upload de fichiers et images
- Interface responsive (web & mobile)

**Points d'attention :**
- Utiliser `EventSource` ou `fetch` avec `ReadableStream` pour le SSE
- Gérer les états : `idle`, `loading`, `streaming`, `error`
- Prévoir un indicateur de chargement pendant l'inférence

---

### 2. API Gateway & Authentification

Point d'entrée unique de toutes les requêtes. Il protège les services internes et gère l'identité des utilisateurs.

**Responsabilités :**
- **Rate limiting** : limiter le nombre de requêtes par utilisateur/IP
- **Authentification** : valider les tokens JWT ou sessions OAuth2
- **Routage** : diriger vers le bon micro-service
- **TLS termination** : HTTPS en façade

**Exemple de configuration Nginx (rate limiting) :**
```nginx
limit_req_zone $binary_remote_addr zone=chat:10m rate=10r/m;

location /api/chat {
    limit_req zone=chat burst=5 nodelay;
    proxy_pass http://backend:3000;
}
```

---

### 3. Orchestrateur (Backend principal)

Le cœur de l'application. Il reçoit la requête, construit le prompt complet, appelle le LLM, et streame la réponse vers le client.

**Responsabilités :**
- Récupérer l'historique de la conversation
- Injecter le **system prompt**
- Tronquer le contexte si nécessaire (gestion de la fenêtre de tokens)
- Appeler le LLM et relayer le stream SSE
- Déclencher les outils si besoin (function calling)
- Sauvegarder la réponse finale en base

**Exemple de construction du prompt :**
```typescript
const messages = [
  { role: "system", content: systemPrompt },
  ...conversationHistory,   // Historique tronqué
  { role: "user", content: userMessage }
];
```

---

### 4. Couche IA / LLM

Interface directe avec le modèle de langage. Elle abstrait les différents providers (Anthropic, OpenAI, modèle local).

**Trois sous-composants :**

**Inférence LLM** — appel à l'API du modèle avec streaming activé.

**System prompt** — instructions permanentes qui définissent le comportement de l'assistant (rôle, limites, ton, format de réponse).

**Gestion des tokens** — calcul de la taille du contexte, troncature intelligente de l'historique, respect du `max_tokens`.

**Exemple avec l'API Anthropic :**
```typescript
const stream = anthropic.messages.stream({
  model: "claude-opus-4-5",
  max_tokens: 4096,
  system: systemPrompt,
  messages: messages,
});

for await (const chunk of stream) {
  res.write(`data: ${JSON.stringify(chunk)}\n\n`);
}
```

---

### 5. Mémoire & Contexte

Gère la continuité des échanges, à court et long terme.

**Trois niveaux :**

**Historique en session** (Redis) — stockage rapide et éphémère de la conversation en cours. Durée de vie : session utilisateur.

**Mémoire persistante** (PostgreSQL) — conservation des conversations passées, profil utilisateur, préférences. Permet de reprendre une conversation interrompue.

**RAG / Vector store** — enrichit le contexte avec des documents pertinents retrouvés par recherche sémantique. Flux : `question → embedding → recherche vectorielle → contexte injecté dans le prompt`.

**Schéma simplifié de la table `conversations` :**
```sql
CREATE TABLE conversations (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id     UUID NOT NULL REFERENCES users(id),
  title       TEXT,
  created_at  TIMESTAMP DEFAULT NOW(),
  updated_at  TIMESTAMP DEFAULT NOW()
);

CREATE TABLE messages (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  conversation_id UUID NOT NULL REFERENCES conversations(id),
  role            TEXT NOT NULL CHECK (role IN ('user', 'assistant', 'system')),
  content         TEXT NOT NULL,
  tokens          INTEGER,
  created_at      TIMESTAMP DEFAULT NOW()
);
```

---

### 6. Outils & Intégrations

Étend les capacités de l'assistant au-delà de la génération de texte, via le **function calling** ou le protocole **MCP (Model Context Protocol)**.

**Outils disponibles :**

| Outil | Description |
|---|---|
| `web_search` | Recherche web en temps réel |
| `code_executor` | Exécution de code Python/JS en sandbox |
| `file_reader` | Lecture et analyse de fichiers (PDF, CSV…) |
| `api_caller` | Appel d'APIs tierces configurables |
| `database_query` | Requêtes sur une base de données |

**Cycle d'exécution d'un outil :**
```
LLM décide d'appeler un outil
       ↓
Orchestrateur extrait les paramètres
       ↓
Outil exécuté dans un sandbox isolé
       ↓
Résultat injecté dans le contexte
       ↓
LLM génère la réponse finale
```

---

### 7. Sécurité & Modération

Filtre toutes les entrées et sorties pour détecter et bloquer les contenus problématiques.

**Filtrage en entrée (input) :**
- Détection de prompt injection
- Blocage de patterns malveillants (jailbreak, extraction de system prompt)
- Validation et sanitisation des données

**Filtrage en sortie (output) :**
- Détection de contenu inapproprié
- Vérification de la conformité avec le system prompt
- Masquage de données sensibles (PII)

**Guardrails :**
- Limites sur les sujets traités
- Détection d'hallucinations critiques
- Vérification des appels d'outils avant exécution

**Audit & conformité :**
- Logging de toutes les interactions (anonymisé)
- Conformité RGPD : droit à l'effacement, portabilité
- Rétention des logs configurable

---

### 8. Infrastructure

Tout ce qui fait tourner le projet en production.

**Containerisation :**
```yaml
# docker-compose.yml (extrait)
services:
  frontend:
    build: ./frontend
    ports: ["3000:3000"]

  backend:
    build: ./backend
    ports: ["4000:4000"]
    environment:
      - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
      - REDIS_URL=redis://redis:6379
      - DATABASE_URL=postgresql://user:pass@postgres:5432/claude

  redis:
    image: redis:7-alpine

  postgres:
    image: postgres:16-alpine
    volumes:
      - pgdata:/var/lib/postgresql/data

  qdrant:
    image: qdrant/qdrant
    ports: ["6333:6333"]

volumes:
  pgdata:
```

**CI/CD (GitHub Actions) :**
```yaml
# .github/workflows/deploy.yml
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build & push Docker images
        run: docker compose build && docker compose push
      - name: Deploy to production
        run: ./infra/deploy.sh
```

**Monitoring :**
- **Langfuse** ou **LangSmith** pour tracer les appels LLM (latence, tokens, coûts)
- **Prometheus + Grafana** pour les métriques système
- **Sentry** pour le tracking des erreurs

---

## 🔄 Flux d'une requête

```
Utilisateur tape un message
         │
         ▼
[Frontend] Envoi POST /api/chat + token JWT
         │
         ▼
[API Gateway] Vérifie token · Rate limit · Route vers backend
         │
         ▼
[Orchestrateur] Récupère historique Redis + PostgreSQL
         │
         ▼
[RAG] Recherche vectorielle si documents disponibles
         │
         ▼
[Modération] Filtre le message utilisateur
         │
         ▼
[LLM] Appel API avec contexte complet → stream de tokens
         │
         ▼
[Tools] Si function call détecté → exécution outil → résultat injecté
         │
         ▼
[Modération] Filtre la réponse générée
         │
         ▼
[Orchestrateur] Sauvegarde en DB · Stream SSE vers le client
         │
         ▼
[Frontend] Affichage token par token
```

---

## 🗺 Roadmap

- [ ] **v0.1** — Chat basique avec streaming (frontend + backend + LLM)
- [ ] **v0.2** — Authentification utilisateur + historique des conversations
- [ ] **v0.3** — Mémoire persistante + gestion du contexte long
- [ ] **v0.4** — Intégration RAG (upload de documents)
- [ ] **v0.5** — Outils : recherche web + exécution de code
- [ ] **v0.6** — Modération & sécurité renforcée
- [ ] **v1.0** — Déploiement production (Docker + CI/CD)
- [ ] **v1.x** — Support multi-modèles · Voix · Multi-modal

---

## 🤝 Contribuer

1. Forkez le dépôt
2. Créez une branche : `git checkout -b feature/ma-fonctionnalite`
3. Committez : `git commit -m "feat: ajouter ma fonctionnalité"`
4. Pushez : `git push origin feature/ma-fonctionnalite`
5. Ouvrez une Pull Request

Conventions de commit : [Conventional Commits](https://www.conventionalcommits.org/)

---

## 📄 Licence

MIT — voir le fichier [LICENSE](./LICENSE) pour les détails.

---

> *Ce projet est à but éducatif. Claude et l'API Anthropic sont des produits d'[Anthropic](https://www.anthropic.com).*
