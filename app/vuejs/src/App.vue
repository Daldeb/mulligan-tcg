<template>
  <div id="app" class="min-h-screen bg-surface">
    <!-- Header avec navigation -->
    <AppHeader />
    
    <!-- Contenu principal -->
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    
    <!-- Toast notifications -->
    <Toast position="top-right" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import AppHeader from '@/components/layout/AppHeader.vue'

const toast = useToast()

onMounted(() => {
  // Message de bienvenue au chargement
  toast.add({
    severity: 'success',
    summary: 'Bienvenue !',
    detail: 'MULLIGAN TCG chargé avec succès',
    life: 3000
  })
})
</script>

<style>
/* Reset et base */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: var(--surface);
  color: var(--text-primary);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding-top: 140px; /* Hauteur du header + navigation */
  min-height: calc(100vh - 140px);
}

/* Transitions entre les pages */
.page-enter-active,
.page-leave-active {
  transition: all 0.4s ease-out;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Utilities responsive */
.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

@media (max-width: 768px) {
  .container {
    padding: 0 1rem;
  }
  
  .main-content {
    padding-top: 120px; /* Header plus compact sur mobile */
  }
}

@media (max-width: 640px) {
  .main-content {
    padding-top: 100px;
  }
}

/* Classes utilitaires */
.flex {
  display: flex;
}

.grid {
  display: grid;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.justify-between {
  justify-content: space-between;
}

.gap-1 { gap: 0.25rem; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 0.75rem; }
.gap-4 { gap: 1rem; }
.gap-6 { gap: 1.5rem; }
.gap-8 { gap: 2rem; }

.p-1 { padding: 0.25rem; }
.p-2 { padding: 0.5rem; }
.p-3 { padding: 0.75rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.p-8 { padding: 2rem; }

.m-1 { margin: 0.25rem; }
.m-2 { margin: 0.5rem; }
.m-3 { margin: 0.75rem; }
.m-4 { margin: 1rem; }
.m-6 { margin: 1.5rem; }
.m-8 { margin: 2rem; }

.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mb-8 { margin-bottom: 2rem; }

.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 0.75rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.mt-8 { margin-top: 2rem; }

.mr-2 { margin-right: 0.5rem; }
.mr-3 { margin-right: 0.75rem; }
.mr-4 { margin-right: 1rem; }

.ml-2 { margin-left: 0.5rem; }
.ml-3 { margin-left: 0.75rem; }
.ml-4 { margin-left: 1rem; }

.w-full { width: 100%; }
.h-full { height: 100%; }

.min-h-screen { min-height: 100vh; }

.bg-surface { background: var(--surface); }
.bg-primary { background: var(--primary); }
.bg-secondary { background: var(--secondary); }

.text-primary { color: var(--text-primary); }
.text-secondary { color: var(--text-secondary); }
.text-white { color: var(--text-inverse); }

.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }

.text-sm { font-size: 0.875rem; }
.text-lg { font-size: 1.125rem; }
.text-xl { font-size: 1.25rem; }
.text-2xl { font-size: 1.5rem; }
.text-3xl { font-size: 1.875rem; }

.rounded { border-radius: var(--border-radius); }
.rounded-lg { border-radius: var(--border-radius-large); }
.rounded-full { border-radius: 50%; }

.shadow { box-shadow: var(--shadow-small); }
.shadow-md { box-shadow: var(--shadow-medium); }
.shadow-lg { box-shadow: var(--shadow-large); }

.cursor-pointer { cursor: pointer; }

.overflow-hidden { overflow: hidden; }

.relative { position: relative; }
.absolute { position: absolute; }
.fixed { position: fixed; }

.z-10 { z-index: 10; }
.z-20 { z-index: 20; }
.z-50 { z-index: 50; }

/* Animation helpers */
.transition {
  transition: all var(--transition-medium);
}

.transition-fast {
  transition: all var(--transition-fast);
}

.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-large);
}

.hover-scale:hover {
  transform: scale(1.05);
}

/* Loading states */
.loading {
  pointer-events: none;
  opacity: 0.6;
}

.loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 20px;
  height: 20px;
  margin: -10px 0 0 -10px;
  border: 2px solid var(--surface-300);
  border-top: 2px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive grid */
.grid-responsive {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .grid-responsive {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

/* Dark mode support (pour plus tard) */
@media (prefers-color-scheme: dark) {
  .dark-mode {
    --surface: #1a1a1a;
    --surface-100: #2a2a2a;
    --surface-200: #3a3a3a;
    --text-primary: #ffffff;
    --text-secondary: #cccccc;
  }
}
</style>