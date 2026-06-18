<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { ref, watch } from 'vue'

const props = defineProps<{
  categories: { id: string; name: string; default_priority?: string }[]
  agents: { id: string; name: string }[]
}>()

const form = useForm({
  subject: '',
  description: '',
  contact_id: '',
  priority: 'medium',
  category_id: '',
  assigned_to: '',
})

const suggestedArticles = ref<Array<{id: string; title: string; excerpt: string}>>([])
const showSuggestions = ref(false)

watch(() => form.subject, async (newSubject) => {
  if (newSubject.length > 5) {
    const response = await fetch('/support/tickets/suggest-articles', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ subject: newSubject }),
    })
    const data = await response.json()
    suggestedArticles.value = data.articles || []
    showSuggestions.value = suggestedArticles.value.length > 0
  } else {
    showSuggestions.value = false
  }
})

const submit = () => {
  form.post('/support/tickets', {
    onSuccess: () => {
      // Redirect handled by server
    }
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Create Ticket" />
    
    <div class="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Create Support Ticket</h1>
        <p class="text-gray-500">Create a new support ticket for a customer.</p>
      </div>

      <Card>
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Subject *</label>
              <Input v-model="form.subject" required placeholder="Brief description of the issue" />
              <div v-if="showSuggestions" class="mt-2 space-y-2">
                <p class="text-sm font-medium text-gray-700">Suggested articles:</p>
                <div v-for="article in suggestedArticles" :key="article.id" class="text-sm">
                  <a :href="`/support/knowledge-base/${article.id}`" class="text-blue-600 hover:underline" target="_blank">
                    {{ article.title }}
                  </a>
                  <p class="text-xs text-gray-500">{{ article.excerpt }}</p>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Description</label>
              <Textarea v-model="form.description" placeholder="Detailed description..." rows="5" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Category *</label>
                <select v-model="form.category_id" required class="w-full p-2 border rounded">
                  <option value="">Select category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Priority</label>
                <select v-model="form.priority" class="w-full p-2 border rounded">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Contact ID *</label>
              <Input v-model="form.contact_id" type="text" placeholder="Contact ULID" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Assign To</label>
              <select v-model="form.assigned_to" class="w-full p-2 border rounded">
                <option value="">Unassigned</option>
                <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                  {{ agent.name }}
                </option>
              </select>
            </div>

            <Button type="submit" :disabled="form.processing">
              Create Ticket
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>