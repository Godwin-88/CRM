<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ThumbsUp, ThumbsDown, MessageCircle } from 'lucide-vue-next'

const props = defineProps<{
  article: {
    id: string
    title: string
    body: string
    view_count: number
    helpful_votes: number
    not_helpful_votes: number
    category: { id: string; name: string }
    author: { id: string; name: string }
    published_at: string
  }
}>()

const rateArticle = (helpful: boolean) => {
  fetch(`/support/knowledge-base/${props.article.id}/rate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    },
    body: JSON.stringify({ helpful }),
  }).then(() => {
    // Show thank you message
  })
}
</script>

<template>
  <AppLayout>
    <Head :title="article.title" />
    
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ article.title }}</h1>
        <p class="text-gray-500">
          Category: {{ article.category?.name }} | 
          Published: {{ article.published_at }}
        </p>
      </div>

      <Card>
        <CardContent class="pt-6">
          <div class="prose max-w-none" v-html="article.body"></div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Was this helpful?</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-4">
            <Button @click="rateArticle(true)" variant="outline">
              <ThumbsUp class="h-4 w-4 mr-2" />
              Yes
            </Button>
            <Button @click="rateArticle(false)" variant="outline">
              <ThumbsDown class="h-4 w-4 mr-2" />
              No
            </Button>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            {{ article.helpful_votes }} found this helpful, {{ article.not_helpful_votes }} did not
          </p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>