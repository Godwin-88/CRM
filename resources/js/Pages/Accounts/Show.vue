<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
  account: any;
  financialSummary?: {
    total_invoiced: number;
    total_paid: number;
    outstanding_balance: number;
    overdue_count: number;
    avg_payment_delay: number;
  };
}>();

const summary = props.financialSummary ?? { total_invoiced: 0, total_paid: 0, outstanding_balance: 0, overdue_count: 0, avg_payment_delay: 0 };
</script>

<template>
  <AppLayout>
    <Head :title="account.name" />
    
    <div class="max-w-7xl mx-auto">
      <div class="mb-4">
        <Link href="/accounts" class="text-blue-600 hover:underline text-sm">← Back to Accounts</Link>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="text-2xl">{{ account.name }}</CardTitle>
                  <p class="text-gray-500">{{ account.type }} · {{ account.industry }}</p>
                </div>
                <Badge :variant="account.status === 'active' ? 'default' : 'secondary'">{{ account.status }}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <dl class="grid grid-cols-2 gap-4">
                <div>
                  <dt class="text-sm text-gray-500">Website</dt>
                  <dd class="font-medium">{{ account.website || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Phone</dt>
                  <dd class="font-medium">{{ account.phone || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">City / State</dt>
                  <dd class="font-medium">{{ account.city || '' }} {{ account.state || '' }} {{ account.country || '' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Annual Revenue</dt>
                  <dd class="font-medium">${{ account.annual_revenue ? Number(account.annual_revenue).toLocaleString() : '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Employees</dt>
                  <dd class="font-medium">{{ account.employee_count || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Manager</dt>
                  <dd class="font-medium">{{ account.account_manager?.name || 'Unassigned' }}</dd>
                </div>
                <div v-if="account.parent_account">
                  <dt class="text-sm text-gray-500">Parent Account</dt>
                  <dd class="font-medium">
                    <Link :href="`/accounts/${account.parent_account.id}`" class="text-blue-600 hover:underline">
                      {{ account.parent_account.name }}
                    </Link>
                  </dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <!-- Financial Summary -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Financial Summary</CardTitle>
            </CardHeader>
            <CardContent>
              <dl class="grid grid-cols-3 gap-4">
                <div>
                  <dt class="text-sm text-gray-500">Total Invoiced</dt>
                  <dd class="font-medium">${{ Number(summary.total_invoiced || 0).toLocaleString() }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Total Paid</dt>
                  <dd class="font-medium">${{ Number(summary.total_paid || 0).toLocaleString() }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Outstanding</dt>
                  <dd class="font-medium">${{ Number(summary.outstanding_balance || 0).toLocaleString() }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Overdue Invoices</dt>
                  <dd class="font-medium">{{ summary.overdue_count || 0 }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Avg Payment Delay</dt>
                  <dd class="font-medium">{{ summary.avg_payment_delay || 0 }} days</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Contacts -->
          <Card>
            <CardHeader><CardTitle class="text-lg">Contacts</CardTitle></CardHeader>
            <CardContent>
              <div v-if="account.contacts?.length" class="space-y-2">
                <div v-for="c in account.contacts" :key="c.id" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                  <Link :href="`/contacts/${c.id}`" class="text-blue-600 hover:underline text-sm">
                    {{ c.first_name }} {{ c.last_name }}
                  </Link>
                  <Badge v-if="c.pivot?.is_primary" variant="default" class="text-xs">Primary</Badge>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No contacts linked</p>
            </CardContent>
          </Card>

          <!-- Sub-accounts -->
          <Card v-if="account.sub_accounts?.length">
            <CardHeader><CardTitle class="text-lg">Subsidiaries</CardTitle></CardHeader>
            <CardContent>
              <div v-for="sub in account.sub_accounts" :key="sub.id" class="p-2">
                <Link :href="`/accounts/${sub.id}`" class="text-blue-600 hover:underline text-sm">{{ sub.name }}</Link>
              </div>
            </CardContent>
          </Card>

          <!-- Deals -->
          <Card>
            <CardHeader><CardTitle class="text-lg">Deals</CardTitle></CardHeader>
            <CardContent>
              <div v-if="account.deals?.length" class="space-y-2">
                <div v-for="deal in account.deals" :key="deal.id" class="p-2 bg-gray-50 rounded">
                  <p class="text-sm font-medium">{{ deal.title }}</p>
                  <p class="text-xs text-gray-500">{{ deal.stage }} · ${{ deal.value ? Number(deal.value).toLocaleString() : '0' }}</p>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No deals</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>