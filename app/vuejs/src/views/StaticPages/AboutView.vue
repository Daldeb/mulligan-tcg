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
        <span class="current-page">À propos</span>
      </div>

      <!-- Contenu -->
      <div class="content-wrapper">
        <h1 class="page-title">À propos de MULLIGAN TCG</h1>
        
        <div class="content-sections">
          <section class="content-section">
            <h2>Notre mission</h2>
            <p>MULLIGAN TCG est né de la passion pour les jeux de cartes à collectionner. Notre mission est de créer la meilleure plateforme pour les joueurs de TCG, en offrant des outils innovants et une communauté bienveillante.</p>
          </section>

          <section class="content-section">
            <h2>Nos services</h2>
            <p>Nous proposons des outils de construction de decks, un suivi du metagame en temps réel, des forums communautaires et une carte interactive des boutiques spécialisées. Tout est pensé pour améliorer votre expérience de jeu.</p>
          </section>

          <section class="content-section">
            <h2>Jeux supportés</h2>
            <p>Actuellement, MULLIGAN TCG supporte Hearthstone, Magic: The Gathering et Pokemon TCG. Nous travaillons constamment à l'ajout de nouveaux jeux pour satisfaire tous les joueurs.</p>
          </section>

          <section class="content-section">
            <h2>Communauté</h2>
            <p>Notre communauté compte déjà plusieurs milliers de joueurs passionnés. Rejoignez-nous pour partager vos decks, discuter des dernières stratégies et participer à des événements exclusifs.</p>
          </section>

          <section class="content-section">
            <h2>Innovation continue</h2>
            <p>Nous développons constamment de nouvelles fonctionnalités basées sur les retours de notre communauté. Votre feedback est précieux pour façonner l'avenir de MULLIGAN TCG.</p>
          </section>

          <section class="content-section">
            <h2>Contact</h2>
            <p>Une question ? Une suggestion ? N'hésitez pas à nous contacter à contact@mulligan-tcg.com ou via nos réseaux sociaux. Nous répondons généralement sous 24h.</p>
          </section>
        </div>

        <div class="last-updated">
          <p class="update-info">Version 2.1 - Août 2024</p>
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