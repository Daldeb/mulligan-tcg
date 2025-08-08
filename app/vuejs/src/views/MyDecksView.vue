<template>
  <div class="my-decks-page">
    <div class="container">
      
      <!-- Header avec actions -->
      <div class="page-header slide-in-down">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="pi pi-user"></i>
              Mes Decks
            </h1>
            <p class="page-subtitle">
              Gérez et organisez vos decks personnels
            </p>
          </div>
          <div class="header-actions">
            <Button 
              label="Créer un deck"
              icon="pi pi-plus"
              class="emerald-button primary"
              @click="showCreateModal = true"
            />
          </div>
        </div>
      </div>

      <!-- Filtres et recherche globaux -->
      <div class="deck-filters slide-in-up">
        <Card class="gaming-card filters-card">
          <template #content>
            <div class="filters-content">
              <div class="search-wrapper">
                <InputText 
                  v-model="searchQuery"
                  placeholder="Rechercher dans mes decks..."
                  class="search-input"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              <div class="filter-buttons">
                <Button 
                  :label="visibilityFilter === 'all' ? 'Tous' : visibilityFilter === 'public' ? 'Publics' : 'Privés'"
                  icon="pi pi-filter"
                  class="filter-btn"
                  @click="toggleVisibilityFilter"
                />
                <Button 
                  :label="sortBy === 'recent' ? 'Récents' : sortBy === 'likes' ? 'Populaires' : 'Alphabétique'"
                  icon="pi pi-sort-alt"
                  class="sort-btn"
                  @click="toggleSort"
                />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sections par jeu -->
      <div class="games-sections" v-if="!isLoading && userDecks.length > 0">
        
        <!-- Section Hearthstone avec filtres avancés -->
        <div v-if="getGameDecks('hearthstone').length > 0" class="game-section hearthstone-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge hearthstone">
                <i class="game-icon">🃏</i>
                <span class="game-name">Hearthstone</span>
              </div>
              <div class="game-stats-integrated">
                <div class="stat-item likes">
                  <i class="pi pi-heart"></i>
                  <span class="stat-value">{{ getGameStats('hearthstone').totalLikes }}</span>
                </div>
                <div class="stat-item public">
                  <i class="pi pi-globe"></i>
                  <span class="stat-value">{{ getGameStats('hearthstone').publicCount }}</span>
                </div>
                <div class="stat-item private">
                  <i class="pi pi-lock"></i>
                  <span class="stat-value">{{ getGameStats('hearthstone').privateCount }}</span>
                </div>
                <div class="stat-item total">
                  <span class="stat-label">{{ getGameDecks('hearthstone').length }} decks</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Filtres Hearthstone avancés -->
            <div class="hearthstone-filters-panel">
              
              <!-- Barre de recherche spécifique -->
              <div class="filter-search-wrapper">
                <InputText 
                  v-model="hearthstoneFilters.search"
                  placeholder="Rechercher un deck Hearthstone..."
                  class="filter-search-input hearthstone-search"
                />
                <i class="pi pi-search search-icon"></i>
              </div>
              
              <!-- Première ligne : Filtres principaux -->
              <div class="filters-main-row">
                
                <!-- Slider coût poussière (simplifié) -->
                <div class="dust-cost-filter-group">
                  <label class="filter-group-label">
                    Coût en poussière : 
                    <span class="dust-range-display">
                      {{ hearthstoneFilters.dustCost.min.toLocaleString() }} - 
                      {{ hearthstoneFilters.dustCost.max >= 10000 ? '10000+' : hearthstoneFilters.dustCost.max.toLocaleString() }}
                    </span>
                  </label>
                  <div class="range-slider-wrapper">
                    <!-- Slider min -->
                    <input 
                      type="range" 
                      :min="0" 
                      :max="10000" 
                      :step="200"
                      v-model="hearthstoneFilters.dustCost.min"
                      class="range-slider range-min"
                      @input="handleDustRangeChange"
                    />
                    <!-- Slider max -->
                    <input 
                      type="range" 
                      :min="0" 
                      :max="10000" 
                      :step="200"
                      v-model="hearthstoneFilters.dustCost.max"
                      class="range-slider range-max"
                      @input="handleDustRangeChange"
                    />
                    <!-- Track visuel -->
                    <div class="range-track">
                      <div class="range-track-fill" :style="dustRangeStyle"></div>
                    </div>
                  </div>
                </div>
                
                <!-- Toggle Standard/Wild -->
                <div class="format-filter-group">
                  <label class="filter-group-label">Format :</label>
                  <div class="format-toggle-container">
                    <div class="format-toggle-buttons">
                      <button 
                        class="format-toggle-btn"
                        :class="{ 'active': hearthstoneFilters.format === 'all' }"
                        @click="hearthstoneFilters.format = 'all'"
                      >
                        <i class="pi pi-globe"></i>
                        <span>Tous</span>
                      </button>
                      <button 
                        class="format-toggle-btn standard"
                        :class="{ 'active': hearthstoneFilters.format === 'standard' }"
                        @click="hearthstoneFilters.format = 'standard'"
                      >
                        <i class="pi pi-star"></i>
                        <span>Standard</span>
                      </button>
                      <button 
                        class="format-toggle-btn wild"
                        :class="{ 'active': hearthstoneFilters.format === 'wild' }"
                        @click="hearthstoneFilters.format = 'wild'"
                      >
                        <i class="pi pi-sun"></i>
                        <span>Wild</span>
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Bouton reset et tri -->
                <div class="filters-actions-group">
                  <Dropdown
                    v-model="hearthstoneFilters.sortBy"
                    :options="sortOptions"
                    option-label="label"
                    option-value="value"
                    class="filter-sort-dropdown"
                  />
                  <Button
                    icon="pi pi-filter-slash"
                    class="reset-filters-btn"
                    @click="resetHearthstoneFilters"
                    v-tooltip="'Réinitialiser les filtres'"
                    text
                    size="small"
                  />
                </div>
                
              </div>
              
              <!-- Deuxième ligne : Classes en ligne (sans label) -->
              <div class="classes-inline-row">
                <div 
                  v-for="hsClass in hearthstoneClassesFilter" 
                  :key="hsClass.value"
                  class="class-checkbox-inline"
                  :class="{ 'selected': hearthstoneFilters.selectedClasses.includes(hsClass.value) }"
                  @click="toggleHearthstoneClass(hsClass.value)"
                >
                  <img 
                    :src="hsClass.icon" 
                    :alt="hsClass.name"
                    class="class-checkbox-icon-inline"
                  />
                  <span class="class-checkbox-name-inline">{{ hsClass.name }}</span>
                  <div class="class-checkbox-indicator-inline" v-if="hearthstoneFilters.selectedClasses.includes(hsClass.value)">
                    <i class="pi pi-check"></i>
                  </div>
                </div>
              </div>
              
            </div>
          
          <div class="decks-grid">
            <HearthstoneCompactDeck 
              v-for="deck in filteredHearthstoneDecks" 
              :key="`my-hs-${deck.id}`"
              :deck="deck"
              context="my-decks"
              :current-user="authStore.user"
              @edit="editDeck"
              @delete="deleteDeck"
              @copyDeckcode="copyDeckcode"
            />
          </div>
        </div>

        <!-- Section Magic avec filtres avancés -->
        <div v-if="getGameDecks('magic').length > 0" class="game-section magic-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge magic">
                <i class="game-icon">🎴</i>
                <span class="game-name">Magic: The Gathering</span>
              </div>
              <div class="game-stats-integrated">
                <div class="stat-item likes">
                  <i class="pi pi-heart"></i>
                  <span class="stat-value">{{ getGameStats('magic').totalLikes }}</span>
                </div>
                <div class="stat-item public">
                  <i class="pi pi-globe"></i>
                  <span class="stat-value">{{ getGameStats('magic').publicCount }}</span>
                </div>
                <div class="stat-item private">
                  <i class="pi pi-lock"></i>
                  <span class="stat-value">{{ getGameStats('magic').privateCount }}</span>
                </div>
                <div class="stat-item total">
                  <span class="stat-label">{{ getGameDecks('magic').length }} decks</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Filtres Magic avancés -->
          <div class="magic-filters-panel">
            
            <!-- Barre de recherche spécifique -->
            <div class="filter-search-wrapper">
              <InputText 
                v-model="magicFilters.search"
                placeholder="Rechercher un deck Magic..."
                class="filter-search-input magic-search"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <!-- Ligne principale des filtres -->
            <div class="filters-main-row">
              
              <!-- Checkboxes Couleurs Magic -->
              <div class="magic-colors-filter">
                <label class="filter-group-label">Couleurs :</label>
                <div class="magic-colors-grid">
                  <div 
                    v-for="color in magicColors" 
                    :key="color.value"
                    class="magic-color-checkbox"
                    :class="{ 'selected': magicFilters.selectedColors.includes(color.value) }"
                    :style="{ 
                      backgroundColor: magicFilters.selectedColors.includes(color.value) ? color.color : 'transparent',
                      color: magicFilters.selectedColors.includes(color.value) ? color.textColor : '#6b7280',
                      borderColor: color.color
                    }"
                    @click="toggleMagicColor(color.value)"
                  >
                    <i class="pi pi-check" v-if="magicFilters.selectedColors.includes(color.value)"></i>
                    <span>{{ color.label }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Dropdown formats Magic -->
              <div class="format-filter-group">
                <label class="filter-group-label">Format :</label>
                <Dropdown
                  v-model="magicFilters.format"
                  :options="magicFormats"
                  option-label="label"
                  option-value="value"
                  placeholder="Tous les formats"
                  class="filter-dropdown magic-dropdown"
                />
              </div>
              
              <!-- Bouton reset et tri -->
              <div class="filters-actions-group">
                <Dropdown
                  v-model="magicFilters.sortBy"
                  :options="sortOptions"
                  option-label="label"
                  option-value="value"
                  class="filter-sort-dropdown"
                />
                <Button
                  icon="pi pi-filter-slash"
                  class="reset-filters-btn"
                  @click="resetMagicFilters"
                  v-tooltip="'Réinitialiser les filtres'"
                  text
                  size="small"
                />
              </div>
              
            </div>
          </div>
          
          <div class="decks-grid">
            <MagicCompactDeck 
              v-for="deck in filteredMagicDecks" 
              :key="`my-magic-${deck.id}`"
              :deck="deck"
              context="my-decks"
              :current-user="authStore.user"
              @edit="editDeck"
              @delete="deleteDeck"
              @copyDeckcode="copyDeckcode"
            />
          </div>
        </div>

        <!-- Section Pokemon avec filtres simples -->
        <div v-if="getGameDecks('pokemon').length > 0" class="game-section pokemon-section slide-in-up">
          <div class="game-header">
            <div class="game-title-area">
              <div class="game-badge pokemon">
                <i class="game-icon">⚡</i>
                <span class="game-name">Pokemon TCG</span>
              </div>
              <div class="game-stats-integrated">
                <div class="stat-item likes">
                  <i class="pi pi-heart"></i>
                  <span class="stat-value">{{ getGameStats('pokemon').totalLikes }}</span>
                </div>
                <div class="stat-item public">
                  <i class="pi pi-globe"></i>
                  <span class="stat-value">{{ getGameStats('pokemon').publicCount }}</span>
                </div>
                <div class="stat-item private">
                  <i class="pi pi-lock"></i>
                  <span class="stat-value">{{ getGameStats('pokemon').privateCount }}</span>
                </div>
                <div class="stat-item total">
                  <span class="stat-label">{{ getGameDecks('pokemon').length }} decks</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Filtres Pokemon simples -->
          <div class="pokemon-filters-panel">
            
            <!-- Barre de recherche spécifique -->
            <div class="filter-search-wrapper">
              <InputText 
                v-model="pokemonFilters.search"
                placeholder="Rechercher un deck Pokemon..."
                class="filter-search-input pokemon-search"
              />
              <i class="pi pi-search search-icon"></i>
            </div>
            
            <!-- Ligne de filtres -->
            <div class="filters-main-row">
              <div class="filters-actions-group">
                <Dropdown
                  v-model="pokemonFilters.sortBy"
                  :options="sortOptions"
                  option-label="label"
                  option-value="value"
                  class="filter-sort-dropdown"
                />
                <Button
                  icon="pi pi-filter-slash"
                  class="reset-filters-btn"
                  @click="resetPokemonFilters"
                  v-tooltip="'Réinitialiser les filtres'"
                  text
                  size="small"
                />
              </div>
            </div>
          </div>
          
          <div class="decks-grid">
            <Card 
              v-for="deck in filteredPokemonDecks" 
              :key="`my-pkmn-${deck.id}`"
              class="deck-card gaming-card hover-lift"
            >
              <template #content>
                <div class="deck-content">
                  <div class="deck-header-info">
                    <h3 class="deck-name">{{ deck.name }}</h3>
                    <div class="deck-status">
                      <i :class="deck.isPublic ? 'pi pi-globe' : 'pi pi-lock'" 
                        :style="{ color: deck.isPublic ? 'var(--primary)' : 'var(--text-secondary)' }"
                        :title="deck.isPublic ? 'Public' : 'Privé'"></i>
                    </div>
                  </div>
                  <div class="deck-meta">
                    <span class="format-badge pokemon">{{ deck.format }}</span>
                  </div>
                  <div class="deck-stats-info">
                    <span class="likes">{{ deck.likes || 0 }} ❤️</span>
                    <span class="views">{{ deck.views || 0 }} 👁️</span>
                    <span class="cards">{{ deck.cardCount || 0 }}/60 cartes</span>
                  </div>
                  <div class="deck-actions">
                    <Button 
                      icon="pi pi-pencil"
                      class="edit-btn"
                      @click="editDeck(deck)"
                      v-tooltip="'Éditer'"
                      size="small"
                    />
                    <Button 
                      icon="pi pi-copy"
                      class="copy-btn"
                      @click="duplicateDeck(deck)"
                      v-tooltip="'Dupliquer'"
                      size="small"
                    />
                    <Button 
                      icon="pi pi-trash"
                      class="delete-btn"
                      @click="deleteDeck(deck)"
                      v-tooltip="'Supprimer'"
                      size="small"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

      </div>

      <!-- État de chargement -->
      <div v-if="isLoading" class="loading-state">
        <Card class="gaming-card loading-card">
          <template #content>
            <div class="loading-content">
              <div class="emerald-spinner"></div>
              <p>Chargement de vos decks...</p>
            </div>
          </template>
        </Card>
      </div>

      <!-- État vide -->
      <div v-if="!isLoading && userDecks.length === 0" class="empty-state">
        <Card class="gaming-card empty-card">
          <template #content>
            <div class="empty-content">
              <i class="pi pi-clone empty-icon"></i>
              <h3 class="empty-title">Aucun deck créé</h3>
              <p class="empty-description">
                Commencez à créer vos premiers decks pour les voir apparaître ici !
              </p>
              <Button 
                label="Créer mon premier deck"
                icon="pi pi-plus"
                class="emerald-button primary"
                @click="showCreateModal = true"
              />
            </div>
          </template>
        </Card>
      </div>

    </div>

    <!-- Modale de création de deck -->
    <Dialog 
      v-model:visible="showCreateModal"
      modal 
      :closable="true"
      :style="{ width: '100%', maxWidth: '540px' }"
      :breakpoints="{ '960px': '85vw', '640px': '95vw' }"
      class="emerald-modal"
    >
      <template #header>
        <div class="modal-header-content">
          <i class="pi pi-plus header-icon"></i>
          <span class="header-title">Créer un nouveau deck</span>
        </div>
      </template>

      <div class="modal-body">
        <form @submit.prevent="createDeck" class="emerald-form">
          
          <!-- Titre du deck -->
          <div class="field-group">
            <label for="deck-title" class="field-label">Titre du deck *</label>
            <InputText
              id="deck-title"
              v-model="deckData.title"
              placeholder="Ex: Deck Aggro Chasseur"
              class="emerald-input"
              :class="{ 'error': errors.title }"
              @input="errors.title = ''"
            />
            <small v-if="errors.title" class="field-error">{{ errors.title }}</small>
          </div>

          <!-- Sélection du jeu -->
          <div class="field-group">
            <label for="deck-game" class="field-label">Jeu *</label>
            <Dropdown
              id="deck-game"
              v-model="deckData.selectedGame"
              :options="availableGames"
              option-label="name"
              option-value="id"
              placeholder="Sélectionner un jeu"
              class="emerald-dropdown"
              :class="{ 'error': errors.game }"
              @change="onGameChange"
            >
              <template #option="{ option }">
                <div class="game-option">
                  <div 
                    class="game-color-badge"
                    :style="{ backgroundColor: option.primaryColor }"
                  ></div>
                  <span class="game-name">{{ option.name }}</span>
                </div>
              </template>
              <template #value="{ value }">
                <div v-if="value" class="selected-game">
                  <div 
                    class="game-color-badge"
                    :style="{ backgroundColor: getSelectedGameColor(value) }"
                  ></div>
                  <span>{{ getSelectedGameName(value) }}</span>
                </div>
                <span v-else class="placeholder-text">Sélectionner un jeu</span>
              </template>
            </Dropdown>
            <small v-if="errors.game" class="field-error">{{ errors.game }}</small>
          </div>

          <!-- Sélection du format -->
          <div class="field-group">
            <label for="deck-format" class="field-label">Format *</label>
            <Dropdown
              id="deck-format"
              v-model="deckData.selectedFormat"
              :options="availableFormats"
              option-label="name"
              option-value="id"
              placeholder="Sélectionner un format"
              class="emerald-dropdown"
              :class="{ 'error': errors.format }"
              :disabled="!deckData.selectedGame"
            >
              <template #option="{ option }">
                <div class="format-option">
                  <div class="format-main">
                    <span class="format-name">{{ option.name }}</span>
                  </div>
                  <div v-if="option.description" class="format-description">
                    {{ option.description }}
                  </div>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.format" class="field-error">{{ errors.format }}</small>
            <small v-else-if="!deckData.selectedGame" class="field-hint">
              Sélectionnez d'abord un jeu pour voir les formats disponibles
            </small>
          </div>

          <!-- Description optionnelle -->
          <div class="field-group">
            <label for="deck-description" class="field-label">Description (optionnelle)</label>
            <Textarea
              id="deck-description"
              v-model="deckData.description"
              placeholder="Décrivez votre stratégie, les combos clés..."
              rows="3"
              class="emerald-textarea"
            />
          </div>

          <div class="field-group" v-if="deckData.selectedGame && getSelectedGameSlug(deckData.selectedGame) === 'hearthstone'">
            <label for="deck-class" class="field-label">Classe Hearthstone *</label>
            <Dropdown
              id="deck-class"
              v-model="deckData.hearthstoneClass"
              :options="hearthstoneClasses"
              option-label="name"
              option-value="value"
              placeholder="Sélectionner une classe"
              class="emerald-dropdown"
              :class="{ 'error': errors.hearthstoneClass }"
            >
              <template #option="{ option }">
                <div class="class-option">
                  <span class="class-name">{{ option.name }}</span>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.hearthstoneClass" class="field-error">{{ errors.hearthstoneClass }}</small>
          </div>

        <div class="field-group" v-if="deckData.selectedGame">
            <label for="deck-archetype" class="field-label">Archetype (optionnel)</label>
            <Dropdown
              id="deck-archetype"
              v-model="deckData.archetype"
              :options="getArchetypesForGame()"
              placeholder="Ex: Aggro, Control, Combo..."
              class="emerald-dropdown"
              :disabled="!deckData.selectedGame"
              editable
            />
            <small v-if="!deckData.selectedGame" class="field-hint">
              Les archetypes dépendent du jeu sélectionné
            </small>
          </div>

        </form>

        <!-- Actions de la modale -->
        <div class="modal-actions">
          <Button
            label="Annuler"
            icon="pi pi-times"
            class="emerald-outline-btn cancel"
            @click="showCreateModal = false"
          />
          <Button
            label="Créer le deck"
            icon="pi pi-check"
            class="emerald-button primary"
            @click="createDeck"
            :loading="isCreating"
            :disabled="!isFormValid"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '../services/api'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown' 
import Textarea from 'primevue/textarea'
import HearthstoneCompactDeck from '../components/decks/HearthstoneCompactDeck.vue'
import MagicCompactDeck from '../components/decks/MagicCompactDeck.vue'

// Stores et composables
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

// State principal
const userDecks = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const visibilityFilter = ref('all') // all, public, private
const sortBy = ref('recent') // recent, likes, name

// State modale
const showCreateModal = ref(false)
const isCreating = ref(false)
const availableGames = ref([])
const availableFormats = ref([])

const deckData = ref({
  title: '',
  description: '',
  selectedGame: null,
  selectedFormat: null,
  hearthstoneClass: null,
  archetype: ''
})

const errors = ref({
  title: '',
  game: '',
  format: '',
  hearthstoneClass: ''
})

// State pour les filtres Hearthstone
const hearthstoneFilters = ref({
  search: '',
  selectedClasses: [],
  dustCost: {
    min: 0,
    max: 10000
  },
  format: 'all', // 'all', 'standard', 'wild'
  sortBy: 'recent'
})

// State pour les filtres Magic
const magicFilters = ref({
  search: '',
  selectedColors: [],
  format: 'all',
  sortBy: 'recent'
})

// State pour les filtres Pokemon
const pokemonFilters = ref({
  search: '',
  sortBy: 'recent'
})

// Classes Hearthstone pour l'affichage
const hearthstoneClasses = ref([
  { name: 'Mage', value: 'mage' },
  { name: 'Chasseur', value: 'hunter' },
  { name: 'Paladin', value: 'paladin' },
  { name: 'Guerrier', value: 'warrior' },
  { name: 'Prêtre', value: 'priest' },
  { name: 'Démoniste', value: 'warlock' },
  { name: 'Chaman', value: 'shaman' },
  { name: 'Voleur', value: 'rogue' },
  { name: 'Druide', value: 'druid' },
  { name: 'Chasseur de démons', value: 'demonhunter' },
  { name: 'Chevalier de la mort', value: 'deathknight' }
])

// Classes Hearthstone avec icônes (même mapping que HearthstoneCompactDeck)
const hearthstoneClassesFilter = ref([
  { 
    name: 'Mage', 
    value: 'mage',
    icon: '/src/assets/images/icons/Alt-Heroes_Mage_Jaina.png.avif'
  },
  { 
    name: 'Chasseur', 
    value: 'hunter',
    icon: '/src/assets/images/icons/Alt-Heroes_Hunter_Rexxar.png.avif'
  },
  { 
    name: 'Paladin', 
    value: 'paladin',
    icon: '/src/assets/images/icons/Alt-Heroes_Paladin_Uther.png.avif'
  },
  { 
    name: 'Guerrier', 
    value: 'warrior',
    icon: '/src/assets/images/icons/Alt-Heroes_Warrior_Garrosh.png.avif'
  },
  { 
    name: 'Prêtre', 
    value: 'priest',
    icon: '/src/assets/images/icons/Alt-Heroes_Priest_Anduin.png.avif'
  },
  { 
    name: 'Démoniste', 
    value: 'warlock',
    icon: '/src/assets/images/icons/Alt-Heroes_Warlock_Guldan.png.avif'
  },
  { 
    name: 'Chaman', 
    value: 'shaman',
    icon: '/src/assets/images/icons/Alt-Heroes_Shaman_Thrall.png.avif'
  },
  { 
    name: 'Voleur', 
    value: 'rogue',
    icon: '/src/assets/images/icons/Alt-Heroes_Rogue_Valeera.png.avif'
  },
  { 
    name: 'Druide', 
    value: 'druid',
    icon: '/src/assets/images/icons/Alt-Heroes_Druid_Malfurion.png.avif'
  },
  { 
    name: 'Chasseur de démons', 
    value: 'demonhunter',
    icon: '/src/assets/images/icons/Alt-Heroes_Demon-Hunter_Illidan.png.avif'
  },
  { 
    name: 'Chevalier de la mort', 
    value: 'deathknight',
    icon: '/src/assets/images/icons/hearthstone-lich-king.webp'
  }
])

// Couleurs Magic
const magicColors = [
  { label: 'Blanc', value: 'W', color: '#FFFBD5', textColor: '#8B4513' },
  { label: 'Bleu', value: 'U', color: '#0E68AB', textColor: '#FFFFFF' },
  { label: 'Noir', value: 'B', color: '#150B00', textColor: '#FFFFFF' },
  { label: 'Rouge', value: 'R', color: '#D3202A', textColor: '#FFFFFF' },
  { label: 'Vert', value: 'G', color: '#00733E', textColor: '#FFFFFF' },
  { label: 'Incolore', value: '', color: '#CCCCCC', textColor: '#333333' }
]

// Formats Magic
const magicFormats = [
  { label: 'Tous les formats', value: 'all' },
  { label: 'Standard', value: 'standard' },
  { label: 'Commander', value: 'commander' },
  { label: 'Modern', value: 'modern' },
  { label: 'Legacy', value: 'legacy' }
]

const archetypes = {
  hearthstone: ['Aggro', 'Midrange', 'Control', 'Combo', 'Tempo', 'Big', 'Zoo', 'Burn', 'Mill'],
  pokemon: ['Aggro', 'Control', 'Combo', 'Toolbox', 'Stall', 'Beatdown', 'Engine', 'Disruption'],
  magic: ['Aggro', 'Control', 'Midrange', 'Combo', 'Ramp', 'Tribal', 'Voltron', 'Stax', 'Storm']
}

// Options de tri
const sortOptions = [
  { label: 'Plus récents', value: 'recent' },
  { label: 'Plus populaires', value: 'likes' },
  { label: 'Alphabétique', value: 'name' },
  { label: 'Coût croissant', value: 'dust-asc' },
  { label: 'Coût décroissant', value: 'dust-desc' }
]

// Computed
const filteredDecks = computed(() => {
  if (!Array.isArray(userDecks.value)) {
    console.warn('userDecks.value n\'est pas un tableau:', userDecks.value)
    return []
  }
  
  let decks = [...userDecks.value]
  
  // Filtre par recherche
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    decks = decks.filter(deck => 
      deck.name && deck.name.toLowerCase().includes(query) ||
      deck.description && deck.description.toLowerCase().includes(query)
    )
  }
  
  // Filtre par visibilité
  if (visibilityFilter.value !== 'all') {
    decks = decks.filter(deck => 
      visibilityFilter.value === 'public' ? deck.isPublic : !deck.isPublic
    )
  }
  
  // Tri
  switch (sortBy.value) {
    case 'likes':
      return decks.sort((a, b) => (b.likes || 0) - (a.likes || 0))
    case 'name':
      return decks.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
    case 'recent':
    default:
      return decks.sort((a, b) => new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0))
  }
})

