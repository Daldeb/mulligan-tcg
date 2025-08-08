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
          <router-link to="/aide" class="footer-link">Aide</router-link>
        </div>
        
        <!-- Brand centre -->
        <div class="footer-brand">
          <div class="brand-logo">
            <img src="/favicon.png" alt="Mulligan TCG" class="footer-favicon" />
            <span class="logo-text">MULLIGAN TCG</span>
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
            <span class="version-badge">V2.1</span>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref, computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped>
.emerald-footer {
  position: relative;
  background: linear-gradient(135deg, #181B66 0%, #3B4AA0 50%, #FFFFFF 100%);
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

.footer-favicon {
  width: 24px;
  height: 24px;
  border-radius: 4px;
  transition: transform var(--transition-fast);
  flex-shrink: 0;
}

.brand-logo:hover .footer-favicon {
  transform: scale(1.05);
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
  
  .footer-favicon {
    width: 20px;
    height: 20px;
  }
}

@media (max-width: 640px) {
  .footer-links {
    gap: 0.75rem;
  }
  
  .footer-link {
    font-size: 0.75rem;
  }
  
  .footer-favicon {
    width: 18px;
    height: 18px;
  }
}
</style>