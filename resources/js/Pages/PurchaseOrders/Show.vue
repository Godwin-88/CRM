<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const props = defineProps<{
  purchaseOrder: any;
  canApprove: boolean;
  canReceive: boolean;
}>();
</script>

<template>
  <AppLayout>
    <Head :title="purchaseOrder.po_number" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/purchase-orders" class="text-blue-600 hover:underline text-sm">← Back to Purchase Orders</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ purchaseOrder.po_number }}</CardTitle>
              <p class="text-gray-500">{{ purchaseOrder.vendor?.name }}</p>
            </div>
            <Badge :variant="purchaseOrder.status === 'approved' || purchaseOrder.status === 'received' ? 'default' : 'outline'">
              {{ purchaseOrder.status }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-3 gap-4 mb-4">
            <div>
              <dt class="text-sm text-gray-500">Created</dt>
              <dd>{{ purchaseOrder.created_at }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Required By</dt>
              <dd>{{ purchaseOrder.required_by_date }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Currency</dt>
              <dd>{{ purchaseOrder.currency }}</dd>
            </div>
            <div v-if="purchaseOrder.approved_at">
              <dt class="text-sm text-gray-500">Approved</dt>
              <dd>{{ purchaseOrder.approved_at }} by {{ purchaseOrder.approver?.name }}</dd>
            </div>
            <div v-if="purchaseOrder.received_at">
              <dt class="text-sm text-gray-500">Received</dt>
              <dd>{{ purchaseOrder.received_at }}</dd>
            </div>
          </dl>

          <div class="flex gap-2 mb-4">
            <form :action="`/purchase-orders/${purchaseOrder.id}/submit`" method="POST">
              <input type="hidden" name="_token" :value="$page.props.csrf_token" />
              <Button type="submit" variant="outline" v-if="purchaseOrder.status === 'draft'">Submit for Approval</Button>
            </form>
            <form :action="`/purchase-orders/${purchaseOrder.id}/approve`" method="POST" v-if="canApprove && purchaseOrder.status === 'submitted'">
              <input type="hidden" name="_token" :value="$page.props.csrf_token" />
              <input type="hidden" name="approved" value="true" />
              <Button type="submit">Approve</Button>
            </form>
          </div>
        </CardContent>
      </Card>

      <Card class="mb-6">
        <CardHeader><CardTitle>Line Items</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Description</TableHead>
                <TableHead>Qty</TableHead>
                <TableHead>Unit Price</TableHead>
                <TableHead>Tax</TableHead>
                <TableHead>Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in purchaseOrder.line_items" :key="item.id">
                <TableCell>{{ item.description }}</TableCell>
                <TableCell>{{ item.quantity }}</TableCell>
                <TableCell>${{ Number(item.unit_price).toLocaleString() }}</TableCell>
                <TableCell>{{ item.tax_rate }}%</TableCell>
                <TableCell>${{ Number(item.line_total).toLocaleString() }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div class="mt-4 text-right">
            <p>Subtotal: ${{ Number(purchaseOrder.subtotal).toLocaleString() }}</p>
            <p>Tax: ${{ Number(purchaseOrder.total_tax).toLocaleString() }}</p>
            <p class="font-bold">Total: ${{ Number(purchaseOrder.total).toLocaleString() }}</p>
          </div>
        </CardContent>
      </Card>

      <Card v-if="purchaseOrder.vendor_invoices?.length">
        <CardHeader><CardTitle>Vendor Invoices</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Invoice #</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="inv in purchaseOrder.vendor_invoices" :key="inv.id">
                <TableCell>{{ inv.vendor_invoice_number }}</TableCell>
                <TableCell>{{ inv.invoice_date }}</TableCell>
                <TableCell>${{ Number(inv.total).toLocaleString() }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>