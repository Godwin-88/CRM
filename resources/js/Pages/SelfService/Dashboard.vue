<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  tickets: {
    data: Array<{
      id: string
      subject: string
      status: string
      priority: string
      created_at: string
      resolved_at: string | null
      closed_at: string | null
      sla_breached_at: string | null
      category: { name: string }
      rating?: { score: number }
    }>
    links: any[]
  }
}>()

const getStatusVariant = (status: string) => {
  if (status === 'open' || status === 'in_progress') return 'destructive'
  if (status === 'waiting_on_customer') return 'secondary'
  return 'outline'
}
</script>

<template>
  <AppLayout>
    <Head title="Support Portal" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Support Portal</h1>
          <p class="text-gray-500">View and manage your support tickets.</p>
        </div>
        <Link :href="$route('self-service.tickets.create')">
          <Button>Create Ticket</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Your Tickets</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="tickets.data.length" class="space-y-4">
            <div v-for="ticket in tickets.data" :key="ticket.id" class="border rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <Link :href="$route('self-service.tickets.show', ticket.id)" class="text-lg font-medium text-blue-600 hover:underline">
                    {{ ticket.subject }}
                  </Link>
                  <p class="text-sm text-gray-500">
                    {{ ticket.category?.name }} • {{ ticket.created_at }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <Badge v-if="ticket.rating" variant="success">
                    Rated {{ ticket.rating.score }}/5
                  </Badge>
                  <Badge :variant="getStatusVariant(ticket.status)">
                    {{ ticket.status.replace('_', ' ') }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-gray-500">No tickets found.</p>
            <Link :href="$route('self-service.tickets.create')" class="mt-4 inline-block">
              <Button>Create Your First Ticket</Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      <div class="flex justify-center">
        <div class="flex gap-2">
          <Link
            v-for="link in tickets.links"
            :key="link.url"
            :href="link.url || '#'"
            :class="[
              'px-3 py-1 rounded',
              link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
              !link.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>