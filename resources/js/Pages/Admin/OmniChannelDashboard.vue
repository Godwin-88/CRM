<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { format } from 'date-fns'
import { Phone, MessageSquare, Mail, Ticket } from 'lucide-vue-next'

const props = defineProps<{
  stats: { open_tickets: number; calls_today: number; chat_active: number; sms_sent_today: number; kiosk_integrations: number }
  recentInteractions: { id: string; channel: { name: string }; contact?: { first_name: string; last_name: string }; direction: string; created_at: string }[]
  recentTickets: { id: string; subject: string; priority: string; status: string; contact?: { first_name: string; last_name: string } }[]
}>()

const stats = ref(props.stats)
const recentInteractions = ref(props.recentInteractions)
const recentTickets = ref(props.recentTickets)
const filter = ref<'all' | 'open' | 'in_progress' | 'resolved'>('all')

const filteredTickets = computed(() => {
  if (filter.value === 'all') return recentTickets.value
  return recentTickets.value.filter(t => t.status === filter.value)
})

const priorityVariant = (p: string) => p === 'critical' ? 'destructive' : p === 'high' ? 'default' : 'secondary'
const iconFor = (name: string) => {
  if (name.toLowerCase().includes('phone')) return Phone
  if (name.toLowerCase().includes('chat')) return MessageSquare
  if (name.toLowerCase().includes('email')) return Mail
  return MessageSquare
}
</script>

<template>
  <AppLayout>
    <Head title="OmniChannel Dashboard" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">OmniChannel Dashboard</h1>
        <p class="text-gray-500">Monitor contact center activity across channels.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card v-for="item in [
          { label: 'Open Tickets', value: stats.open_tickets, icon: Ticket, color: 'text-blue-500' },
          { label: 'Calls Today', value: stats.calls_today, icon: Phone, color: 'text-emerald-500' },
          { label: 'Chat Active', value: stats.chat_active, icon: MessageSquare, color: 'text-purple-500' },
          { label: 'SMS Sent', value: stats.sms_sent_today, icon: Mail, color: 'text-amber-500' },
          { label: 'Kiosk Integrations', value: stats.kiosk_integrations, icon: MessageSquare, color: 'text-teal-500' },
        ]" :key="item.label">
          <CardContent class="pt-6 flex items-center gap-4">
            <component :is="item.icon" class="h-8 w-8" :class="item.color" />
            <div>
              <p class="text-xs text-gray-500">{{ item.label }}</p>
              <p class="text-2xl font-bold">{{ item.value }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>Recent Interactions</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="item in recentInteractions.slice(0, 8)" :key="item.id" class="flex items-center justify-between p-3 border rounded-lg">
                <div class="flex items-center gap-3">
                  <component :is="iconFor(item.channel.name)" class="h-4 w-4 text-gray-500" />
                  <div>
                    <p class="text-sm font-medium">{{ item.subject }}</p>
                    <p class="text-xs text-gray-500">{{ item.contact ? `${item.contact.first_name} ${item.contact.last_name}` : 'Unknown' }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500">{{ format(new Date(item.created_at), 'MMM dd HH:mm') }}</p>
                  <Badge :variant="item.direction === 'inbound' ? 'secondary' : 'outline'" class="mt-1">{{ item.direction }}</Badge>
                </div>
              </div>
              <div v-if="!recentInteractions.length" class="text-sm text-gray-500 text-center py-8">No recent interactions.</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Recent Tickets</CardTitle>
            <div class="flex gap-2">
              <Button v-for="f in ['all','open','in_progress','resolved']" :key="f" size="sm" :variant="filter === f ? 'default' : 'ghost'" @click="filter = f as any">{{ f.replace('_', ' ') }}</Button>
            </div>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="t in filteredTickets" :key="t.id" class="flex items-center justify-between p-3 border rounded-lg">
                <div>
                  <p class="text-sm font-medium">{{ t.subject }}</p>
                  <p class="text-xs text-gray-500">{{ t.contact?.first_name }} {{ t.contact?.last_name }}</p>
                </div>
                <div class="text-right">
                  <Badge :variant="t.status === 'open' ? 'destructive' : t.status === 'resolved' ? 'secondary' : 'default'">{{ t.status }}</Badge>
                  <Badge :variant="priorityVariant(t.priority)" class="ml-2">{{ t.priority }}</Badge>
                </div>
              </div>
              <div v-if="!filteredTickets.length" class="text-sm text-gray-500 text-center py-8">No tickets.</div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
