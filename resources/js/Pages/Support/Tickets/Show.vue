<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { ref } from 'vue'

const props = defineProps<{
  ticket: {
    id: string
    subject: string
    description: string
    priority: string
    status: string
    created_at: string
    sla_breached_at: string | null
    contact: { id: string; first_name: string; last_name: string; email: string }
    account: { id: string; name: string } | null
    assignee: { id: string; name: string } | null
    category: { id: string; name: string }
    sla_instance?: { first_response_deadline: string; resolution_deadline: string }
    internal_notes: any[]
    related_tickets: any[]
    rating?: { score: number; comment: string }
    linked_articles: any[]
    interactions: any[]
  }
  cannedResponses: { id: string; title: string; body: string }[]
}>()

const showInternalNotes = ref(true)

const form = useForm({
  status: props.ticket.status,
  priority: props.ticket.priority,
  assigned_to: props.ticket.assignee?.id ?? '',
  resolution_note: '',
})

const submitStatusChange = () => {
  form.put(`/support/tickets/${props.ticket.id}`, {
    onSuccess: () => {
      // Refresh page or show toast
    }
  })
}

const resolveTicket = () => {
  form.post(`/support/tickets/${props.ticket.id}/resolve`, {
    onSuccess: () => {
      form.resolution_note = ''
    }
  })
}

const statusOptions = [
  { value: 'open', label: 'Open' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'waiting_on_customer', label: 'Waiting on Customer' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
]

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
]
</script>

<template>
  <AppLayout>
    <Head :title="`Ticket #${ticket.id}`" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ ticket.subject }}</h1>
          <p class="text-gray-500">Ticket #{{ ticket.id }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Badge v-if="ticket.sla_breached_at" variant="destructive">SLA Breached</Badge>
          <Badge :variant="ticket.priority === 'urgent' ? 'destructive' : 'secondary'">
            {{ ticket.priority }}
          </Badge>
          <Badge :variant="ticket.status === 'open' ? 'destructive' : 'outline'">
            {{ ticket.status }}
          </Badge>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Ticket Details</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div>
                  <label class="text-sm font-medium text-gray-500">Description</label>
                  <p class="mt-1 whitespace-pre-wrap">{{ ticket.description }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Interaction History</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="interaction in ticket.interactions" :key="interaction.id" 
                     class="border-l-4 pl-4" 
                     :class="interaction.direction === 'outbound' ? 'border-blue-500' : 'border-gray-300'">
                  <p class="text-sm">
                    {{ interaction.direction === 'outbound' ? 'Agent' : 'Customer' }}: 
                    {{ interaction.subject }}
                  </p>
                  <p class="text-xs text-gray-500">{{ interaction.created_at }}</p>
                </div>
                <p v-if="!ticket.interactions?.length" class="text-gray-500">No interactions yet.</p>
              </div>
            </CardContent>
          </Card>

          <Card v-if="showInternalNotes && ticket.internal_notes?.length">
            <CardHeader>
              <CardTitle class="flex items-center justify-between">
                <span>Internal Notes</span>
                <Button @click="showInternalNotes = false" variant="ghost" size="sm">
                  Hide
                </Button>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="note in ticket.internal_notes" :key="note.id" 
                     :id="`note-${note.id}`"
                     class="bg-yellow-50 p-4 rounded border-l-4 border-yellow-400">
                  <p class="text-sm whitespace-pre-wrap">{{ note.body }}</p>
                  <p class="text-xs text-gray-500 mt-2">
                    {{ note.author?.name }} at {{ note.created_at }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card v-if="ticket.linked_articles?.length">
            <CardHeader>
              <CardTitle>Linked Knowledge Base Articles</CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-2">
                <li v-for="article in ticket.linked_articles" :key="article.id">
                  <a :href="`/support/knowledge-base/${article.id}`" class="text-blue-600 hover:underline">
                    {{ article.title }}
                  </a>
                </li>
              </ul>
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <label class="text-sm font-medium">Change Status</label>
                <select v-model="form.status" class="w-full mt-1 p-2 border rounded">
                  <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
                <Button @click="submitStatusChange" class="w-full mt-2" size="sm">
                  Update Status
                </Button>
              </div>

              <div v-if="['in_progress', 'open'].includes(ticket.status)">
                <label class="text-sm font-medium">Resolution Note</label>
                <Textarea v-model="form.resolution_note" placeholder="Enter resolution details..." />
                <Button @click="resolveTicket" class="w-full mt-2" size="sm">
                  Resolve Ticket
                </Button>
              </div>

              <div v-if="ticket.rating">
                <label class="text-sm font-medium">Customer Rating</label>
                <p>Score: {{ ticket.rating.score }}/5</p>
                <p v-if="ticket.rating.comment" class="text-sm">{{ ticket.rating.comment }}</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Related Tickets</CardTitle>
            </CardHeader>
            <CardContent>
              <ul class="space-y-2">
                <li v-for="related in ticket.related_tickets" :key="related.id">
                  <a :href="`/support/tickets/${related.id}`" class="text-blue-600 hover:underline">
                    {{ related.subject }}
                  </a>
                </li>
                <li v-if="!ticket.related_tickets?.length" class="text-gray-500">
                  No related tickets.
                </li>
              </ul>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>