Pour les vérifications de rôles classiques → TOKEN JWT 🎯
Avantages :

⚡ Performance : Pas de requête BDD à chaque vérification
🔒 Sécurité : Token signé cryptographiquement (impossible à falsifier)
🚀 Scalabilité : Peut gérer des milliers de requêtes sans surcharger la BDD

Inconvénients :

⏰ Pas de révocation instantanée : Si tu bannis un user, son token reste valide jusqu'à expiration (1h dans ton cas)

MAIS il faut checker la BDD pour :

🚫 Bannissements/désactivations : user.is_active = false
🔄 Changements de rôles en temps réel : Admin → User
📊 Données dynamiques : Nombre de posts, niveau, etc.



LOGIQUE POUR UNE INTEGRATION SIMPLE BOUTIQUES SCRAPPER ET BOUTIQUE OWNERSHIP PAR LES USERS : 



ouais, le but c'est d'avoir une édition d'adresse aussi dans modifier mon profil, d'avoir aussi ça dans le formulaire de demande de rôle boutique (et que si l'user a déjà une adresse alors on l'affiche dans le formulaire)

et je me pose une question actuellement, il faudrait que user ait au moins toutes les colonnes de role_request ou des equivalent non : 

MySQL » mysql » tcg_db » Table: role_request
Table: role_request
Afficher les données Afficher la structure Modifier la table Nouvel élément
ColonneTypeCommentaireidint Incrément automatique user_idint reviewed_by_idint NULL requested_rolevarchar(50) statusvarchar(20) messagelongtext NULL admin_responselongtext NULL created_atdatetime(DC2Type:datetime_immutable) reviewed_atdatetime NULL(DC2Type:datetime_immutable) shop_namevarchar(255) NULL shop_phonevarchar(50) NULL shop_websitevarchar(255) NULL siret_numbervarchar(100) NULL shop_address_idint NULL
Index
PRIMARYidINDEXuser_idINDEXreviewed_by_idINDEXshop_address_id
Modifier les index
Clés étrangères
SourceCibleON DELETEON UPDATEshop_address_idaddresses(id)RESTRICTRESTRICTModifieruser_iduser(id)RESTRICTRESTRICTModifierreviewed_by_iduser(id)RESTRICTRESTRICTModifier
Ajouter une clé étrangère
Checks
Create check
Déclencheurs
Ajouter un déclencheur
Adminer 5.3.0
Langue: EnglishالعربيةБългарскиবাংলাBosanskiCatalàČeštinaDanskDeutschΕλληνικάEspañolEestiفارسیSuomiFrançaisGalegoעבריתहिन्दीMagyarBahasa IndonesiaItaliano日本語ქართული한국어LietuviųLatviešuBahasa MelayuNederlandsNorskPolskiPortuguêsPortuguês (Brazil)Limba RomânăРусскийSlovenčinaSlovenskiСрпскиSvenskaத‌மிழ்ภาษาไทยTürkçeУкраїнськаOʻzbekchaTiếng Việt简体中文繁體中文
BD: information_schemaperformance_schematcg_db
Requête SQL Importer Exporter Créer une table
* select addresses
* select doctrine_migration_versions
* select role_request
* select user
tcg_user

parce que dans mon idée, une boutique c'est juste un user particulier avec plus de droits et de configs, donc techniquement quand le form va prendre en compte par exemple siret_number, shop_website, shop_phone

il va bien falloir stocker ça quelque part non ? 

j'ai déja penser a faire une table boutique et ducoup les users ont un profil et pour les users qui ont le rôle boutique on va chercher dans la table boutique les données complémentaires, bref je me demande comment faire, sachant que j'avais une idée : 

en gros on va récuperer les boutiques de toutes la france visibles sur maps, et en plus on va donner la possibilité aux users de s'enregistrer en tant que boutique, comment faire cette séparation gracieuse mais en meme temps l'effet seamless entre les boutiques scrappés, identifiés et affichés sur le site et notamment sur la carte des boutiques et les boutiques vraiment enregistrés sur notre site via inscription, bref je me pose des questions

Excellente question ! Vous touchez à un point architectural fondamental. Je vais analyser votre problématique et proposer une solution claire.

