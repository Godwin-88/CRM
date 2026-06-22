<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { ref, watch } from 'vue'

const props = defineProps<{
  categories: { id: string; name: string }[]
  agents: { id: string; name: string }[]
  contacts: { id: string; first_name?: string; last_name?: string; email?: string }[]
  accounts: { id: string; name?: string }[]
  prefill?: Record<string, string>
}>()

const form = useForm({
  subject: props.prefill?.subject || '',
  description: props.prefill?.description || '',
  contact_id: props.prefill?.contact_id || '',
  account_id: props.prefill?.account_id || '',
  priority: props.prefill?.priority || 'medium',
  category_id: props.prefill?.category_id || '',
  assigned_to: props.prefill?.assigned_to || '',
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
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Create Support Ticket</h1>
          <p class="text-gray-500">Create a new support ticket for a customer.</p>
        </div>
        <Button variant="outline" @click="$inertia.visit('/support/tickets')">Back</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Ticket Details</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label>Subject <span class="text-red-500">*</span></Label>
              <Input v-model="form.subject" required placeholder="Brief description of the issue" class="mt-1" />
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
              <Label>Description</Label>
              <Textarea v-model="form.description" placeholder="Detailed description..." rows="5" class="mt-1" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <Label>Category <span class="text-red-500">*</span></Label>
                <Select v-model="form.category_id" class="mt-1">
                  <SelectTrigger>
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">Select category</SelectItem>
                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label>Priority</Label>
                <Select v-model="form.priority" class="mt-1">
                  <SelectTrigger>
                    <SelectValue placeholder="Select priority" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low</SelectItem>
                    <SelectItem value="medium">Medium</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label>Account</Label>
              <Select v-model="form.account_id" class="mt-1" >
                <SelectTrigger>
                  <SelectValue placeholder="Select account (optional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">No account</SelectItem>
                  <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.name || account.id }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Contact <span class="text-red-500">*</span></Label>
              <Select v-model="form.contact_id" class="mt-1" >
                <SelectTrigger>
                  <SelectValue placeholder="Select contact" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="" disabled>Select contact</SelectItem>
                  <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                    {{ contact.first_name }} {{ contact.last_name }}{{ contact.email ? ` (${contact.email})` : '' }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Assign To</Label>
              <Select v-model="form.assigned_to" class="mt-1">
                <SelectTrigger>
                  <SelectValue placeholder="Unassigned" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Unassigned</SelectItem>
                  <SelectItem v-for="agent in agents" :key="agent.id" :value="agent.id">
                    {{ agent.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="flex gap-2 pt-2">
              <Button type="submit" :disabled="form.processing">Create Ticket</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
