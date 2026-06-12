<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { format } from 'date-fns'
import { TrendingUp, Users, DollarSign, Activity } from 'lucide-vue-next'

const props = defineProps<{
  stats: {
    avg_clv: number
    total_points_issued: number
    total_points_redeemed: number
    redemption_rate: number
    total_enrollments: number
    active_enrollments: number
    churn_risk_count: number
  }
  topContacts: { id: string; first_name: string; last_name: string; email: string; clv_score: number; ltv: number }[]
}>()

const stats = ref(props.stats)
const contacts = ref(props.topContacts)
const timeRange = ref<'30d' | '90d' | '1y'>('30d')
</script>

<template>
  <AppLayout>
    <Head title="CLV Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">CLV Analytics</h1>
          <p class="text-gray-500">Customer Lifetime Value, segmentation, and churn risk.</p>
        </div>
        <div class="flex gap-2">
          <Button v-for="range in ['30d', '90d', '1y']" :key="range" :variant="timeRange === range ? 'default' : 'outline'" size="sm" @click="timeRange = range">{{ range }}</Button>
          <Button variant="ghost" size="sm" @click="router.post('/admin/clv-analytics/recalculate')">Recalculate</Button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <Card><CardContent class="pt-6 flex items-center gap-3"><DollarSign class="h-5 w-5 text-emerald-500" /><div><p class="text-xs text-gray-500">Avg CLV</p><p class="text-lg font-bold">{{ Number(stats.avg_clv ?? 0).toLocaleString() }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-3"><Activity class="h-5 w-5 text-blue-500" /><div><p class="text-xs text-gray-500">Points Issued</p><p class="text-lg font-bold">{{ Number(stats.total_points_issued ?? 0).toLocaleString() }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-3"><TrendingUp class="h-5 w-5 text-purple-500" /><div><p class="text-xs text-gray-500">Redeemed</p><p class="text-lg font-bold">{{ Number(stats.total_points_redeemed ?? 0).toLocaleString() }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-3"><TrendingUp class="h-5 w-5 text-amber-500" /><div><p class="text-xs text-gray-500">Redemption</p><p class="text-lg font-bold">{{ stats.redemption_rate ?? 0 }}%</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-3"><Users class="h-5 w-5 text-teal-500" /><div><p class="text-xs text-gray-500">Enrollments</p><p class="text-lg font-bold">{{ stats.active_enrollments }} / {{ stats.total_enrollments }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-3"><Activity class="h-5 w-5 text-rose-500" /><div><p class="text-xs text-gray-500">Churn Risk</p><p class="text-lg font-bold">{{ stats.churn_risk_count ?? 0 }}</p></div></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Top Contacts by CLV</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Contact</th><th class="p-4">Email</th><th class="p-4">CLV Score</th><th class="p-4">LTV</th></tr></thead>
            <tbody>
              <tr v-for="c in contacts" :key="c.id" class="border-b hover:bg-gray-50">
                <td class="p-4 font-medium">{{ c.first_name }} {{ c.last_name }}</td>
                <td class="p-4 text-gray-600">{{ c.email }}</td>
                <td class="p-4"><Badge variant="default">{{ Number(c.clv_score ?? 0).toLocaleString() }}</Badge></td>
                <td class="p-4">{{ Number(c.ltv ?? 0).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
