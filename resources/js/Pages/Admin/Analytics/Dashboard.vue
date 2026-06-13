<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DollarSign, Users, Ticket, Activity, BarChart3, RefreshCw } from 'lucide-vue-next'

const props = defineProps<{
  pipeline?: {
    open_deal_count: number
    open_deal_value: number
    weighted_pipeline_value: number
    by_stage?: Array
  }
  activity?: {
    due_today: number
    overdue: number
  }
  tickets?: {
    open_ticket_count: number
    sla_breach_count: number
  }
  revenue?: {
    revenue_closed_month: number
    deals_closed_month: number
    win_rate: number
  }
  system_health?: {
    queue_depth: number
    failed_jobs: number
    last_scheduler_run: string
  }
  recent_interactions?: Array
}>()

const widgets = ref([
  { id: 'pipeline', name: 'Pipeline', enabled: true, component: 'pipeline' },
  { id: 'activity', name: 'Activity', enabled: true, component: 'activity' },
  { id: 'tickets', name: 'Tickets', enabled: true, component: 'tickets' },
  { id: 'revenue', name: 'Revenue', enabled: true, component: 'revenue' },
  { id: 'system', name: 'System Health', enabled: true, component: 'system' },
])

const refreshAll = () => {
  window.location.reload()
}
</script>

<template>
  <AppLayout>
    <Head title="Analytics Dashboard" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
          <p class="text-gray-500">Role-appropriate overview of your CRM metrics</p>
        </div>
        <Button variant="outline" size="sm" @click="refreshAll">
          <RefreshCw class="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <DollarSign class="h-5 w-5 text-emerald-500" />
              Pipeline
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Open Deals</p>
                <p class="text-2xl font-bold">{{ props.pipeline?.open_deal_count ?? 0 }}</p>
                <p class="text-sm text-gray-600">Value: {{ Number(props.pipeline?.open_deal_value ?? 0).toLocaleString() }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Weighted Value</p>
                <p class="text-xl font-semibold text-emerald-600">
                  {{ Number(props.pipeline?.weighted_pipeline_value ?? 0).toLocaleString() }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Activity class="h-5 w-5 text-blue-500" />
              Activity
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Due Today</p>
                <p class="text-2xl font-bold">{{ props.activity?.due_today ?? 0 }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Overdue</p>
                <p class="text-xl font-semibold text-rose-600">{{ props.activity?.overdue ?? 0 }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Ticket class="h-5 w-5 text-amber-500" />
              Tickets
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Open Tickets</p>
                <p class="text-2xl font-bold">{{ props.tickets?.open_ticket_count ?? 0 }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">SLA Breaches</p>
                <p class="text-xl font-semibold text-rose-600">{{ props.tickets?.sla_breach_count ?? 0 }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BarChart3 class="h-5 w-5 text-purple-500" />
              Revenue (MTD)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Closed Revenue</p>
                <p class="text-2xl font-bold">{{ Number(props.revenue?.revenue_closed_month ?? 0).toLocaleString() }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Win Rate</p>
                <p class="text-xl font-semibold">{{ props.revenue?.win_rate ?? 0 }}%</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Activity class="h-5 w-5 text-gray-500" />
              System Health
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-xs text-gray-500">Queue Depth</span>
                <span class="font-semibold">{{ props.system_health?.queue_depth ?? 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-xs text-gray-500">Failed Jobs</span>
                <Badge :variant="props.system_health?.failed_jobs > 0 ? 'destructive' : 'default'">
                  {{ props.system_health?.failed_jobs ?? 0 }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>