<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Search, HelpCircle, ExternalLink } from 'lucide-vue-next'

interface Article {
  id: string
  title: string
  slug: string
  feature_refs: string[]
}

const props = defineProps<{
  currentRoute: string
  userRoles: string[]
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const audience = computed(() => {
  if (props.userRoles.includes('admin')) return 'admin'
  if (props.userRoles.includes('manager')) return 'manager'
  return 'agent'
})

const articles = ref<Article[]>([])
const loading = ref(false)
const searchQuery = ref('')

watch(isOpen, async (open) => {
  if (open && props.currentRoute) {
    loading.value = true
    try {
      const response = await fetch(`/api/v1/knowledge-base/contextual?route=${encodeURIComponent(props.currentRoute)}&audience=${audience.value}`)
      if (response.ok) {
        const data = await response.json()
        articles.value = data.data || data
      }
    } catch (e) {
    }
    loading.value = false
  }
})

const filteredArticles = computed(() => {
  if (!searchQuery.value) return articles.value
  return articles.value.filter(a =>
    a.title.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const requestDocumentation = () => {
  fetch('/api/v1/doc-requests', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    },
    body: JSON.stringify({
      screen_identifier: props.currentRoute,
      comment: null,
    }),
  })
  emit('update:open', false)
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <Button variant="ghost" size="icon" title="Help">
        <HelpCircle class="h-5 w-5" />
      </Button>
    </DialogTrigger>
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Help for this page</DialogTitle>
      </DialogHeader>

      <div class="space-y-4">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <Input v-model="searchQuery" placeholder="Search documentation..." class="pl-10" />
        </div>

        <div v-if="loading" class="text-center py-4 text-gray-500">
          Loading...
        </div>

        <div v-else-if="filteredArticles.length > 0" class="space-y-2 max-h-80 overflow-y-auto">
          <div v-for="article in filteredArticles" :key="article.id" class="p-3 border rounded-lg hover:bg-gray-50">
            <Link :href="`/docs/${article.slug}`" class="font-medium text-blue-600 hover:underline" @click="emit('update:open', false)">
              {{ article.title }}
            </Link>
            <div v-if="article.feature_refs?.length" class="text-xs text-gray-500 mt-1">
              Refs: {{ article.feature_refs.join(', ') }}
            </div>
          </div>
        </div>

        <div v-else class="text-center py-4 text-gray-500">
          <p>No specific articles found for this screen.</p>
          <Button @click="requestDocumentation" variant="outline" size="sm" class="mt-2">
            Request documentation
          </Button>
        </div>

        <div class="pt-2 border-t">
          <Link href="/docs" class="text-sm text-blue-600 hover:underline flex items-center gap-1" @click="emit('update:open', false)">
            Open full documentation center
            <ExternalLink class="h-3 w-3" />
          </Link>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>