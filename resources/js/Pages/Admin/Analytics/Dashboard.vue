<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DollarSign, Users, Ticket, Activity, BarChart3, RefreshCw } from 'lucide-vue-next'

const props = defineProps<{
  role?: string
  metrics?: {
    period?: string
    generated_at?: string
    scope?: string
    pipeline?: {
      open_deal_count?: number
      open_deal_value?: number
      weighted_pipeline_value?: number
      by_stage?: Array<{ stage: string; count: number; value: number; weighted_value: number }>
      recent_interactions?: Array<any>
      top_deals?: Array<any>
    }
    activity?: {
      due_today?: number
      overdue?: number
      completion_rate?: number
    }
    tickets?: {
      open_ticket_count?: number
      sla_breach_count?: number
    }
    revenue?: {
      revenue_closed_month?: number
      deals_closed_month?: number
      win_rate?: number
    }
    system_health?: {
      queue_depth?: number
      failed_jobs?: number
      last_scheduler_run?: string
    }
    admin?: {
      contacts_created_month?: number
      campaign_sends_month?: number
      active_user_count?: number
    }
    agent_performance?: Array<any>
  }
  widgets?: Array<any>
  filters?: Record<string, any>
}>()

const metrics = computed(() => props.metrics ?? {})
const pipeline = computed(() => metrics.value.pipeline ?? {})
const activity = computed(() => metrics.value.activity ?? {})
const tickets = computed(() => metrics.value.tickets ?? {})
const revenue = computed(() => metrics.value.revenue ?? {})
const systemHealth = computed(() => metrics.value.system_health ?? {})
const admin = computed(() => metrics.value.admin ?? {})

const refreshAll = () => {
  window.location.reload()
}

const money = (value?: number | string) => Number(value ?? 0).toLocaleString()
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

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                <p class="text-2xl font-bold">{{ pipeline.open_deal_count ?? 0 }}</p>
                <p class="text-sm text-gray-600">Value: {{ money(pipeline.open_deal_value) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Weighted Value</p>
                <p class="text-xl font-semibold text-emerald-600">{{ money(pipeline.weighted_pipeline_value) }}</p>
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
                <p class="text-2xl font-bold">{{ activity.due_today ?? 0 }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Overdue</p>
                <p class="text-xl font-semibold text-rose-600">{{ activity.overdue ?? 0 }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Completion Rate</p>
                <p class="text-xl font-semibold">{{ activity.completion_rate ?? 0 }}%</p>
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
                <p class="text-2xl font-bold">{{ tickets.open_ticket_count ?? 0 }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">SLA Breaches</p>
                <p class="text-xl font-semibold text-rose-600">{{ tickets.sla_breach_count ?? 0 }}</p>
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
                <p class="text-2xl font-bold">{{ money(revenue.revenue_closed_month) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500">Win Rate</p>
                <p class="text-xl font-semibold">{{ revenue.win_rate ?? 0 }}%</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card v-if="role === 'admin'">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Users class="h-5 w-5 text-gray-500" />
              Organisation
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <div class="flex justify-between"><span class="text-xs text-gray-500">Contacts Created</span><span>{{ admin.contacts_created_month ?? 0 }}</span></div>
            <div class="flex justify-between"><span class="text-xs text-gray-500">Campaign Sends</span><span>{{ admin.campaign_sends_month ?? 0 }}</span></div>
            <div class="flex justify-between"><span class="text-xs text-gray-500">Active Users</span><span>{{ admin.active_user_count ?? 0 }}</span></div>
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
                <span class="font-semibold">{{ systemHealth.queue_depth ?? 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-xs text-gray-500">Failed Jobs</span>
                <Badge :variant="(systemHealth.failed_jobs ?? 0) > 0 ? 'destructive' : 'default'">
                  {{ systemHealth.failed_jobs ?? 0 }}
                </Badge>
              </div>
              <div class="text-xs text-gray-500">Scheduler: {{ systemHealth.last_scheduler_run }}</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card>
          <CardHeader><CardTitle>Pipeline by Stage</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="stage in pipeline.by_stage" :key="stage.stage" class="flex items-center justify-between border-b pb-2">
                <div>
                  <p class="font-medium">{{ stage.stage }}</p>
                  <p class="text-xs text-gray-500">{{ stage.count }} deals</p>
                </div>
                <div class="text-right">
                  <p class="font-semibold">{{ money(stage.value) }}</p>
                  <p class="text-xs text-gray-500">Weighted {{ money(stage.weighted_value) }}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Recent Interactions</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="interaction in pipeline.recent_interactions" :key="interaction.id" class="border-b pb-2">
                <p class="font-medium">{{ interaction.subject || interaction.type }}</p>
                <p class="text-xs text-gray-500">{{ interaction.contact_name }} · {{ interaction.created_at }}</p>
              </div>
              <p v-if="!pipeline.recent_interactions?.length" class="text-sm text-gray-500">No recent interactions.</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card v-if="role === 'manager'">
        <CardHeader><CardTitle>Agent Performance</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="agent in metrics.agent_performance" :key="agent.user_id" class="rounded border p-3">
              <p class="font-medium">{{ agent.name }}</p>
              <p class="text-sm text-gray-500">Tickets resolved: {{ agent.tickets_resolved }}</p>
              <p class="text-sm text-gray-500">Deals moved this week: {{ agent.deals_moved_this_week }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Top Deals</CardTitle></CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div v-for="deal in pipeline.top_deals" :key="deal.id" class="flex items-center justify-between border-b pb-2">
              <div>
                <p class="font-medium">{{ deal.title }}</p>
                <p class="text-xs text-gray-500">{{ deal.account }} · {{ deal.owner }}</p>
              </div>
              <Badge>{{ money(deal.value) }}</Badge>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
