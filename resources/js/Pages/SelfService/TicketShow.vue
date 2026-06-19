<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { ThumbsUp, ThumbsDown, Send } from 'lucide-vue-next'

const props = defineProps<{
  ticket: {
    id: string
    subject: string
    description: string
    priority: string
    status: string
    created_at: string
    resolved_at: string | null
    closed_at: string | null
    sla_breached_at: string | null
    category: { name: string }
    rating?: { score: number; comment: string }
    interactions: Array<{
      id: string
      direction: 'inbound' | 'outbound'
      subject: string
      body: string
      created_at: string
    }>
  }
  cannedResponses: Array<{
    id: string
    title: string
    body: string
    category_tag: string
  }>
}>()

const getStatusVariant = (status: string) => {
  if (status === 'open' || status === 'in_progress') return 'destructive'
  if (status === 'waiting_on_customer') return 'secondary'
  return 'outline'
}

const rateTicket = (score: number) => {
  router.post(route('self-service.tickets.rate', [props.ticket.id, score]))
}
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
        <Badge :variant="getStatusVariant(ticket.status)">
          {{ ticket.status.replace('_', ' ') }}
        </Badge>
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

          <Card v-if="ticket.interactions?.length">
            <CardHeader>
              <CardTitle>Conversation History</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="interaction in ticket.interactions" :key="interaction.id" class="border-l-4 pl-4"
                     :class="interaction.direction === 'outbound' ? 'border-blue-500' : 'border-gray-300'">
                  <p class="text-sm font-medium" :class="interaction.direction === 'outbound' ? 'text-blue-600' : 'text-gray-600'">
                    {{ interaction.direction === 'outbound' ? 'Agent' : 'You' }}
                  </p>
                  <p class="text-sm mt-1 whitespace-pre-wrap">{{ interaction.body }}</p>
                  <p class="text-xs text-gray-500">{{ interaction.created_at }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card v-if="!ticket.rating && ticket.status === 'resolved'">
            <CardHeader>
              <CardTitle>Rate Your Experience</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm text-gray-500 mb-3">How would you rate the support you received?</p>
              <div class="flex gap-2">
                <Button
                  v-for="n in 5"
                  :key="n"
                  @click="rateTicket(n)"
                  variant="outline"
                  size="sm"
                >
                  {{ n }}
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card v-if="ticket.rating">
            <CardHeader>
              <CardTitle>Your Rating</CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-2xl font-bold">{{ ticket.rating.score }}/5</p>
              <p v-if="ticket.rating.comment" class="text-sm mt-2">{{ ticket.rating.comment }}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Ticket Info</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
              <div><span class="text-gray-500">Priority:</span> {{ ticket.priority }}</div>
              <div><span class="text-gray-500">Category:</span> {{ ticket.category?.name }}</div>
              <div><span class="text-gray-500">Created:</span> {{ ticket.created_at }}</div>
              <div v-if="ticket.resolved_at"><span class="text-gray-500">Resolved:</span> {{ ticket.resolved_at }}</div>
              <div v-if="ticket.closed_at"><span class="text-gray-500">Closed:</span> {{ ticket.closed_at }}</div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>