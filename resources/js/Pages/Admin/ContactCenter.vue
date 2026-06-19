<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Users, Phone, MessageSquare, Mail, AlertTriangle, ArrowUp, ArrowDown, Minus } from 'lucide-vue-next'
import { formatDistanceToNow } from 'date-fns'

const stats = ref<any>(null)
const history = ref<any[]>([])
const prevTotalOpen = ref<number | null>(null)
const pollTimer = ref<number | null>(null)

onMounted(async () => {
  await loadStats()
  await loadHistory()
  pollTimer.value = window.setInterval(loadStats, 5000)
})

onUnmounted(() => {
  if (pollTimer.value) window.clearInterval(pollTimer.value)
})

const loadStats = async () => {
  const res = await fetch('/api/v1/contact-centre/stats', { headers: { 'Accept': 'application/json' } })
  if (res.ok) {
    const data = await res.json()
    if (prevTotalOpen.value !== null && stats.value) {
      stats.value.delta_total_open = data.total_open - prevTotalOpen.value
    }
    prevTotalOpen.value = data.total_open
    stats.value = data
  }
}

const loadHistory = async () => {
  const res = await fetch('/api/v1/contact-centre/history?hours=24', { headers: { 'Accept': 'application/json' } })
  if (res.ok) {
    history.value = await res.json()
  }
}

const iconForChannel = (channel: string) => {
  if (channel === 'call') return Phone
  if (channel === 'chat') return MessageSquare
  if (channel === 'email') return Mail
  return MessageSquare
}

const deltaBadge = (delta: number) => {
  if (delta > 0) return { variant: 'destructive' as const, icon: ArrowUp, label: `+${delta}` }
  if (delta < 0) return { variant: 'default' as const, icon: ArrowDown, label: `${delta}` }
  return { variant: 'outline' as const, icon: Minus, label: '0' }
}

const formatWait = (seconds: number) => {
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}m ${s}s`
}
</script>

<template>
  <AppLayout>
    <Head title="Contact Center - Queue Stats" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Queue Stats</h1>
          <p class="text-gray-500">Real-time contact centre workload and SLA monitoring.</p>
        </div>
        <Badge variant="outline">Auto-refresh: 5s</Badge>
      </div>

      <div v-if="stats" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <Users class="h-5 w-5 text-blue-500" />
            <div>
              <p class="text-xs text-gray-500">Open Interactions</p>
              <p class="text-xl font-bold">{{ stats.total_open }}</p>
              <div class="flex items-center gap-1 mt-1">
                <component :is="deltaBadge(stats.delta_total_open ?? 0).icon" class="h-3 w-3" />
                <Badge :variant="deltaBadge(stats.delta_total_open ?? 0).variant" class="text-[10px]">
                  {{ deltaBadge(stats.delta_total_open ?? 0).label }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <AlertTriangle class="h-5 w-5 text-red-500" />
            <div>
              <p class="text-xs text-gray-500">SLA Risk</p>
              <p class="text-xl font-bold">{{ stats.sla_breach_risk }}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <Phone class="h-5 w-5 text-emerald-500" />
            <div>
              <p class="text-xs text-gray-500">Avg Wait</p>
              <p class="text-xl font-bold">{{ formatWait(stats.avg_unassigned_wait_seconds || 0) }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>By Channel</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="item in stats?.by_channel" :key="item.channel" class="flex items-center justify-between border rounded-lg p-3">
                <div class="flex items-center gap-2">
                  <component :is="iconForChannel(item.channel)" class="h-4 w-4 text-gray-500" />
                  <span class="text-sm font-medium capitalize">{{ item.channel }}</span>
                </div>
                <Badge>{{ item.count }}</Badge>
              </div>
              <div v-if="!stats?.by_channel?.length" class="text-sm text-gray-500 text-center py-4">No data.</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Per Agent</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="agent in stats?.per_agent" :key="agent.agent_id" class="flex items-center justify-between border rounded-lg p-3">
                <div>
                  <p class="text-sm font-medium">{{ agent.agent_name }}</p>
                  <p class="text-xs text-gray-500">ID: {{ agent.agent_id }}</p>
                </div>
                <Badge :variant="agent.open_count > 3 ? 'destructive' : 'default'">{{ agent.open_count }}</Badge>
              </div>
              <div v-if="!stats?.per_agent?.length" class="text-sm text-gray-500 text-center py-4">No agents.</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Historical Queue (last 24h)</CardTitle></CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="row in history.slice(-20)" :key="row.recorded_at" class="flex items-center justify-between text-sm border rounded p-2">
              <span class="text-gray-500">{{ new Date(row.recorded_at).toLocaleTimeString() }}</span>
              <Badge variant="outline">open: {{ row.total_open }}</Badge>
              <span class="text-xs text-gray-500">wait: {{ Math.round(row.avg_wait_seconds || 0) }}s</span>
            </div>
            <div v-if="!history.length" class="text-sm text-gray-500 text-center py-4">No history yet.</div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
