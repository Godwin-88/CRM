<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Shield, Users, Filter, AlertTriangle } from 'lucide-vue-next'

const props = defineProps<{
  audit_stats?: {
    total_events: number
    event_breakdown?: Array
    top_users?: Array
    daily_activity?: Array
  }
  last_calculated?: string
}>()

const timeRange = ref<'30d' | '90d' | '1y'>('30d')
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
        <div class="flex gap-2">
          <Button v-for="range in ['30d', '90d', '1y']" :key="range" :variant="timeRange === range ? 'default' : 'outline'" size="sm" @click="timeRange = range">
            {{ range }}
          </Button>
          <Button variant="ghost" size="sm">
            <Filter class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card>
          <CardHeader>
            <CardTitle>Total Events</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-3xl font-bold">{{ Number(props.audit_stats?.total_events ?? 0).toLocaleString() }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Event Breakdown</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <div v-for="event in props.audit_stats?.event_breakdown" :key="event.event" class="flex justify-between">
              <span class="text-sm text-gray-600">{{ event.event }}</span>
              <Badge>{{ event.count }}</Badge>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Anomaly Alerts</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-3">
              <AlertTriangle class="h-5 w-5 text-amber-500" />
              <span class="text-sm">No active alerts</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Top Users by Activity</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-3">User</TableHead>
                <TableHead class="p-3">Events</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in props.audit_stats?.top_users" :key="user.user_id" class="border-b hover:bg-gray-50">
                <TableCell class="p-3">{{ user.name }}</TableCell>
                <TableCell class="p-3"><Badge>{{ user.count }}</Badge></TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
