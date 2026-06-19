<script setup lang="ts">
import { ref, onMounted } from 'vue'
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
import { BarChart3, Users, FileText, Ticket } from 'lucide-vue-next'
import { formatDistanceToNow } from 'date-fns'

const activeTab = ref('dashboard')
const stats = ref<any>(null)
const history = ref<any[]>([])
const ivrInteractions = ref<any[]>([])
const tickets = ref<any[]>([])
const pollTimer = ref<number | null>(null)

onMounted(async () => {
  await loadStats()
  await loadHistory()
  pollTimer.value = window.setInterval(loadStats, 5000)
})

const loadStats = async () => {
  try {
    const res = await fetch('/api/v1/contact-centre/stats', { headers: { 'Accept': 'application/json' } })
    if (res.ok) stats.value = await res.json()
  } catch (e) {}
}

const loadHistory = async () => {
  try {
    const res = await fetch('/api/v1/contact-centre/history?hours=24', { headers: { 'Accept': 'application/json' } })
    if (res.ok) history.value = await res.json()
  } catch (e) {}
}

const formatWait = (seconds: number) => {
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}m ${s}s`
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
        <TabsList class="grid w-full grid-cols-4">
          <TabsTrigger value="dashboard" class="flex items-center gap-2">
            <BarChart3 class="h-4 w-4" />
            Dashboard
          </TabsTrigger>
          <TabsTrigger value="contact-center" class="flex items-center gap-2">
            <Users class="h-4 w-4" />
            Queue Stats
          </TabsTrigger>
          <TabsTrigger value="ivr" class="flex items-center gap-2">
            <FileText class="h-4 w-4" />
            IVR
          </TabsTrigger>
          <TabsTrigger value="tickets" class="flex items-center gap-2">
            <Ticket class="h-4 w-4" />
            Tickets
          </TabsTrigger>
        </TabsList>

        <TabsContent value="dashboard" class="mt-4">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <Card v-for="item in [{ label: 'Open Tickets', value: 0, icon: Ticket, color: 'text-blue-500' },
              { label: 'Calls Today', value: 0, icon: BarChart3, color: 'text-emerald-500' },
              { label: 'Chat Active', value: 0, icon: Users, color: 'text-purple-500' },
              { label: 'SMS Sent', value: 0, icon: Users, color: 'text-amber-500' },
              { label: 'Kiosk', value: 0, icon: Users, color: 'text-teal-500' }]" :key="item.label">
              <CardContent class="pt-6 flex items-center gap-4">
                <component :is="item.icon" class="h-8 w-8" :class="item.color" />
                <div>
                  <p class="text-xs text-gray-500">{{ item.label }}</p>
                  <p class="text-2xl font-bold">{{ item.value }}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="contact-center" class="mt-4">
          <div v-if="stats" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <Card>
              <CardContent class="pt-6 flex items-center gap-3">
                <Users class="h-5 w-5 text-blue-500" />
                <div>
                  <p class="text-xs text-gray-500">Open Interactions</p>
                  <p class="text-xl font-bold">{{ stats.total_open }}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6 flex items-center gap-3">
                <Users class="h-5 w-5 text-red-500" />
                <div>
                  <p class="text-xs text-gray-500">SLA Risk</p>
                  <p class="text-xl font-bold">{{ stats.sla_breach_risk }}</p>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6 flex items-center gap-3">
                <Users class="h-5 w-5 text-emerald-500" />
                <div>
                  <p class="text-xs text-gray-500">Avg Wait</p>
                  <p class="text-xl font-bold">{{ formatWait(stats.avg_unassigned_wait_seconds || 0) }}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

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
                    <TableCell colspan="2" class="p-8 text-center text-gray-500">No IVR transcripts.</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="tickets" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Tickets</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Subject</TableHead>
                    <TableHead class="p-4">Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell colspan="2" class="p-8 text-center text-gray-500">No tickets.</TableCell>
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