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
        <span class="current-page">Conditions d'utilisation</span>
      </div>

      <!-- Contenu -->
      <div class="content-wrapper">
        <h1 class="page-title">Conditions d'utilisation</h1>
        
        <div class="content-sections">
          <section class="content-section">
            <h2>1. Acceptation des conditions</h2>
            <p>En accédant et en utilisant MULLIGAN TCG, vous acceptez d'être lié par ces conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.</p>
          </section>

          <section class="content-section">
            <h2>2. Description du service</h2>
            <p>MULLIGAN TCG est une plateforme dédiée aux jeux de cartes à collectionner, offrant des outils de gestion de decks, des forums communautaires et des informations sur le metagame.</p>
          </section>

          <section class="content-section">
            <h2>3. Comptes utilisateur</h2>
            <p>Vous êtes responsable de maintenir la confidentialité de votre compte et mot de passe. Vous acceptez de nous notifier immédiatement de toute utilisation non autorisée de votre compte.</p>
          </section>

          <section class="content-section">
            <h2>4. Contenu utilisateur</h2>
            <p>Vous conservez tous les droits sur le contenu que vous publiez. Cependant, vous nous accordez une licence pour utiliser, modifier et afficher ce contenu dans le cadre de notre service.</p>
          </section>

          <section class="content-section">
            <h2>5. Comportement acceptable</h2>
            <p>Les utilisateurs s'engagent à respecter les autres membres de la communauté et à ne pas publier de contenu offensant, illégal ou inapproprié.</p>
          </section>

          <section class="content-section">
            <h2>6. Modifications des conditions</h2>
            <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prendront effet dès leur publication sur le site.</p>
          </section>
        </div>

        <div class="last-updated">
          <p class="update-info">Dernière mise à jour : 15 juillet 2024</p>
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