Problématique identifiée :
Stockage des données boutique - Où mettre siret_number, shop_website, shop_phone ?
Séparation boutiques scrappées vs inscrites - Comment gérer les deux types ?
Architecture User vs Shop - Une table séparée ou tout dans User ?
Solution recommandée : Architecture hybride avec table Shop

Architecture complète - Entité Shop et relations
Code 
Architecture recommandée :
1. Entité Shop séparée
✅ Boutiques inscrites : type = 'registered' avec owner_id vers User
✅ Boutiques scrappées : type = 'scraped' avec owner_id = NULL
✅ Boutiques revendiquées : Scrappées puis liées à un User
2. Relation User ↔ Shop
php
// Dans User.php, ajouter :
#[ORM\OneToOne(targetEntity: Shop::class, mappedBy: 'owner')]
private ?Shop $shop = null;
3. Workflow boutique :
Inscription classique :

User demande rôle boutique via RoleRequest
Admin approuve → User obtient ROLE_SHOP
Création automatique d'une entité Shop liée au User
Revendication boutique existante :

User trouve une boutique scrappée sur la carte
User demande à la revendiquer (avec justificatifs)
Admin vérifie → Lie la Shop existante au User
4. Migration des données RoleRequest :
Quand une demande boutique est approuvée, on :

Crée l'entité Shop avec les données de RoleRequest
Lie la Shop au User approuvé
Archive les données dans RoleRequest
Voulez-vous que je continue avec :

Modification de l'entité User pour la relation Shop
Migration pour créer la table shops
Ou d'abord finir le composant Vue.js d'autocomplétion ?
Cette architecture permet la séparation clean entre boutiques scrappées et inscrites, tout en gardant un système unifié.




ok donc en gros on lie un compte a une entité shop qui a des données en plus, et c'est donner son affiché et en quelque sorte "melangé" aux données de l'user sur le profil, ça me parrait bon, et qu'on puisse claim etre le detenteur d'une boutique avec justificatifs me parrrait cool, comme ca on affiche plein de boutique de base donc plein de shop mais si un user veut pouvoir agir en tant que cette boutique sur le site (créer topics ou events ou tournois il pourra en reclamant le droit sur cette entité boutique) ok ça me parait un bon fonctionnement, tu valide ?

🎯 Parfaitement validé ! Votre vision est excellente et architecturalement très solide.

Récapitulatif du workflow approuvé :
📍 Phase 1 : Données de base
Boutiques scrappées partout en France (Google Maps, Pages Jaunes, etc.)
Affichage sur carte publique avec infos de base
Shop avec type='scraped' et owner_id=NULL
👤 Phase 2 : Inscription utilisateur
User normal peut demander création d'une nouvelle boutique
Ou revendiquer une boutique existante (le gros plus !)
Justificatifs requis : SIRET, photos boutique, etc.
🏪 Phase 3 : Boutique revendiquée
Shop devient type='verified' avec owner_id vers User
User obtient ROLE_SHOP
Peut agir au nom de la boutique : topics, événements, tournois
Profil "hybride" User + Shop dans l'interface
🎮 Phase 4 : Actions boutique
Créer événements localisés à la boutique
Topics avec badge "Boutique officielle"
Gestion calendrier/stock dans le futur
Statistiques boutique vs user
Avantages de cette approche :
✅ Base de données riche dès le lancement
✅ Acquisition naturelle via revendication
✅ Pas de boutiques fantômes (toutes réelles)
✅ Vérification facilitée (SIRET vs adresse réelle)
✅ Évolutif pour futurs services

On continue sur cette base ?

D'abord finir le composant Vue.js d'autocomplétion
Puis implémenter l'entité Shop complète
Workflow de revendication en dernière étape
Ça vous va comme plan ?




allez on continue sur ça, c'est vraiment du lourd, mais d'abord finissons ce qu'on fesait








LOGIQUE DES NOTIFICATIONS : 





# 🔔 Guide d'implémentation des notifications - MULLIGAN TCG

Ce guide explique l'architecture complète du système de notifications et comment ajouter facilement de nouveaux types de notifications.

## 📋 Table des matières

