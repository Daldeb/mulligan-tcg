<template>
  <div class="event-map-container">
    <!-- En-tête de la carte -->
    <div class="map-header">
      <div class="map-title">
        <h3>Événements à proximité</h3>
        <p v-if="userLocation">{{ nearbyEventsCount }} événement{{ nearbyEventsCount > 1 ? 's' : '' }} dans un rayon de {{ searchRadius }}km</p>
        <p v-else class="no-location">Activez la géolocalisation pour voir les événements près de chez vous</p>
      </div>
      <div class="map-controls">
        <button 
          v-if="!userLocation" 
          class="locate-btn"
          @click="requestLocation"
          :disabled="isLocating"
        >
          <i class="pi pi-map-marker"></i>
          <span v-if="isLocating">Localisation...</span>
          <span v-else>Me localiser</span>
        </button>
        <div v-if="userLocation" class="radius-control">
          <label for="radius-slider">Rayon: {{ searchRadius }}km</label>
          <input 
            id="radius-slider"
            type="range" 
            v-model="searchRadius"
            min="5" 
            max="100" 
            step="5"
            @change="updateNearbyEvents"
            class="radius-slider"
          >
        </div>
      </div>
    </div>

    <!-- Carte avec overlay de localisation -->
    <div class="map-wrapper">
      <div id="event-map" class="map-container"></div>
      
      <!-- Overlay pour demander la localisation -->
      <div v-if="!userLocation && !hasTriedLocation" class="location-overlay">
        <div class="location-prompt">
          <i class="pi pi-map-marker location-icon"></i>
          <h4>Voir les événements près de chez vous</h4>
          <p>Autorisez la géolocalisation pour découvrir les événements TCG dans votre région</p>
          <button class="locate-action-btn" @click="requestLocation">
            <i class="pi pi-crosshairs"></i>
            Activer la géolocalisation
          </button>
        </div>
      </div>
      
      <!-- Loading overlay -->
      <div v-if="loading" class="map-loading">
        <div class="emerald-spinner"></div>
        <p>Chargement de la carte...</p>
      </div>
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
  events: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['event-selected', 'location-changed'])

// State
const loading = ref(true)
const map = ref(null)
const userLocation = ref(null)
const isLocating = ref(false)
const hasTriedLocation = ref(false)
const searchRadius = ref(25) // Rayon par défaut en km
const nearbyEventsCount = ref(0)
const eventMarkers = ref([])
const userMarker = ref(null)

// Lifecycle
onMounted(async () => {
  await initMap()
  
  // Essayer de récupérer la position sauvegardée
  const savedLocation = localStorage.getItem('user_location')
  if (savedLocation) {
    try {
      userLocation.value = JSON.parse(savedLocation)
      await updateMapWithUserLocation()
    } catch (e) {
      console.warn('Position sauvegardée invalide')
      localStorage.removeItem('user_location')
    }
  }
  
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

// Watchers
watch(() => props.events, () => {
  updateEventMarkers()
}, { deep: true })

watch(searchRadius, () => {
  if (userLocation.value) {
    updateNearbyEvents()
  }
})

// Methods
const initMap = async () => {
  try {
    // Initialiser la carte centrée sur la France
    map.value = L.map('event-map', {
      scrollWheelZoom: false,
      doubleClickZoom: true,
      touchZoom: false,
      boxZoom: true,
      keyboard: true,
      zoomControl: true
    }).setView([46.603354, 1.888334], 6)
    
    // Ajouter les tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map.value)
    
    setupKeyboardListeners()
    
    console.log('✅ Carte événements initialisée')
  } catch (error) {
    console.error('❌ Erreur initialisation carte:', error)
  } finally {
    loading.value = false
  }
}

const setupKeyboardListeners = () => {
  const handleKeyDown = (e) => {
    if (e.ctrlKey || e.metaKey) {
      if (map.value) {
        map.value.scrollWheelZoom.enable()
        map.value.touchZoom.enable()
      }
    }
  }

  const handleKeyUp = (e) => {
    if (!e.ctrlKey && !e.metaKey) {
      if (map.value) {
        map.value.scrollWheelZoom.disable()
        map.value.touchZoom.disable()
      }
    }
  }

  const handleWindowBlur = () => {
    if (map.value) {
      map.value.scrollWheelZoom.disable()
      map.value.touchZoom.disable()
    }
  }

  document.addEventListener('keydown', handleKeyDown)
  document.addEventListener('keyup', handleKeyUp)
  window.addEventListener('blur', handleWindowBlur)
}

const requestLocation = async () => {
  if (!navigator.geolocation) {
    console.warn('Géolocalisation non supportée')
    return
  }

  isLocating.value = true
  hasTriedLocation.value = true

  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000 // 5 minutes
      })
    })

    userLocation.value = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy
    }

    // Sauvegarder la position
    localStorage.setItem('user_location', JSON.stringify(userLocation.value))

    await updateMapWithUserLocation()
    emit('location-changed', userLocation.value)

    console.log('✅ Position utilisateur obtenue:', userLocation.value)
  } catch (error) {
    console.error('❌ Erreur géolocalisation:', error)
    hasTriedLocation.value = true
  } finally {
    isLocating.value = false
  }
}

