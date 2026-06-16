<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Users, TrendingUp, RefreshCw, Filter } from 'lucide-vue-next'

const props = defineProps<{
  cohort_retention?: Array
  last_calculated?: string
}>()

const timeRange = ref<'30d' | '90d' | '1y'>('30d')
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
          <Button v-for="range in ['30d', '90d', '1y']" :key="range" :variant="timeRange === range ? 'default' : 'outline'" size="sm" @click="timeRange = range">
            {{ range }}
          </Button>
          <Button variant="ghost" size="sm">
            <Filter class="h-4 w-4" />
          </Button>
          <Button variant="outline" size="sm">
            <RefreshCw class="h-4 w-4 mr-2" />
            Refresh
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Cohort Retention</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-3">Months Active</TableHead>
                <TableHead class="p-3">Cohort Size</TableHead>
                <TableHead class="p-3">Active</TableHead>
                <TableHead class="p-3">Retention Rate</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in props.cohort_retention" :key="row.month" class="border-b hover:bg-gray-50">
                <TableCell class="p-3">{{ row.month }} mo</TableCell>
                <TableCell class="p-3">{{ row.cohort_size }}</TableCell>
                <TableCell class="p-3">{{ row.active_count }}</TableCell>
                <TableCell class="p-3">
                  <Badge :variant="row.retention_rate > 50 ? 'default' : 'secondary'">
                    {{ row.retention_rate }}%
                  </Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