1. [Architecture générale](#architecture-générale)
2. [Backend - Symfony](#backend---symfony)
3. [Frontend - Vue.js](#frontend---vuejs)
4. [Ajouter un nouveau type de notification](#ajouter-un-nouveau-type-de-notification)
5. [Points d'intégration](#points-dintégration)
6. [Troubleshooting](#troubleshooting)

---

## Architecture générale

### Vue d'ensemble

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   ÉVÉNEMENT     │ -> │ NOTIFICATION     │ -> │   AFFICHAGE     │
│   BUSINESS      │    │   MANAGER        │    │   FRONTEND      │
│                 │    │                  │    │                 │
│ • Rôle refusé   │    │ • Création BDD   │    │ • AppHeader     │
│ • Nouveau msg   │    │ • Sérialisation  │    │ • ProfileView   │
│ • Like reçu     │    │ • Templates      │    │ • Polling       │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

### Flux de données

1. **Événement métier** → Déclenche la création d'une notification
2. **NotificationManager** → Crée et sauvegarde en BDD
3. **API REST** → Expose les notifications
4. **Frontend** → Polling + affichage temps réel

---

## Backend - Symfony

### 🗃️ Entité Notification

**Fichier :** `src/Entity/Notification.php`

```php
// Types de notifications supportés
public const TYPE_ROLE_APPROVED = 'role_approved';
public const TYPE_ROLE_REJECTED = 'role_rejected';
public const TYPE_EVENT_CREATED = 'event_created';
public const TYPE_REPLY_RECEIVED = 'reply_received';
public const TYPE_MESSAGE_RECEIVED = 'message_received';
// ➕ Ajouter vos nouveaux types ici

// Propriétés principales
- user: User              // Destinataire
- type: string           // Type de notification
- title: string          // Titre court
- message: string        // Message complet
- data: json            // Données contextuelles
- isRead: boolean       // État de lecture
- createdAt: datetime   // Date de création
- actionUrl: string     // URL d'action (optionnel)
- actionLabel: string   // Libellé du bouton (optionnel)
- icon: string          // Emoji/icône (optionnel)
```

### 🔧 NotificationManager

**Fichier :** `src/Service/NotificationManager.php`

**Méthodes principales :**
- `create()` - Création générique
- `createRoleApprovedNotification()` - Template rôle approuvé
- `createRoleRejectedNotification()` - Template rôle rejeté
- `serializeForHeader()` - Format header (4 max)
- `serializeForProfile()` - Format activité récente

### 🌐 API Controller

**Fichier :** `src/Controller/Api/NotificationController.php`

**Endpoints disponibles :**
```
GET    /api/notifications/header          # 4 non lues pour header
GET    /api/notifications/recent          # Paginées pour ProfileView
GET    /api/notifications/unread-count    # Compteur seulement
POST   /api/notifications/{id}/read       # Marquer comme lue
POST   /api/notifications/mark-all-read   # Tout marquer lu
GET    /api/notifications/poll            # Polling optimisé
```

### 🗂️ Repository

**Fichier :** `src/Repository/NotificationRepository.php`

**Méthodes utiles :**
- `findUnreadForHeader()` - 4 non lues max
- `findRecentForProfile()` - Pagination ProfileView
- `countUnread()` - Compteur non lues
- `markAllAsReadForUser()` - Marquage global

---

## Frontend - Vue.js

### 🏪 Store Pinia

**Fichier :** `app/vuejs/src/stores/notifications.js`

**État géré :**
```javascript
// Données
headerNotifications: []     // 4 pour header
recentNotifications: []     // Pour ProfileView
unreadCount: 0             // Badge numérique
recentPagination: {}       // Pagination

// Actions principales
loadHeaderNotifications()   // Charger header
loadRecentNotifications()   // Charger activité
markAsRead(id)             // Marquer lue
markAllAsRead()            // Tout marquer
startPolling()             // Polling auto
```

### 🎯 Composable

**Fichier :** `app/vuejs/src/composables/useNotifications.js`

Interface simplifiée entre store et composants :
```javascript
const {
  notifications,          // Notifications header
  recentNotifications,   // Notifications activité
  unreadCount,          // Compteur
  handleNotificationClick, // Clic + navigation
  loadMore              // Pagination
} = useNotifications()
```

### 🎨 Composants d'affichage

#### AppHeader - Dropdown notifications
**Fichier :** `app/vuejs/src/components/AppHeader.vue`

- Badge animé avec compteur
- Dropdown avec 4 notifications max
- Bouton "Tout marquer lu"
- Polling automatique toutes les 30s

#### ProfileView - Activité récente
**Fichier :** `app/vuejs/src/views/ProfileView.vue`

- Toutes les notifications (lues + non lues)
- Pagination "Charger plus" (6 par page)
- Indication visuelle non lues
- Scroll infini

---

## Ajouter un nouveau type de notification

### Étape 1 : Définir le type

**Dans `src/Entity/Notification.php` :**
```php
// Ajouter la constante
public const TYPE_COMMENT_LIKED = 'comment_liked';

// Ajouter dans la validation
#[Assert\Choice(choices: [
    // ... types existants
    self::TYPE_COMMENT_LIKED
])]

// Ajouter dans getAvailableTypes()
public static function getAvailableTypes(): array
{
    return [
        // ... types existants
        self::TYPE_COMMENT_LIKED,
    ];
}

// Ajouter dans getTypeLabel()
public function getTypeLabel(): string
{
    return match($this->type) {
        // ... cas existants
        self::TYPE_COMMENT_LIKED => 'Commentaire aimé',
        default => 'Notification'
    };
}
```

### Étape 2 : Créer le template

**Dans `src/Service/NotificationManager.php` :**
```php
/**
 * Notification pour commentaire liké
 */
public function createCommentLikedNotification(
    User $user, 
    Comment $comment, 
    User $liker
): Notification {
    return $this->create(
        user: $user,
        type: Notification::TYPE_COMMENT_LIKED,
        title: 'Votre commentaire a été aimé !',
        message: "{$liker->getPseudo()} a aimé votre commentaire sur \"{$comment->getTopic()->getTitle()}\"",
        data: [
            'comment_id' => $comment->getId(),
            'topic_id' => $comment->getTopic()->getId(),
            'liker_id' => $liker->getId(),
            'liker_pseudo' => $liker->getPseudo()
        ],
        actionUrl: "/topics/{$comment->getTopic()->getId()}#comment-{$comment->getId()}",
        actionLabel: 'Voir le commentaire',
        icon: '👍'
    );
}
```

### Étape 3 : Déclencher la notification

**Dans votre controller/service métier :**
```php
// Exemple : dans CommentController après un like
public function likeComment(int $commentId): JsonResponse
{
    $comment = $this->commentRepository->find($commentId);
    $currentUser = $this->getUser();
    
    // Logique métier du like...
    $this->commentService->toggleLike($comment, $currentUser);
    
    // 🔔 CRÉER LA NOTIFICATION
    if ($comment->getAuthor() !== $currentUser) {
        $this->notificationManager->createCommentLikedNotification(
            $comment->getAuthor(),
            $comment,
            $currentUser
        );
    }
    
    return $this->json(['success' => true]);
}
```

### Étape 4 : Frontend (optionnel)

**Icône spécifique dans `useNotifications.js` :**
```javascript
const getNotificationIcon = (type) => {
  const icons = {
    'role_approved': '🎉',
    'role_rejected': '❌',
    'comment_liked': '👍',  // ➕ Nouveau
    // ... autres types
  }
  return icons[type] || '🔔'
}
```

---

## Points d'intégration

### 🎯 Où déclencher les notifications

1. **Controllers API** - Après actions utilisateur
2. **Event Listeners** - Sur événements Doctrine
3. **Services métier** - Dans la logique business
4. **Commands** - Pour notifications programmées

### 📝 Exemple avec Event Listener

```php
// src/EventListener/CommentSubscriber.php
class CommentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationManager $notificationManager
    ) {}
    
    public function onCommentCreated(CommentCreatedEvent $event): void
    {
        $comment = $event->getComment();
        $topicAuthor = $comment->getTopic()->getAuthor();
        
        // Ne pas notifier si c'est l'auteur qui commente
        if ($topicAuthor !== $comment->getAuthor()) {
            $this->notificationManager->createReplyNotification(
                $topicAuthor,
                [
                    'id' => $comment->getId(),
                    'topic_id' => $comment->getTopic()->getId(),
                    'topic_title' => $comment->getTopic()->getTitle(),
                    'author_name' => $comment->getAuthor()->getPseudo()
                ]
            );
        }
    }
}
```

### ⚡ Optimisations

1. **Éviter le spam** - Regrouper notifications similaires
2. **Préférences utilisateur** - Types de notifications désirées
3. **Batch processing** - Créer plusieurs notifications d'un coup
4. **Queue system** - Traitement asynchrone pour gros volumes

---

## Troubleshooting

### ❌ Problèmes fréquents

**1. Notifications non affichées**
```bash
# Vérifier les logs API
tail -f var/log/dev.log | grep notification

# Tester l'endpoint
curl -H "Authorization: Bearer TOKEN" http://localhost:8000/api/notifications/header
```

**2. Polling ne fonctionne pas**
```javascript
// Dans la console browser
console.log('Polling status:', notificationStore.pollInterval)

// Forcer un refresh
await notificationStore.loadHeaderNotifications()
```

**3. Badge ne se met pas à jour**
```javascript
// Vérifier le store
console.log('Unread count:', notificationStore.unreadCount)

// Recharger le compteur
await notificationStore.loadUnreadCount()
```

### 🔧 Debug utiles

**Backend :**
```php
// Compter notifications d'un user
$count = $notificationRepository->countUnread($user);
dump($count);

// Voir dernières notifications
$recent = $notificationRepository->findRecentForProfile($user, 0, 5);
dump($recent);
```

**Frontend :**
```javascript
// État du store
console.log('Store state:', notificationStore.$state)

// Forcer le polling
notificationStore.pollNotifications()
```

---

## 🚀 Prochaines évolutions

### Fonctionnalités avancées

1. **Notifications push** - Web Push API
2. **Notifications email** - Templates Symfony Mailer
3. **Préférences** - Interface de configuration
4. **Analytics** - Taux de lecture, clics
5. **Real-time** - WebSocket / Server-Sent Events

### Exemples de nouveaux types

```php
// E-commerce
TYPE_ORDER_SHIPPED = 'order_shipped'
TYPE_PAYMENT_FAILED = 'payment_failed'

// Social
TYPE_FRIEND_REQUEST = 'friend_request'
TYPE_POST_SHARED = 'post_shared'

// Gaming
TYPE_TOURNAMENT_STARTING = 'tournament_starting'
TYPE_ACHIEVEMENT_UNLOCKED = 'achievement_unlocked'
```

---

## 📚 Ressources

- **Documentation Symfony** - https://symfony.com/doc/current/doctrine.html
- **Pinia Store** - https://pinia.vuejs.org/
- **PrimeVue Components** - https://primevue.org/
- **Web Push API** - https://developer.mozilla.org/en-US/docs/Web/API/Push_API

---

**🎯 Le système de notifications MULLIGAN TCG est maintenant prêt pour tous vos besoins métier !**




IMPORT DES CARTES 


php bin/console app:import-pokemon-cards swsh9     # Brilliant Stars
php bin/console app:import-pokemon-cards swsh10    # Astral Radiance  
php bin/console app:import-pokemon-cards swsh10.5  # Pokémon GO
php bin/console app:import-pokemon-cards swsh11    # Lost Origin
php bin/console app:import-pokemon-cards swsh12    # Silver Tempest
php bin/console app:import-pokemon-cards swsh12.5  # Crown Zenith

php bin/console app:import-pokemon-cards sv01      # Scarlet & Violet
php bin/console app:import-pokemon-cards sv02      # Paldea Evolved
php bin/console app:import-pokemon-cards sv03      # Obsidian Flames
php bin/console app:import-pokemon-cards sv03.5    # 151
php bin/console app:import-pokemon-cards sv04      # Paradox Rift
php bin/console app:import-pokemon-cards sv04.5    # Paldean Fates
php bin/console app:import-pokemon-cards sv05      # Temporal Forces
php bin/console app:import-pokemon-cards sv06      # Twilight Masquerade
php bin/console app:import-pokemon-cards sv06.5    # Shrouded Fable
php bin/console app:import-pokemon-cards sv07      # Stellar Crown
php bin/console app:import-pokemon-cards sv08      # Surging Sparks
php bin/console app:import-pokemon-cards sv08.5    # Prismatic Evolutions