const updateMapWithUserLocation = async () => {
  if (!map.value || !userLocation.value) return

  // Centrer la carte sur l'utilisateur
  const userLatLng = [userLocation.value.latitude, userLocation.value.longitude]
  map.value.setView(userLatLng, 11)

  // Ajouter ou mettre à jour le marqueur utilisateur
  if (userMarker.value) {
    map.value.removeLayer(userMarker.value)
  }

  const userIcon = L.divIcon({
    html: `
      <div class="user-marker">
        <div class="user-marker-dot"></div>
        <div class="user-marker-ring"></div>
      </div>
    `,
    className: 'custom-user-marker',
    iconSize: [20, 20],
    iconAnchor: [10, 10]
  })

  userMarker.value = L.marker(userLatLng, { icon: userIcon })
    .addTo(map.value)
    .bindTooltip('Votre position', { 
      direction: 'top',
      offset: [0, -10],
      className: 'user-tooltip'
    })

  // Ajouter cercle de rayon de recherche
  if (map.value.searchCircle) {
    map.value.removeLayer(map.value.searchCircle)
  }

  map.value.searchCircle = L.circle(userLatLng, {
    radius: searchRadius.value * 1000, // Convertir km en mètres
    fillColor: '#26a69a',
    fillOpacity: 0.1,
    color: '#26a69a',
    weight: 2,
    opacity: 0.6
  }).addTo(map.value)

  // Mettre à jour les événements
  updateEventMarkers()
}

const updateNearbyEvents = () => {
  if (!userLocation.value) return

  // Mettre à jour le cercle de recherche
  if (map.value.searchCircle) {
    map.value.searchCircle.setRadius(searchRadius.value * 1000)
  }

  // Recompter les événements à proximité
  updateEventMarkers()
}

