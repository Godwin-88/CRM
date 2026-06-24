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
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
          <div class="article-body prose" v-html="article.body"></div>
        </CardContent>
      </Card>

      <style scoped>
      .article-body :deep(h2) {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        padding-bottom: 0.25rem;
        border-bottom: 2px solid #e5e7eb;
      }
      .article-body :deep(h3) {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-top: 1.25rem;
        margin-bottom: 0.4rem;
      }
      .article-body :deep(p) {
        color: #374151;
        line-height: 1.7;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
      }
      .article-body :deep(ul),
      .article-body :deep(ol) {
        margin: 0.5rem 0 1rem 1.5rem;
        padding-left: 1rem;
      }
      .article-body :deep(ul) {
        list-style-type: disc;
      }
      .article-body :deep(ol) {
        list-style-type: decimal;
      }
      .article-body :deep(li) {
        color: #374151;
        line-height: 1.65;
        margin-bottom: 0.35rem;
        font-size: 0.95rem;
      }
      .article-body :deep(li::marker) {
        color: #4b5563;
        font-weight: 600;
      }
      .article-body :deep(strong) {
        font-weight: 600;
        color: #111827;
      }
      .article-body :deep(code) {
        background: #f3f4f6;
        padding: 0.15rem 0.35rem;
        border-radius: 0.25rem;
        font-size: 0.9em;
        color: #dc2626;
      }
      .article-body :deep(a) {
        color: #2563eb;
        text-decoration: underline;
      }
      .article-body :deep(hr) {
        border: none;
        border-top: 1px solid #e5e7eb;
        margin: 1.5rem 0;
      }
      </style>

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