const publicDecksCount = computed(() => 
  Array.isArray(userDecks.value) ? userDecks.value.filter(deck => deck.isPublic).length : 0
)

const totalLikes = computed(() => 
  Array.isArray(userDecks.value) ? userDecks.value.reduce((sum, deck) => sum + (deck.likes || 0), 0) : 0
)

const averageViews = computed(() => {
  if (!Array.isArray(userDecks.value) || userDecks.value.length === 0) return 0
  const totalViews = userDecks.value.reduce((sum, deck) => sum + (deck.views || 0), 0)
  return Math.round(totalViews / userDecks.value.length)
})

const isFormValid = computed(() => {
  const baseValid = deckData.value.title.trim().length >= 3 &&
                    deckData.value.selectedGame &&
                    deckData.value.selectedFormat
  
  // Si Hearthstone, classe requise
  if (deckData.value.selectedGame && getSelectedGameSlug(deckData.value.selectedGame) === 'hearthstone') {
    return baseValid && deckData.value.hearthstoneClass
  }
  
  return baseValid
})

// Computed pour le style du slider Hearthstone
const dustRangeStyle = computed(() => {
  const min = hearthstoneFilters.value.dustCost.min
  const max = hearthstoneFilters.value.dustCost.max
  const minPercent = (min / 10000) * 100
  const maxPercent = (max / 10000) * 100
  
  return {
    left: `${minPercent}%`,
    width: `${maxPercent - minPercent}%`
  }
})

