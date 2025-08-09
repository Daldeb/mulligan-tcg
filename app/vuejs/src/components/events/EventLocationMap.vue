<template>
  <div class="event-location-map">
    <!-- En-tête de la carte -->
    <div class="map-header">
      <div class="map-title">
        <i class="pi pi-map-marker map-icon"></i>
        <div class="title-content">
          <h4>Localisation</h4>
          <p class="location-address">{{ formatAddress() }}</p>
        </div>
      </div>
    </div>

    <!-- Conteneur de la carte -->
    <div class="map-wrapper">
      <div id="event-location-map" class="map-container"></div>
      
      <!-- Loading overlay -->
      <div v-if="loading" class="map-loading">
        <div class="emerald-spinner"></div>
        <p>Chargement de la carte...</p>
      </div>

      <!-- Error state -->
      <div v-if="error" class="map-error">
        <i class="pi pi-exclamation-triangle"></i>
        <p>Impossible de charger la carte</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="map-actions">
      <button 
        class="map-action-btn primary"
        @click="openDirections"
      >
        <i class="pi pi-directions"></i>
        Itinéraire
      </button>
      <button 
        class="map-action-btn secondary"
        @click="copyAddress"
      >
        <i class="pi pi-copy"></i>
        Copier
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

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

// Props
const props = defineProps({
address: {
  type: Object,
  required: true,
  validator: (address) => {
    return address && 
           address.street_address &&
           address.city &&
           address.postal_code &&
           typeof address.latitude === 'number' && 
           typeof address.longitude === 'number' &&
           address.latitude !== 0 && address.longitude !== 0
  }
  },
  eventTitle: {
    type: String,
    default: 'Événement'
  }
})

// State
const loading = ref(true)
const error = ref(false)
const map = ref(null)

// Lifecycle
onMounted(async () => {
  await initMap()
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
    map.value = null
  }
})

// Watcher pour réinitialiser la carte si l'adresse change
watch(() => props.address, async () => {
  if (map.value) {
    map.value.remove()
    map.value = null
  }
  await initMap()
}, { deep: true })

// Methods
const initMap = async () => {
  loading.value = true
  error.value = false

  try {
    const { latitude, longitude } = props.address

    // Vérifier que les coordonnées sont valides
    if (!latitude || !longitude || 
        latitude < -90 || latitude > 90 || 
        longitude < -180 || longitude > 180) {
      throw new Error('Coordonnées invalides')
    }

    // Initialiser la carte avec un zoom précis sur l'adresse
    map.value = L.map('event-location-map', {
      zoomControl: true,
      scrollWheelZoom: true,
      doubleClickZoom: true,
      touchZoom: true,
      boxZoom: false,
      keyboard: true
    }).setView([latitude, longitude], 16) // Zoom 16 pour voir les rues

    // Ajouter les tuiles OpenStreetMap avec attribution
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19,
      minZoom: 10
    }).addTo(map.value)

    // Créer un marqueur personnalisé pour l'événement
    const eventIcon = L.divIcon({
      html: createEventMarkerHtml(),
      className: 'custom-event-marker',
      iconSize: [50, 50],
      iconAnchor: [25, 50],
      popupAnchor: [0, -50]
    })

    // Ajouter le marqueur avec popup
    const marker = L.marker([latitude, longitude], { 
      icon: eventIcon 
    }).addTo(map.value)

    const popupContent = createEventPopup()
    marker.bindPopup(popupContent, {
      maxWidth: 250,
      className: 'event-popup-container'
    })

    // Tooltip au hover
    marker.bindTooltip(`📍 ${props.eventTitle}`, {
      direction: 'top',
      offset: [0, -55],
      className: 'event-tooltip-container'
    })

    // Force le redimensionnement après l'initialisation
    setTimeout(() => {
      if (map.value) {
        map.value.invalidateSize()
      }
    }, 100)

    console.log('✅ Carte événement initialisée:', props.eventTitle)

  } catch (err) {
    console.error('❌ Erreur initialisation carte événement:', err)
    error.value = true
  } finally {
    loading.value = false
  }
}

const createEventMarkerHtml = () => {
  return `
    <div class="event-marker">
      <div class="marker-pin">
        <i class="pi pi-calendar"></i>
      </div>
      <div class="marker-pulse"></div>
    </div>
  `
}

const createEventPopup = () => {
  return `
    <div class="event-popup-detail">
      <div class="popup-header">
        <h4>${props.eventTitle}</h4>
      </div>
      <div class="popup-content">
        <div class="popup-address">
          <i class="pi pi-map-marker"></i>
          <div class="address-text">
            <div>${props.address.street_address}</div>
            <div>${props.address.postal_code} ${props.address.city}</div>
          </div>
        </div>
      </div>
    </div>
  `
}

