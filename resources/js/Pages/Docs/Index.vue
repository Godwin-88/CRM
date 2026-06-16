<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Library, BookOpen } from 'lucide-vue-next'

const props = defineProps<{
  categories: Array<{
    id: string
    name: string
    slug: string
    description: string
    articles_count: number
  }>
}>()
</script>

<template>
  <AppLayout>
    <Head title="Documentation Center" />

    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <Library class="h-8 w-8 text-blue-600" />
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Documentation Center</h1>
          <p class="text-gray-500">Help guides and documentation for the CRM platform</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card v-for="category in categories" :key="category.id" class="hover:shadow-md transition-shadow">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BookOpen class="h-5 w-5 text-blue-500" />
              {{ category.name }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-gray-600 mb-4">{{ category.description ?? 'Documentation for ' + category.name }}</p>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-500">{{ category.articles_count }} articles</span>
              <Link :href="`/docs/category/${category.slug}`" class="text-sm text-blue-600 hover:underline">
                Browse →
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>

      <div v-if="categories.length === 0" class="text-center py-12 text-gray-500">
        No documentation categories available.
      </div>
    </div>
  </AppLayout>
</template>