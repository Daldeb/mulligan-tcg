<template>
  <Card class="magic-deck-card gaming-card hover-lift" :class="`magic-${formatBadgeClass}`">
    <template #content>
      <div class="magic-deck-content">
        
        <!-- Header du deck Magic -->
        <div class="deck-header-magic">
          <div class="deck-title-section">
            <h3 class="deck-name">{{ deck.title }}</h3>
            <div class="deck-status">
              <i :class="deck.isPublic ? 'pi pi-globe' : 'pi pi-lock'" 
                 :style="{ color: deck.isPublic ? 'var(--primary)' : 'var(--text-secondary)' }"
                 :title="deck.isPublic ? 'Public' : 'Privé'">
              </i>
            </div>
          </div>
          
          <div class="deck-meta-line">
            <span class="color-identity">{{ getColorIdentityDisplay(deck.colorIdentity) }}</span>
            <span class="separator">•</span>
            <span class="format-badge" :class="formatBadgeClass">
              {{ deck.format.name }}
            </span>
            <span class="separator">•</span>
            <span class="cards-count" :class="{ 'complete': isComplete }">
              {{ deck.totalCards }}/{{ getMaxCards(deck.format.slug) }} cartes
            </span>
            <span v-if="isComplete" class="complete-badge">COMPLET</span>
          </div>
          
          <div class="deck-stats-line">
            <span class="cmc-avg">
              <i class="pi pi-circle-fill mana-icon"></i>
              CMC moy: {{ deck.averageCost?.toFixed(1) || '0.0' }}
            </span>
            <span class="separator">•</span>
            <span class="archetype" v-if="deck.archetype">
              {{ deck.archetype }}
            </span>
            <span v-else class="no-archetype">Aucun archetype</span>
          </div>
        </div>

        <!-- Zone cartes expandable -->
        <div class="cards-section" v-if="isExpanded && deck.cards && deck.cards.length > 0">
          <div class="cards-header">
            <h4 class="cards-title">
              <i class="pi pi-list"></i>
              Liste des cartes ({{ deck.cards.length }})
            </h4>
          </div>
          
          <!-- Sections par type Magic -->
          <div class="magic-cards-list">
            <div 
              v-for="section in deckSections" 
              :key="section.type"
              class="magic-card-section"
              v-show="section.cards.length > 0"
            >
              <div class="section-header">
                <span class="section-name">
                  <i :class="section.icon"></i>
                  {{ section.name }} ({{ section.cards.length }})
                </span>
              </div>
              
              <div class="section-cards">
                <div 
                  v-for="cardEntry in section.cards" 
                  :key="cardEntry.card.id"
                  class="magic-card-entry"
                >
                  <div class="card-cmc-badge">{{ cardEntry.card.cmc || 0 }}</div>
                  <div class="card-info-magic">
                    <span class="card-name" :class="{ 'legendary': cardEntry.card.isLegendary }">
                      {{ cardEntry.card.name }}
                    </span>
                    <span class="card-type-magic">{{ cardEntry.card.typeLine }}</span>
                  </div>
                  <div class="card-quantity" v-if="deck.format.slug !== 'commander'">
                    x{{ cardEntry.quantity }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-if="!deck.cards || deck.cards.length === 0" class="empty-cards">
            <i class="pi pi-info-circle"></i>
            <span>Aucune carte ajoutée</span>
          </div>
        </div>

        <!-- Actions du deck Magic -->
        <div class="magic-deck-actions">
          <Button 
            :label="isExpanded ? 'Masquer' : 'Voir les cartes'"
            :icon="isExpanded ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
            class="toggle-cards-btn"
            size="small"
            outlined
            @click="toggleExpand"
          />
          
          <div class="action-buttons">
            <Button 
              icon="pi pi-copy"
              class="action-btn copy-btn"
              size="small"
              outlined
              @click="copyDeckcode"
              v-tooltip="'Copier le deckcode'"
            />
            <Button 
              icon="pi pi-pencil"
              class="action-btn edit-btn"
              size="small"
              @click="editDeck"
              v-tooltip="'Éditer le deck'"
            />
            <Button 
              icon="pi pi-trash"
              class="action-btn delete-btn"
              size="small"
              severity="danger"
              outlined
              @click="deleteDeck"
              v-tooltip="'Supprimer le deck'"
            />
          </div>
        </div>

      </div>
    </template>
  </Card>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'

// Props
const props = defineProps({
  deck: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['edit', 'delete', 'copyDeckcode'])

// Composables
const toast = useToast()

// State
const isExpanded = ref(false)

// Computed
const isComplete = computed(() => {
  const maxCards = getMaxCards(props.deck.format.slug)
  return props.deck.totalCards === maxCards
})

const formatBadgeClass = computed(() => {
  return props.deck.format.slug.toLowerCase()
})

// Organisation des cartes par sections Magic
const deckSections = computed(() => {
  if (!props.deck.cards || props.deck.cards.length === 0) return []
  
  const sections = [
    { 
      type: 'planeswalker', 
      name: 'Planeswalkers', 
      icon: 'pi pi-star',
      cards: getCardsByType('planeswalker') 
    },
    { 
      type: 'creature', 
      name: 'Créatures', 
      icon: 'pi pi-users',
      cards: getCardsByType('creature') 
    },
    { 
      type: 'instant', 
      name: 'Instantanés', 
      icon: 'pi pi-bolt',
      cards: getCardsByType('instant') 
    },
    { 
      type: 'sorcery', 
      name: 'Rituels', 
      icon: 'pi pi-book',
      cards: getCardsByType('sorcery') 
    },
    { 
      type: 'enchantment', 
      name: 'Enchantements', 
      icon: 'pi pi-sparkles',
      cards: getCardsByType('enchantment') 
    },
    { 
      type: 'artifact', 
      name: 'Artefacts', 
      icon: 'pi pi-cog',
      cards: getCardsByType('artifact') 
    },
    { 
      type: 'land', 
      name: 'Terrains', 
      icon: 'pi pi-map',
      cards: getCardsByType('land') 
    }
  ]
  
  // Filtrer les sections vides et trier par CMC
  return sections.map(section => ({
    ...section,
    cards: section.cards.sort((a, b) => {
      const cmcDiff = (a.card.cmc || 0) - (b.card.cmc || 0)
      if (cmcDiff !== 0) return cmcDiff
      return a.card.name.localeCompare(b.card.name)
    })
  })).filter(section => section.cards.length > 0)
})

// Méthodes utilitaires
const getMaxCards = (formatSlug) => {
  return formatSlug === 'commander' ? 100 : 60
}

const getColorIdentityDisplay = (colorIdentity) => {
  if (!colorIdentity || colorIdentity.length === 0) {
    return 'Incolore'
  }
  return colorIdentity.join('')
}

const getCardsByType = (type) => {
  if (!props.deck.cards) return []
  
  return props.deck.cards.filter(entry => 
    entry.card.cardType === type
  )
}

// Actions
const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
}

const editDeck = () => {
  emit('edit', props.deck)
}

const deleteDeck = () => {
  if (confirm(`Supprimer le deck "${props.deck.title}" ?`)) {
    emit('delete', props.deck)
  }
}

const copyDeckcode = () => {
  emit('copyDeckcode', props.deck)
  toast.add({
    severity: 'info',
    summary: 'Deckcode copié',
    detail: 'Fonctionnalité bientôt disponible pour Magic...',
    life: 2000
  })
}
</script>

<style scoped>
/* === MAGIC COMPACT DECK - THÈME TERRE/BRUN === */

.magic-deck-card {
  background: linear-gradient(135deg, #2d1b0e 0%, #4a2c17 100%);
  border: 2px solid #6b4423;
  border-radius: 12px;
  transition: all var(--transition-medium);
  overflow: hidden;
  position: relative;
}

.magic-deck-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #d97706, #fbbf24, #d97706);
  opacity: 0;
  transition: opacity var(--transition-fast);
}

.magic-deck-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(139, 69, 19, 0.3);
  border-color: #d97706;
}

