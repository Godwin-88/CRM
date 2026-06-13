<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  invoice: any;
  canPay: boolean;
}>();
</script>

<template>
  <AppLayout>
    <Head :title="invoice.invoice_number" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/invoices" class="text-blue-600 hover:underline text-sm">← Back to Invoices</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ invoice.invoice_number }}</CardTitle>
              <p class="text-gray-500">{{ invoice.account?.name }}</p>
            </div>
            <Badge :variant="invoice.status === 'paid' ? 'default' : 'outline'">
              {{ invoice.status }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <dt class="text-sm text-gray-500">Due Date</dt>
              <dd>{{ invoice.due_date }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Currency</dt>
              <dd>{{ invoice.currency }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Subtotal</dt>
              <dd>${{ Number(invoice.subtotal).toLocaleString() }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Total Tax</dt>
              <dd>${{ Number(invoice.total_tax).toLocaleString() }}</dd>
            </div>
            <div class="col-span-2">
              <dt class="text-sm text-gray-500">Total</dt>
              <dd class="font-bold text-lg">${{ Number(invoice.total).toLocaleString() }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Amount Paid</dt>
              <dd>${{ Number(invoice.total_paid).toLocaleString() }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Outstanding</dt>
              <dd class="font-medium">${{ Number(invoice.outstanding_balance).toLocaleString() }}</dd>
            </div>
          </dl>

          <div class="flex gap-2">
            <a :href="`/invoices/${invoice.id}/download`" target="_blank" class="btn btn-outline">Download PDF</a>
            <Link :href="`/invoices/${invoice.id}/edit`" v-if="canPay">
              <Button variant="outline">Edit</Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      <Card class="mb-6">
        <CardHeader><CardTitle>Line Items</CardTitle></CardHeader>
        <CardContent>
          <table class="w-full">
            <thead>
              <tr class="border-b">
                <th class="text-left">Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in invoice.line_items" :key="item.id" class="border-b">
                <td>{{ item.description }}</td>
                <td class="text-right">{{ item.quantity }}</td>
                <td class="text-right">${{ Number(item.unit_price).toLocaleString() }}</td>
                <td class="text-right">{{ item.tax_rate }}%</td>
                <td class="text-right">${{ Number(item.line_total).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>

      <Card v-if="invoice.payments?.length">
        <CardHeader><CardTitle>Payment History</CardTitle></CardHeader>
        <CardContent>
          <table class="w-full">
            <thead>
              <tr class="border-b">
                <th class="text-left">Date</th>
                <th class="text-left">Method</th>
                <th class="text-left">Reference</th>
                <th class="text-right">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in invoice.payments" :key="payment.id" class="border-b">
                <td>{{ payment.payment_date }}</td>
                <td>{{ payment.payment_method }}</td>
                <td>{{ payment.reference_number || '—' }}</td>
                <td class="text-right">${{ Number(payment.amount).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>