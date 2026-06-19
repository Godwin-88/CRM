<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { ref } from 'vue'

const props = defineProps<{
  articles: {
    data: Array<{
      id: string
      title: string
      published_at: string
      category: { name: string }
    }>
    links: Array<{
      url: string | null
      label: string
      active: boolean
    }>
  }
  categories: Array<{
    id: string
    name: string
    children?: any[]
  }>
}>()

const searchQuery = ref('')
</script>

<template>
  <AppLayout>
    <Head title="Knowledge Base" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Knowledge Base</h1>
        <p class="text-gray-500">Find answers to common questions.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
          <Card>
            <CardHeader>
              <CardTitle>Categories</CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-2">
                <li>
                  <Link href="/portal/knowledge-base" class="text-blue-600 hover:underline text-sm font-medium">
                    All Articles
                  </Link>
                </li>
                <li v-for="category in categories" :key="category.id" class="ml-2">
                  <Link :href="`/portal/knowledge-base?category=${category.id}`" class="text-gray-700 hover:text-blue-600 text-sm">
                    {{ category.name }}
                  </Link>
                </li>
              </ul>
            </CardContent>
          </Card>
        </div>

        <div class="lg:col-span-3 space-y-4">
          <Input v-model="searchQuery" placeholder="Search articles..." @keyup.enter="() => {}" />

          <div v-if="articles.data.length" class="space-y-4">
            <Card v-for="article in articles.data" :key="article.id">
              <CardContent class="pt-6">
                <Link :href="`/portal/knowledge-base/${article.id}`" class="text-lg font-medium text-blue-600 hover:underline">
                  {{ article.title }}
                </Link>
                <p class="text-sm text-gray-500 mt-1">
                  {{ article.category?.name }} • {{ article.published_at }}
                </p>
              </CardContent>
            </Card>
          </div>

          <div v-else class="text-center py-8">
            <p class="text-gray-500">No articles found.</p>
          </div>

          <div class="flex justify-center">
            <div class="flex gap-2">
              <Link
                v-for="link in articles.links"
                :key="link.url || 'noid'"
                :href="link.url || '#'"
                :class="[
                  'px-3 py-1 rounded',
                  link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                  !link.url ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                v-html="link.label"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>