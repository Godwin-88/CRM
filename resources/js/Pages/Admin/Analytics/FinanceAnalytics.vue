<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DollarSign, Package, BarChart3, Filter } from 'lucide-vue-next'

const props = defineProps<{
  revenue_by_product?: Array
  revenue_by_account?: Array
  revenue_trend?: Array
  ar_aging?: {
    current?: { value: number; count: number }
    '31_60'?: { value: number; count: number }
    '61_90'?: { value: number; count: number }
    over_90?: { value: number; count: number }
  }
  last_calculated?: string
}>()

const timeRange = ref<'30d' | '90d' | '1y'>('30d')
</script>

<template>
  <AppLayout>
    <Head title="Finance Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Finance Analytics</h1>
          <p class="text-gray-500">Revenue, product performance, and accounts receivable</p>
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

      <Card>
        <CardHeader>
          <CardTitle>Revenue by Product</CardTitle>
        </CardHeader>
        <CardContent>
          <table class="w-full text-sm">
            <thead class="border-b">
              <tr class="text-left text-gray-500">
                <th class="p-3">Product</th>
                <th class="p-3">Category</th>
                <th class="p-3">Revenue</th>
                <th class="p-3">Deals</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in props.revenue_by_product" :key="row.product" class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">{{ row.product }}</td>
                <td class="p-3">{{ row.category ?? '-' }}</td>
                <td class="p-3">{{ Number(row.total_revenue).toLocaleString() }}</td>
                <td class="p-3">{{ row.deal_count }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Accounts Receivable Aging</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-4 gap-4">
            <div class="text-center p-4 bg-green-50 rounded-lg">
              <p class="text-xs text-gray-500">Current (0-30)</p>
              <p class="text-xl font-bold text-green-600">{{ Number(props.ar_aging?.current?.value ?? 0).toLocaleString() }}</p>
              <p class="text-sm text-gray-600">{{ props.ar_aging?.current?.count ?? 0 }} invoices</p>
            </div>
            <div class="text-center p-4 bg-amber-50 rounded-lg">
              <p class="text-xs text-gray-500">31-60 days</p>
              <p class="text-xl font-bold text-amber-600">{{ Number(props.ar_aging?.['31_60']?.value ?? 0).toLocaleString() }}</p>
              <p class="text-sm text-gray-600">{{ props.ar_aging?.['31_60']?.count ?? 0 }} invoices</p>
            </div>
            <div class="text-center p-4 bg-orange-50 rounded-lg">
              <p class="text-xs text-gray-500">61-90 days</p>
              <p class="text-xl font-bold text-orange-600">{{ Number(props.ar_aging?.['61_90']?.value ?? 0).toLocaleString() }}</p>
              <p class="text-sm text-gray-600">{{ props.ar_aging?.['61_90']?.count ?? 0 }} invoices</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
              <p class="text-xs text-gray-500">Over 90 days</p>
              <p class="text-xl font-bold text-red-600">{{ Number(props.ar_aging?.over_90?.value ?? 0).toLocaleString() }}</p>
              <p class="text-sm text-gray-600">{{ props.ar_aging?.over_90?.count ?? 0 }} invoices</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Revenue Trend</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="h-64 flex items-end justify-between gap-2">
            <div v-for="point in props.revenue_trend" :key="point.month" class="flex-1 flex flex-col items-center">
              <div class="w-full bg-blue-200 rounded-t" :style="{ height: Math.max(point.revenue / 1000, 10) + 'px' }"></div>
              <span class="text-xs text-gray-500 mt-1">{{ point.month }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>