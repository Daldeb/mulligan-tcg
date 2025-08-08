<template>
  <div class="static-page">
    <div class="container">
      <!-- Breadcrumbs -->
      <div class="breadcrumbs">
        <Button 
          icon="pi pi-arrow-left" 
          label="Retour" 
          text 
          @click="goBack"
          class="back-btn"
        />
        <span class="breadcrumb-separator">></span>
        <span class="current-page">Aide & Support</span>
      </div>

      <!-- Contenu -->
      <div class="content-wrapper">
        <h1 class="page-title">Aide & Support</h1>
        
        <div class="content-sections">
          <section class="content-section">
            <h2>1. Questions fréquentes</h2>
            <p>Consultez notre FAQ pour trouver rapidement des réponses aux questions les plus courantes sur l'utilisation de MULLIGAN TCG, la gestion des decks et les fonctionnalités avancées.</p>
          </section>

          <section class="content-section">
            <h2>2. Premiers pas</h2>
            <p>Nouveau sur MULLIGAN TCG ? Commencez par créer votre compte, configurez votre profil de joueur et découvrez nos outils de construction de decks. Notre guide de démarrage vous accompagne étape par étape.</p>
          </section>

          <section class="content-section">
            <h2>3. Gestion des decks</h2>
            <p>Apprenez à créer, modifier et partager vos decks pour Hearthstone, Magic et Pokémon. Utilisez nos outils d'analyse pour optimiser vos constructions et suivre les tendances du métagame.</p>
          </section>

          <section class="content-section">
            <h2>4. Forums et communauté</h2>
            <p>Participez aux discussions communautaires, partagez vos stratégies et connectez-vous avec d'autres joueurs passionnés de jeux de cartes à collectionner.</p>
          </section>

          <section class="content-section">
            <h2>5. Événements et tournois</h2>
            <p>Découvrez comment créer et participer aux événements locaux, gérer vos inscriptions et suivre les résultats des tournois dans votre région.</p>
          </section>

          <section class="content-section">
            <h2>6. Contact et assistance</h2>
            <p>Pour toute question spécifique ou problème technique, contactez notre équipe support à l'adresse support@mulligan-tcg.com. Nous répondons généralement sous 24 heures.</p>
          </section>
        </div>

        <div class="last-updated">
          <p class="update-info">Dernière mise à jour : 5 août 2024</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const router = useRouter()

const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}
</script>

<style scoped>
.static-page {
  min-height: calc(100vh - 140px);
  background: var(--surface);
  padding: 2rem 0;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Breadcrumbs */
.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--surface-200);
}

:deep(.back-btn) {
  color: var(--primary) !important;
  font-weight: 500 !important;
}

:deep(.back-btn:hover) {
  background: rgba(38, 166, 154, 0.1) !important;
}

.breadcrumb-separator {
  color: var(--text-secondary);
  font-weight: 500;
}

.current-page {
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 0.9rem;
}

/* Contenu */
.content-wrapper {
  background: white;
  border-radius: var(--border-radius-large);
  padding: 2.5rem;
  box-shadow: var(--shadow-small);
  border: 1px solid var(--surface-200);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2rem 0;
  text-align: center;
  padding-bottom: 1rem;
  border-bottom: 3px solid var(--primary);
}

.content-sections {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.content-section h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--primary);
  margin: 0 0 0.75rem 0;
}

.content-section p {
  line-height: 1.7;
  color: var(--text-secondary);
  margin: 0;
}

.last-updated {
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 1px solid var(--surface-200);
  text-align: center;
}

.update-info {
  color: var(--text-secondary);
  font-size: 0.9rem;
  font-style: italic;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .content-wrapper {
    padding: 1.5rem;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .breadcrumbs {
    gap: 0.25rem;
  }
  
  .current-page {
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .content-wrapper {
    padding: 1rem;
  }
  
  .page-title {
    font-size: 1.25rem;
  }
  
  .content-section h2 {
    font-size: 1.1rem;
  }
}
</style>