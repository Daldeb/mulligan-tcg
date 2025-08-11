# 🔧 MULLIGAN TCG — Guide de développement local

Bienvenue dans le guide de développement du projet **MULLIGAN TCG**. Ce document est une référence centrale pour démarrer, développer, tester et déployer efficacement le projet, que ce soit en local ou sur le serveur distant.

---

## ✅ Environnements & URLs

| Environnement | URL d'accès                                          | Description                                |
| ------------- | ---------------------------------------------------- | ------------------------------------------ |
| **Prod**      | [http://51.178.27.41](http://51.178.27.41)           | Site en ligne avec le frontend Vite buildé |
| **Legacy**    | [http://51.178.27.41:8080](http://51.178.27.41:8080) | Ancienne version du site                   |
| **Adminer**   | [http://51.178.27.41:8084](http://51.178.27.41:8084) | Interface BDD (MySQL)                      |

---

## 🚀 Développement local (Docker - Recommandé)

**Workflow simplifié pour le développement quotidien :**

```bash
# 1. Cloner le repo
git clone git@github.com:Daldeb/mulligan-tcg.git
cd mulligan-tcg

# 2. Installer les dépendances frontend
cd app/vuejs
npm install
cd ../..

# 3. Démarrer l'environnement backend (Docker)
cd infrastructure
make up

# 4. Démarrer le frontend (dans un autre terminal)
cd app/vuejs
npm run dev
```

**URLs de développement :**
- **Frontend** : http://localhost:5173 (avec hot reload)
- **API Backend** : http://localhost:8000/api (auto-démarré via Docker)
- **Adminer BDD locale** : http://localhost:8081

**Le serveur Symfony se lance automatiquement** dans le container Docker, plus besoin de commandes manuelles !

---

## 📋 Logs et debugging

```bash
# Logs Docker en temps réel
docker logs tcg_local_app -f

# Logs Symfony détaillés (dans le container)
cd infrastructure
make shell
tail -f var/log/dev.log

# Web Profiler Symfony (recommandé pour le debug)
# → http://localhost:8000/_profiler
```

---

## 🐳 Commandes Docker utiles

```bash
cd infrastructure

make up       # Démarrer l'environnement complet
make down     # Arrêter l'environnement  
make shell    # Entrer dans le container Symfony
make logs     # Voir les logs des containers
```

---

## 📂 Accès à la base de données locale

### 🌐 Adminer (Interface web)

Ouvre [http://localhost:8081](http://localhost:8081) dans ton navigateur.

**Identifiants :**

| Champ        | Valeur    |
| ------------ | --------- |
| Système      | MySQL     |
| Serveur      | mysql     |
| Utilisateur  | tcg\_user |
| Mot de passe | tcg\_pass |
| Base         | tcg\_db   |

### 🔧 Commandes Symfony (dans le container)

```bash
# Entrer dans le container
make shell

# Commandes utiles
php bin/console debug:router                    # Voir toutes les routes
php bin/console doctrine:migrations:migrate     # Appliquer les migrations
php bin/console doctrine:fixtures:load          # Charger des données de test
php bin/console cache:clear                     # Vider le cache
php bin/console lexik:jwt:generate-token email@example.com  # Tester JWT
```

---

## 🔒 JWT et authentification

### Génération des clés JWT

Si tu viens de cloner le repo :

```bash
# Dans le container (make shell)
php bin/console lexik:jwt:generate-keypair
```

Les clés sont générées sans passphrase (compatible avec la config `.env.local`).

### Test API en local

```bash
# Test register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password","pseudo":"testuser"}'

# Test login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

---

## 🔐 Accès Adminer (prod)

* URL : [http://51.178.27.41:8084](http://51.178.27.41:8084)
* Serveur : `mysql`
* Utilisateur : `tcg_prod_user`
* Mot de passe : `tcg_prod_password`
* Base de données : `tcg_prod_db`

---

## 🚀 Déploiement automatique (CI/CD)

Chaque push sur `main` déclenche automatiquement :

* Pull du code
* Build du frontend Vite
* Rebuild des containers
* Migration DB Doctrine
* **Correction automatique des permissions JWT** ✨
* Cache clear

### Test de déploiement

```bash
# Commit et push
git add .
git commit -m "✨ Feature description"
git push

# Vérifier le déploiement
curl -s http://51.178.27.41 | grep "VERSION"

# Tester l'API après déploiement
curl -X POST http://51.178.27.41/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"daldeb.daldeb@gmail.com","password":"12Avenue"}'
```

**Le login API fonctionne automatiquement après chaque déploiement** grâce aux corrections de permissions JWT intégrées dans le pipeline.

---

## 🛠️ Development Workflow

### Démarrage quotidien

```bash
# Terminal 1 - Backend
cd ~/mulligan-tcg/infrastructure
make up

# Terminal 2 - Frontend  
cd ~/mulligan-tcg/app/vuejs
npm run dev
```

### Debugging

- **Logs détaillés** : `tail -f var/log/dev.log` (dans le container)
- **Web Profiler** : http://localhost:8000/_profiler
- **Dump variables** : `dump($var)` dans le code PHP

### Gestion des rôles

Les tokens JWT contiennent automatiquement les rôles utilisateur :
- **Vérification côté Symfony** : `$this->isGranted('ROLE_ADMIN')`
- **Performance** : Pas de requête BDD pour les vérifications de rôles basiques
- **Sécurité** : Vérifications BDD pour les actions critiques (bannissement, etc.)

---

## 🔍 Monitoring et maintenance

```bash
# État des containers prod
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep tcg_prod

# Test santé MySQL
docker exec tcg_prod_mysql mysql -u tcg_prod_user -ptcg_prod_password -e "SELECT 'MySQL OK' as status;" tcg_prod_db

# Test API fonctionnelle
curl -s -o /dev/null -w "API Status: %{http_code}\n" -X POST http://51.178.27.41/api/register

# Logs récents en cas de problème
docker logs tcg_prod_app --tail=20 --since=5m
```

---

## ✅ Conclusion

Le projet est maintenant :

* ✅ **Développement local simplifié** avec Docker auto-configuré
* ✅ **API d'authentification fonctionnelle** avec JWT
* ✅ **Déploiement automatique fiable** avec corrections JWT
* ✅ **Logs et debugging intégrés** pour un développement efficace
* ✅ **Architecture scalable** prête pour les fonctionnalités avancées

**Ready to build amazing features! 🚀**


MAILER SMTP GOOGLE : mulligan.alltcg@gmail.com


Mot de passe d'application dans gmail : fmfh bzev mswi evww

Sommaire proposé
1. Liste des compétences couvertes par le projet
Récapitulatif des 11 compétences du titre CDA, avec précision de leur mise en œuvre dans le cadre du projet Mulligan TCG.

2. Expression des besoins
Présentation non technique des besoins exprimés par le “client” (la communauté TCG) : contexte, enjeux, objectifs fonctionnels et non-fonctionnels.

3. Présentation de l’entreprise et du service
Historique, secteur et contexte d’utilisation.
Description des services rendus par la plateforme Mulligan TCG et de ses utilisateurs cibles.

4. Gestion de projet
Méthodologie appliquée (Agile/Scrum simplifié).

Outils utilisés (Trello, Discord, GitHub).

Planning prévisionnel et réel (diagramme de Gantt).

Suivi qualité et évolutions post-production.

5. Spécifications fonctionnelles
5.1 Contraintes du projet et livrables attendus
5.2 Architecture logicielle du projet (front-end, back-end, base de données, services externes, infrastructure et déploiement)
5.3 Maquettes et enchaînement des maquettes ✅ (déjà fait)
5.4 Modèle entités-associations (MCD) et modèle physique (MLD)
5.5 Script de création ou de modification de la base de données
5.6 Diagramme de comportement des fonctionnalités (use-case UML)
5.7 Diagramme de séquence des cas d’utilisation les plus significatifs

6. Spécifications techniques
Langages, frameworks, SGBD, serveurs, environnements de développement et outils tiers utilisés.

7. Réalisations du candidat
7.1 Interfaces utilisateur et code correspondant
7.2 Extraits de code de composants métier
7.3 Extraits de code de composants d’accès aux données
7.4 Extraits de code d’autres composants (contrôleurs, utilitaires)

8. Éléments de sécurité de l’application
Mesures mises en place pour protéger les données et contrer les failles de sécurité classiques.

9. Plan de tests
Scénarios, organisation, outils et résultats des tests.

10. Jeu d’essai
Présentation des données d’entrée, résultats attendus, résultats obtenus et analyse des écarts.

11. Veille technologique
Thèmes, méthodes de recherche, outils de veille, sources consultées, synthèse des informations utiles.

12. Annexes
12.1 Maquettes complètes des interfaces utilisateur
12.2 Captures d’écrans d’interfaces et code correspondant
12.3 Code complet des composants métier les plus significatifs
12.4 Code complet des composants d’accès aux données les plus significatifs
12.5 Code complet d’autres composants (contrôleurs, utilitaires)

