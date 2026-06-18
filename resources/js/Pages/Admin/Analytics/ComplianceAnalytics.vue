<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Shield, Users, Filter, AlertTriangle } from 'lucide-vue-next'

const props = defineProps<{
  role?: string
  tab?: string
  filters?: Record<string, any>
  audit_stats?: {
    total_events: number
    event_breakdown?: Array<{ event: string; count: number }>
    top_users?: Array<{ user_id: string; name: string; count: number }>
    daily_activity?: Array<{ date: string; count: number }>
  }
  anomalies?: Array<{
    id: string
    user_id?: string
    event_type: string
    description: string
    detected_at?: string
    severity: string
    acknowledged_at?: string | null
    metadata?: Record<string, any>
  }>
  audit_trail?: Array<any> | { data?: Array<any> }
  retention_settings?: { audit_retention_months: number }
  last_calculated?: string
}>()

const selectedTab = ref(props.tab ?? 'anomalies')
const filters = ref<Record<string, any>>({ ...props.filters })

const tabs = [
  { id: 'anomalies', label: 'Anomalies' },
  { id: 'audit_trail', label: 'Audit Trail' },
]

const applyFilters = () => {
  router.get('/admin/analytics/compliance', { ...filters.value, tab: selectedTab.value }, { preserveState: true, preserveScroll: true })
}

const acknowledge = (id: string) => {
  const note = window.prompt('Acknowledgement note') ?? undefined
  router.post(`/api/v1/audit-anomalies/${id}/acknowledge`, { note }, { preserveState: true })
}

const auditTrailEntries = computed(() => Array.isArray(props.audit_trail) ? props.audit_trail : (props.audit_trail?.data ?? []))

const severityVariant = (severity?: string) => severity === 'critical' ? 'destructive' : 'secondary'
</script>

<template>
  <AppLayout>
    <Head title="Compliance & GRC Intelligence" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Compliance & GRC Intelligence</h1>
          <p class="text-gray-500">Audit trails and anomaly detection</p>
        </div>
        <Button variant="outline" size="sm" @click="applyFilters">
          <Filter class="h-4 w-4 mr-2" />
          Apply
        </Button>
      </div>

      <Card>
        <CardHeader><CardTitle>Filters</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <Input class="rounded border px-3 py-2" type="date" v-model="filters.date_from" />
            <Input class="rounded border px-3 py-2" type="date" v-model="filters.date_to" />
            <Input class="rounded border px-3 py-2" v-model="filters.user_id" placeholder="User ID" />
            <Input class="rounded border px-3 py-2" v-model="filters.event_type" placeholder="Event type" />
            <Input class="rounded border px-3 py-2" v-model="filters.ip_address" placeholder="IP address" />
            <select v-model="filters.acknowledged" class="rounded border px-3 py-2">
              <option value="">All alerts</option>
              <option value="true">Acknowledged</option>
              <option value="false">Unacknowledged</option>
            </select>
          </div>
          <p class="mt-2 text-xs text-gray-500">Retention: {{ props.retention_settings?.audit_retention_months ?? 84 }} months</p>
        </CardContent>
      </Card>

      <div class="flex gap-2 border-b">
        <Button v-for="tab in tabs" :key="tab.id" variant="ghost" :class="{ 'border-b-2 border-gray-900 rounded-none': selectedTab === tab.id }" @click="selectedTab = tab.id; applyFilters()">
          {{ tab.label }}
        </Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card>
          <CardHeader><CardTitle>Total Events</CardTitle></CardHeader>
          <CardContent><p class="text-3xl font-bold">{{ Number(props.audit_stats?.total_events ?? 0).toLocaleString() }}</p></CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Event Breakdown</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <div v-for="event in props.audit_stats?.event_breakdown" :key="event.event" class="flex justify-between">
              <span class="text-sm text-gray-600">{{ event.event }}</span><Badge>{{ event.count }}</Badge>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Anomaly Alerts</CardTitle></CardHeader>
          <CardContent>
            <div class="flex items-center gap-3">
              <AlertTriangle class="h-5 w-5 text-amber-500" />
              <span class="text-sm">{{ props.anomalies?.length ?? 0 }} active alerts</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card v-if="selectedTab === 'anomalies'">
        <CardHeader><CardTitle>Anomaly Alerts</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Severity</TableHead>
                <TableHead>User</TableHead>
                <TableHead>Event Type</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Detected At</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="alert in props.anomalies" :key="alert.id">
                <TableCell><Badge :variant="severityVariant(alert.severity)">{{ alert.severity }}</Badge></TableCell>
                <TableCell>{{ alert.user_id ?? '-' }}</TableCell>
                <TableCell>{{ alert.event_type }}</TableCell>
                <TableCell>{{ alert.description }}</TableCell>
                <TableCell>{{ alert.detected_at ?? '-' }}</TableCell>
                <TableCell>{{ alert.acknowledged_at ? 'Acknowledged' : 'Unacknowledged' }}</TableCell>
                <TableCell>
                  <Button v-if="!alert.acknowledged_at" variant="outline" size="sm" @click="acknowledge(alert.id)">Acknowledge</Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-if="!props.anomalies?.length" class="text-sm text-gray-500">No anomaly alerts match the selected filters.</p>
        </CardContent>
      </Card>

      <Card v-else>
        <CardHeader><CardTitle>Audit Trail</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Event</TableHead>
                <TableHead>Model</TableHead>
                <TableHead>IP</TableHead>
                <TableHead>Created At</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="entry in auditTrailEntries" :key="entry.id">
                <TableCell>{{ entry.event }}</TableCell>
                <TableCell>{{ entry.subject_type ?? '-' }}</TableCell>
                <TableCell>{{ entry.properties?.ip_address ?? '-' }}</TableCell>
                <TableCell>{{ entry.created_at }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Top Users by Activity</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow><TableHead><Users class="h-4 w-4 inline mr-2" />User</TableHead><TableHead class="text-right">Events</TableHead></TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in props.audit_stats?.top_users" :key="user.user_id">
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell class="text-right"><Badge>{{ user.count }}</Badge></TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle><Shield class="h-5 w-5 inline mr-2" />Daily Audit Timeline</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 md:grid-cols-7 gap-2">
            <div v-for="day in props.audit_stats?.daily_activity" :key="day.date" class="rounded border p-3 text-center">
              <p class="text-xs text-gray-500">{{ day.date }}</p>
              <p class="text-xl font-bold">{{ day.count }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