// Computed pour les decks filtrés par jeu
const filteredHearthstoneDecks = computed(() => {
  let decks = getGameDecks('hearthstone')
  
  // Filtre par recherche
  if (hearthstoneFilters.value.search.trim()) {
    const query = hearthstoneFilters.value.search.toLowerCase()
    decks = decks.filter(deck => 
      deck.title?.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.archetype?.toLowerCase().includes(query)
    )
  }
  
  // Filtre par classes
  if (hearthstoneFilters.value.selectedClasses.length > 0) {
    decks = decks.filter(deck => 
      hearthstoneFilters.value.selectedClasses.includes(deck.hearthstoneClass)
    )
  }
  
  // Filtre par format
  if (hearthstoneFilters.value.format !== 'all') {
    decks = decks.filter(deck => 
      deck.format?.slug === hearthstoneFilters.value.format
    )
  }
  
  // Filtre par coût poussière
  decks = decks.filter(deck => {
    const dustCost = calculateDeckDustCost(deck)
    return dustCost >= hearthstoneFilters.value.dustCost.min && 
           dustCost <= hearthstoneFilters.value.dustCost.max
  })
  
  // Tri
  return sortHearthstoneDecks(decks, hearthstoneFilters.value.sortBy)
})

const filteredMagicDecks = computed(() => {
  let decks = getGameDecks('magic')
  
  // Filtre par recherche
  if (magicFilters.value.search.trim()) {
    const query = magicFilters.value.search.toLowerCase()
    decks = decks.filter(deck => 
      deck.title?.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query) ||
      deck.archetype?.toLowerCase().includes(query)
    )
  }
  
  // Filtre par couleurs
  if (magicFilters.value.selectedColors.length > 0) {
    decks = decks.filter(deck => {
      const deckColors = deck.colorIdentity || []
      return magicFilters.value.selectedColors.some(selectedColor => 
        selectedColor === '' ? deckColors.length === 0 : deckColors.includes(selectedColor)
      )
    })
  }
  
  // Filtre par format
  if (magicFilters.value.format !== 'all') {
    decks = decks.filter(deck => 
      deck.format?.slug === magicFilters.value.format
    )
  }
  
  // Tri
  return sortDecks(decks, magicFilters.value.sortBy)
})

