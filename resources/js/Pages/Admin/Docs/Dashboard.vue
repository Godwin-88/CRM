<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AlertCircle, FileText, BarChart3 } from 'lucide-vue-next'

const props = defineProps<{
  stale_articles: Array<any>
  low_helpfulness_articles: Array<any>
  coverage_gaps: Array<any>
  pending_requests: Array<any>
}>()
</script>

<template>
  <AppLayout>
    <Head title="Documentation Dashboard" />

    <div class="space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Documentation Dashboard</h1>
        <p class="text-gray-500">Monitor documentation coverage and quality</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <AlertCircle class="h-5 w-5 text-amber-600" />
              Stale Articles (6+ months since verification)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="stale_articles.length > 0" class="space-y-3 max-h-64 overflow-y-auto">
              <div v-for="article in stale_articles" :key="article.id" class="text-sm">
                <Link :href="`/support/knowledge-base/${article.id}/edit`" class="font-medium text-blue-600 hover:underline">
                  {{ article.title }}
                </Link>
                <p class="text-gray-500 text-xs">{{ article.category?.name }}</p>
              </div>
            </div>
            <p v-else class="text-gray-500">No stale articles found.</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BarChart3 class="h-5 w-5 text-red-600" />
              Low Helpfulness Articles (&lt;40% helpful)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="low_helpfulness_articles.length > 0" class="space-y-3 max-h-64 overflow-y-auto">
              <div v-for="article in low_helpfulness_articles" :key="article.id" class="text-sm">
                <Link :href="`/support/knowledge-base/${article.id}`" class="font-medium text-blue-600 hover:underline">
                  {{ article.title }}
                </Link>
                <p class="text-gray-500 text-xs">
                  Helpfulness: {{ article.helpful_votes }}/{{ article.helpful_votes + article.not_helpful_votes }}
                </p>
              </div>
            </div>
            <p v-else class="text-gray-500">No low helpfulness articles found.</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <FileText class="h-5 w-5 text-blue-600" />
            Documentation Coverage Gaps
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="coverage_gaps.length > 0" class="space-y-3 max-h-64 overflow-y-auto">
            <div v-for="gap in coverage_gaps" :key="gap.feature" class="text-sm">
              <span class="font-medium">{{ gap.title }}</span>
              <span class="text-gray-500 text-xs ml-2">({{ gap.feature }})</span>
            </div>
          </div>
          <p v-else class="text-gray-500">All spec sections have documentation coverage.</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Documentation Requests</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="pending_requests.length > 0" class="space-y-3 max-h-64 overflow-y-auto">
            <div v-for="request in pending_requests" :key="request.id" class="flex justify-between items-start text-sm border-b pb-2 last:border-0">
              <div>
                <span class="font-medium">{{ request.screen_identifier }}</span>
                <p class="text-gray-500 text-xs">
                  {{ request.request_count }} request(s) | {{ new Date(request.created_at).toLocaleDateString() }}
                </p>
                <p v-if="request.comment" class="text-gray-600 text-xs mt-1">{{ request.comment }}</p>
              </div>
              <Button @click="router.post(`/admin/docs/${request.id}/resolve`)" size="sm">
                Resolve
              </Button>
            </div>
          </div>
          <p v-else class="text-gray-500">No pending documentation requests.</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>