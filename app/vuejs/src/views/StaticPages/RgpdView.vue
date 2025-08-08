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
        <span class="current-page">Protection des données</span>
      </div>

      <!-- Contenu -->
      <div class="content-wrapper">
        <h1 class="page-title">Protection des données (RGPD)</h1>
        
        <div class="content-sections">
          <section class="content-section">
            <h2>1. Responsable du traitement</h2>
            <p>MULLIGAN TCG, en tant que responsable du traitement, s'engage à protéger vos données personnelles conformément au Règlement Général sur la Protection des Données (RGPD).</p>
          </section>

          <section class="content-section">
            <h2>2. Données collectées</h2>
            <p>Nous collectons uniquement les données nécessaires au fonctionnement de notre service : email, pseudonyme, préférences de jeu et données de navigation anonymisées.</p>
          </section>

          <section class="content-section">
            <h2>3. Finalités du traitement</h2>
            <p>Vos données sont utilisées pour : la gestion de votre compte, l'amélioration de nos services, la communication sur nos fonctionnalités et la personnalisation de votre expérience.</p>
          </section>

          <section class="content-section">
            <h2>4. Base légale</h2>
            <p>Le traitement de vos données repose sur votre consentement explicite et sur l'exécution du contrat de service que vous avez accepté.</p>
          </section>

          <section class="content-section">
            <h2>5. Vos droits</h2>
            <p>Vous disposez du droit d'accès, de rectification, de suppression, de portabilité et d'opposition au traitement de vos données. Contactez-nous à privacy@mulligan-tcg.com pour exercer ces droits.</p>
          </section>

          <section class="content-section">
            <h2>6. Conservation des données</h2>
            <p>Vos données sont conservées pendant toute la durée de votre compte actif, puis supprimées dans un délai maximum de 3 ans après votre dernière connexion.</p>
          </section>

          <section class="content-section">
            <h2>7. Sécurité</h2>
            <p>Nous mettons en œuvre toutes les mesures techniques et organisationnelles appropriées pour protéger vos données contre tout accès non autorisé, perte ou divulgation.</p>
          </section>
        </div>

        <div class="last-updated">
          <p class="update-info">Dernière mise à jour : 20 juin 2024</p>
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