const filteredPokemonDecks = computed(() => {
  let decks = getGameDecks('pokemon')
  
  // Filtre par recherche
  if (pokemonFilters.value.search.trim()) {
    const query = pokemonFilters.value.search.toLowerCase()
    decks = decks.filter(deck => 
      deck.title?.toLowerCase().includes(query) ||
      deck.description?.toLowerCase().includes(query)
    )
  }
  
  // Tri
  return sortDecks(decks, pokemonFilters.value.sortBy)
})

const getGameStats = (gameSlug) => {
  const gameDecks = getGameDecks(gameSlug)
  
  if (gameDecks.length === 0) {
    return {
      totalLikes: 0,
      publicCount: 0,
      privateCount: 0,
      totalDecks: 0
    }
  }
  
  const totalLikes = gameDecks.reduce((sum, deck) => sum + (deck.likesCount || 0), 0)
  const publicCount = gameDecks.filter(deck => deck.isPublic).length
  const privateCount = gameDecks.filter(deck => !deck.isPublic).length
  
  return {
    totalLikes,
    publicCount,
    privateCount,
    totalDecks: gameDecks.length
  }
}

// Méthodes
const loadUserDecks = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/api/decks/my-decks')
    
    console.log('🔍 Réponse API complète:', response.data)
    
    // Gérer les différents formats de réponse
    if (response.data.success && Array.isArray(response.data.data)) {
      userDecks.value = response.data.data
      console.log('✅ Decks chargés depuis response.data.data:', userDecks.value.length)
    } else if (Array.isArray(response.data)) {
      userDecks.value = response.data
      console.log('✅ Decks chargés depuis response.data:', userDecks.value.length)
    } else {
      console.warn('❌ Format réponse inattendu:', response.data)
      userDecks.value = []
    }
    
  } catch (error) {
    console.error('💥 Erreur chargement decks:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger vos decks',
      life: 3000
    })
    userDecks.value = []
  } finally {
    isLoading.value = false
  }
}

const loadGames = async () => {
  try {
    const response = await api.get('/api/games')
    if (response.data.success) {
      availableGames.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement jeux:', error)
  }
}

const loadFormatsForGame = async (gameId) => {
  if (!gameId) {
    availableFormats.value = []
    return
  }
  
  try {
    const response = await api.get(`/api/games/${gameId}/formats`)
    if (response.data.success) {
      availableFormats.value = response.data.data
    }
  } catch (error) {
    console.error('Erreur chargement formats:', error)
    availableFormats.value = []
  }
}

const getGameDecks = (game) => {
  return filteredDecks.value.filter(deck => {
    // Gérer les deux formats : deck.game.slug ou deck.game directement
    const gameSlug = typeof deck.game === 'object' ? deck.game.slug : deck.game
    return gameSlug === game
  })
}

const filteredDecksByGame = (gameSlug) => {
  return filteredDecks.value.filter(deck => {
    // Gérer les deux formats : deck.game.slug ou deck.game directement
    const deckGameSlug = typeof deck.game === 'object' ? deck.game.slug : deck.game
    return deckGameSlug === gameSlug
  })
}

const getClassDisplayName = (classValue) => {
  const classObj = hearthstoneClasses.value.find(c => c.value === classValue)
  return classObj ? classObj.name : classValue
}

const editDeck = (deck) => {
  router.push(`/edition/${deck.game.slug}/${deck.format.slug}/${deck.slug}`)
}

const duplicateDeck = async (deck) => {
  try {
    const response = await api.post(`/api/decks/${deck.id}/duplicate`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Deck dupliqué',
        detail: `"${deck.name}" a été dupliqué`,
        life: 3000
      })
      await loadUserDecks() // Recharger la liste
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de dupliquer le deck',
      life: 3000
    })
  }
}

