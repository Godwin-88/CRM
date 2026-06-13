<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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
          <table class="w-full text-sm">
            <thead class="border-b">
              <tr class="text-left text-gray-500">
                <th class="p-3">Months Active</th>
                <th class="p-3">Cohort Size</th>
                <th class="p-3">Active</th>
                <th class="p-3">Retention Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in props.cohort_retention" :key="row.month" class="border-b hover:bg-gray-50">
                <td class="p-3">{{ row.month }} mo</td>
                <td class="p-3">{{ row.cohort_size }}</td>
                <td class="p-3">{{ row.active_count }}</td>
                <td class="p-3">
                  <Badge :variant="row.retention_rate > 50 ? 'default' : 'secondary'">
                    {{ row.retention_rate }}%
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>