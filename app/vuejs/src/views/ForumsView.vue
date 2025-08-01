<template>
  <div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-semibold mb-6">Forums</h1>

    <div v-if="loading" class="text-center py-8">Chargement...</div>

    <div v-else class="grid gap-6">
      <div v-for="forum in forums" :key="forum.slug" class="p-card p-4 shadow-md bg-white rounded-xl">
        <div class="flex justify-between items-start mb-2">
          <div>
            <h2 class="text-xl font-bold text-primary mb-1">{{ forum.name }}</h2>
            <p class="text-secondary text-sm">{{ forum.description }}</p>
          </div>
          <RouterLink :to="`/forums/${forum.slug}`" class="emerald-button primary">
            Voir plus
          </RouterLink>
        </div>

        <div v-if="forum.posts && forum.posts.length" class="mt-4 border-t pt-2">
          <div v-for="post in forum.posts.slice(0, 3)" :key="post.id" class="mb-2">
            <RouterLink :to="`/posts/${post.id}`" class="text-accent hover:underline">
              {{ post.title }}
            </RouterLink>
            <div class="text-sm text-secondary">
              par <strong>{{ post.author }}</strong> · {{ formatDate(post.createdAt) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const forums = ref([]);
const loading = ref(true);
const router = useRouter();

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString();
};

onMounted(async () => {
  try {
    const { data: forumsList } = await api.get('/api/forums');
    const forumsWithPosts = await Promise.all(
      forumsList.map(async (forum) => {
        const res = await api.get(`/api/forums/${forum.slug}/posts`);
        return { ...forum, posts: res.data.posts || [] };
      })
    );
    forums.value = forumsWithPosts;
  } catch (err) {
    console.error('Erreur chargement forums', err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.p-card {
  transition: all 0.3s ease;
}
.p-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-medium);
}
</style>