const deleteDeck = async (deck) => {
  if (!confirm(`Supprimer définitivement "${deck.name}" ?`)) return
  
  try {
    await api.delete(`/api/decks/${deck.id}`)
    userDecks.value = userDecks.value.filter(d => d.id !== deck.id)
    toast.add({
      severity: 'success',
      summary: 'Deck supprimé',
      detail: `"${deck.name}" a été supprimé`,
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de supprimer le deck',
      life: 3000
    })
  }
}

const toggleVisibilityFilter = () => {
  const filters = ['all', 'public', 'private']
  const currentIndex = filters.indexOf(visibilityFilter.value)
  visibilityFilter.value = filters[(currentIndex + 1) % filters.length]
}

const toggleSort = () => {
  const sorts = ['recent', 'likes', 'name']
  const currentIndex = sorts.indexOf(sortBy.value)
  sortBy.value = sorts[(currentIndex + 1) % sorts.length]
}

// Méthodes filtres Hearthstone
const toggleHearthstoneClass = (classValue) => {
  const classes = hearthstoneFilters.value.selectedClasses
  const index = classes.indexOf(classValue)
  
  if (index > -1) {
    classes.splice(index, 1)
  } else {
    classes.push(classValue)
  }
}

const handleDustRangeChange = () => {
  // S'assurer que min <= max
  if (hearthstoneFilters.value.dustCost.min > hearthstoneFilters.value.dustCost.max) {
    hearthstoneFilters.value.dustCost.min = hearthstoneFilters.value.dustCost.max
  }
}

const calculateDeckDustCost = (deck) => {
  if (!deck.cards || deck.cards.length === 0) return 0
  
  const dustCosts = {
    'common': 40,
    'rare': 100,
    'epic': 400,
    'legendary': 1600
  }
  
  return deck.cards.reduce((sum, cardEntry) => {
    const rarity = cardEntry.card.rarity?.toLowerCase() || 'common'
    const cardCost = dustCosts[rarity] || 40
    return sum + (cardCost * cardEntry.quantity)
  }, 0)
}

const sortHearthstoneDecks = (decks, sortBy) => {
  switch (sortBy) {
    case 'likes':
      return [...decks].sort((a, b) => (b.likesCount || 0) - (a.likesCount || 0))
    case 'name':
      return [...decks].sort((a, b) => a.title.localeCompare(b.title))
    case 'dust-asc':
      return [...decks].sort((a, b) => calculateDeckDustCost(a) - calculateDeckDustCost(b))
    case 'dust-desc':
      return [...decks].sort((a, b) => calculateDeckDustCost(b) - calculateDeckDustCost(a))
    case 'recent':
    default:
      return [...decks].sort((a, b) => new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0))
  }
}

const resetHearthstoneFilters = () => {
  hearthstoneFilters.value = {
    search: '',
    selectedClasses: [],
    dustCost: {
      min: 0,
      max: 10000
    },
    format: 'all',
    sortBy: 'recent'
  }
}

// Méthodes filtres Magic
const toggleMagicColor = (colorValue) => {
  const colors = magicFilters.value.selectedColors
  const index = colors.indexOf(colorValue)
  
  if (index > -1) {
    colors.splice(index, 1)
  } else {
    colors.push(colorValue)
  }
}

const resetMagicFilters = () => {
  magicFilters.value = {
    search: '',
    selectedColors: [],
    format: 'all',
    sortBy: 'recent'
  }
}

// Méthodes filtres Pokemon
const resetPokemonFilters = () => {
  pokemonFilters.value = {
    search: '',
    sortBy: 'recent'
  }
}

// Méthode de tri générique
const sortDecks = (decks, sortBy) => {
  switch (sortBy) {
    case 'likes':
      return [...decks].sort((a, b) => (b.likesCount || 0) - (a.likesCount || 0))
    case 'name':
      return [...decks].sort((a, b) => a.title.localeCompare(b.title))
    case 'recent':
    default:
      return [...decks].sort((a, b) => new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0))
  }
}

// Méthodes modale
const getSelectedGameName = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.name : ''
}

const getSelectedGameColor = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.primaryColor : '#26a69a'
}

const getSelectedGameSlug = (gameId) => {
  const game = availableGames.value.find(g => g.id === gameId)
  return game ? game.slug : ''
}

const getArchetypesForGame = () => {
  if (!deckData.value.selectedGame) return []
  const game = availableGames.value.find(g => g.id === deckData.value.selectedGame)
  return game ? archetypes[game.slug] || [] : []
}

const onGameChange = () => {
  deckData.value.selectedFormat = null
  deckData.value.hearthstoneClass = null 
  deckData.value.archetype = ''
  errors.value.game = ''
  errors.value.hearthstoneClass = ''
  
  if (deckData.value.selectedGame) {
    loadFormatsForGame(deckData.value.selectedGame)
  }
}

const validateForm = () => {
  errors.value = { title: '', game: '', format: '', hearthstoneClass: '' }
  let isValid = true

  if (!deckData.value.title.trim()) {
    errors.value.title = 'Le titre est requis'
    isValid = false
  } else if (deckData.value.title.trim().length < 3) {
    errors.value.title = 'Le titre doit faire au moins 3 caractères'
    isValid = false
  }

  if (!deckData.value.selectedGame) {
    errors.value.game = 'Veuillez sélectionner un jeu'
    isValid = false
  }

  if (!deckData.value.selectedFormat) {
    errors.value.format = 'Veuillez sélectionner un format'
    isValid = false
  }

  if (deckData.value.selectedGame && getSelectedGameSlug(deckData.value.selectedGame) === 'hearthstone') {
    if (!deckData.value.hearthstoneClass) {
      errors.value.hearthstoneClass = 'Veuillez sélectionner une classe'
      isValid = false
    }
  }

  return isValid
}

const copyDeckcode = (deck) => {
  toast.add({
    severity: 'info',
    summary: 'Deckcode',
    detail: 'Fonctionnalité bientôt disponible...',
    life: 2000
  })
}

const createDeck = async () => {
  if (!validateForm()) return

  isCreating.value = true

  try {
    const deckPayload = {
      title: deckData.value.title.trim(),
      gameId: deckData.value.selectedGame,
      formatId: deckData.value.selectedFormat,
      description: deckData.value.description?.trim() || null,
      hearthstoneClass: deckData.value.hearthstoneClass,
      archetype: deckData.value.archetype?.trim() || null
    }

    const response = await api.post('/api/decks', deckPayload)

    if (response.data.success) {
      const deckInfo = response.data.data
      
      showCreateModal.value = false
      
      // Reset du formulaire
      deckData.value = {
        title: '',
        description: '',
        selectedGame: null,
        selectedFormat: null,
        hearthstoneClass: null,
        archetype: ''
      }
      
      toast.add({
        severity: 'success',
        summary: 'Deck créé !',
        detail: `"${deckInfo.title}" est prêt à être édité`,
        life: 3000
      })

      // Redirection vers l'éditeur
      const editUrl = `/edition/${deckInfo.gameSlug}/${deckInfo.formatSlug}/${deckInfo.slug}`
      await router.push(editUrl)

    } else {
      toast.add({
        severity: 'error',
        summary: 'Erreur de création',
        detail: response.data.message || 'Erreur lors de la création',
        life: 4000
      })
    }

  } catch (error) {
    console.error('💥 Erreur création deck:', error)
    
    let errorMessage = 'Erreur lors de la création du deck'
    
    if (error.response?.status === 400) {
      errorMessage = error.response.data?.message || 'Données invalides'
    } else if (error.response?.status === 401) {
      errorMessage = 'Vous devez être connecté pour créer un deck'
    } else if (error.response?.status === 403) {
      errorMessage = 'Vous n\'avez pas les permissions nécessaires'
    } else if (error.response?.status >= 500) {
      errorMessage = 'Erreur serveur, veuillez réessayer'
    }

    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: errorMessage,
      life: 4000
    })
  } finally {
    isCreating.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await loadUserDecks()
  await loadGames()
})
</script>

