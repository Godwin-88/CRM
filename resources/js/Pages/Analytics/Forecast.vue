<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface ForecastData {
  period: string
  period_start: string
  period_end: string
  total_unweighted: number
  total_weighted: number
  best_case_value: number
  target_revenue?: number
  forecast_gap?: number
  forecast_gap_percentage?: number
  gap_status?: string
  by_stage?: Array<{ stage: string; count: number; total_value: number; weighted_value: number; best_case_value: number }>
  deals?: Array<{
    id: string
    deal_name: string
    account?: string
    stage: string
    value: number
    weighted_value: number
    owner?: string
    expected_close_date?: string
  }>
}

const props = defineProps<{
  role?: string
  forecast: ForecastData
  period?: string
  filters?: Record<string, any>
}>()

const forecast = computed(() => props.forecast)
const periodFilter = ref(props.period ?? 'current_quarter')
const filters = ref<Record<string, any>>({
  owner_id: props.filters?.owner_id ?? '',
  team_id: props.filters?.team_id ?? '',
  pipeline_id: props.filters?.pipeline_id ?? '',
  pipeline_stage: props.filters?.pipeline_stage ?? '',
  region: props.filters?.region ?? '',
  close_from: props.filters?.close_from ?? '',
  close_to: props.filters?.close_to ?? '',
})

const loadForecast = () => {
  router.get('/analytics/forecast', {
    ...filters.value,
    period: periodFilter.value,
  }, { preserveState: true, preserveScroll: true })
}

const money = (value?: number | string) => Number(value ?? 0).toLocaleString()
const statusClass = computed(() => {
  if (forecast.value.gap_status === 'green') return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  if (forecast.value.gap_status === 'amber') return 'bg-amber-50 text-amber-700 border-amber-200'
  if (forecast.value.gap_status === 'red') return 'bg-red-50 text-red-700 border-red-200'
  return 'bg-gray-50 text-gray-700 border-gray-200'
})
</script>

<template>
  <AppLayout>
    <Head title="Revenue Forecast" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">Revenue Forecast</h1>
          <p class="text-gray-500">Weighted pipeline forecast, targets, and deal-level breakdown.</p>
        </div>
        <Button @click="loadForecast">Apply Filters</Button>
      </div>

      <Card>
        <CardHeader><CardTitle>Filters</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <select v-model="periodFilter" class="rounded border px-3 py-2">
              <option value="current_month">Current month</option>
              <option value="next_month">Next month</option>
              <option value="current_quarter">Current quarter</option>
              <option value="next_quarter">Next quarter</option>
              <option value="custom">Custom range</option>
            </select>
            <Input v-model="filters.close_from" type="date" placeholder="Close from" />
            <Input v-model="filters.close_to" type="date" placeholder="Close to" />
            <Input v-model="filters.owner_id" placeholder="Owner ID" />
            <Input v-model="filters.pipeline_stage" placeholder="Stage" />
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader><CardTitle>Target Revenue</CardTitle></CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ money(forecast.target_revenue) }}</p>
            <p class="text-sm text-gray-500">{{ forecast.period_start }} to {{ forecast.period_end }}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Weighted Pipeline</CardTitle></CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ money(forecast.total_weighted) }}</p>
            <p class="text-sm text-gray-500">Sum of deal value × stage probability</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Best Case Pipeline</CardTitle></CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ money(forecast.best_case_value) }}</p>
            <p class="text-sm text-gray-500">All open deal value</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Forecast Gap</CardTitle></CardHeader>
        <CardContent>
          <div class="flex flex-wrap items-center justify-between gap-3 rounded border p-4" :class="statusClass">
            <div>
              <p class="text-sm">Gap</p>
              <p class="text-2xl font-bold">{{ money(forecast.forecast_gap) }}</p>
            </div>
            <div>
              <p class="text-sm">Gap Percentage</p>
              <p class="text-2xl font-bold">{{ forecast.forecast_gap_percentage ?? 0 }}%</p>
            </div>
            <Badge :variant="forecast.gap_status === 'green' ? 'default' : 'secondary'">
              {{ forecast.gap_status ?? 'none' }}
            </Badge>
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card>
          <CardHeader><CardTitle>By Stage</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="stage in forecast.by_stage" :key="stage.stage" class="flex items-center justify-between border-b pb-2">
                <div>
                  <p class="font-medium">{{ stage.stage }}</p>
                  <p class="text-xs text-gray-500">{{ stage.count }} deals</p>
                </div>
                <div class="text-right">
                  <p class="font-semibold">{{ money(stage.total_value) }}</p>
                  <p class="text-xs text-gray-500">Weighted {{ money(stage.weighted_value) }}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Historical Win Rates</CardTitle></CardHeader>
          <CardContent>
            <p class="text-sm text-gray-500">Historical win rates are calculated from closed deals in the trailing 12 months.</p>
            <div class="mt-3 text-sm text-gray-500">Configured probabilities are shown on each pipeline stage in the CRM.</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Deal Breakdown</CardTitle></CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b text-left text-gray-500">
                  <th class="p-2">Deal</th>
                  <th class="p-2">Account</th>
                  <th class="p-2">Stage</th>
                  <th class="p-2 text-right">Value</th>
                  <th class="p-2 text-right">Weighted</th>
                  <th class="p-2">Owner</th>
                  <th class="p-2">Expected Close</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="deal in forecast.deals" :key="deal.id" class="border-b">
                  <td class="p-2 font-medium">{{ deal.deal_name }}</td>
                  <td class="p-2">{{ deal.account ?? '-' }}</td>
                  <td class="p-2">{{ deal.stage }}</td>
                  <td class="p-2 text-right">{{ money(deal.value) }}</td>
                  <td class="p-2 text-right">{{ money(deal.weighted_value) }}</td>
                  <td class="p-2">{{ deal.owner ?? '-' }}</td>
                  <td class="p-2">{{ deal.expected_close_date ?? '-' }}</td>
                </tr>
                <tr v-if="!forecast.deals?.length">
                  <td colspan="7" class="p-3 text-center text-gray-500">No open deals match the selected forecast period.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
