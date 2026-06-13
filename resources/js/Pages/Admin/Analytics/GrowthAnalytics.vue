<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Users, TrendingUp, Filter } from 'lucide-vue-next'

const props = defineProps<{
  lead_conversion?: {
    total_leads: number
    lead_to_opportunity_rate: number
    opportunity_to_won_rate: number
    lead_to_customer_rate: number
    conversion_funnel?: Record<string, number>
  }
  cac?: {
    cac: number
    campaign_spend: number
    new_customers: number
  }
  ltv_to_cac?: {
    ratio: number
    avg_ltv: number
    cac: number
    status: string
  }
}>()

const timeRange = ref<'30d' | '90d' | '1y'>('30d')
</script>

<template>
  <AppLayout>
    <Head title="Growth Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Growth Analytics</h1>
          <p class="text-gray-500">Lead conversion, CAC, and LTV:CAC metrics</p>
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
            <CardTitle>Lead Conversion</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <p class="text-xs text-gray-500">Total Leads</p>
              <p class="text-2xl font-bold">{{ props.lead_conversion?.total_leads ?? 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Lead → Opportunity</p>
              <p class="text-xl font-semibold">{{ props.lead_conversion?.lead_to_opportunity_rate ?? 0 }}%</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Opportunity → Won</p>
              <p class="text-xl font-semibold">{{ props.lead_conversion?.opportunity_to_won_rate ?? 0 }}%</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Lead → Customer (End-to-end)</p>
              <p class="text-xl font-semibold">{{ props.lead_conversion?.lead_to_customer_rate ?? 0 }}%</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Customer Acquisition Cost</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <p class="text-xs text-gray-500">Total CAC</p>
              <p class="text-3xl font-bold text-blue-600">{{ Number(props.cac?.cac ?? 0).toLocaleString() }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Campaign Spend</p>
              <p class="text-lg">{{ Number(props.cac?.campaign_spend ?? 0).toLocaleString() }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">New Customers</p>
              <p class="text-lg">{{ props.cac?.new_customers ?? 0 }}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>LTV to CAC Ratio</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <p class="text-xs text-gray-500">Ratio</p>
              <p class="text-3xl font-bold" :class="{
                'text-green-600': props.ltv_to_cac?.status === 'good',
                'text-amber-600': props.ltv_to_cac?.status === 'warning',
                'text-red-600': props.ltv_to_cac?.status === 'critical',
              }">
                {{ props.ltv_to_cac?.ratio ?? 0 }}:1
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Avg LTV</p>
              <p class="text-lg">{{ Number(props.ltv_to_cac?.avg_ltv ?? 0).toLocaleString() }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Avg CAC</p>
              <p class="text-lg">{{ Number(props.ltv_to_cac?.cac ?? 0).toLocaleString() }}</p>
            </div>
            <Badge :variant="props.ltv_to_cac?.status === 'good' ? 'default' : 'destructive'">
              {{ props.ltv_to_cac?.status === 'good' ? 'Healthy' : (props.ltv_to_cac?.status === 'warning' ? 'Monitor' : 'Critical') }}
            </Badge>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Conversion Funnel</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between">
            <div v-for="(value, stage) in props.lead_conversion?.conversion_funnel" :key="stage" class="text-center">
              <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                <span class="text-xl font-bold text-blue-700">{{ value }}</span>
              </div>
              <p class="text-sm capitalize">{{ stage.replace('_', ' ') }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>