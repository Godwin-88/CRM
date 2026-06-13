<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  metrics: {
    total_invoiced: number;
    total_collected: number;
    total_outstanding: number;
    collection_rate: number;
    overdue_count: number;
    overdue_value: number;
    po_spend: number;
  };
  revenueTrend: any[];
  agingBuckets: {
    current: number;
    days_31_60: number;
    days_61_90: number;
    over_90: number;
  };
  topAccounts: any[];
  vendorSpend: any[];
  topVendors: any[];
  filters: any;
  currencies: string[];
  lastCalculated: string;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Financial Dashboard" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Financial Dashboard</h1>
        <form method="POST" action="/finance/refresh">
          <input type="hidden" name="_token" :value="$page.props.csrf_token" />
          <Button type="submit" variant="outline">Refresh Metrics</Button>
        </form>
      </div>

      <!-- Metrics Row -->
      <div class="grid grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader><CardTitle class="text-sm">Total Invoiced</CardTitle></CardHeader>
          <CardContent><p class="text-2xl font-bold">${{ Number(metrics.total_invoiced).toLocaleString() }}</p></CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="text-sm">Total Collected</CardTitle></CardHeader>
          <CardContent><p class="text-2xl font-bold">${{ Number(metrics.total_collected).toLocaleString() }}</p></CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="text-sm">Outstanding</CardTitle></CardHeader>
          <CardContent><p class="text-2xl font-bold">${{ Number(metrics.total_outstanding).toLocaleString() }}</p></CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="text-sm">Collection Rate</CardTitle></CardHeader>
          <CardContent><p class="text-2xl font-bold">{{ metrics.collection_rate }}%</p></CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Aging Buckets -->
        <Card>
          <CardHeader><CardTitle>Accounts Receivable Aging</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between"><span>Current (0-30)</span><span>${{ Number(agingBuckets.current).toLocaleString() }}</span></div>
              <div class="flex justify-between"><span>31-60 Days</span><span>${{ Number(agingBuckets.days_31_60).toLocaleString() }}</span></div>
              <div class="flex justify-between"><span>61-90 Days</span><span>${{ Number(agingBuckets.days_61_90).toLocaleString() }}</span></div>
              <div class="flex justify-between"><span>Over 90 Days</span><span>${{ Number(agingBuckets.over_90).toLocaleString() }}</span></div>
            </div>
          </CardContent>
        </Card>

        <!-- Top Accounts -->
        <Card>
          <CardHeader><CardTitle>Top 10 Accounts by Outstanding</CardTitle></CardHeader>
          <CardContent>
            <table class="w-full text-sm">
              <thead><tr><th class="text-left">Account</th><th class="text-right">Outstanding</th></tr></thead>
              <tbody>
                <tr v-for="account in topAccounts" :key="account.id">
                  <td>{{ account.name }}</td>
                  <td class="text-right">${{ Number(account.outstanding_balance || 0).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </CardContent>
        </Card>
      </div>

      <div class="mb-6">
        <p class="text-xs text-gray-500">Last calculated: {{ lastCalculated }}</p>
      </div>
    </div>
  </AppLayout>
</template>