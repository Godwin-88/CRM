<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
  relationship: any;
  canViewFinancials: boolean;
}>();
</script>

<template>
  <AppLayout>
    <Head :title="relationship.institution_name" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/banking" class="text-blue-600 hover:underline text-sm">← Back to Banking</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ relationship.institution_name }}</CardTitle>
              <p class="text-gray-500">{{ relationship.relationship_type }}</p>
            </div>
            <Badge variant="default">Active</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-2 gap-4">
            <div>
              <dt class="text-sm text-gray-500">Relationship Manager</dt>
              <dd>{{ relationship.relationship_manager_name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Email</dt>
              <dd>{{ relationship.relationship_manager_email }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Phone</dt>
              <dd>{{ relationship.relationship_manager_phone }}</dd>
            </div>
            <div v-if="canViewFinancials">
              <dt class="text-sm text-gray-500">Account Number</dt>
              <dd>{{ relationship.account_number || '—' }}</dd>
            </div>
            <div v-if="canViewFinancials && relationship.credit_limit">
              <dt class="text-sm text-gray-500">Credit Limit</dt>
              <dd>${{ Number(relationship.credit_limit).toLocaleString() }}</dd>
            </div>
            <div v-if="relationship.facility_expiry_date">
              <dt class="text-sm text-gray-500">Facility Expiry</dt>
              <dd>{{ relationship.facility_expiry_date }}</dd>
            </div>
            <div v-if="canViewFinancials && relationship.interest_rate">
              <dt class="text-sm text-gray-500">Interest Rate</dt>
              <dd>{{ relationship.interest_rate }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>