.magic-deck-card:hover::before {
  opacity: 1;
}

/* Badges format Magic */
.magic-deck-card.magic-standard {
  border-left: 4px solid #3b82f6;
}

.magic-deck-card.magic-commander {
  border-left: 4px solid #dc2626;
}

.magic-deck-card.magic-modern {
  border-left: 4px solid #059669;
}

.magic-deck-card.magic-legacy {
  border-left: 4px solid #7c3aed;
}

/* Contenu principal */
.magic-deck-content {
  padding: 1.5rem;
  color: #f7fafc;
}

/* Header du deck Magic */
.deck-header-magic {
  margin-bottom: 1rem;
}

.deck-title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.deck-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f7fafc;
  margin: 0;
  line-height: 1.2;
  flex: 1;
  margin-right: 1rem;
}

.deck-status i {
  font-size: 1.1rem;
}

/* Métadonnées du deck */
.deck-meta-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.color-identity {
  font-weight: 700;
  font-family: 'Courier New', monospace;
  color: #fbbf24;
  font-size: 1rem;
  text-shadow: 0 0 3px rgba(251, 191, 36, 0.5);
}

.format-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.format-badge.standard {
  background: #3b82f6;
  color: white;
}

.format-badge.commander {
  background: #dc2626;
  color: white;
}

.format-badge.modern {
  background: #059669;
  color: white;
}

.format-badge.legacy {
  background: #7c3aed;
  color: white;
}

.cards-count {
  color: #cbd5e0;
  font-weight: 600;
}

.cards-count.complete {
  color: #10b981;
}

