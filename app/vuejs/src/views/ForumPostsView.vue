<template>
  <div class="container mx-auto px-4 py-6">
    <div v-if="loading" class="text-center py-8">Chargement...</div>

    <div v-else>
      <h1 class="text-3xl font-bold mb-1">{{ forum.name }}</h1>
      <p class="text-secondary mb-6">{{ forum.description }}</p>

      <RouterLink to="/forums" class="text-primary text-sm hover:underline">
        ← Retour aux forums
      </RouterLink>

      <PostCreateForm :forumSlug="forum.slug" class="mt-6" />

      <div v-if="posts.length === 0" class="text-sm text-secondary mt-6">
        Aucun sujet pour le moment.
      </div>

      <div v-else class="mt-4 space-y-4">
        <div
          v-for="post in posts"
          :key="post.id"
          class="p-card p-4 rounded-xl shadow-md bg-white hover-lift"
        >
          <RouterLink :to="`/posts/${post.id}`" class="text-accent text-lg font-semibold hover:underline">
            {{ post.title }}
          </RouterLink>
          <div class="text-sm text-secondary mt-1">
            par <strong>{{ post.author }}</strong> · {{ formatDate(post.createdAt) }} · Score : {{ post.score }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import PostCreateForm from '@/components/forum/PostCreateForm.vue'

const route = useRoute();
const slug = route.params.slug;
const forum = ref({});
const posts = ref([]);
const loading = ref(true);

const formatDate = (d) => new Date(d).toLocaleString();

onMounted(async () => {
  try {
    const res = await api.get(`/forums/${slug}/posts`);
    forum.value = res.data.forum;
    posts.value = res.data.posts;
  } catch (e) {
    console.error('Erreur chargement forum', e);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.hover-lift {
  transition: transform 0.3s ease;
}
.hover-lift:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-medium);
}
</style>
