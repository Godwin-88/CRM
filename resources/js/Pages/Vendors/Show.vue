<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  vendor: any;
  canViewFinancials: boolean;
  purchaseOrders: any[];
}>();
</script>

<template>
  <AppLayout>
    <Head :title="vendor.name" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/vendors" class="text-blue-600 hover:underline text-sm">← Back to Vendors</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ vendor.name }}</CardTitle>
              <p class="text-gray-500 capitalize">{{ vendor.category }}</p>
            </div>
            <Badge :variant="vendor.status === 'active' ? 'default' : 'secondary'">
              {{ vendor.status }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-2 gap-4">
            <div>
              <dt class="text-sm text-gray-500">Contact</dt>
              <dd>{{ vendor.primary_contact_name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Email</dt>
              <dd>{{ vendor.primary_contact_email }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Phone</dt>
              <dd>{{ vendor.primary_contact_phone }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Registration #</dt>
              <dd>{{ vendor.registration_number || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Tax ID</dt>
              <dd>{{ vendor.tax_identification_number || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Website</dt>
              <dd><a v-if="vendor.website" :href="vendor.website" target="_blank" class="text-blue-600 hover:underline">{{ vendor.website }}</a><span v-else>—</span></dd>
            </div>
            <div v-if="canViewFinancials">
              <dt class="text-sm text-gray-500">Bank</dt>
              <dd>{{ vendor.bank_name }} - {{ vendor.account_number || vendor.masked_account_number }}</dd>
            </div>
            <div v-if="vendor.physical_address">
              <dt class="text-sm text-gray-500">Address</dt>
              <dd>{{ vendor.physical_address }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card v-if="vendor.ratings?.length" class="mb-6">
        <CardHeader><CardTitle>Vendor Ratings</CardTitle></CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="rating in vendor.ratings" :key="rating.id" class="border-b pb-2">
              <p class="font-medium">{{ rating.rater?.name }} - {{ rating.rated_at }}</p>
              <div class="flex gap-4 text-sm">
                <span>Quality: {{ rating.quality }}/5</span>
                <span>Delivery: {{ rating.delivery_timeliness }}/5</span>
                <span>Communication: {{ rating.communication }}/5</span>
                <span>Value: {{ rating.value_for_money }}/5</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="purchaseOrders?.length">
        <CardHeader><CardTitle>Recent Purchase Orders</CardTitle></CardHeader>
        <CardContent>
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b">
                <th class="text-left">PO #</th>
                <th class="text-left">Status</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="po in purchaseOrders" :key="po.id" class="border-b">
                <td><Link :href="`/purchase-orders/${po.id}`" class="text-blue-600 hover:underline">{{ po.po_number }}</Link></td>
                <td><Badge variant="outline">{{ po.status }}</Badge></td>
                <td class="text-right">{{ po.currency }} {{ Number(po.total).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>