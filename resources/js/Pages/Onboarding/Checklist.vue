<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { CheckCircle2, Circle, X } from 'lucide-vue-next'

const props = defineProps<{
  checklist: Array<{
    key: string
    title: string
    description: string
    route: string
    article_slug: string
    completed: boolean
    dismissed: boolean
  }>
}>()

const markComplete = (key: string) => {
  router.post('/onboarding/checklist/complete', { checklist_item_key: key }, {
    preserveScroll: true,
  })
}

const dismissItem = (key: string) => {
  router.post('/onboarding/checklist/dismiss', { checklist_item_key: key }, {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Onboarding Checklist" />

    <div class="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Onboarding Checklist</h1>
        <p class="text-gray-500">Complete these steps to get started with the platform</p>
      </div>

      <div class="space-y-3">
        <Card v-for="item in checklist" :key="item.key" :class="{'opacity-60': item.dismissed}">
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <component :is="item.completed ? CheckCircle2 : Circle" class="h-5 w-5" :class="item.completed ? 'text-green-600' : 'text-gray-400'" />
                {{ item.title }}
              </div>
              <Button v-if="!item.dismissed" @click="dismissItem(item.key)" variant="ghost" size="icon" class="text-gray-400 hover:text-gray-600">
                <X class="h-4 w-4" />
              </Button>
            </CardTitle>
          </CardHeader>
          <CardContent v-show="!item.dismissed">
            <p class="text-sm text-gray-600 mb-4">{{ item.description }}</p>
            <div class="flex gap-3">
              <Button v-if="!item.completed" @click="markComplete(item.key)" size="sm">
                Mark Complete
              </Button>
              <Link :href="item.route" class="text-sm text-blue-600 hover:underline">
                Go to Screen →
              </Link>
              <Link v-if="item.article_slug" :href="`/docs/${item.article_slug}`" class="text-sm text-blue-600 hover:underline">
                Read Guide
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>

      <div v-if="checklist.length === 0" class="text-center py-8 text-gray-500">
        No checklist items available for your role.
      </div>
    </div>
  </AppLayout>
</template>