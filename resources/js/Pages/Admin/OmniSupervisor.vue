<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
} from 'chart.js'
import {
  BarChart3,
  Users,
  FileText,
  Ticket,
  Phone,
  MessageSquare,
  RefreshCw,
} from 'lucide-vue-next'

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
)

const activeTab = ref('dashboard')
const stats = ref<any>(null)
const history = ref<any[]>([])
const interactions = ref<any[]>([])
const interactionsMeta = ref<any>(null)
const agents = ref<any[]>([])
const pollTimer = ref<number | null>(null)
const reassigningId = ref<string | null>(null)

onMounted(async () => {
  await loadStats()
  await loadHistory()
  await loadAgents()
  pollTimer.value = window.setInterval(loadStats, 5000)
})

onUnmounted(() => {
  if (pollTimer.value) window.clearInterval(pollTimer.value)
})

const loadStats = async () => {
  try {
    const res = await fetch('/api/v1/contact-centre/stats', {
      headers: { 'Accept': 'application/json' },
    })
    if (res.ok) stats.value = await res.json()
  } catch (e) {}
}

const loadHistory = async () => {
  try {
    const res = await fetch('/api/v1/contact-centre/history?hours=168', {
      headers: { 'Accept': 'application/json' },
    })
    if (res.ok) history.value = await res.json()
  } catch (e) {}
}

const loadInteractions = async () => {
  try {
    const res = await fetch('/api/v1/contact-centre/interactions?per_page=50', {
      headers: { 'Accept': 'application/json' },
    })
    if (res.ok) {
      const json = await res.json()
      interactions.value = json.data || []
      interactionsMeta.value = json
    }
  } catch (e) {}
}

const loadAgents = async () => {
  try {
    const res = await fetch('/api/v1/users?role=agent', {
      headers: { 'Accept': 'application/json' },
    })
    if (res.ok) {
      const json = await res.json()
      agents.value = json.data || json
    }
  } catch (e) {}
}

