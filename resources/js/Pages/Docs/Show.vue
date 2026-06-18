<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ThumbsUp, ThumbsDown, FileText } from 'lucide-vue-next'

const props = defineProps<{
  article: {
    id: string
    title: string
    body: string
    view_count: number
    helpful_votes: number
    not_helpful_votes: number
    audience: string
    feature_refs: string[]
    last_verified_at: string | null
    published_at: string
    category: { id: string; name: string; slug: string }
    author: { id: string; name: string }
  }
}>()

const rateArticle = (helpful: boolean) => {
  fetch(`/support/knowledge-base/${props.article.id}/rate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content || '',
    },
    body: JSON.stringify({ helpful }),
  })
}

const isStale = () => {
  if (!props.article.last_verified_at) return true
  const verified = new Date(props.article.last_verified_at)
  const sixMonthsAgo = new Date()
  sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6)
  return verified < sixMonthsAgo
}
</script>

<template>
  <AppLayout>
    <Head :title="article.title" />

    <div class="max-w-4xl mx-auto space-y-6">
      <div class="flex items-start gap-3">
        <FileText class="h-6 w-6 text-blue-600 mt-1" />
        <div class="flex-1">
          <h1 class="text-3xl font-bold text-gray-900">{{ article.title }}</h1>
          <p class="text-gray-500 mt-1">
            Category: {{ article.category?.name }} |
            Audience: {{ article.audience }}
          </p>
          <div v-if="isStale()" class="mt-2">
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded">
              Last verified: {{ article.last_verified_at ? new Date(article.last_verified_at).toLocaleDateString() : 'Never' }}
            </span>
          </div>
        </div>
      </div>

      <Card>
        <CardContent class="pt-6">
          <div class="prose max-w-none" v-html="article.body"></div>
        </CardContent>
      </Card>

      <div v-if="article.feature_refs && article.feature_refs.length > 0" class="text-xs text-gray-500">
        References: {{ article.feature_refs.join(', ') }}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Was this helpful?</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-4">
            <Button @click="rateArticle(true)" variant="outline" size="sm">
              <ThumbsUp class="h-4 w-4 mr-2" />
              Yes
            </Button>
            <Button @click="rateArticle(false)" variant="outline" size="sm">
              <ThumbsDown class="h-4 w-4 mr-2" />
              No
            </Button>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            {{ article.helpful_votes }} found this helpful, {{ article.not_helpful_votes }} did not
          </p>
        </CardContent>
      </Card>

      <div class="pt-4 border-t">
        <a href="/docs" class="text-sm text-blue-600 hover:underline">
          ← Back to Documentation Center
        </a>
      </div>
    </div>
  </AppLayout>
</template>