.complete-badge {
  background: #10b981;
  color: white;
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.separator {
  color: #6b7280;
}

/* Stats du deck */
.deck-stats-line {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #a0aec0;
  flex-wrap: wrap;
}

.cmc-avg {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 600;
}

.mana-icon {
  color: #d97706;
  font-size: 0.7rem;
}

.archetype {
  color: #fbbf24;
  font-weight: 600;
  font-style: italic;
}

.no-archetype {
  color: #6b7280;
  font-style: italic;
}

/* Section cartes expandable */
.cards-section {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
  border: 1px solid #6b4423;
}

.cards-header {
  margin-bottom: 0.75rem;
}

.cards-title {
  color: #f7fafc;
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cards-title i {
  color: #d97706;
}

/* Sections Magic par type */
.magic-cards-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.magic-card-section {
  background: rgba(75, 44, 23, 0.3);
  border-radius: 6px;
  padding: 0.75rem;
  border: 1px solid #6b4423;
}

.section-header {
  margin-bottom: 0.5rem;
}

.section-name {
  color: #fbbf24;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-cards {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.magic-card-entry {
  display: flex;
  align-items: center;
  padding: 0.375rem 0.5rem;
  background: rgba(75, 44, 23, 0.4);
  border-radius: 4px;
  border: 1px solid #6b4423;
  transition: all var(--transition-fast);
}

.magic-card-entry:hover {
  background: rgba(75, 44, 23, 0.6);
  border-color: #d97706;
}

.card-cmc-badge {
  width: 18px;
  height: 18px;
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.7rem;
  margin-right: 0.5rem;
  flex-shrink: 0;
  border: 1px solid #b45309;
}

.card-info-magic {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.card-name {
  color: #f7fafc;
  font-weight: 500;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-name.legendary {
  color: #fbbf24;
  font-weight: 600;
  text-shadow: 0 0 2px rgba(251, 191, 36, 0.5);
}

.card-type-magic {
  color: #a0aec0;
  font-size: 0.7rem;
  font-style: italic;
}

.card-quantity {
  color: #d97706;
  font-weight: 700;
  font-size: 0.8rem;
  margin-left: 0.5rem;
  flex-shrink: 0;
}

.empty-cards {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.empty-cards i {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  opacity: 0.7;
}

/* Actions du deck Magic */
.magic-deck-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #6b4423;
}

:deep(.toggle-cards-btn) {
  border-color: #6b4423 !important;
  color: #cbd5e0 !important;
}

:deep(.toggle-cards-btn:hover) {
  border-color: #d97706 !important;
  color: #fbbf24 !important;
  background: rgba(217, 119, 6, 0.1) !important;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

:deep(.action-btn) {
  width: 32px !important;
  height: 32px !important;
  padding: 0 !important;
  border-radius: 6px !important;
}

:deep(.copy-btn) {
  border-color: #6b4423 !important;
  color: #cbd5e0 !important;
}

:deep(.copy-btn:hover) {
  border-color: #d97706 !important;
  color: #fbbf24 !important;
  background: rgba(217, 119, 6, 0.1) !important;
}

:deep(.edit-btn) {
  background: #d97706 !important;
  border-color: #d97706 !important;
  color: white !important;
}

:deep(.edit-btn:hover) {
  background: #b45309 !important;
  border-color: #b45309 !important;
}

:deep(.delete-btn) {
  border-color: #dc2626 !important;
  color: #fca5a5 !important;
}

:deep(.delete-btn:hover) {
  border-color: #b91c1c !important;
  color: #fecaca !important;
  background: rgba(220, 38, 38, 0.1) !important;
}

/* Responsive */
@media (max-width: 768px) {
  .magic-deck-content {
    padding: 1rem;
  }
  
  .deck-meta-line,
  .deck-stats-line {
    font-size: 0.8rem;
  }
  
  .magic-deck-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: center;
  }
}

/* Animation d'expansion */
.cards-section {
  animation: expandIn 0.3s ease-out;
}

@keyframes expandIn {
  from {
    opacity: 0;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
  }
  to {
    opacity: 1;
    max-height: 500px;
    padding-top: 1rem;
    padding-bottom: 1rem;
  }
}

/* Effet de brillance sur hover */
.magic-deck-card::after {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(217, 119, 6, 0.1), transparent);
  transform: rotate(45deg);
  transition: all 0.6s ease;
  opacity: 0;
  pointer-events: none;
}

.magic-deck-card:hover::after {
  animation: shine 0.6s ease-in-out;
}

@keyframes shine {
  0% {
    transform: translateX(-100%) translateY(-100%) rotate(45deg);
    opacity: 0;
  }
  50% {
    opacity: 1;
  }
  100% {
    transform: translateX(100%) translateY(100%) rotate(45deg);
    opacity: 0;
  }
}
</style>