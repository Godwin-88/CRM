<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { DollarSign, Package, BarChart3, Filter } from 'lucide-vue-next'

const props = defineProps<{
  role?: string
  tab?: string
  filters?: Record<string, any>
  revenue_by_product?: Array<{ product: string; category?: string; total_revenue: number; deal_count: number; avg_deal_value: number }>
  revenue_by_account?: Array<{ account: string; total_revenue: number; deal_count: number }>
  revenue_by_agent?: Array<{ agent_id: string; agent: string; total_revenue: number; deal_count: number }>
  revenue_trend?: Array<{ month: string; revenue: number }>
  revenue_allocation?: {
    by_pipeline?: Array<{ name: string; value: number }>
    by_contact_type?: Array<{ type: string; value: number }>
    by_region?: Array<{ country: string; value: number }>
  }
  ar_aging?: Record<string, { label: string; value: number; count: number; invoices: Array<{ id: string; invoice_number: string; account?: string; value: number; due_date?: string; assigned_agent?: string }> }>
  last_calculated?: string
}>()

const selectedTab = ref(props.tab ?? 'revenue_by_product')
const filters = ref<Record<string, any>>({ ...props.filters })

const tabs = [
  { id: 'revenue_by_product', label: 'Revenue by Product' },
  { id: 'revenue_by_account', label: 'Revenue by Account' },
  { id: 'revenue_by_agent', label: 'Revenue by Agent' },
  { id: 'ar_aging', label: 'Accounts Receivable' },
]

const applyFilters = () => {
  router.get('/admin/analytics/finance', { ...filters.value, tab: selectedTab.value }, { preserveState: true, preserveScroll: true })
}

const money = (value?: number | string) => Number(value ?? 0).toLocaleString()
const totalRevenue = computed(() => (props.revenue_by_product ?? []).reduce((sum, row) => sum + Number(row.total_revenue ?? 0), 0))
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
        <Button variant="outline" size="sm" @click="applyFilters">
          <Filter class="h-4 w-4 mr-2" />
          Apply
        </Button>
      </div>

      <Card>
        <CardHeader><CardTitle>Filters</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <Input v-model="filters.date_from" type="date" placeholder="From" />
            <Input v-model="filters.date_to" type="date" placeholder="To" />
            <Input v-model="filters.product_category" placeholder="Product category" />
            <Input v-model="filters.account_id" placeholder="Account ID" />
          </div>
          <p class="mt-2 text-xs text-gray-500">Last calculation: {{ props.last_calculated ?? 'Nightly analytics job' }}</p>
        </CardContent>
      </Card>

      <div class="flex gap-2 border-b">
        <Button v-for="tab in tabs" :key="tab.id" variant="ghost" :class="{ 'border-b-2 border-gray-900 rounded-none': selectedTab === tab.id }" @click="selectedTab = tab.id; applyFilters()">
          {{ tab.label }}
        </Button>
      </div>

      <div v-if="selectedTab === 'revenue_by_product'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card>
          <CardHeader><CardTitle class="flex items-center gap-2"><Package class="h-5 w-5" /> Revenue by Product</CardTitle></CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead class="text-right">Revenue</TableHead>
                  <TableHead class="text-right">Deals</TableHead>
                  <TableHead class="text-right">Avg Deal</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in props.revenue_by_product" :key="row.product">
                  <TableCell class="font-medium">{{ row.product }}</TableCell>
                  <TableCell>{{ row.category ?? '-' }}</TableCell>
                  <TableCell class="text-right">{{ money(row.total_revenue) }}</TableCell>
                  <TableCell class="text-right">{{ row.deal_count }}</TableCell>
                  <TableCell class="text-right">{{ money(row.avg_deal_value) }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Revenue Trend</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="month in props.revenue_trend" :key="month.month" class="flex items-center justify-between border-b pb-2">
                <span class="font-medium">{{ month.month }}</span>
                <span>{{ money(month.revenue) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card v-else-if="selectedTab === 'revenue_by_account'">
        <CardHeader><CardTitle>Revenue by Account</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow><TableHead>Account</TableHead><TableHead class="text-right">Revenue</TableHead><TableHead class="text-right">Deals</TableHead></TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in props.revenue_by_account" :key="row.account">
                <TableCell class="font-medium">{{ row.account }}</TableCell>
                <TableCell class="text-right">{{ money(row.total_revenue) }}</TableCell>
                <TableCell class="text-right">{{ row.deal_count }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card v-else-if="selectedTab === 'revenue_by_agent'">
        <CardHeader><CardTitle>Revenue by Agent</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow><TableHead>Agent</TableHead><TableHead class="text-right">Revenue</TableHead><TableHead class="text-right">Deals</TableHead></TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in props.revenue_by_agent" :key="row.agent_id">
                <TableCell class="font-medium">{{ row.agent }}</TableCell>
                <TableCell class="text-right">{{ money(row.total_revenue) }}</TableCell>
                <TableCell class="text-right">{{ row.deal_count }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card v-else>
        <CardHeader><CardTitle>Accounts Receivable Aging</CardTitle></CardHeader>
        <CardContent class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div v-for="bucket in props.ar_aging" :key="bucket.label" class="rounded border p-4">
              <p class="text-xs text-gray-500">{{ bucket.label }}</p>
              <p class="text-2xl font-bold">{{ money(bucket.value) }}</p>
              <p class="text-sm text-gray-500">{{ bucket.count }} invoices</p>
            </div>
          </div>

          <div v-for="bucket in props.ar_aging" :key="`${bucket.label}-invoices`" class="rounded border">
            <div class="border-b px-4 py-3 font-medium">{{ bucket.label }} Invoices</div>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Invoice</TableHead>
                  <TableHead>Account</TableHead>
                  <TableHead class="text-right">Value</TableHead>
                  <TableHead>Due Date</TableHead>
                  <TableHead>Assigned Agent</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="invoice in bucket.invoices" :key="invoice.id">
                  <TableCell class="font-medium">{{ invoice.invoice_number }}</TableCell>
                  <TableCell>{{ invoice.account ?? '-' }}</TableCell>
                  <TableCell class="text-right">{{ money(invoice.value) }}</TableCell>
                  <TableCell>{{ invoice.due_date ?? '-' }}</TableCell>
                  <TableCell>{{ invoice.assigned_agent ?? '-' }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Revenue Allocation</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <h3 class="font-semibold">By Pipeline</h3>
              <div v-for="row in props.revenue_allocation?.by_pipeline" :key="row.name" class="flex justify-between text-sm"><span>{{ row.name }}</span><span>{{ money(row.value) }}</span></div>
            </div>
            <div>
              <h3 class="font-semibold">By Contact Type</h3>
              <div v-for="row in props.revenue_allocation?.by_contact_type" :key="row.type" class="flex justify-between text-sm"><span>{{ row.type }}</span><span>{{ money(row.value) }}</span></div>
            </div>
            <div>
              <h3 class="font-semibold">By Region</h3>
              <div v-for="row in props.revenue_allocation?.by_region" :key="row.country" class="flex justify-between text-sm"><span>{{ row.country }}</span><span>{{ money(row.value) }}</span></div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