const formatAddress = () => {
  if (!props.address) return ''
  return `${props.address.city} (${props.address.postal_code})`
}

const openDirections = () => {
  const { latitude, longitude } = props.address
  const query = encodeURIComponent(`${props.address.street_address}, ${props.address.city}`)
  const url = `https://maps.google.com/search/${query}/@${latitude},${longitude},16z`
  window.open(url, '_blank')
}

const copyAddress = async () => {
  const fullAddress = `${props.address.street_address}, ${props.address.postal_code} ${props.address.city}, ${props.address.country || 'France'}`
  
  try {
    await navigator.clipboard.writeText(fullAddress)
    // TODO: Afficher un toast de succès
    console.log('✅ Adresse copiée:', fullAddress)
  } catch (err) {
    // Fallback pour navigateurs plus anciens
    const textArea = document.createElement('textarea')
    textArea.value = fullAddress
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    console.log('✅ Adresse copiée (fallback):', fullAddress)
  }
}
</script>

<style scoped>
.event-location-map {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-medium);
  overflow: hidden;
  border: 1px solid var(--surface-200);
}

.map-header {
  padding: 1rem;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.map-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.map-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.title-content h4 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
}

.location-address {
  margin: 0;
  font-size: 0.875rem;
  opacity: 0.9;
  line-height: 1.3;
}

.map-wrapper {
  position: relative;
  height: 350px;
}

.map-container {
  width: 100%;
  height: 100%;
  position: relative;
  z-index: 1;
}

.map-loading,
.map-error {
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

.map-loading p,
.map-error p {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin: 0;
}

.map-error i {
  font-size: 2rem;
  color: #ef4444;
}

.map-actions {
  display: flex;
  gap: 0.5rem;
  padding: 1rem;
  background: var(--surface-100);
  border-top: 1px solid var(--surface-200);
}

.map-action-btn {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.map-action-btn.primary {
  background: var(--primary);
  color: white;
}

.map-action-btn.primary:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

.map-action-btn.secondary {
  background: white;
  color: var(--text-primary);
  border: 1px solid var(--surface-300);
}

.map-action-btn.secondary:hover {
  background: var(--surface-200);
  transform: translateY(-1px);
}

/* Styles pour les marqueurs personnalisés */
:deep(.custom-event-marker) {
  background: none !important;
  border: none !important;
}

:deep(.event-marker) {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

:deep(.marker-pin) {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
  box-shadow: 0 3px 10px rgba(38, 166, 154, 0.4);
  border: 3px solid white;
  z-index: 2;
  position: relative;
}

:deep(.marker-pin i) {
  transform: rotate(45deg);
}

:deep(.marker-pulse) {
  position: absolute;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(38, 166, 154, 0.3);
  animation: eventPulse 2s infinite;
  z-index: 1;
}

@keyframes eventPulse {
  0% {
    transform: scale(0.8);
    opacity: 1;
  }
  70% {
    transform: scale(1.2);
    opacity: 0.3;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

/* Tooltips et popups */
:deep(.event-tooltip-container) {
  background: rgba(0, 0, 0, 0.8) !important;
  border: none !important;
  border-radius: 6px !important;
  color: white !important;
  font-size: 12px !important;
  padding: 6px 10px !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
}

:deep(.event-popup-container) {
  max-width: 250px !important;
}

:deep(.event-popup-container .leaflet-popup-content-wrapper) {
  border-radius: 8px !important;
  padding: 0 !important;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
}

:deep(.event-popup-container .leaflet-popup-content) {
  margin: 0 !important;
  padding: 0 !important;
}

:deep(.event-popup-detail) {
  padding: 0;
  min-width: 220px;
}

:deep(.popup-header) {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 12px;
}

:deep(.popup-header h4) {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

:deep(.popup-content) {
  padding: 12px;
}

:deep(.popup-address) {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 13px;
  line-height: 1.4;
}

:deep(.popup-address i) {
  color: var(--primary);
  margin-top: 2px;
  flex-shrink: 0;
}

:deep(.address-text) {
  flex: 1;
}

/* Responsive */
@media (max-width: 768px) {
  .event-location-map {
    display: none; /* Cache sur mobile */
  }
}

@media (max-width: 1024px) {
  .map-wrapper {
    height: 300px;
  }
  
  .map-header {
    padding: 0.75rem;
  }
  
  .map-actions {
    padding: 0.75rem;
    gap: 0.375rem;
  }
  
  .map-action-btn {
    padding: 0.5rem;
    font-size: 0.8rem;
  }
}
</style>