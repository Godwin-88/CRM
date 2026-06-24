<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { DollarSign, Users, Ticket, Activity, BarChart3, RefreshCw, ChevronRight } from 'lucide-vue-next'

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

const openModal = ref<string | null>(null)
const modalData = ref<any>(null)
const modalLoading = ref(false)

const money = (value?: number | string) => Number(value ?? 0).toLocaleString()

const openDetails = async (type: string) => {
  openModal.value = type
  modalData.value = null
  modalLoading.value = true
  try {
    const res = await fetch(`/api/v1/analytics/dashboard/${type}`, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '', 'Accept': 'application/json' },
    })
    if (res.ok) {
      modalData.value = await res.json()
    }
  } catch {
    // silent
  } finally {
    modalLoading.value = false
  }
}

const closeModal = () => {
  openModal.value = null
  modalData.value = null
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
        <Button variant="outline" size="sm" @click="window.location.reload()">
          <RefreshCw class="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card class="cursor-pointer hover:shadow-md transition-shadow" @click="openDetails('pipeline')">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <DollarSign class="h-5 w-5 text-emerald-500" />
              Pipeline
              <ChevronRight class="h-4 w-4 ml-auto text-gray-400" />
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

        <Card class="cursor-pointer hover:shadow-md transition-shadow" @click="openDetails('activity')">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Activity class="h-5 w-5 text-blue-500" />
              Activity
              <ChevronRight class="h-4 w-4 ml-auto text-gray-400" />
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

        <Card class="cursor-pointer hover:shadow-md transition-shadow" @click="openDetails('tickets')">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Ticket class="h-5 w-5 text-amber-500" />
              Tickets
              <ChevronRight class="h-4 w-4 ml-auto text-gray-400" />
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

        <Card class="cursor-pointer hover:shadow-md transition-shadow" @click="openDetails('revenue')">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BarChart3 class="h-5 w-5 text-purple-500" />
              Revenue (MTD)
              <ChevronRight class="h-4 w-4 ml-auto text-gray-400" />
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

        <Card v-if="role === 'admin'" class="cursor-pointer hover:shadow-md transition-shadow" @click="openDetails('admin')">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Users class="h-5 w-5 text-gray-500" />
              Organisation
              <ChevronRight class="h-4 w-4 ml-auto text-gray-400" />
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <div class="flex justify-between"><span class="text-xs text-gray-500">Contacts Created</span><span>{{ admin.contacts_created_month ?? 0 }}</span></div>
            <div class="flex justify-between"><span class="text-xs text-gray-500">Campaign Sends</span><span>{{ admin.campaign_sends_month ?? 0 }}</span></div>
            <div class="flex justify-between"><span class="text-xs text-gray-500">Active Users</span><span>{{ admin.active_user_count ?? 0 }}</span></div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer hover:shadow-md transition-shadow" @click="openDetails('system-health')">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Activity class="h-5 w-5 text-gray-500" />
              System Health
              <ChevronRight class="h-4 w-4 ml-auto text-gray-400" />
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

      <!-- Pipeline Detail Modal -->
      <Dialog :open="openModal === 'pipeline'" @update:open="closeModal">
        <DialogContent class="sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>Pipeline Details</DialogTitle>
          </DialogHeader>
          <div v-if="modalLoading" class="text-center py-8 text-gray-500">Loading...</div>
          <div v-else-if="modalData" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Open Deals</p>
                <p class="text-2xl font-bold">{{ modalData.deals?.length || 0 }}</p>
              </div>
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Total Value</p>
                <p class="text-2xl font-bold text-emerald-600">{{ money(modalData.deals?.reduce((sum: number, d: any) => sum + (d.value || 0), 0)) }}</p>
              </div>
            </div>
            <div class="rounded-md border max-h-96 overflow-y-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left p-2">Deal</th>
                    <th class="text-left p-2">Account</th>
                    <th class="text-left p-2">Owner</th>
                    <th class="text-right p-2">Value</th>
                    <th class="text-right p-2">Weighted</th>
                    <th class="text-left p-2">Stage</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="deal in modalData.deals" :key="deal.id" class="border-t">
                    <td class="p-2 font-medium">{{ deal.title }}</td>
                    <td class="p-2">{{ deal.account || '—' }}</td>
                    <td class="p-2">{{ deal.owner || '—' }}</td>
                    <td class="p-2 text-right">{{ money(deal.value) }}</td>
                    <td class="p-2 text-right text-emerald-600">{{ money(deal.weighted_value) }}</td>
                    <td class="p-2"><Badge variant="outline">{{ deal.stage }}</Badge></td>
                  </tr>
                  <tr v-if="!modalData.deals?.length">
                    <td colspan="6" class="text-center py-6 text-gray-500">No open deals.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Activity Detail Modal -->
      <Dialog :open="openModal === 'activity'" @update:open="closeModal">
        <DialogContent class="sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>Activity Details</DialogTitle>
          </DialogHeader>
          <div v-if="modalLoading" class="text-center py-8 text-gray-500">Loading...</div>
          <div v-else-if="modalData" class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Due Today</p>
                <p class="text-2xl font-bold">{{ modalData.activities?.filter((a: any) => a.status === 'pending' && a.due_at?.startsWith(new Date().toISOString().slice(0,10))).length || 0 }}</p>
              </div>
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Overdue</p>
                <p class="text-2xl font-bold text-rose-600">{{ modalData.activities?.filter((a: any) => a.status === 'overdue').length || 0 }}</p>
              </div>
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Completed</p>
                <p class="text-2xl font-bold text-green-600">{{ modalData.activities?.filter((a: any) => a.status === 'completed').length || 0 }}</p>
              </div>
            </div>
            <div class="rounded-md border max-h-96 overflow-y-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left p-2">Subject</th>
                    <th class="text-left p-2">Type</th>
                    <th class="text-left p-2">Due At</th>
                    <th class="text-left p-2">Status</th>
                    <th class="text-left p-2">Assignee</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="act in modalData.activities" :key="act.id" class="border-t">
                    <td class="p-2 font-medium">{{ act.subject }}</td>
                    <td class="p-2">{{ act.type }}</td>
                    <td class="p-2">{{ act.due_at ? new Date(act.due_at).toLocaleString() : '—' }}</td>
                    <td class="p-2">
                      <Badge :variant="act.status === 'completed' ? 'default' : (act.status === 'overdue' ? 'destructive' : 'outline')">
                        {{ act.status }}
                      </Badge>
                    </td>
                    <td class="p-2">{{ act.assignee || '—' }}</td>
                  </tr>
                  <tr v-if="!modalData.activities?.length">
                    <td colspan="5" class="text-center py-6 text-gray-500">No activities found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Tickets Detail Modal -->
      <Dialog :open="openModal === 'tickets'" @update:open="closeModal">
        <DialogContent class="sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>Ticket Details</DialogTitle>
          </DialogHeader>
          <div v-if="modalLoading" class="text-center py-8 text-gray-500">Loading...</div>
          <div v-else-if="modalData" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Open Tickets</p>
                <p class="text-2xl font-bold">{{ modalData.tickets?.length || 0 }}</p>
              </div>
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">SLA Breaches</p>
                <p class="text-2xl font-bold text-rose-600">{{ modalData.tickets?.filter((t: any) => t.sla_breached).length || 0 }}</p>
              </div>
            </div>
            <div class="rounded-md border max-h-96 overflow-y-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left p-2">Subject</th>
                    <th class="text-left p-2">Status</th>
                    <th class="text-left p-2">Priority</th>
                    <th class="text-left p-2">Assigned</th>
                    <th class="text-left p-2">SLA</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ticket in modalData.tickets" :key="ticket.id" class="border-t">
                    <td class="p-2 font-medium">{{ ticket.subject }}</td>
                    <td class="p-2"><Badge variant="outline">{{ ticket.status }}</Badge></td>
                    <td class="p-2">{{ ticket.priority }}</td>
                    <td class="p-2">{{ ticket.assigned_to || '—' }}</td>
                    <td class="p-2">
                      <Badge :variant="ticket.sla_breached ? 'destructive' : 'default'">
                        {{ ticket.sla_breached ? 'Breached' : 'OK' }}
                      </Badge>
                    </td>
                  </tr>
                  <tr v-if="!modalData.tickets?.length">
                    <td colspan="5" class="text-center py-6 text-gray-500">No tickets found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Revenue Detail Modal -->
      <Dialog :open="openModal === 'revenue'" @update:open="closeModal">
        <DialogContent class="sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>Revenue Details</DialogTitle>
          </DialogHeader>
          <div v-if="modalLoading" class="text-center py-8 text-gray-500">Loading...</div>
          <div v-else-if="modalData" class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Closed Revenue (MTD)</p>
                <p class="text-2xl font-bold text-emerald-600">{{ money(modalData.deals?.reduce((sum: number, d: any) => sum + (d.value || 0), 0)) }}</p>
              </div>
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Win Rate</p>
                <p class="text-2xl font-bold">{{ modalData.win_rate ?? 0 }}%</p>
              </div>
              <div class="rounded-lg border p-3">
                <p class="text-xs text-gray-500">Deals Closed</p>
                <p class="text-2xl font-bold">{{ modalData.deals?.length || 0 }}</p>
              </div>
            </div>
            <div class="rounded-md border max-h-96 overflow-y-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left p-2">Deal</th>
                    <th class="text-left p-2">Account</th>
                    <th class="text-left p-2">Owner</th>
                    <th class="text-right p-2">Value</th>
                    <th class="text-left p-2">Closed At</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="deal in modalData.deals" :key="deal.id" class="border-t">
                    <td class="p-2 font-medium">{{ deal.title }}</td>
                    <td class="p-2">{{ deal.account || '—' }}</td>
                    <td class="p-2">{{ deal.owner || '—' }}</td>
                    <td class="p-2 text-right font-semibold">{{ money(deal.value) }}</td>
                    <td class="p-2">{{ deal.closed_at ? new Date(deal.closed_at).toLocaleDateString() : '—' }}</td>
                  </tr>
                  <tr v-if="!modalData.deals?.length">
                    <td colspan="5" class="text-center py-6 text-gray-500">No closed deals this month.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- System Health Detail Modal -->
      <Dialog :open="openModal === 'system-health'" @update:open="closeModal">
        <DialogContent class="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>System Health</DialogTitle>
          </DialogHeader>
          <div v-if="modalLoading" class="text-center py-8 text-gray-500">Loading...</div>
          <div v-else-if="modalData" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-lg border p-4">
                <p class="text-xs text-gray-500">Queue Depth</p>
                <p class="text-3xl font-bold">{{ modalData.queue_depth ?? 0 }}</p>
              </div>
              <div class="rounded-lg border p-4">
                <p class="text-xs text-gray-500">Failed Jobs</p>
                <p class="text-3xl font-bold" :class="(modalData.failed_jobs ?? 0) > 0 ? 'text-red-600' : 'text-green-600'">{{ modalData.failed_jobs ?? 0 }}</p>
              </div>
            </div>
            <div class="rounded-lg border p-4">
              <p class="text-xs text-gray-500">Last Scheduler Run</p>
              <p class="text-lg font-semibold">{{ modalData.last_scheduler_run ? new Date(modalData.last_scheduler_run).toLocaleString() : 'Never' }}</p>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Admin Detail Modal -->
      <Dialog :open="openModal === 'admin'" @update:open="closeModal">
        <DialogContent class="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Organisation Overview</DialogTitle>
          </DialogHeader>
          <div v-if="modalLoading" class="text-center py-8 text-gray-500">Loading...</div>
          <div v-else class="space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="rounded-lg border p-4 text-center">
                <p class="text-xs text-gray-500">Contacts Created</p>
                <p class="text-3xl font-bold">{{ admin.contacts_created_month ?? 0 }}</p>
              </div>
              <div class="rounded-lg border p-4 text-center">
                <p class="text-xs text-gray-500">Campaign Sends</p>
                <p class="text-3xl font-bold">{{ admin.campaign_sends_month ?? 0 }}</p>
              </div>
              <div class="rounded-lg border p-4 text-center">
                <p class="text-xs text-gray-500">Active Users</p>
                <p class="text-3xl font-bold">{{ admin.active_user_count ?? 0 }}</p>
              </div>
            </div>
            <p class="text-sm text-gray-500">Showing current month metrics for all users in your organisation.</p>
          </div>
        </DialogContent>
      </Dialog>

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
