<template>
  <Card 
    class="deck-card gaming-card hover-lift"
    @click="$emit('click')"
  >
    <template #content>
      <div class="deck-content">
        
        <!-- Header du deck -->
        <div class="deck-header">
          <div class="format-badge" :class="`format-${deck.format}`">
            {{ deck.format?.toUpperCase() }}
          </div>
          <div class="deck-actions" @click.stop>
            <Button 
              icon="pi pi-ellipsis-v"
              class="menu-btn"
            />
          </div>
        </div>

        <!-- Preview du deck -->
        <div class="deck-preview" :class="`bg-${deck.game}`">
          <div class="deck-placeholder">
            <i class="pi pi-clone deck-icon"></i>
          </div>
          <div class="deck-stats-overlay">
            <span class="card-count">{{ deck.cardCount || 0 }} cartes</span>
          </div>
        </div>

        <!-- Informations du deck -->
        <div class="deck-info">
          <h3 class="deck-name">{{ deck.name }}</h3>
          <p class="deck-author">Par {{ deck.author }}</p>
          
          <div class="deck-meta">
            <div class="meta-item">
              <i class="pi pi-heart"></i>
              <span>{{ deck.likes || 0 }}</span>
            </div>
            <div class="meta-item">
              <i class="pi pi-eye"></i>
              <span>{{ deck.views || 0 }}</span>
            </div>
            <div class="meta-item" v-if="deck.winRate">
              <i class="pi pi-chart-line"></i>
              <span>{{ deck.winRate }}%</span>
            </div>
          </div>
        </div>

      </div>
    </template>
  </Card>
</template>

<script setup>
const props = defineProps({
  deck: {
    type: Object,
    required: true
  }
})

defineEmits(['click', 'edit', 'delete', 'copy'])
</script>

<style scoped>
.deck-card {
  cursor: pointer;
  transition: all var(--transition-medium);
}

.deck-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-large);
}

.deck-content {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.deck-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.format-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.format-standard {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.format-wild {
  background: rgba(249, 115, 22, 0.1);
  color: #ea580c;
  border: 1px solid rgba(249, 115, 22, 0.3);
}

.deck-preview {
  aspect-ratio: 16/10;
  border-radius: var(--border-radius);
  overflow: hidden;
  position: relative;
  background: var(--surface-200);
}

.deck-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.deck-icon {
  font-size: 2rem;
  color: var(--text-secondary);
}

.bg-hearthstone {
  background: linear-gradient(135deg, rgba(38, 166, 154, 0.2), rgba(38, 166, 154, 0.1));
}

.bg-magic {
  background: linear-gradient(135deg, rgba(139, 69, 19, 0.2), rgba(139, 69, 19, 0.1));
}

.bg-pokemon {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 193, 7, 0.1));
}

.deck-stats-overlay {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
}

.deck-info {
  text-align: center;
}

.deck-name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}

.deck-author {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin: 0 0 0.75rem 0;
}

.deck-meta {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.meta-item i {
  font-size: 0.7rem;
  color: var(--primary);
}

:deep(.menu-btn) {
  background: none !important;
  border: 1px solid var(--surface-300) !important;
  color: var(--text-secondary) !important;
  width: 32px !important;
  height: 32px !important;
  border-radius: 50% !important;
  padding: 0 !important;
}

:deep(.menu-btn:hover) {
  border-color: var(--primary) !important;
  color: var(--primary) !important;
  background: rgba(38, 166, 154, 0.1) !important;
}
</style>