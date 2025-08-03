<template>
  <div class="shop-map-container">
    <!-- En-tête de la carte -->
    <div class="map-header">
      <div class="map-title">
        <h2>Carte des boutiques TCG</h2>
        <p>Découvrez les boutiques de cartes à collectionner près de chez vous</p>
      </div>
      <div class="map-stats">
        <div class="stat-item">
          <span class="stat-value">{{ totalShops }}</span>
          <span class="stat-label">Boutiques</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ onlineShops }}</span>
          <span class="stat-label">En ligne</span>
        </div>
      </div>
    </div>

    <!-- Conteneur de la carte -->
    <div class="map-wrapper">
      <div id="shop-map" class="map-container"></div>
      
      <!-- Loading overlay -->
      <div v-if="loading" class="map-loading">
        <div class="emerald-spinner"></div>
        <p>Chargement de la carte...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import api from '@/services/api'

// Fix pour les icônes Leaflet avec Vite
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'

// Configuration des icônes par défaut
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
})

// State
const loading = ref(true)
const map = ref(null)
const shops = ref([])
const totalShops = ref(0)
const onlineShops = ref(0)

// Lifecycle
onMounted(async () => {
  await initMap()
  await loadShops()
  
  // Force le redimensionnement de la carte après le montage
  setTimeout(() => {
    if (map.value) {
      map.value.invalidateSize()
    }
  }, 100)
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})

// Methods
const initMap = async () => {
  try {
    // Initialiser la carte centrée sur la France
    map.value = L.map('shop-map').setView([46.603354, 1.888334], 6)
    
    // Ajouter les tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map.value)
    
    console.log('✅ Carte initialisée')
  } catch (error) {
    console.error('❌ Erreur initialisation carte:', error)
  }
}

const loadShops = async () => {
  try {
    loading.value = true
    
    // Récupérer les données des boutiques depuis l'API
    const response = await api.shops.getMapData()
    shops.value = response.data.data
    
    // Mettre à jour les stats
    totalShops.value = shops.value.length
    onlineShops.value = shops.value.filter(shop => shop.website).length
    
    // Ajouter les marqueurs sur la carte
    addMarkersToMap()
    
    // Force le redimensionnement après ajout des marqueurs
    setTimeout(() => {
      if (map.value) {
        map.value.invalidateSize()
      }
    }, 50)
    
    console.log(`✅ ${shops.value.length} boutiques chargées`)
  } catch (error) {
    console.error('❌ Erreur chargement boutiques:', error)
  } finally {
    loading.value = false
  }
}

const addMarkersToMap = () => {
  if (!map.value) return
  
  shops.value.forEach(shop => {
    if (shop.latitude && shop.longitude) {
      // Créer une icône personnalisée selon le type de boutique
      const customIcon = L.divIcon({
        html: getShopIconHtml(shop),
        className: 'custom-shop-marker',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
      })
      
      // Créer le marqueur avec l'icône personnalisée
      const marker = L.marker([shop.latitude, shop.longitude], { 
        icon: customIcon 
      }).addTo(map.value)
        
      // Créer le popup avec les infos complètes
      const popupContent = createShopPopup(shop)
      marker.bindPopup(popupContent, {
        maxWidth: 300,
        className: 'shop-popup-container'
      })
      
      // Tooltip au hover (résumé rapide)
      const tooltipContent = `
        <div class="shop-tooltip">
          <strong>${shop.name}</strong><br>
          <span class="tooltip-location">${shop.city}</span>
          ${shop.rating ? `<span class="tooltip-rating">⭐ ${shop.rating}/5</span>` : ''}
        </div>
      `
      
      marker.bindTooltip(tooltipContent, {
        direction: 'top',
        offset: [0, -45],
        className: 'shop-tooltip-container'
      })
    }
  })
}

// Générer l'HTML pour l'icône personnalisée
const getShopIconHtml = (shop) => {
  const iconColor = getShopIconColor(shop.type)
  const statusDot = shop.type === 'verified' ? '<div class="status-dot verified"></div>' : ''
  
  return `
    <div class="shop-marker" style="background-color: ${iconColor}">
      <i class="pi pi-shop"></i>
      ${statusDot}
    </div>
  `
}

// Couleurs selon le type de boutique
const getShopIconColor = (type) => {
  switch (type) {
    case 'verified': return '#26a69a'   // Vert emerald
    case 'registered': return '#ff5722' // Orange accent  
    case 'scraped': return '#78909c'    // Gris
    default: return '#78909c'
  }
}

