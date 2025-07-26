<template>
    <nav class="navigation-tabs">
      <div class="nav-container">
        <div class="nav-tabs-wrapper">
          <Button
            v-for="(tab, index) in navigationTabs"
            :key="tab.name"
            :label="tab.label"
            :icon="tab.icon"
            class="nav-tab"
            :class="{ 
              'nav-tab-active': isActiveTab(tab.route),
              'slide-in-up': true
            }"
            :style="{ animationDelay: `${index * 0.1}s` }"
            @click="navigateToTab(tab)"
            outlined
          >
            <!-- Indicator pour l'onglet actif -->
            <div 
              v-if="isActiveTab(tab.route)" 
              class="active-indicator"
            ></div>
            
            <!-- Badge de notification si besoin -->
            <Badge 
              v-if="tab.badge && tab.badge > 0"
              :value="tab.badge.toString()"
              class="tab-badge"
              severity="danger"
            />
          </Button>
        </div>
        
        <!-- Indicateur de progression pour les notifications -->
        <div 
          v-if="hasNotifications" 
          class="notification-pulse"
        ></div>
      </div>
    </nav>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, watch } from 'vue'
  import { useRouter, useRoute } from 'vue-router'
  import { useToast } from 'primevue/usetoast'
  
  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  
  // Navigation tabs data
  const navigationTabs = ref([
    {
      name: 'discussions',
      label: 'Discussions',
      icon: 'pi pi-comments',
      route: '/discussions',
      badge: 5, // Nouvelles discussions
      description: 'Forum communautaire'
    },
    {
      name: 'decks',
      label: 'Decks',
      icon: 'pi pi-clone',
      route: '/decks',
      badge: 0,
      description: 'Collection de decks'
    },
    {
      name: 'rankings',
      label: 'Classements',
      icon: 'pi pi-trophy',
      route: '/rankings',
      badge: 2, // Nouveaux classements
      description: 'Classements et tournois'
    },
    {
      name: 'shops',
      label: 'Boutiques',
      icon: 'pi pi-shopping-bag',
      route: '/shops',
      badge: 0,
      description: 'Marketplace et boutiques'
    }
  ])
  
  // Computed properties
  const currentRoute = computed(() => route.path)
  
  const hasNotifications = computed(() => {
    return navigationTabs.value.some(tab => tab.badge && tab.badge > 0)
  })
  
  // Methods
  const isActiveTab = (tabRoute) => {
    if (tabRoute === '/' && currentRoute.value === '/') {
      return true
    }
    return currentRoute.value.startsWith(tabRoute) && tabRoute !== '/'
  }
  
  const navigateToTab = (tab) => {
    // Animation de feedback
    const tabElement = event.currentTarget
    tabElement.style.transform = 'scale(0.95)'
    
    setTimeout(() => {
      tabElement.style.transform = ''
    }, 150)
  
    // Toast de navigation
    toast.add({
      severity: 'info',
      summary: 'Navigation',
      detail: `${tab.description}`,
      life: 2000
    })
  
    // Navigation effective
    if (currentRoute.value !== tab.route) {
      router.push(tab.route)
    }
  }
  
  // Simuler la réduction des badges au fil du temps
  const simulateBadgeUpdates = () => {
    setInterval(() => {
      navigationTabs.value.forEach(tab => {
        if (tab.badge > 0 && Math.random() > 0.8) {
          tab.badge = Math.max(0, tab.badge - 1)
        }
      })
    }, 10000) // Toutes les 10 secondes
  }
  
  // Lifecycle
  onMounted(() => {
    simulateBadgeUpdates()
  })
  
  // Watch route changes for animations
  watch(currentRoute, (newRoute, oldRoute) => {
    if (newRoute !== oldRoute) {
      // Animation subtile lors du changement de route
      const activeTab = document.querySelector('.nav-tab-active')
      if (activeTab) {
        activeTab.style.transform = 'scale(1.05)'
        setTimeout(() => {
          activeTab.style.transform = ''
        }, 200)
      }
    }
  })
  </script>
  
  <style scoped>
  .navigation-tabs {
    background: var(--surface);
    border-bottom: 1px solid var(--surface-200);
    padding: 0.5rem 0;
    position: relative;
    overflow: hidden;
  }
  
  .navigation-tabs::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, 
      transparent 0%, 
      var(--primary-light) 50%, 
      transparent 100%
    );
  }
  
  .nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
  }
  
  .nav-tabs-wrapper {
    display: flex;
    justify-content: center;
    gap: 1rem;
    position: relative;
  }
  
  .nav-tab {
    border-radius: 50px !important;
    padding: 0.75rem 1.5rem !important;
    transition: all var(--transition-medium) !important;
    border: 2px solid var(--surface-300) !important;
    font-weight: 500 !important;
    font-size: 0.9rem !important;
    position: relative;
    overflow: visible !important;
    background: var(--surface) !important;
    color: var(--text-secondary) !important;
    min-width: 140px;
    justify-content: center;
  }
  
  .nav-tab:hover {
    border-color: var(--primary) !important;
    color: var(--primary) !important;
    background: rgba(38, 166, 154, 0.05) !important;
    transform: translateY(-3px);
    box-shadow: var(--shadow-medium);
  }
  
  .nav-tab:hover::before {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: inherit;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    z-index: -1;
    opacity: 0.1;
  }
  
  .nav-tab-active {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
    border-color: var(--primary) !important;
    color: var(--text-inverse) !important;
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
  }
  
  .nav-tab-active:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary)) !important;
    transform: translateY(-4px);
    box-shadow: var(--shadow-large);
  }
  
  .nav-tab-active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid var(--surface);
  }
  
  .active-indicator {
    position: absolute;
    top: -3px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-light), var(--accent));
    border-radius: 0 0 3px 3px;
    animation: indicatorPulse 2s infinite;
  }
  
  @keyframes indicatorPulse {
    0%, 100% { opacity: 1; transform: translateX(-50%) scaleX(1); }
    50% { opacity: 0.7; transform: translateX(-50%) scaleX(1.2); }
  }
  
  .tab-badge {
    position: absolute !important;
    top: -8px !important;
    right: -8px !important;
    min-width: 18px !important;
    height: 18px !important;
    font-size: 0.7rem !important;
    font-weight: 600 !important;
    animation: badgeBounce 0.5s ease-out;
  }
  
  @keyframes badgeBounce {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
  }
  
  .notification-pulse {
    position: absolute;
    top: 0;
    right: 2rem;
    width: 8px;
    height: 8px;
    background: var(--accent);
    border-radius: 50%;
    animation: notificationPulse 2s infinite;
  }
  
  @keyframes notificationPulse {
    0% { 
      opacity: 1;
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(255, 87, 34, 0.7);
    }
    70% { 
      opacity: 0.7;
      transform: scale(1.1);
      box-shadow: 0 0 0 8px rgba(255, 87, 34, 0);
    }
    100% { 
      opacity: 1;
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(255, 87, 34, 0);
    }
  }
  
  /* Animation d'entrée */
  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .slide-in-up {
    animation: slideInUp 0.6s ease-out forwards;
    opacity: 0;
  }
  
  /* Responsive Design */
  @media (max-width: 1024px) {
    .nav-container {
      padding: 0 1rem;
    }
    
    .nav-tabs-wrapper {
      gap: 0.75rem;
    }
    
    .nav-tab {
      padding: 0.625rem 1.25rem !important;
      font-size: 0.85rem !important;
      min-width: 120px;
    }
  }
  
  @media (max-width: 768px) {
    .nav-tabs-wrapper {
      gap: 0.5rem;
    }
    
    .nav-tab {
      padding: 0.5rem 1rem !important;
      font-size: 0.8rem !important;
      min-width: 100px;
    }
  }
  
  @media (max-width: 640px) {
    .nav-container {
      padding: 0 0.5rem;
    }
    
    .nav-tabs-wrapper {
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.5rem;
    }
    
    .nav-tab {
      flex: 1;
      min-width: 80px;
      padding: 0.5rem 0.75rem !important;
      font-size: 0.75rem !important;
    }
    
    .notification-pulse {
      right: 0.5rem;
    }
  }
  
  @media (max-width: 480px) {
    .nav-tab {
      font-size: 0.7rem !important;
      padding: 0.4rem 0.5rem !important;
    }
    
    .nav-tab .p-button-label {
      display: none;
    }
    
    .nav-tab .p-button-icon {
      margin: 0 !important;
    }
  }
  
  /* Effet de survol pour les très petits écrans */
  @media (hover: none) {
    .nav-tab:hover {
      transform: none;
      box-shadow: var(--shadow-small);
    }
    
    .nav-tab-active:hover {
      transform: translateY(-2px);
    }
  }
  
  /* Dark mode support */
  @media (prefers-color-scheme: dark) {
    .dark-mode .navigation-tabs {
      background: var(--surface-dark);
      border-bottom-color: var(--surface-300);
    }
    
    .dark-mode .nav-tab {
      background: var(--surface-dark) !important;
      border-color: var(--surface-400) !important;
      color: var(--text-primary-dark) !important;
    }
  }
  </style>