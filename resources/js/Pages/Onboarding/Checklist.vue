<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { CheckCircle2, Circle, X, Check } from 'lucide-vue-next'
import { computed, ref } from 'vue'

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

const checklist = ref(props.checklist.map(item => ({ ...item })))
const successMessage = ref('')
const processingKey = ref<string | null>(null)

const markComplete = async (key: string) => {
  processingKey.value = key
  try {
    const response = await fetch('/onboarding/checklist/complete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ checklist_item_key: key }),
    })

    if (response.ok) {
      const item = checklist.value.find(i => i.key === key)
      if (item) {
        item.completed = true
        item.dismissed = false
      }
      successMessage.value = `"${checklist.value.find(i => i.key === key)?.title}" marked as complete!`
      setTimeout(() => {
        successMessage.value = ''
      }, 3000)
    }
  } catch {
    // silent fail
  } finally {
    processingKey.value = null
  }
}

const dismissItem = async (key: string) => {
  processingKey.value = key
  try {
    const response = await fetch('/onboarding/checklist/dismiss', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ checklist_item_key: key }),
    })

    if (response.ok) {
      const item = checklist.value.find(i => i.key === key)
      if (item) {
        item.dismissed = true
      }
    }
  } catch {
    // silent fail
  } finally {
    processingKey.value = null
  }
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

      <div v-if="successMessage" class="rounded-lg border border-green-200 bg-green-50 p-4">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0">
            <Check class="h-5 w-5 text-green-600" />
          </div>
          <p class="text-sm font-medium text-green-700">{{ successMessage }}</p>
        </div>
      </div>

      <div class="space-y-3">
        <Card v-for="item in checklist" :key="item.key" :class="{'opacity-60': item.dismissed}">
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <component :is="item.completed ? CheckCircle2 : Circle" class="h-5 w-5" :class="item.completed ? 'text-green-600' : 'text-gray-400'" />
                {{ item.title }}
              </div>
              <Button v-if="!item.dismissed" @click="dismissItem(item.key)" variant="ghost" size="icon" class="text-gray-400 hover:text-gray-600" :disabled="processingKey === item.key">
                <X class="h-4 w-4" />
              </Button>
            </CardTitle>
          </CardHeader>
          <CardContent v-show="!item.dismissed">
            <p class="text-sm text-gray-600 mb-4">{{ item.description }}</p>
            <div class="flex gap-3">
              <Button v-if="!item.completed" @click="markComplete(item.key)" size="sm" :disabled="processingKey === item.key">
                <Check class="h-4 w-4 mr-1" />
                {{ processingKey === item.key ? 'Saving...' : 'Mark Complete' }}
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
