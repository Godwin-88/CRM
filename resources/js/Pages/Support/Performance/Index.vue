<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{
  metrics: {
    period_start: string
    period_end: string
    tickets_created: number
    tickets_resolved: number
    tickets_closed: number
    avg_first_response_hours: number
    avg_resolution_hours: number
    sla_breach_count: number
    sla_breach_rate: number
    avg_csat_score: number
  }
}>()
</script>

<template>
  <AppLayout>
    <Head title="Agent Performance" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Agent Performance</h1>
        <p class="text-gray-500">Support metrics and analytics.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent class="pt-6">
            <p class="text-sm text-gray-500">Tickets Created</p>
            <p class="text-3xl font-bold">{{ metrics.tickets_created }}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent class="pt-6">
            <p class="text-sm text-gray-500">Resolution Rate</p>
            <p class="text-3xl font-bold">
              {{ metrics.tickets_created > 0 ? Math.round((metrics.tickets_resolved / metrics.tickets_created) * 100) : 0 }}%
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="pt-6">
            <p class="text-sm text-gray-500">SLA Breach Rate</p>
            <p class="text-3xl font-bold">
              <Badge :variant="metrics.sla_breach_rate > 5 ? 'destructive' : 'success'">
                {{ metrics.sla_breach_rate }}%
              </Badge>
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Response Times</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Avg First Response</p>
              <p class="text-2xl font-bold">{{ metrics.avg_first_response_hours }} hrs</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Avg Resolution Time</p>
              <p class="text-2xl font-bold">{{ metrics.avg_resolution_hours }} hrs</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Customer Satisfaction</CardTitle>
        </CardHeader>
        <CardContent>
          <div>
            <p class="text-sm text-gray-500">Avg CSAT Score</p>
            <p class="text-3xl font-bold">{{ metrics.avg_csat_score }}/5</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>