<style scoped>
/* === MY DECKS PAGE EMERALD GAMING === */

.my-decks-page {
  min-height: calc(100vh - 140px);
  background: var(--surface-gradient);
  padding: 2rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Page header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: var(--primary);
}

.page-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0;
}

.header-actions {
  flex-shrink: 0;
}

/* Deck filters */
.deck-filters {
  margin-bottom: 2rem;
}

.filters-card {
  border: none;
  box-shadow: var(--shadow-small);
}

.filters-content {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.search-wrapper {
  position: relative;
  flex: 1;
  max-width: 400px;
}

:deep(.search-input) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: 25px !important;
  font-size: 1rem !important;
  background: var(--surface-100) !important;
}

:deep(.search-input:focus) {
  border-color: var(--primary) !important;
  background: white !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  font-size: 1rem;
  pointer-events: none;
}

.filter-buttons {
  display: flex;
  gap: 1rem;
}

:deep(.filter-btn),
:deep(.sort-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  padding: 0.75rem 1.25rem !important;
  border-radius: 20px !important;
  font-size: 0.9rem !important;
  font-weight: 500 !important;
  transition: all var(--transition-fast) !important;
}

:deep(.filter-btn:hover),
:deep(.sort-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

/* Game sections */
.games-sections {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.game-section {
  background: white;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--surface-200);
  box-shadow: var(--shadow-small);
  overflow: hidden;
  position: relative;
}

.game-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.hearthstone-section::before {
  background: linear-gradient(90deg, #d97706, #f59e0b, #b45309);
}

.magic-section::before {
  background: linear-gradient(90deg, #7c3aed, #8b5cf6, #a855f7);
}

.pokemon-section::before {
  background: linear-gradient(90deg, #ffc107, #ff6f00);
}

/* === STATS INTÉGRÉES DANS LES HEADERS === */
.game-header {
  padding: 1.5rem 2rem;
  background: var(--surface-50);
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.game-title-area {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 2rem;
}

.game-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  border-radius: 25px;
  font-weight: 600;
  font-size: 1.1rem;
}

.game-badge.hearthstone {
  background: rgba(217, 119, 6, 0.1);
  color: #d97706;
  border: 2px solid rgba(217, 119, 6, 0.3);
}

.game-badge.magic {
  background: rgba(124, 58, 237, 0.1);
  color: #7c3aed;
  border: 2px solid rgba(124, 58, 237, 0.3);
}

.game-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
  border: 2px solid rgba(255, 193, 7, 0.3);
}

.game-icon {
  font-size: 1.5rem;
}

.game-stats-integrated {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: white;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--surface-200);
  transition: all var(--transition-fast);
}

.stat-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stat-item.likes {
  color: #e11d48;
  border-color: rgba(225, 29, 72, 0.2);
}

.stat-item.likes:hover {
  background: rgba(225, 29, 72, 0.05);
  border-color: #e11d48;
}

.stat-item.likes i {
  color: #e11d48;
  font-size: 0.9rem;
}

.stat-item.public {
  color: var(--primary);
  border-color: rgba(38, 166, 154, 0.2);
}

.stat-item.public:hover {
  background: rgba(38, 166, 154, 0.05);
  border-color: var(--primary);
}

.stat-item.public i {
  color: var(--primary);
  font-size: 0.9rem;
}

.stat-item.private {
  color: #6b7280;
  border-color: rgba(107, 114, 128, 0.2);
}

.stat-item.private:hover {
  background: rgba(107, 114, 128, 0.05);
  border-color: #6b7280;
}

.stat-item.private i {
  color: #6b7280;
  font-size: 0.9rem;
}

.stat-item.total {
  color: var(--text-primary);
  border-color: var(--surface-300);
  background: var(--surface-100);
  font-style: italic;
}

.stat-item.total:hover {
  background: var(--surface-200);
  border-color: var(--surface-400);
}

.stat-value {
  font-weight: 700;
  font-size: 0.9rem;
  min-width: 20px;
  text-align: center;
  color: var(--text-primary) !important; 
}

.stat-label {
  font-weight: 500;
  font-size: 0.85rem;
  white-space: nowrap;
}

/* === ADAPTATIONS THÉMATIQUES PAR JEU === */

/* Hearthstone - Orange/Feu */
.hearthstone-section .stat-item.likes {
  color: #ff5722;
  border-color: rgba(255, 87, 34, 0.2);
}

.hearthstone-section .stat-item.likes:hover {
  background: rgba(255, 87, 34, 0.05);
  border-color: #ff5722;
}

.hearthstone-section .stat-item.public {
  color: #d97706;
  border-color: rgba(217, 119, 6, 0.2);
}

.hearthstone-section .stat-item.public:hover {
  background: rgba(217, 119, 6, 0.05);
  border-color: #d97706;
}

/* Magic - Violet/Noir */
.magic-section .stat-item.likes {
  color: #c2410c;
  border-color: rgba(194, 65, 12, 0.2);
}

.magic-section .stat-item.likes:hover {
  background: rgba(194, 65, 12, 0.05);
  border-color: #c2410c;
}

.magic-section .stat-item.public {
  color: #7c3aed;
  border-color: rgba(124, 58, 237, 0.2);
}

.magic-section .stat-item.public:hover {
  background: rgba(124, 58, 237, 0.05);
  border-color: #7c3aed;
}

/* Pokemon - Jaune/Rouge */
.pokemon-section .stat-item.likes {
  color: #dc2626;
  border-color: rgba(220, 38, 38, 0.2);
}

.pokemon-section .stat-item.likes:hover {
  background: rgba(220, 38, 38, 0.05);
  border-color: #dc2626;
}

.pokemon-section .stat-item.public {
  color: #fbbf24;
  border-color: rgba(251, 191, 36, 0.2);
}

.pokemon-section .stat-item.public:hover {
  background: rgba(251, 191, 36, 0.05);
  border-color: #fbbf24;
}

/* Decks grid */
.decks-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
}

/* Deck cards */
.deck-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  height: 100%;
}

.deck-header-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.deck-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  flex: 1;
  line-height: 1.3;
}

.deck-status {
  flex-shrink: 0;
}

.deck-status i {
  font-size: 1.1rem;
}

