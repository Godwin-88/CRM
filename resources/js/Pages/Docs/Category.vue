<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { List } from 'lucide-vue-next'

const props = defineProps<{
  category: {
    id: string
    name: string
    slug: string
    description: string
  }
  articles: Array<{
    id: string
    title: string
    slug: string
    published_at: string
    author: { id: string; name: string }
  }>
}>()
</script>

<template>
  <AppLayout>
    <Head :title="category.name" />

    <div class="max-w-4xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <List class="h-6 w-6 text-blue-600" />
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ category.name }}</h1>
          <p class="text-gray-500">{{ category.description }}</p>
        </div>
      </div>

      <div class="space-y-4">
        <Card v-for="article in articles" :key="article.id">
          <CardHeader>
            <CardTitle>
              <Link :href="`/docs/${article.slug}`" class="hover:underline">
                {{ article.title }}
              </Link>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-gray-500">
              Published: {{ new Date(article.published_at).toLocaleDateString() }} |
              Author: {{ article.author?.name }}
            </p>
          </CardContent>
        </Card>

        <div v-if="articles.length === 0" class="text-center py-8 text-gray-500">
          No articles in this category yet.
        </div>
      </div>

      <div class="pt-4 border-t">
        <Link href="/docs" class="text-sm text-blue-600 hover:underline">
          ← Back to Documentation Center
        </Link>
      </div>
    </div>
  </AppLayout>
</template>