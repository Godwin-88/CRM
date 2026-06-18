<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Zap, Settings, Filter } from 'lucide-vue-next'

const props = defineProps<{
  role?: string
  tab?: string
  filters?: Record<string, any>
  weights?: Record<string, number>
  deal_scores?: Array<{
    id: string
    title: string
    value: number
    stage: string
    owner?: string
    contact?: string | null
    account?: string | null
    score: number
    label: string
    signals?: Record<string, number>
    manual_score?: number | null
  }>
}>()

const filters = ref<Record<string, any>>({ ...props.filters })

const applyFilters = () => {
  router.get('/admin/analytics/predictive-scoring', { ...filters.value, tab: 'deal_scores' }, { preserveState: true, preserveScroll: true })
}

const recalculateScores = () => {
  router.post('/api/v1/analytics/deal-scores/recalculate', {}, { preserveState: true })
}

const scoreVariant = (label?: string) => {
  if (label === 'very_hot') return 'destructive'
  if (label === 'hot') return 'default'
  if (label === 'warm') return 'secondary'
  return 'outline'
}

const money = (value?: number | string) => Number(value ?? 0).toLocaleString()
</script>

<template>
  <AppLayout>
    <Head title="Predictive Deal Scoring" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Predictive Deal Scoring</h1>
          <p class="text-gray-500">Rule-based deal likelihood to close</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="applyFilters">
            <Filter class="h-4 w-4 mr-2" />
            Apply
          </Button>
          <Button variant="outline" size="sm" @click="recalculateScores">
            <Zap class="h-4 w-4 mr-2" />
            Recalculate
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle>Filters</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <Input v-model="filters.owner_id" placeholder="Owner ID" />
            <Input v-model="filters.pipeline_stage" placeholder="Stage" />
            <Input v-model="filters.date_from" type="date" />
            <Input v-model="filters.date_to" type="date" />
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500">Cold (0-25)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-gray-400" />
          <p class="text-sm">Low priority</p>
        </div>
        <div class="text-center p-4 bg-blue-50 rounded-lg">
          <p class="text-xs text-gray-500">Warm (26-50)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-blue-400" />
          <p class="text-sm">Medium priority</p>
        </div>
        <div class="text-center p-4 bg-amber-50 rounded-lg">
          <p class="text-xs text-gray-500">Hot (51-75)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-amber-500" />
          <p class="text-sm">High priority</p>
        </div>
        <div class="text-center p-4 bg-red-50 rounded-lg">
          <p class="text-xs text-gray-500">Very Hot (76-100)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-red-500" />
          <p class="text-sm">Critical priority</p>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle class="flex items-center gap-2"><Settings class="h-5 w-5" /> Scoring Weights</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <div v-for="(weight, signal) in props.weights" :key="signal" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 capitalize">{{ String(signal).replace('_', ' ') }}</span>
            <Badge>{{ weight }}%</Badge>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Deal Scores</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Deal</TableHead>
                <TableHead>Account</TableHead>
                <TableHead>Stage</TableHead>
                <TableHead class="text-right">Value</TableHead>
                <TableHead>Owner</TableHead>
                <TableHead class="text-right">Score</TableHead>
                <TableHead>Override</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="deal in props.deal_scores" :key="deal.id">
                <TableCell class="font-medium">{{ deal.title }}</TableCell>
                <TableCell>{{ deal.account ?? deal.contact ?? '-' }}</TableCell>
                <TableCell>{{ deal.stage }}</TableCell>
                <TableCell class="text-right">{{ money(deal.value) }}</TableCell>
                <TableCell>{{ deal.owner ?? '-' }}</TableCell>
                <TableCell class="text-right">
                  <Badge :variant="scoreVariant(deal.label)">{{ deal.score }} {{ deal.label }}</Badge>
                </TableCell>
                <TableCell>{{ deal.manual_score ?? '-' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-if="!props.deal_scores?.length" class="text-sm text-gray-500">No open deals match the selected filters.</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
