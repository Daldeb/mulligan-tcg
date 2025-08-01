<template>
  <div class="container mx-auto px-4 py-6">
    <div v-if="loading" class="text-center py-8">Chargement...</div>

    <div v-else-if="error" class="text-red-600 text-center py-8">
      {{ error }}
    </div>

    <div v-else>
      <h1 class="text-3xl font-bold text-primary mb-2">{{ post.title }}</h1>
      <p class="text-secondary text-sm mb-4">
        par <strong>{{ post.author }}</strong> · {{ formatDate(post.createdAt) }} · Score : {{ post.score }}
      </p>
      <div class="p-card p-4 mb-6 bg-white rounded-xl shadow-sm">
        <p class="whitespace-pre-line">{{ post.content }}</p>
      </div>

      <h2 class="text-xl font-semibold mb-2">Commentaires</h2>

      <div v-if="comments.length === 0" class="text-sm text-secondary mb-4">
        Aucun commentaire pour l’instant.
      </div>

      <div v-for="comment in comments" :key="comment.id" class="p-card p-3 mb-3 bg-white rounded-lg shadow-sm">
        <p class="text-sm text-secondary mb-1">
          <strong>{{ comment.author }}</strong> — {{ formatDate(comment.createdAt) }}
        </p>
        <p class="text-base whitespace-pre-line">{{ comment.content }}</p>
      </div>

      <div class="mt-6 p-card p-4 bg-white rounded-xl shadow-sm space-y-2">
        <h3 class="text-lg font-semibold">Ajouter un commentaire</h3>
        <textarea v-model="newComment" rows="4" class="p-inputtext w-full" placeholder="Votre commentaire..." />
        <button class="emerald-button primary" @click="submitComment" :disabled="loadingComment">
          Publier
        </button>
        <p v-if="commentError" class="text-red-600 text-sm mt-1">{{ commentError }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';

const route = useRoute();
const postId = route.params.id;

const post = ref({});
const comments = ref([]);
const loading = ref(true);
const error = ref('');

const newComment = ref('');
const loadingComment = ref(false);
const commentError = ref('');

const formatDate = (d) => new Date(d).toLocaleString();

const fetchPost = async () => {
  try {
    const res = await api.get(`/posts/${postId}`);
    post.value = res.data;
    comments.value = res.data.comments;
  } catch (err) {
    error.value = 'Erreur lors du chargement du post';
  } finally {
    loading.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value) {
    commentError.value = 'Le commentaire ne peut pas être vide';
    return;
  }

  loadingComment.value = true;
  commentError.value = '';

  try {
    await api.post(`/posts/${postId}/comments`, {
      content: newComment.value
    });
    newComment.value = '';
    await fetchPost(); // recharger les commentaires
  } catch (err) {
    commentError.value = 'Erreur lors de l’envoi';
  } finally {
    loadingComment.value = false;
  }
};

onMounted(fetchPost);
</script>
