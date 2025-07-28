<template>
  <footer class="emerald-footer">
    <div class="footer-gradient"></div>
    
    <div class="container">
      <!-- Tout sur une seule ligne -->
      <div class="footer-nav">
        <!-- Links à gauche -->
        <div class="footer-links">
          <router-link to="/conditions" class="footer-link">Conditions</router-link>
          <router-link to="/rgpd" class="footer-link">RGPD</router-link>
          <router-link to="/about" class="footer-link">À propos</router-link>
          <router-link to="/team" class="footer-link">Équipe</router-link>
          <router-link to="/help" class="footer-link">Aide</router-link>
        </div>

        <!-- Brand centre -->
        <div class="footer-brand">
          <span class="logo-text">MULLIGAN TCG 👑</span>
          <span class="copyright">&copy; {{ currentYear }}</span>
        </div>

        <!-- Status + Stats à droite -->
        <div class="footer-status">
          <div class="status-dot online"></div>
          <span class="status-text">En ligne</span>
          <span class="version-badge">V9000</span>
          <span class="stat">{{ onlineUsers }} joueurs</span>
          <span class="stat">{{ totalGames }} parties</span>
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
const totalDecks = ref('45K')

// Animation des stats au chargement
onMounted(() => {
  // Simulation de mise à jour temps réel des joueurs connectés
  setInterval(() => {
    onlineUsers.value = Math.floor(Math.random() * 500) + 1000
  }, 30000) // Toutes les 30 secondes
})
</script>

<style scoped>
/* === FOOTER EMERALD GAMING === */

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
  padding: 3rem 2rem 1rem 2rem;
  position: relative;
}

/* Navigation footer */
.footer-nav {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.footer-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  border-radius: var(--border-radius);
  transition: all var(--transition-fast);
  border: 1px solid transparent;
  font-size: 0.9rem;
  font-weight: 500;
}

.footer-link:hover {
  color: white;
  background: rgba(38, 166, 154, 0.2);
  border-color: rgba(38, 166, 154, 0.3);
  transform: translateY(-1px);
}

.footer-link.active {
  color: var(--primary);
  background: rgba(38, 166, 154, 0.15);
  border-color: var(--primary);
}

.link-icon {
  font-size: 1rem;
  opacity: 0.9;
}

/* Divider gaming */
.footer-divider {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 2rem 0;
}

.divider-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(38, 166, 154, 0.5), transparent);
}

.divider-icon {
  color: var(--primary);
  font-size: 1.5rem;
  padding: 0.5rem;
  background: rgba(38, 166, 154, 0.1);
  border-radius: 50%;
  border: 1px solid rgba(38, 166, 154, 0.3);
}

/* Footer info grid */
.footer-info {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 2rem;
  align-items: center;
  margin-bottom: 2rem;
}

/* Brand section */
.footer-brand {
  text-align: left;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  letter-spacing: 1px;
}

.logo-crown {
  font-size: 1.25rem;
}

.copyright {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.tagline {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
  margin: 0;
  font-style: italic;
}

/* Status section */
.footer-status {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
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
  gap: 0.75rem;
}

.version-badge,
.deploy-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.version-badge {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.deploy-badge {
  background: rgba(255, 87, 34, 0.2);
  color: var(--accent);
  border: 1px solid rgba(255, 87, 34, 0.3);
}

/* Gaming stats */
.footer-stats {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  text-align: right;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.stat-label {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 0.125rem;
}

/* Social section */
.footer-social {
  text-align: center;
  margin-bottom: 2rem;
}

.social-title {
  font-size: 1rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 1rem;
}

.social-links {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.social-link {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.7);
  border: 2px solid rgba(255, 255, 255, 0.2);
  text-decoration: none;
  transition: all var(--transition-fast);
  font-size: 1.25rem;
}

.social-link:hover {
  transform: translateY(-2px);
  color: white;
  border-color: var(--primary);
  background: rgba(38, 166, 154, 0.2);
}

.social-link.discord:hover {
  border-color: #5865F2;
  background: rgba(88, 101, 242, 0.2);
}

.social-link.twitter:hover {
  border-color: #1DA1F2;
  background: rgba(29, 161, 242, 0.2);
}

.social-link.youtube:hover {
  border-color: #FF0000;
  background: rgba(255, 0, 0, 0.2);
}

.social-link.reddit:hover {
  border-color: #FF4500;
  background: rgba(255, 69, 0, 0.2);
}

/* Footer bottom */
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  font-size: 0.875rem;
}

.bottom-text {
  color: rgba(255, 255, 255, 0.7);
}

.bottom-tech {
  display: flex;
  gap: 0.5rem;
}

.tech-badge {
  padding: 0.25rem 0.5rem;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
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
    padding: 0.5rem 1rem;
  }
  
  .footer-nav {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
  }
  
  .footer-links {
    order: 2;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .footer-brand {
    order: 1;
  }
  
  .footer-status {
    order: 3;
    justify-content: center;
    gap: 0.5rem;
  }
}

@media (max-width: 640px) {
  .footer-links {
    gap: 0.75rem;
  }
  
  .footer-link {
    font-size: 0.75rem;
  }
  
  .footer-status {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>