<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const props = defineProps<{
  articles: any[]
  categories: any[]
}>()

const searchForm = useForm({
  search: '',
})
</script>

<template>
  <AppLayout>
    <Head title="Knowledge Base" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Knowledge Base</h1>
        <p class="text-gray-500">Find answers to common questions.</p>
      </div>

      <form @submit.prevent="searchForm.get('/support/knowledge-base')" class="flex gap-2 max-w-md">
        <Input v-model="searchForm.search" placeholder="Search articles..." class="flex-1" />
        <Button type="submit">Search</Button>
      </form>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="md:col-span-1">
          <Card>
            <CardHeader>
              <CardTitle>Categories</CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-2">
                <li v-for="category in categories" :key="category.id">
                  <a href="#" class="text-blue-600 hover:underline">{{ category.name }}</a>
                </li>
              </ul>
            </CardContent>
          </Card>
        </div>

        <div class="md:col-span-3">
          <div class="space-y-4">
            <Card v-for="article in articles" :key="article.id">
              <CardHeader>
                <CardTitle>
                  <a :href="`/support/knowledge-base/${article.id}`" class="hover:underline">
                    {{ article.title }}
                  </a>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p class="text-sm text-gray-600 line-clamp-3" v-html="article.body?.substring(0, 200) + '...'"></p>
                <p class="text-xs text-gray-500 mt-2">
                  {{ article.view_count }} views | Helpfulness: {{ article.helpful_votes }}/{{ article.helpful_votes + article.not_helpful_votes }}
                </p>
              </CardContent>
            </Card>
          </div>
          
          <div v-if="articles.length === 0" class="text-center py-8 text-gray-500">
            No articles found.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>