const formatWait = (seconds: number) => {
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}m ${s}s`
}

watch(activeTab, (val) => {
  if (val === 'queue-stats') loadInteractions()
})

const reassignInteraction = async (interactionId: string, agentId: string) => {
  reassigningId.value = interactionId
  try {
    await fetch(`/api/v1/contact-centre/interactions/${interactionId}/reassign`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ agent_id: agentId }),
    })
    await loadStats()
    await loadInteractions()
  } catch (e) {}
  reassigningId.value = null
}

const lineChartData = computed(() => {
  const sorted = [...history.value].sort(
    (a, b) => new Date(a.recorded_at).getTime() - new Date(b.recorded_at).getTime()
  )
  return {
    labels: sorted.map((h) =>
      new Date(h.recorded_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
    ),
    datasets: [
      {
        label: 'Open Interactions',
        data: sorted.map((h) => h.total_open),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,0.1)',
        fill: true,
        tension: 0.3,
        pointRadius: 2,
        pointHoverRadius: 4,
      },
    ],
  }
})

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    x: {
      ticks: { maxTicksLimit: 8, font: { size: 11 } },
      grid: { display: false },
    },
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1, font: { size: 11 } },
      grid: { color: 'rgba(0,0,0,0.05)' },
    },
  },
}

const donutData = computed(() => {
  if (!stats.value?.by_channel?.length) {
    return { labels: [], datasets: [{ data: [], backgroundColor: [] }] }
  }
  const palette = [
    '#3b82f6', '#f97316', '#10b981', '#f59e0b', '#8b5cf6',
    '#ec4899', '#06b6d4', '#84cc16', '#6366f1', '#14b8a6',
  ]
  return {
    labels: stats.value.by_channel.map((c: any) => c.channel),
    datasets: [
      {
        data: stats.value.by_channel.map((c: any) => c.count),
        backgroundColor: stats.value.by_channel.map((_: any, i: number) => palette[i % palette.length]),
        borderWidth: 0,
        hoverOffset: 4,
      },
    ],
  }
})

const donutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right' as const,
      labels: { boxWidth: 12, padding: 16, font: { size: 12 } },
    },
  },
}

const barData = computed(() => {
  if (!stats.value?.per_agent?.length) {
    return { labels: [], datasets: [{ label: 'Open', data: [], backgroundColor: '#3b82f6' }] }
  }
  return {
    labels: stats.value.per_agent.map((a: any) => a.agent_name),
    datasets: [
      {
        label: 'Open Interactions',
        data: stats.value.per_agent.map((a: any) => a.open_count),
        backgroundColor: '#3b82f6',
        borderRadius: 4,
      },
    ],
  }
})

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    x: {
      ticks: { font: { size: 11 } },
      grid: { display: false },
    },
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1, font: { size: 11 } },
      grid: { color: 'rgba(0,0,0,0.05)' },
    },
  },
}

const channelLabel: Record<string, string> = {
  call: 'Call',
  email: 'Email',
  chat: 'Live Chat',
  sms: 'SMS',
  whatsapp: 'WhatsApp',
  facebook: 'Facebook',
  linkedin: 'LinkedIn',
  instagram: 'Instagram',
  tiktok: 'TikTok',
  in_person: 'In-Person',
  field_visit: 'Field Visit',
  kiosk: 'Kiosk',
}

const channelColor: Record<string, string> = {
  call: 'bg-emerald-100 text-emerald-700',
  email: 'bg-blue-100 text-blue-700',
  chat: 'bg-purple-100 text-purple-700',
  sms: 'bg-amber-100 text-amber-700',
  whatsapp: 'bg-green-100 text-green-700',
  facebook: 'bg-indigo-100 text-indigo-700',
  linkedin: 'bg-sky-100 text-sky-700',
  instagram: 'bg-pink-100 text-pink-700',
  tiktok: 'bg-gray-100 text-gray-700',
  in_person: 'bg-teal-100 text-teal-700',
  field_visit: 'bg-orange-100 text-orange-700',
  kiosk: 'bg-cyan-100 text-cyan-700',
}
</script>

<template>
  <AppLayout>
    <Head title="OmniChannel Supervisor" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Supervisor</h1>
        <p class="text-gray-500">Monitor and manage contact centre operations.</p>
      </div>

      <Tabs v-model:model-value="activeTab" default-value="dashboard">
        <TabsList class="grid w-full grid-cols-2 md:grid-cols-4">
          <TabsTrigger value="dashboard" class="flex items-center gap-2">
            <BarChart3 class="h-4 w-4" />
            Dashboard
          </TabsTrigger>
          <TabsTrigger value="queue-stats" class="flex items-center gap-2">
            <Users class="h-4 w-4" />
            Queue Stats
          </TabsTrigger>
          <TabsTrigger value="ivr" class="flex items-center gap-2">
            <FileText class="h-4 w-4" />
            IVR
          </TabsTrigger>
          <TabsTrigger value="unmatched" class="flex items-center gap-2">
            <Ticket class="h-4 w-4" />
            Unmatched
          </TabsTrigger>
        </TabsList>

        <!-- DASHBOARD TAB -->
        <TabsContent value="dashboard" class="mt-4 space-y-6">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Card>
              <CardContent class="pt-6">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs text-gray-500">Open Interactions</p>
                    <p class="text-2xl font-bold">{{ stats?.total_open ?? '—' }}</p>
                  </div>
                  <MessageSquare class="h-8 w-8 text-blue-500" />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs text-gray-500">SLA Breach Risk</p>
                    <p class="text-2xl font-bold">{{ stats?.sla_breach_risk ?? '—' }}</p>
                  </div>
                  <Phone class="h-8 w-8 text-red-500" />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs text-gray-500">Avg Wait (Unassigned)</p>
                    <p class="text-2xl font-bold">
                      {{ stats?.avg_unassigned_wait_seconds ? formatWait(stats.avg_unassigned_wait_seconds) : '—' }}
                    </p>
                  </div>
                  <Users class="h-8 w-8 text-amber-500" />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs text-gray-500">Active Channels</p>
                    <p class="text-2xl font-bold">
                      {{ stats?.by_channel?.length ?? (stats ? stats.by_channel.length : '—') }}
                    </p>
                  </div>
                  <BarChart3 class="h-8 w-8 text-purple-500" />
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <CardTitle>7-Day Queue Trend</CardTitle>
                <Button variant="ghost" size="sm" @click="loadHistory">
                  <RefreshCw class="h-4 w-4" />
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div class="h-72">
                <Line v-if="history.length" :data="lineChartData" :options="lineOptions" />
                <p v-else class="text-sm text-gray-500 text-center py-16">No historical data available.</p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- QUEUE STATS TAB -->
        <TabsContent value="queue-stats" class="mt-4 space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Channel Distribution</CardTitle>
              </CardHeader>
              <CardContent>
                <div class="h-72">
                  <Doughnut v-if="stats?.by_channel?.length" :data="donutData" :options="donutOptions" />
                  <p v-else class="text-sm text-gray-500 text-center py-16">No open interactions.</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Interactions per Agent</CardTitle>
              </CardHeader>
              <CardContent>
                <div class="h-72">
                  <Bar v-if="stats?.per_agent?.length" :data="barData" :options="barOptions" />
                  <p v-else class="text-sm text-gray-500 text-center py-16">No agent data.</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <CardTitle>Open Interactions (Team)</CardTitle>
                <Button variant="ghost" size="sm" @click="loadInteractions">
                  <RefreshCw class="h-4 w-4" />
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Channel</TableHead>
                    <TableHead class="p-4">Contact</TableHead>
                    <TableHead class="p-4">Subject</TableHead>
                    <TableHead class="p-4">Agent</TableHead>
                    <TableHead class="p-4">Created</TableHead>
                    <TableHead class="p-4">Reassign</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!interactions.length">
                    <TableCell colspan="6" class="p-8 text-center text-gray-500">
                      No open interactions in the team queue.
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="item in interactions" :key="item.id">
                    <TableCell class="p-4">
                      <Badge :class="channelColor[item.type] || 'bg-gray-100 text-gray-700'">
                        {{ channelLabel[item.type] || item.type }}
                      </Badge>
                    </TableCell>
                    <TableCell class="p-4">
                      {{ item.contact?.first_name && item.contact?.last_name
                        ? item.contact.first_name + ' ' + item.contact.last_name
                        : '—' }}
                    </TableCell>
                    <TableCell class="p-4">{{ item.subject || '—' }}</TableCell>
                    <TableCell class="p-4">{{ item.agent?.name || 'Unassigned' }}</TableCell>
                    <TableCell class="p-4">{{ item.created_at }}</TableCell>
                    <TableCell class="p-4">
                      <Select
                        :model-value="item.agent_id"
                        :disabled="reassigningId === item.id"
                        @update:model-value="(v) => v && reassignInteraction(item.id, String(v))"
                      >
                        <SelectTrigger class="w-40">
                          <SelectValue placeholder="Reassign..." />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="agent in agents" :key="agent.id" :value="agent.id">
                            {{ agent.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- IVR TAB -->
        <TabsContent value="ivr" class="mt-4">
          <Card>
            <CardHeader><CardTitle>IVR Transcriptions</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Contact</TableHead>
                    <TableHead class="p-4">Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell colspan="2" class="p-8 text-center text-gray-500">
                      No IVR transcripts.
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- UNMATCHED TAB -->
        <TabsContent value="unmatched" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Unmatched Items</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Source</TableHead>
                    <TableHead class="p-4">Status</TableHead>
                    <TableHead class="p-4">Created</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell colspan="3" class="p-8 text-center text-gray-500">
                      No unmatched items.
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