const updateEventMarkers = () => {
  if (!map.value) return

  // Supprimer les anciens marqueurs d'événements
  eventMarkers.value.forEach(marker => {
    map.value.removeLayer(marker)
  })
  eventMarkers.value = []

  let nearbyCount = 0

  // Ajouter les nouveaux marqueurs
  props.events.forEach(event => {
    if (!event.address?.latitude || !event.address?.longitude) return

    const eventLatLng = [event.address.latitude, event.address.longitude]
    
    // Vérifier si l'événement est dans le rayon si l'utilisateur est localisé
    let isNearby = true
    if (userLocation.value) {
      const distance = calculateDistance(
        userLocation.value.latitude,
        userLocation.value.longitude,
        event.address.latitude,
        event.address.longitude
      )
      isNearby = distance <= searchRadius.value
      if (isNearby) nearbyCount++
    }

    // Créer l'icône personnalisée selon le type d'événement
    const customIcon = L.divIcon({
      html: getEventIconHtml(event, isNearby),
      className: 'custom-event-marker',
      iconSize: [35, 35],
      iconAnchor: [17, 35],
      popupAnchor: [0, -35]
    })

    // Créer le marqueur
    const marker = L.marker(eventLatLng, { icon: customIcon })
      .addTo(map.value)

    // Popup avec infos de l'événement
    const popupContent = createEventPopup(event)
    marker.bindPopup(popupContent, {
      maxWidth: 300,
      className: 'event-popup-container'
    })

    // Tooltip au hover
    const tooltipContent = `
      <div class="event-tooltip">
        <strong>${event.title}</strong><br>
        <span class="tooltip-location">${event.address.city}</span><br>
        <span class="tooltip-date">${new Date(event.start_date).toLocaleDateString()}</span>
        ${userLocation.value ? `<br><span class="tooltip-distance">${calculateDistance(
          userLocation.value.latitude,
          userLocation.value.longitude,
          event.address.latitude,
          event.address.longitude
        ).toFixed(1)}km</span>` : ''}
      </div>
    `

    marker.bindTooltip(tooltipContent, {
      direction: 'top',
      offset: [0, -40],
      className: 'event-tooltip-container'
    })

    // Click handler
    marker.on('click', () => {
      emit('event-selected', event)
    })

    eventMarkers.value.push(marker)
  })

  nearbyEventsCount.value = nearbyCount
}

const getEventIconHtml = (event, isNearby = true) => {
  const typeColors = {
    'TOURNOI': '#e74c3c',
    'AVANT_PREMIERE': '#9b59b6',
    'RENCONTRE': '#3498db',
    'GENERIQUE': '#2ecc71'
  }

  const iconColor = typeColors[event.event_type] || '#95a5a6'
  const opacity = isNearby ? 1 : 0.4
  const statusDot = event.status === 'APPROVED' ? '<div class="event-status-dot approved"></div>' : ''

  return `
    <div class="event-marker" style="background-color: ${iconColor}; opacity: ${opacity}">
      <i class="pi ${getEventTypeIcon(event.event_type)}"></i>
      ${statusDot}
    </div>
  `
}

const getEventTypeIcon = (eventType) => {
  const icons = {
    'TOURNOI': 'pi-trophy',
    'AVANT_PREMIERE': 'pi-star',
    'RENCONTRE': 'pi-users',
    'GENERIQUE': 'pi-calendar'
  }
  return icons[eventType] || 'pi-calendar'
}

const createEventPopup = (event) => {
  const startDate = new Date(event.start_date)
  const distance = userLocation.value ? calculateDistance(
    userLocation.value.latitude,
    userLocation.value.longitude,
    event.address.latitude,
    event.address.longitude
  ) : null

  return `
    <div class="event-popup-detail">
      <div class="popup-header">
        <h3>${event.title}</h3>
        <div class="event-type-badge ${event.event_type.toLowerCase()}">${getEventTypeLabel(event.event_type)}</div>
      </div>
      
      <div class="popup-content">
        <div class="popup-location">
          <i class="pi pi-map-marker"></i>
          <span><strong>${event.address.city}</strong> (${event.address.postal_code})</span>
          ${distance ? `<span class="distance">${distance.toFixed(1)}km</span>` : ''}
        </div>
        
        <div class="popup-date">
          <i class="pi pi-calendar"></i>
          <span>${startDate.toLocaleDateString()} à ${startDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
        </div>
        
        <div class="popup-participants">
          <i class="pi pi-users"></i>
          <span>${event.current_participants}/${event.max_participants || '∞'} participants</span>
        </div>
        
        <div class="popup-actions">
          <button class="popup-btn primary" onclick="window.dispatchEvent(new CustomEvent('event-selected', { detail: ${event.id} }))">
            <i class="pi pi-eye"></i>
            Voir détails
          </button>
          <button class="popup-btn secondary" onclick="window.open('https://maps.google.com/search/${encodeURIComponent(event.address.full_address)}', '_blank')">
            <i class="pi pi-directions"></i>
            Itinéraire
          </button>
        </div>
      </div>
    </div>
  `
}