.deck-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.format-badge {
  padding: 0.25rem 0.75rem;
  background: rgba(38, 166, 154, 0.1);
  color: var(--primary);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.format-badge.magic {
  background: rgba(139, 69, 19, 0.1);
  color: #8b4513;
}

.format-badge.pokemon {
  background: rgba(255, 193, 7, 0.1);
  color: #ff6f00;
}

.class-badge {
  padding: 0.25rem 0.75rem;
  background: var(--surface-200);
  color: var(--text-primary);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.deck-stats-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.deck-stats-info span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.deck-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

:deep(.deck-actions .p-button) {
  flex: 1 !important;
  padding: 0.5rem !important;
  border-radius: 6px !important;
  font-size: 0.85rem !important;
}

:deep(.edit-btn) {
  background: var(--primary) !important;
  border-color: var(--primary) !important;
  color: white !important;
}

:deep(.edit-btn:hover) {
  background: var(--primary-dark) !important;
  border-color: var(--primary-dark) !important;
}

:deep(.copy-btn) {
  background: white !important;
  border: 2px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
}

:deep(.copy-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}

:deep(.delete-btn) {
  background: white !important;
  border: 2px solid rgba(255, 87, 34, 0.3) !important;
  color: var(--accent) !important;
}

:deep(.delete-btn:hover) {
  background: var(--accent) !important;
  border-color: var(--accent) !important;
  color: white !important;
}

/* Loading and empty states */
.loading-state,
.empty-state {
  display: flex;
  justify-content: center;
  margin: 3rem 0;
}

.loading-card,
.empty-card {
  max-width: 600px;
  width: 100%;
}

.loading-content,
.empty-content {
  padding: 3rem 2rem;
  text-align: center;
}

.emerald-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 1rem;
  border: 3px solid var(--surface-200);
  border-top: 3px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  opacity: 0.7;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.empty-description {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

/* Modal styles */
:deep(.emerald-modal .p-dialog) {
  border-radius: var(--border-radius-large) !important;
  box-shadow: var(--shadow-large) !important;
  border: 1px solid var(--surface-200) !important;
  overflow: hidden !important;
}

:deep(.emerald-modal .p-dialog-header) {
  background: var(--emerald-gradient) !important;
  color: var(--text-inverse) !important;
  padding: 1.5rem 2rem !important;
  border-bottom: none !important;
}

:deep(.emerald-modal .p-dialog-content) {
  padding: 0 !important;
  background: var(--surface) !important;
}

.modal-header-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.header-icon {
  font-size: 1.5rem;
  opacity: 0.9;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.modal-body {
  padding: 2rem;
}

.emerald-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

:deep(.emerald-input) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-input:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-input.error) {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1) !important;
}

:deep(.emerald-textarea) {
  width: 100% !important;
  padding: 0.875rem 1rem !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  color: var(--text-primary) !important;
  font-size: 0.95rem !important;
  transition: all var(--transition-fast) !important;
  resize: vertical !important;
  min-height: 80px !important;
}

:deep(.emerald-textarea:focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

:deep(.emerald-dropdown) {
  width: 100% !important;
  border: 2px solid var(--surface-300) !important;
  border-radius: var(--border-radius) !important;
  background: var(--surface) !important;
  transition: all var(--transition-fast) !important;
}

:deep(.emerald-dropdown.p-focus) {
  border-color: var(--primary) !important;
  box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.1) !important;
  background: white !important;
}

.game-option,
.selected-game {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
}

.game-color-badge {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  flex-shrink: 0;
}

.format-option {
  padding: 0.5rem 0;
}

.format-main {
  font-weight: 500;
  color: var(--text-primary);
}

.format-description {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

.field-error {
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 500;
}

.field-hint {
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-style: italic;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 0 0 0;
  border-top: 1px solid var(--surface-200);
}

/* === RESPONSIVE STATS === */
@media (max-width: 1024px) {
  .container {
    padding: 0 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .filters-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .search-wrapper {
    max-width: none;
  }
  
  .decks-grid {
    padding: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  
  .game-stats-integrated {
    gap: 1rem;
  }
  
  .stat-item {
    padding: 0.4rem 0.6rem;
    font-size: 0.8rem;
  }
  
  .stat-value {
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .my-decks-page {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .game-header {
    padding: 1rem 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-title-area {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .game-stats-integrated {
    align-self: stretch;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  
  .stat-item {
    flex: 1;
    min-width: 60px;
    justify-content: center;
    padding: 0.5rem 0.25rem;
  }
  
  .decks-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .filter-buttons {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 640px) {
  .deck-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.deck-actions .p-button) {
    width: 100% !important;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .modal-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  :deep(.modal-actions .p-button) {
    width: 100% !important;
  }
  
  .game-stats-integrated {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
  }
  
  .stat-item {
    justify-content: center;
    text-align: center;
  }
}

/* Animations */
.slide-in-down {
  animation: slideInDown 0.6s ease-out;
}

.slide-in-up {
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hover-lift {
  transition: all var(--transition-fast);
}

.hover-lift:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-medium);
}

/* === FILTRES HEARTHSTONE AVANCÉS === */

.hearthstone-filters-panel {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.04), rgba(255, 152, 0, 0.02));
  border-left: 6px solid #d97706;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  animation: fadeInScale 0.3s ease-out;
}

/* Barre de recherche spécifique Hearthstone */
.filter-search-wrapper {
  position: relative;
  max-width: 400px;
  width: 100%;
}

:deep(.hearthstone-search) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid #d97706 !important;
  border-radius: 25px !important;
  background: white !important;
  font-size: 0.9rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.hearthstone-search:focus) {
  border-color: #b45309 !important;
  box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15) !important;
  outline: none !important;
}

/* Ligne principale des filtres */
.filters-main-row {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  flex-wrap: wrap;
}

.filter-group-label {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  color: #d97706;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.75rem;
}

/* === CHECKBOXES CLASSES AVEC IMAGES === */


.classes-inline-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(217, 119, 6, 0.2);
  margin-top: 0.75rem;
}

.class-checkbox-inline {
  position: relative;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid transparent;
  background: white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  min-width: 100px;
}

.class-checkbox-inline:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
  border-color: #d97706;
}

.class-checkbox-inline.selected {
  border-color: #d97706;
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.15), rgba(255, 152, 0, 0.08));
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(217, 119, 6, 0.25);
}

.class-checkbox-icon-inline {
  width: 32px;
  height: 32px;
  object-fit: contain;
  transition: all var(--transition-fast);
  filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.2));
  flex-shrink: 0;
}

.class-checkbox-inline:hover .class-checkbox-icon-inline {
  transform: scale(1.1);
}

.class-checkbox-name-inline {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.class-checkbox-indicator-inline {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  width: 16px;
  height: 16px;
  background: #d97706;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.6rem;
  animation: checkPop 0.3s ease-out;
  box-shadow: 0 2px 4px rgba(217, 119, 6, 0.4);
}

/* === SLIDER COÛT SIMPLIFIÉ (SANS INPUT FIELDS) === */
.dust-cost-filter-group {
  min-width: 280px;
  flex: 1;
}

.class-checkbox {
  position: relative;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid transparent;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.class-checkbox:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 6px 20px rgba(217, 119, 6, 0.2);
  border-color: #d97706;
}

.class-checkbox.selected {
  border-color: #d97706;
  background: linear-gradient(135deg, rgba(217, 119, 6, 0.1), rgba(255, 152, 0, 0.05));
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 8px 25px rgba(217, 119, 6, 0.3);
}

.class-checkbox.selected::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(45deg, #d97706, transparent, #f59e0b);
  border-radius: 14px;
  z-index: -1;
  opacity: 0.6;
  animation: classGlow 2s ease-in-out infinite alternate;
}

@keyframes classGlow {
  0% { opacity: 0.4; }
  100% { opacity: 0.8; }
}

.class-checkbox-content {
  padding: 0.75rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  position: relative;
}

.class-checkbox-icon {
  width: 48px;
  height: 48px;
  object-fit: contain;
  transition: all var(--transition-fast);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.class-checkbox:hover .class-checkbox-icon {
  transform: scale(1.1);
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}

.class-checkbox-name {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--text-primary);
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.2;
}

.class-checkbox-indicator {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 20px;
  height: 20px;
  background: #d97706;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.7rem;
  animation: checkPop 0.3s ease-out;
  box-shadow: 0 2px 6px rgba(217, 119, 6, 0.4);
}

@keyframes checkPop {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); opacity: 1; }
}

/* === SLIDER COÛT POUSSIÈRE === */
.dust-cost-filter-group {
  min-width: 250px;
  flex: 1;
}

.dust-range-display {
  font-weight: 600;
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.8rem;
}

.range-slider-wrapper {
  position: relative;
  height: 40px;
  display: flex;
  align-items: center;
}

.range-slider {
  position: absolute;
  width: 100%;
  height: 6px;
  background: transparent;
  outline: none;
  -webkit-appearance: none;
  cursor: pointer;
}

.range-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  background: #d97706;
  border-radius: 50%;
  cursor: pointer;
  border: 3px solid white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  transition: all var(--transition-fast);
}

.range-slider::-webkit-slider-thumb:hover {
  background: #b45309;
  transform: scale(1.2);
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4);
}

.range-slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #d97706;
  border-radius: 50%;
  border: 3px solid white;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.range-track {
  position: absolute;
  width: 100%;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  z-index: -1;
}

.range-track-fill {
  position: absolute;
  height: 100%;
  background: linear-gradient(90deg, #d97706, #f59e0b);
  border-radius: 3px;
  transition: all var(--transition-fast);
  box-shadow: 0 2px 4px rgba(217, 119, 6, 0.3);
}

.range-input-group label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.range-number-input:focus {
  border-color: #d97706;
  box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.1);
  outline: none;
}

@media (max-width: 1024px) {
  .classes-inline-row {
    gap: 0.5rem;
  }
  
  .class-checkbox-inline {
    min-width: 90px;
    padding: 0.4rem 0.6rem;
  }
  
  .class-checkbox-icon-inline {
    width: 28px;
    height: 28px;
  }
  
  .class-checkbox-name-inline {
    font-size: 0.7rem;
  }
}

