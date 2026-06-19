<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'
import { ref } from 'vue'

const props = defineProps<{
  agentMetrics: Array<{
    agent_id: string
    agent_name: string
    agent_email: string
    tickets_created: number
    tickets_resolved: number
    tickets_closed: number
    avg_first_response_hours: number
    avg_resolution_hours: number
    sla_breach_count: number
    sla_breach_rate: number
    avg_csat_score: number
  }>
  teamMetrics: {
    tickets_created: number
    tickets_resolved: number
    tickets_closed: number
    sla_breach_count: number
    sla_breach_rate: number
    avg_csat_score: number
    trends: {
      tickets_created_change: { direction: string; percent: number }
      sla_breach_rate_change: { direction: string; percent: number }
    }
  }
  teams: Array<{ id: string; name: string }>
  categories: Array<{ id: string; name: string }>
  filters: {
    range: string
    team_id: string | null
    custom_start: string | null
    custom_end: string | null
  }
}>()

const dateRanges = [
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
  { value: 'last_7_days', label: 'Last 7 Days' },
  { value: 'last_30_days', label: 'Last 30 Days' },
  { value: 'this_month', label: 'This Month' },
  { value: 'last_month', label: 'Last Month' },
  { value: 'custom', label: 'Custom Range' },
]

const selectedRange = ref(props.filters.range)
const selectedTeam = ref(props.filters.team_id || '')
const customStart = ref(props.filters.custom_start || '')
const customEnd = ref(props.filters.custom_end || '')

const applyFilters = () => {
  router.get(route('support.performance.index'), {
    range: selectedRange.value,
    team_id: selectedTeam.value,
    custom_start: selectedRange.value === 'custom' ? customStart.value : null,
    custom_end: selectedRange.value === 'custom' ? customEnd.value : null,
  })
}

const exportCsv = () => {
  router.get(route('support.performance.export'), {
    range: selectedRange.value,
    team_id: selectedTeam.value,
  })
}

const getTrendIcon = (trend: { direction: string }) => {
  if (trend.direction === 'up') return '↑'
  if (trend.direction === 'down') return '↓'
  return '→'
}

const getTrendColor = (trend: { direction: string }) => {
  if (trend.direction === 'up') return 'text-red-600'
  if (trend.direction === 'down') return 'text-green-600'
  return 'text-gray-500'
}
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Agent Performance</h1>
        <p class="text-gray-500">Monitor team output and individual agent metrics.</p>
      </div>
      <div class="flex items-center gap-2">
        <select v-model="selectedRange" class="p-2 border rounded" @change="applyFilters">
          <option v-for="opt in dateRanges" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
        <select v-model="selectedTeam" class="p-2 border rounded" @change="applyFilters">
          <option value="">All Teams</option>
          <option v-for="team in teams" :key="team.id" :value="team.id">
            {{ team.name }}
          </option>
        </select>
        <Button @click="exportCsv" variant="outline">Export CSV</Button>
      </div>
    </div>

    <!-- Custom Date Range Picker -->
    <div v-if="selectedRange === 'custom'" class="flex gap-2">
      <input v-model="customStart" type="date" class="p-2 border rounded" />
      <input v-model="customEnd" type="date" class="p-2 border rounded" />
      <Button @click="applyFilters">Apply</Button>
    </div>

    <!-- Team Summary -->
    <Card class="bg-blue-50">
      <CardHeader>
        <CardTitle>Team Summary</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-sm text-gray-500">Tickets Created</p>
            <p class="text-2xl font-bold">{{ teamMetrics.tickets_created }}</p>
            <span :class="['text-xs', getTrendColor(teamMetrics.trends.tickets_created_change)]">
              {{ getTrendIcon(teamMetrics.trends.tickets_created_change) }} {{ teamMetrics.trends.tickets_created_change.percent }}%
            </span>
          </div>
          <div>
            <p class="text-sm text-gray-500">Resolution Rate</p>
            <p class="text-2xl font-bold">
              {{ teamMetrics.tickets_created > 0
                ? Math.round((teamMetrics.tickets_resolved / teamMetrics.tickets_created) * 100)
                : 0 }}%
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">SLA Breach Rate</p>
            <p class="text-2xl font-bold">
<Badge :variant="teamMetrics.sla_breach_rate > 5 ? 'destructive' : 'success'">
              {{ teamMetrics.sla_breach_rate }}%
            </Badge>
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Avg CSAT</p>
            <p class="text-2xl font-bold">{{ teamMetrics.avg_csat_score }}/5</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Agent Metrics Table -->
    <Card>
      <CardContent class="p-0">
        <Table>
          <TableHead>
            <TableRow>
              <TableHead>Agent</TableHead>
              <TableHead>Created</TableHead>
              <TableHead>Resolved</TableHead>
              <TableHead>Closed</TableHead>
              <TableHead>Avg Response</TableHead>
              <TableHead>Avg Resolution</TableHead>
              <TableHead>Breach Rate</TableHead>
              <TableHead>CSAT</TableHead>
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow v-for="agent in agentMetrics" :key="agent.agent_id">
              <TableCell>
                <div>
                  <p class="font-medium">{{ agent.agent_name }}</p>
                  <p class="text-xs text-gray-500">{{ agent.agent_email }}</p>
                </div>
              </TableCell>
              <TableCell>{{ agent.tickets_created }}</TableCell>
              <TableCell>{{ agent.tickets_resolved }}</TableCell>
              <TableCell>{{ agent.tickets_closed }}</TableCell>
              <TableCell>{{ agent.avg_first_response_hours }} hrs</TableCell>
              <TableCell>{{ agent.avg_resolution_hours }} hrs</TableCell>
              <TableCell>
                <Badge :variant="agent.sla_breach_rate > 5 ? 'destructive' : 'success'">
                  {{ agent.sla_breach_rate }}%
                </Badge>
              </TableCell>
              <TableCell>{{ agent.avg_csat_score }}/5</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>