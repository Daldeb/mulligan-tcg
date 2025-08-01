<template>
  <div class="p-card p-4 rounded-xl shadow-md bg-white space-y-4">
    <h2 class="text-xl font-bold text-primary">Créer un nouveau sujet</h2>

    <div class="p-fluid space-y-2">
      <label for="title">Titre</label>
      <input
        id="title"
        v-model="title"
        type="text"
        class="p-inputtext"
        placeholder="Ex : Idée de deck budget"
      />

      <label for="content">Contenu</label>
      <textarea
        id="content"
        v-model="content"
        rows="6"
        class="p-inputtext"
        placeholder="Décrivez votre idée, partagez un lien, etc..."
      />

      <div class="flex justify-end mt-4">
        <button class="emerald-button primary" @click="submitPost" :disabled="loading">
          Publier
        </button>
      </div>
    </div>

    <p v-if="error" class="text-red-600 text-sm mt-2">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const props = defineProps({
  forumSlug: String
});

const title = ref('');
const content = ref('');
const loading = ref(false);
const error = ref('');
const router = useRouter();

const submitPost = async () => {
  if (!title.value || !content.value) {
    error.value = 'Titre et contenu requis';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const res = await api.post(`/api/forums/${props.forumSlug}/posts`, {
      title: title.value,
      content: content.value
    });

    router.push(`/posts/${res.data.postId}`);
  } catch (err) {
    console.error(err);
    error.value = 'Erreur lors de la création du post';
  } finally {
    loading.value = false;
  }
};
</script>