@media (max-width: 768px) {
  .classes-inline-row {
    justify-content: flex-start;
    gap: 0.4rem;
  }
  
  .class-checkbox-inline {
    min-width: 80px;
    padding: 0.35rem 0.5rem;
  }
  
  .class-checkbox-icon-inline {
    width: 24px;
    height: 24px;
  }
  
  .class-checkbox-name-inline {
    font-size: 0.65rem;
  }
  
  .class-checkbox-indicator-inline {
    width: 14px;
    height: 14px;
    font-size: 0.55rem;
  }
}

@media (max-width: 480px) {
  .classes-inline-row {
    gap: 0.25rem;
  }
  
  .class-checkbox-inline {
    min-width: 70px;
    padding: 0.3rem 0.4rem;
  }
  
  .class-checkbox-icon-inline {
    width: 20px;
    height: 20px;
  }
  
  .class-checkbox-name-inline {
    font-size: 0.6rem;
  }
}

/* === TOGGLE FORMAT STANDARD/WILD === */
.format-filter-group {
  min-width: 200px;
}

.format-toggle-container {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.format-toggle-buttons {
  display: flex;
  background: white;
  border-radius: 12px;
  padding: 0.25rem;
  border: 2px solid var(--surface-300);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.format-toggle-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.format-toggle-btn:hover {
  background: rgba(217, 119, 6, 0.1);
  color: #d97706;
  transform: translateY(-1px);
}

.format-toggle-btn.active {
  background: linear-gradient(135deg, #d97706, #f59e0b);
  color: white;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
  transform: translateY(-2px);
}

.format-toggle-btn.standard.active {
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.format-toggle-btn.wild.active {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.format-toggle-btn i {
  font-size: 0.9rem;
}

/* === ACTIONS FILTRES === */
.filters-actions-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  align-items: flex-end;
}

:deep(.filter-sort-dropdown) {
  min-width: 160px !important;
  border: 2px solid #d97706 !important;
  border-radius: 8px !important;
  background: white !important;
  font-size: 0.85rem !important;
}

:deep(.filter-sort-dropdown:hover) {
  border-color: #b45309 !important;
  box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1) !important;
}

:deep(.filter-sort-dropdown.p-focus) {
  border-color: #b45309 !important;
  box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.2) !important;
}

:deep(.reset-filters-btn) {
  background: none !important;
  border: 2px solid #ef4444 !important;
  color: #ef4444 !important;
  width: 40px !important;
  height: 40px !important;
  border-radius: 50% !important;
  transition: all var(--transition-fast) !important;
  opacity: 0.8;
}

:deep(.reset-filters-btn:hover) {
  background: #ef4444 !important;
  color: white !important;
  transform: scale(1.05) rotate(90deg) !important;
  opacity: 1;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
}

/* === RESPONSIVE === */
@media (max-width: 1024px) {
  .hearthstone-filters-panel {
    padding: 1.5rem;
  }
  
  .filters-main-row {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .classes-checkboxes-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.5rem;
  }
  
  .class-checkbox-icon {
    width: 40px;
    height: 40px;
  }
  
  .class-checkbox-name {
    font-size: 0.65rem;
  }
  
  .range-inputs {
    flex-direction: column;
    gap: 0.5rem;
  }
}

@media (max-width: 768px) {
  .hearthstone-filters-panel {
    padding: 1rem;
    gap: 1rem;
  }
  
  .filter-search-wrapper {
    max-width: 100%;
  }
  
  .classes-checkboxes-grid {
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 0.5rem;
  }
  
  .class-checkbox-content {
    padding: 0.5rem 0.25rem;
  }
  
  .class-checkbox-icon {
    width: 36px;
    height: 36px;
  }
  
  .class-checkbox-name {
    font-size: 0.6rem;
  }
  
  .format-toggle-buttons {
    flex-direction: column;
  }
  
  .format-toggle-btn {
    padding: 0.6rem;
    font-size: 0.8rem;
  }
  
  .filters-actions-group {
    align-items: stretch;
  }
  
  :deep(.filter-sort-dropdown) {
    min-width: auto !important;
    width: 100% !important;
  }
}

@media (max-width: 480px) {
  .classes-checkboxes-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 0.25rem;
  }
  
  .class-checkbox-content {
    padding: 0.4rem 0.2rem;
  }
  
  .class-checkbox-icon {
    width: 32px;
    height: 32px;
  }
  
  .class-checkbox-name {
    font-size: 0.55rem;
  }
  
  .class-checkbox-indicator {
    width: 16px;
    height: 16px;
    font-size: 0.6rem;
  }
  
  .dust-range-display {
    font-size: 0.7rem;
  }
  
  .range-number-input {
    padding: 0.4rem;
    font-size: 0.8rem;
  }
}
/* === FILTRES MAGIC AVANCÉS (STYLE HEARTHSTONE) === */

.magic-filters-panel {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.04), rgba(139, 92, 246, 0.02));
  border-left: 6px solid #7c3aed;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  animation: fadeInScale 0.3s ease-out;
}

/* Barre de recherche spécifique Magic */
:deep(.magic-search) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid #7c3aed !important;
  border-radius: 25px !important;
  background: white !important;
  font-size: 0.9rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.magic-search:focus) {
  border-color: #5b21b6 !important;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
  outline: none !important;
}

/* === CHECKBOXES COULEURS MAGIC REDESIGNÉES === */
.magic-colors-filter {
  min-width: 320px;
  flex: 1;
}

.magic-colors-grid {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-start;
}

.magic-color-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 2px solid;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.85rem;
  font-weight: 600;
  user-select: none;
  min-width: 85px;
  justify-content: center;
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  background: white;
}

.magic-color-checkbox::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s ease;
}

.magic-color-checkbox:hover::before {
  left: 100%;
}

.magic-color-checkbox:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.magic-color-checkbox.selected {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
  position: relative;
}

.magic-color-checkbox.selected::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(45deg, currentColor, transparent, currentColor);
  border-radius: 14px;
  z-index: -1;
  opacity: 0.3;
  animation: magicGlow 2s ease-in-out infinite alternate;
}

@keyframes magicGlow {
  0% { opacity: 0.3; }
  100% { opacity: 0.6; }
}

.magic-color-checkbox i {
  font-size: 0.75rem;
  animation: checkPop 0.3s ease-out;
}

/* === DROPDOWN FORMATS MAGIC === */
:deep(.magic-dropdown) {
  min-width: 160px !important;
  border: 2px solid #7c3aed !important;
  border-radius: 8px !important;
  background: white !important;
  font-size: 0.85rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.magic-dropdown:hover) {
  border-color: #5b21b6 !important;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1) !important;
  transform: translateY(-1px) !important;
}

:deep(.magic-dropdown.p-focus) {
  border-color: #5b21b6 !important;
  box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.2) !important;
}

/* === RESPONSIVE MAGIC === */
@media (max-width: 1024px) {
  .magic-filters-panel {
    padding: 1.5rem;
  }
  
  .magic-colors-grid {
    gap: 0.5rem;
  }
  
  .magic-color-checkbox {
    min-width: 75px;
    padding: 0.6rem 0.8rem;
    font-size: 0.8rem;
  }
}

@media (max-width: 768px) {
  .magic-filters-panel {
    padding: 1rem;
    gap: 1rem;
  }
  
  .magic-colors-grid {
    justify-content: center;
    gap: 0.4rem;
  }
  
  .magic-color-checkbox {
    min-width: 70px;
    padding: 0.5rem 0.7rem;
    font-size: 0.75rem;
  }
}

@media (max-width: 480px) {
  .magic-colors-grid {
    gap: 0.3rem;
  }
  
  .magic-color-checkbox {
    min-width: 60px;
    padding: 0.4rem 0.6rem;
    font-size: 0.7rem;
  }
}

/* === FILTRES POKEMON SIMPLES === */

.pokemon-filters-panel {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.04), rgba(255, 152, 0, 0.02));
  border-left: 6px solid #ffc107;
  border-bottom: 1px solid var(--surface-200);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  animation: fadeInScale 0.3s ease-out;
}

/* Barre de recherche spécifique Pokemon */
:deep(.pokemon-search) {
  width: 100% !important;
  padding: 0.875rem 1rem 0.875rem 3rem !important;
  border: 2px solid #ffc107 !important;
  border-radius: 25px !important;
  background: white !important;
  font-size: 0.9rem !important;
  transition: all var(--transition-fast) !important;
}

:deep(.pokemon-search:focus) {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.15) !important;
  outline: none !important;
}

@media (max-width: 1024px) {
  .pokemon-filters-panel {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .pokemon-filters-panel {
    padding: 1rem;
    gap: 1rem;
  }
}
</style>