const getEventTypeLabel = (type) => {
  const labels = {
    'TOURNOI': 'Tournoi',
    'AVANT_PREMIERE': 'Avant-première',
    'RENCONTRE': 'Rencontre',
    'GENERIQUE': 'Événement'
  }
  return labels[type] || 'Événement'
}

const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371 // Rayon de la Terre en km
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
    Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
  return R * c
}

// Event listener pour les popups
onMounted(() => {
  window.addEventListener('event-selected', (e) => {
    emit('event-selected', { id: e.detail })
  })
})
</script>

<style scoped>
.event-map-container {
  background: white;
  border-radius: var(--border-radius-large);
  box-shadow: var(--shadow-small);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.map-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, var(--primary-light), var(--primary));
  color: white;
}

.map-title h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.map-title p {
  margin: 0;
  font-size: 0.875rem;
  opacity: 0.9;
}

.no-location {
  font-style: italic;
  opacity: 0.8;
}

.map-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.locate-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: var(--border-radius);
  color: white;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.locate-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
}

.locate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.radius-control {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 120px;
}

.radius-control label {
  font-size: 0.75rem;
  font-weight: 500;
  opacity: 0.9;
}

.radius-slider {
  appearance: none;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
  cursor: pointer;
}

.radius-slider::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  background: white;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.radius-slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: white;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.map-wrapper {
  position: relative;
  height: 300px;
}

.map-container {
  width: 100%;
  height: 100%;
  position: relative;
  z-index: 1;
}

.location-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.location-prompt {
  text-align: center;
  padding: 2rem;
  max-width: 400px;
}

.location-icon {
  font-size: 3rem;
  color: var(--primary);
  margin-bottom: 1rem;
}

.location-prompt h4 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.location-prompt p {
  font-size: 0.95rem;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 2rem 0;
}

.locate-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.875rem 1.5rem;
  background: var(--primary);
  border: none;
  border-radius: var(--border-radius);
  color: white;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition-fast);
  margin: 0 auto;
}

.locate-action-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
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

/* Marqueurs personnalisés */
:deep(.custom-event-marker) {
  background: none !important;
  border: none !important;
}

:deep(.event-marker) {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  border: 3px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: bold;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

:deep(.event-marker:hover) {
  transform: scale(1.15);
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}

:deep(.event-status-dot) {
  position: absolute;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  top: -2px;
  right: -2px;
  border: 2px solid white;
}

:deep(.event-status-dot.approved) {
  background: #4caf50;
}

:deep(.custom-user-marker) {
  background: none !important;
  border: none !important;
}

:deep(.user-marker) {
  position: relative;
}

:deep(.user-marker-dot) {
  width: 12px;
  height: 12px;
  background: var(--primary);
  border: 3px solid white;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

:deep(.user-marker-ring) {
  width: 20px;
  height: 20px;
  border: 2px solid var(--primary);
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0.3;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.3;
  }
  70% {
    transform: translate(-50%, -50%) scale(1.8);
    opacity: 0;
  }
  100% {
    transform: translate(-50%, -50%) scale(2);
    opacity: 0;
  }
}

/* Tooltips */
:deep(.event-tooltip-container),
:deep(.user-tooltip) {
  background: rgba(0, 0, 0, 0.8) !important;
  border: none !important;
  border-radius: 8px !important;
  color: white !important;
  font-size: 13px !important;
  padding: 8px 12px !important;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
}

:deep(.event-tooltip-container::before),
:deep(.user-tooltip::before) {
  border-top-color: rgba(0, 0, 0, 0.8) !important;
}

:deep(.event-tooltip) {
  text-align: center;
  line-height: 1.4;
}

:deep(.tooltip-location) {
  color: #ccc;
  font-size: 12px;
}

:deep(.tooltip-date) {
  color: #80cbc4;
  font-size: 12px;
}

:deep(.tooltip-distance) {
  color: #ffd700;
  font-size: 12px;
  font-weight: 600;
}

/* Popups */
:deep(.event-popup-container) {
  max-width: 300px !important;
}

:deep(.event-popup-container .leaflet-popup-content-wrapper) {
  border-radius: 12px !important;
  padding: 0 !important;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2) !important;
}

