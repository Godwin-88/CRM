<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

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
              <label class="block text-sm font-medium mb-1">Subject</label>
              <Input v-model="form.subject" required placeholder="Brief description of the issue" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Description</label>
              <Textarea v-model="form.description" placeholder="Detailed description..." rows="5" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Category</label>
                <select v-model="form.category_id" class="w-full p-2 border rounded">
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
              <label class="block text-sm font-medium mb-1">Contact ID</label>
              <Input v-model="form.contact_id" type="text" placeholder="Contact ULID" />
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