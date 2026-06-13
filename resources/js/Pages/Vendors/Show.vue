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
              <p class="text-gray-500">{{ vendor.category }}</p>
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
            <div v-if="canViewFinancials">
              <dt class="text-sm text-gray-500">Account Number</dt>
              <dd>{{ vendor.account_number || vendor.masked_account_number }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card v-if="vendor.ratings?.length">
        <CardHeader><CardTitle>Vendor Ratings</CardTitle></CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="rating in vendor.ratings" :key="rating.id">
              <p class="font-medium">{{ rating.rater?.name }} - {{ rating.rated_at }}</p>
              <p>Quality: {{ rating.quality }}/5, Delivery: {{ rating.delivery_timeliness }}/5, Communication: {{ rating.communication }}/5, Value: {{ rating.value_for_money }}/5</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>