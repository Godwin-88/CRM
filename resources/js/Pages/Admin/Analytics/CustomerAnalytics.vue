<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Users, TrendingUp, RefreshCw, Filter } from 'lucide-vue-next'

const props = defineProps<{
  role?: string
  tab?: string
  filters?: Record<string, any>
  segment_performance?: Array<{
    id: string
    name: string
    contact_count: number
    average_clv: number
    average_deal_value: number
    average_csat_score: number
    open_ticket_count: number
    campaign_engagement_rate: number
    last_campaign?: { name?: string; started_at?: string } | null
  }>
  cohort_retention?: Array<Record<string, any>>
  churn_risk?: Array<{
    id: string
    name: string
    account?: string
    assigned_agent?: string
    days_since_last_interaction?: number | null
    open_ticket_count: number
    last_nps_score?: number | null
    churn_risk_score: number
  }>
  customer_journey?: Array<{ stage: string; contact_count: number; average_duration_minutes: number }>
  contact_types?: string[]
  loyalty_tiers?: string[]
  owners?: string[]
  last_calculated_at?: string
}>()

const selectedTab = ref(props.tab ?? 'segment_performance')
const filters = ref<Record<string, any>>({ ...props.filters })

const tabs = [
  { id: 'segment_performance', label: 'Segment Performance' },
  { id: 'cohort_retention', label: 'Cohort Retention' },
  { id: 'churn_risk', label: 'Churn Risk' },
]

const applyFilters = () => {
  router.get('/admin/analytics/customer', { ...filters.value, tab: selectedTab.value }, { preserveState: true, preserveScroll: true })
}

const money = (value?: number | string) => Number(value ?? 0).toLocaleString()
</script>

<template>
  <AppLayout>
    <Head title="Customer Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Customer Analytics</h1>
          <p class="text-gray-500">Segment performance, cohort retention, and churn risk</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="applyFilters">
            <RefreshCw class="h-4 w-4 mr-2" />
            Refresh
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <select v-model="filters.contact_type" class="rounded border px-3 py-2">
              <option value="">All contact types</option>
              <option v-for="type in props.contact_types" :key="type" :value="type">{{ type }}</option>
            </select>
            <select v-model="filters.loyalty_tier" class="rounded border px-3 py-2">
              <option value="">All loyalty tiers</option>
              <option v-for="tier in props.loyalty_tiers" :key="tier" :value="tier">{{ tier }}</option>
            </select>
            <select v-model="filters.owner_id" class="rounded border px-3 py-2">
              <option value="">All owners</option>
              <option v-for="owner in props.owners" :key="owner" :value="owner">{{ owner }}</option>
            </select>
            <Input class="rounded border px-3 py-2" type="date" v-model="filters.date_from" />
            <Input class="rounded border px-3 py-2" type="date" v-model="filters.date_to" />
          </div>
          <p class="mt-2 text-xs text-gray-500">Last calculation: {{ props.last_calculated_at ?? 'Nightly analytics job' }}</p>
        </CardContent>
      </Card>

      <div class="flex gap-2 border-b">
        <Button v-for="tab in tabs" :key="tab.id" variant="ghost" :class="{ 'border-b-2 border-gray-900 rounded-none': selectedTab === tab.id }" @click="selectedTab = tab.id; applyFilters()">
          {{ tab.label }}
        </Button>
      </div>

      <Card v-if="selectedTab === 'segment_performance'">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><Users class="h-5 w-5" /> Segment Performance</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Segment</TableHead>
                <TableHead class="text-right">Contacts</TableHead>
                <TableHead class="text-right">Avg CLV</TableHead>
                <TableHead class="text-right">Avg Deal Value</TableHead>
                <TableHead class="text-right">Avg CSAT</TableHead>
                <TableHead class="text-right">Open Tickets</TableHead>
                <TableHead class="text-right">Engagement</TableHead>
                <TableHead>Last Campaign</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="segment in props.segment_performance" :key="segment.id">
                <TableCell class="font-medium">{{ segment.name }}</TableCell>
                <TableCell class="text-right">{{ segment.contact_count }}</TableCell>
                <TableCell class="text-right">{{ money(segment.average_clv) }}</TableCell>
                <TableCell class="text-right">{{ money(segment.average_deal_value) }}</TableCell>
                <TableCell class="text-right">{{ segment.average_csat_score }}</TableCell>
                <TableCell class="text-right">{{ segment.open_ticket_count }}</TableCell>
                <TableCell class="text-right">{{ segment.campaign_engagement_rate }}%</TableCell>
                <TableCell>{{ segment.last_campaign?.name ?? '-' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card v-else-if="selectedTab === 'cohort_retention'">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><TrendingUp class="h-5 w-5" /> Cohort Retention</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b text-left text-gray-500">
                  <th class="p-2">Cohort Month</th>
                  <th class="p-2 text-right">Cohort Size</th>
                  <th class="p-2 text-right">Month 1</th>
                  <th class="p-2 text-right">Month 3</th>
                  <th class="p-2 text-right">Month 6</th>
                  <th class="p-2 text-right">Month 12</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="cohort in props.cohort_retention" :key="cohort.cohort_month" class="border-b">
                  <td class="p-2 font-medium">{{ cohort.cohort_month }}</td>
                  <td class="p-2 text-right">{{ cohort.cohort_size }}</td>
                  <td class="p-2 text-right">{{ cohort.month_1 ?? 0 }}%</td>
                  <td class="p-2 text-right">{{ cohort.month_3 ?? 0 }}%</td>
                  <td class="p-2 text-right">{{ cohort.month_6 ?? 0 }}%</td>
                  <td class="p-2 text-right">{{ cohort.month_12 ?? 0 }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card v-else>
        <CardHeader><CardTitle>Churn Risk</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Contact</TableHead>
                <TableHead>Account</TableHead>
                <TableHead>Assigned Agent</TableHead>
                <TableHead class="text-right">Days Since Last Interaction</TableHead>
                <TableHead class="text-right">Open Tickets</TableHead>
                <TableHead class="text-right">Last NPS</TableHead>
                <TableHead class="text-right">Risk Score</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="contact in props.churn_risk" :key="contact.id">
                <TableCell class="font-medium">{{ contact.name }}</TableCell>
                <TableCell>{{ contact.account ?? '-' }}</TableCell>
                <TableCell>{{ contact.assigned_agent ?? '-' }}</TableCell>
                <TableCell class="text-right">{{ contact.days_since_last_interaction ?? '-' }}</TableCell>
                <TableCell class="text-right">{{ contact.open_ticket_count }}</TableCell>
                <TableCell class="text-right">{{ contact.last_nps_score ?? '-' }}</TableCell>
                <TableCell class="text-right">
                  <Badge :variant="contact.churn_risk_score >= 75 ? 'destructive' : 'secondary'">{{ contact.churn_risk_score }}</Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-if="!props.churn_risk?.length" class="text-sm text-gray-500">No high-risk contacts found.</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
