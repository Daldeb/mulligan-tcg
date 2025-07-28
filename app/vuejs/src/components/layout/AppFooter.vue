<template>
  <footer class="bg-gray-200 border-t border-gray-300 mt-auto">
    <div class="container mx-auto py-6">
      <div class="flex flex-wrap justify-center space-x-8 text-sm text-gray-600">
        <router-link 
          to="/conditions" 
          class="hover:text-gray-900 transition-colors"
        >
          Conditions d'utilisations
        </router-link>

        <router-link 
          to="/rgpd" 
          class="hover:text-gray-900 transition-colors"
        >
          RGPD
        </router-link>

        <router-link 
          to="/about" 
          class="hover:text-gray-900 transition-colors"
        >
          À propos
        </router-link>

        <router-link 
          to="/team" 
          class="hover:text-gray-900 transition-colors"
        >
          Notre équipe
        </router-link>

        <router-link 
          to="/help" 
          class="hover:text-gray-900 transition-colors"
        >
          Aide
        </router-link>
      </div>

      <Divider class="my-4" />

      <div class="text-center text-xs text-gray-500">
        <p>&copy; {{ currentYear }} MULLIGAN TCG. Tous droits réservés.</p>
        <p class="mt-1">Votre hub TCG ultime pour Hearthstone et plus</p>
        <p class="mt-1 text-red-500 font-semibold">🔥 Version 9000 - Deployed</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped>
footer a {
  text-decoration: none;
}

@media (max-width: 640px) {
  .flex.space-x-8 {
    flex-direction: column;
    gap: 1rem;
    align-items: center;
  }
}
</style>