// Créer le contenu du popup détaillé
const createShopPopup = (shop) => {
  return `
    <div class="shop-popup-detail">
      <div class="popup-header">
        <h3>${shop.name}</h3>
        <div class="shop-type-badge ${shop.type}">${getShopTypeLabel(shop.type)}</div>
      </div>
      
      <div class="popup-content">
        <div class="popup-location">
          <i class="pi pi-map-marker"></i>
          <span><strong>${shop.city}</strong> (${shop.postal_code})</span>
        </div>
        
        ${shop.rating ? `
          <div class="popup-rating">
            <i class="pi pi-star-fill"></i>
            <span>${shop.rating}/5 (${shop.reviewsCount || 0} avis)</span>
          </div>
        ` : ''}
        
        ${shop.website ? `
          <div class="popup-link">
            <i class="pi pi-external-link"></i>
            <a href="${shop.website}" target="_blank">Site web</a>
          </div>
        ` : ''}
        
        <div class="popup-actions">
          <button class="popup-btn primary" onclick="window.open('https://maps.google.com/search/${encodeURIComponent(shop.name + ' ' + shop.city)}', '_blank')">
            <i class="pi pi-directions"></i>
            Itinéraire
          </button>
          <button class="popup-btn secondary">
            <i class="pi pi-info-circle"></i>
            Détails
          </button>
        </div>
      </div>
    </div>
  `
}

const getShopTypeLabel = (type) => {
  switch (type) {
    case 'verified': return 'Vérifiée'
    case 'registered': return 'Inscrite'
    case 'scraped': return 'Référencée'
    default: return 'Boutique'
  }
}
</script>

<style scoped>
.shop-map-container {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.map-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  margin-bottom: 1rem;
}

.map-title h2 {
  margin: 0 0 0.5rem 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 700;
}

.map-title p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 1rem;
}

.map-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.map-wrapper {
  flex: 1;
  position: relative;
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  overflow: hidden;
}

.map-container {
  width: 100%;
  height: 800px;
  min-height: 800px;
  position: relative;
  z-index: 1; /* Assure que la carte reste sous le header principal */
}

.map-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  z-index: 1000;
}

.map-loading p {
  color: var(--text-secondary);
  font-weight: 500;
  margin: 0;
}

@keyframes emeraldSpin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Marqueurs personnalisés */
:deep(.custom-shop-marker) {
  background: none !important;
  border: none !important;
}

:deep(.shop-marker) {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 3px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
  font-weight: bold;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

:deep(.shop-marker:hover) {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

:deep(.status-dot) {
  position: absolute;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  top: -2px;
  right: -2px;
  border: 2px solid white;
}

:deep(.status-dot.verified) {
  background: #4caf50;
}

/* Tooltips au hover */
:deep(.shop-tooltip-container) {
  background: rgba(0, 0, 0, 0.8) !important;
  border: none !important;
  border-radius: 8px !important;
  color: white !important;
  font-size: 13px !important;
  padding: 8px 12px !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
}

:deep(.shop-tooltip-container::before) {
  border-top-color: rgba(0, 0, 0, 0.8) !important;
}

:deep(.shop-tooltip) {
  text-align: center;
  line-height: 1.4;
}

:deep(.tooltip-location) {
  color: #ccc;
  font-size: 12px;
}

:deep(.tooltip-rating) {
  color: #ffd700;
  font-size: 12px;
  margin-left: 8px;
}

/* Popups détaillés */
:deep(.shop-popup-container) {
  max-width: 300px !important;
}

:deep(.shop-popup-container .leaflet-popup-content-wrapper) {
  border-radius: 12px !important;
  padding: 0 !important;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2) !important;
}

:deep(.shop-popup-container .leaflet-popup-content) {
  margin: 0 !important;
  padding: 0 !important;
}

:deep(.shop-popup-detail) {
  padding: 0;
  min-width: 280px;
}

:deep(.popup-header) {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

:deep(.popup-header h3) {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
  max-width: 200px;
}

:deep(.shop-type-badge) {
  font-size: 10px;
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

:deep(.shop-type-badge.verified) {
  background: rgba(76, 175, 80, 0.2);
  color: #4caf50;
  border: 1px solid rgba(76, 175, 80, 0.3);
}

:deep(.shop-type-badge.registered) {
  background: rgba(255, 87, 34, 0.2);
  color: var(--accent);
  border: 1px solid rgba(255, 87, 34, 0.3);
}

:deep(.shop-type-badge.scraped) {
  background: rgba(120, 144, 156, 0.2);
  color: #78909c;
  border: 1px solid rgba(120, 144, 156, 0.3);
}

:deep(.popup-content) {
  padding: 16px;
}

:deep(.popup-location),
:deep(.popup-rating),
:deep(.popup-link) {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 14px;
}

:deep(.popup-location i) {
  color: var(--primary);
}

:deep(.popup-rating i) {
  color: #ffd700;
}

:deep(.popup-link i) {
  color: var(--primary);
}

:deep(.popup-link a) {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
}

:deep(.popup-link a:hover) {
  text-decoration: underline;
}

:deep(.popup-actions) {
  display: flex;
  gap: 8px;
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid var(--surface-200);
}

:deep(.popup-btn) {
  flex: 1;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

:deep(.popup-btn.primary) {
  background: var(--primary);
  color: white;
}

:deep(.popup-btn.primary:hover) {
  background: var(--primary-dark);
}

:deep(.popup-btn.secondary) {
  background: var(--surface-200);
  color: var(--text-primary);
}

:deep(.popup-btn.secondary:hover) {
  background: var(--surface-300);
}

/* Responsive */
@media (max-width: 768px) {
  .map-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .map-stats {
    justify-content: center;
  }
  
  .map-container {
    height: 600px;
    min-height: 600px;
  }
}
</style>