:deep(.event-popup-container .leaflet-popup-content) {
  margin: 0 !important;
  padding: 0 !important;
}

:deep(.event-popup-detail) {
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

:deep(.event-type-badge) {
  font-size: 10px;
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

:deep(.event-type-badge.tournoi) {
  background: rgba(231, 76, 60, 0.2);
  color: #e74c3c;
  border: 1px solid rgba(231, 76, 60, 0.3);
}

:deep(.event-type-badge.avant_premiere) {
  background: rgba(155, 89, 182, 0.2);
  color: #9b59b6;
  border: 1px solid rgba(155, 89, 182, 0.3);
}

:deep(.event-type-badge.rencontre) {
  background: rgba(52, 152, 219, 0.2);
  color: #3498db;
  border: 1px solid rgba(52, 152, 219, 0.3);
}

:deep(.event-type-badge.generique) {
  background: rgba(46, 204, 113, 0.2);
  color: #2ecc71;
  border: 1px solid rgba(46, 204, 113, 0.3);
}

:deep(.popup-content) {
  padding: 16px;
}

:deep(.popup-location),
:deep(.popup-date),
:deep(.popup-participants) {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 14px;
}

:deep(.popup-location i),
:deep(.popup-date i),
:deep(.popup-participants i) {
  color: var(--primary);
  min-width: 16px;
}

:deep(.distance) {
  background: var(--accent);
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 600;
  margin-left: 8px;
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
  
  .map-controls {
    justify-content: center;
  }
  
  .radius-control {
    min-width: 100px;
  }
  
  .map-wrapper {
    height: 250px;
  }
  
  .location-prompt {
    padding: 1.5rem;
  }
  
  .location-prompt h4 {
    font-size: 1.1rem;
  }
  
  .location-prompt p {
    font-size: 0.9rem;
  }
}

@media (max-width: 640px) {
  .map-header {
    padding: 1rem;
  }
  
  .map-title h3 {
    font-size: 1rem;
  }
  
  .map-title p {
    font-size: 0.8rem;
  }
  
  .locate-btn,
  .locate-action-btn {
    font-size: 0.85rem;
    padding: 0.75rem 1.25rem;
  }
  
  .radius-control label {
    font-size: 0.7rem;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .map-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .map-controls {
    justify-content: center;
  }
  
  .radius-control {
    min-width: 100px;
  }
  
  .map-wrapper {
    height: 250px;
  }
  
  .location-prompt {
    padding: 1.5rem;
  }
  
  .location-prompt h4 {
    font-size: 1.1rem;
  }
  
  .location-prompt p {
    font-size: 0.9rem;
  }
}

@media (max-width: 640px) {
  .map-header {
    padding: 1rem;
  }
  
  .map-title h3 {
    font-size: 1rem;
  }
  
  .map-title p {
    font-size: 0.8rem;
  }
  
  .locate-btn,
  .locate-action-btn {
    font-size: 0.85rem;
    padding: 0.75rem 1.25rem;
  }
  
  .radius-control label {
    font-size: 0.7rem;
  }
}
</style>