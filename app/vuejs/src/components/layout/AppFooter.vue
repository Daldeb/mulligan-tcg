<template>
  <footer class="emerald-footer">
    <div class="footer-gradient"></div>
    
    <div class="container">
      <!-- Footer info grid - structure corrigée -->
      <div class="footer-info">
        <!-- Links à gauche -->
        <div class="footer-links">
          <router-link to="/conditions" class="footer-link">Conditions</router-link>
          <router-link to="/rgpd" class="footer-link">RGPD</router-link>
          <router-link to="/about" class="footer-link">À propos</router-link>
          <router-link to="/equipe" class="footer-link">Équipe</router-link>
          <router-link to="/aide" class="footer-link">Aide</router-link>
        </div>

        <!-- Brand centre -->
        <div class="footer-brand">
          <div class="brand-logo">
            <span class="logo-text">MULLIGAN TCG</span>
            <span class="logo-crown">👑</span>
          </div>
          <p class="copyright">&copy; {{ currentYear }}</p>
        </div>

        <!-- Status + Stats à droite -->
        <div class="footer-stats">
          <div class="status-indicator">
            <div class="status-dot"></div>
            <span class="status-text">En ligne</span>
          </div>
          <div class="version-info">
            <span class="version-badge">V9000</span>
          </div>
          <div class="gaming-stats">
            <span class="stat">{{ onlineUsers }} joueurs</span>
            <span class="stat">{{ totalGames }} parties</span>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const currentYear = computed(() => new Date().getFullYear())

// Gaming stats (simulées)
const onlineUsers = ref(1247)
const totalGames = ref('2.8M')

// Animation des stats au chargement
onMounted(() => {
  // Simulation de mise à jour temps réel des joueurs connectés
  setInterval(() => {
    onlineUsers.value = Math.floor(Math.random() * 500) + 1000
  }, 30000) // Toutes les 30 secondes
})
</script>

<style scoped>
.emerald-footer {
  position: relative;
  background: linear-gradient(135deg, var(--secondary-dark) 0%, var(--secondary) 50%, var(--surface-300) 100%);
  color: var(--text-inverse);
  margin-top: auto;
  overflow: hidden;
}

.footer-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--emerald-gradient);
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem;
  position: relative;
}

/* Footer info grid - structure corrigée */
.footer-info {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 2rem;
  align-items: center;
}

/* Links à gauche */
.footer-links {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  justify-self: start;
}

.footer-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.5rem 0;
  transition: all var(--transition-fast);
  border-bottom: 1px solid transparent;
}

.footer-link:hover {
  color: white;
  border-bottom-color: var(--primary);
}

/* Brand au centre */
.footer-brand {
  text-align: center;
  justify-self: center;
}

.brand-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
  letter-spacing: 1px;
}

.logo-crown {
  font-size: 1.25rem;
}

.copyright {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.75rem;
  margin: 0;
}

/* Stats à droite */
.footer-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.75rem;
  justify-self: end;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  animation: pulse 2s infinite;
}

.status-text {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
}

.version-info {
  display: flex;
  gap: 0.5rem;
}

.version-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.gaming-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
}

.stat {
  color: rgba(255, 255, 255, 0.8);
  font-weight: 500;
}

/* Animations */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 1rem;
  }
  
  .footer-info {
    grid-template-columns: 1fr;
    gap: 1.5rem;
    text-align: center;
  }
  
  .footer-links {
    justify-content: center;
    gap: 1rem;
  }
  
  .footer-stats {
    align-items: center;
  }
  
  .gaming-stats {
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .footer-links {
    gap: 0.75rem;
  }
  
  .footer-link {
    font-size: 0.75rem;
  }
  
  .gaming-stats {
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
  }
}
</style>