<template>
    <Dialog
    v-model:visible="localVisible"
    :modal="true"
    :closable="false"
    :dismissableMask="true"
    :style="{ width: '100%', maxWidth: '460px' }"
    class="emerald-modal"
    @hide="$emit('close')"
    >

    <template #header>
      <div class="modal-header-content">
        <i class="pi pi-lock header-icon"></i>
        <span class="header-title">Connexion requise</span>
      </div>
    </template>

    <div class="modal-body space-y-4">
      <p class="text-secondary text-sm leading-relaxed">
        Les forums sont réservés aux membres connectés.
        Veuillez vous connecter pour accéder à cette section.
      </p>

      <div class="flex justify-end gap-3 pt-2">
        <Button 
          label="Se connecter"
          icon="pi pi-sign-in"
          class="emerald-button primary"
          @click="goToLogin"
        />
        <Button 
          label="Annuler"
          class="emerald-outline-btn cancel"
          @click="$emit('close')"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { computed } from 'vue'

const props = defineProps({
  visible: Boolean
})
const emit = defineEmits(['close', 'update:visible'])

const localVisible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val)
})

const router = useRouter()

const goToLogin = () => {
  emit('close')
  router.push('/profile') // ou route "login" si tu en as une séparée
}
</script>
