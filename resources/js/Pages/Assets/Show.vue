<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pencil } from 'lucide-vue-next';

const props = defineProps<{
    asset: any;
    canAssign: boolean;
    canEdit: boolean;
    users: any[];
    accounts: any[];
}>();

const assignForm = useForm({
  assigned_to: '',
  assigned_to_account: '',
  assignment_start_date: '',
  expected_return_date: '',
});

const returnForm = useForm({
  returned_at: '',
  condition_note: '',
});

const submitAssign = () => {
  assignForm.post(`/assets/${props.asset.id}/assign`, {
    onSuccess: () => assignForm.reset(),
  });
};

const submitReturn = () => {
  returnForm.post(`/assets/${props.asset.id}/return`, {
    onSuccess: () => returnForm.reset(),
  });
};
</script>

<template>
  <AppLayout>
    <Head :title="asset.name" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/assets" class="text-blue-600 hover:underline text-sm">← Back to Assets</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ asset.name }}</CardTitle>
              <p class="text-gray-500">{{ asset.type }}</p>
            </div>
            <div class="flex items-center gap-2">
              <Badge :variant="asset.status === 'available' ? 'default' : 'secondary'">
                {{ asset.status }}
              </Badge>
              <Link v-if="canEdit" :href="`/assets/${asset.id}/edit`" class="text-gray-600 hover:text-gray-900">
                <Pencil class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <dt class="text-sm text-gray-500">Identifier</dt>
              <dd>{{ asset.identifier || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Purchase Date</dt>
              <dd>{{ asset.purchase_date || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Book Value</dt>
              <dd>${{ Number(asset.book_value || 0).toLocaleString() }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Depreciation</dt>
              <dd>${{ Number(asset.depreciation || 0).toLocaleString() }}</dd>
            </div>
            <div v-if="asset.total_quantity">
              <dt class="text-sm text-gray-500">Available / Total</dt>
              <dd>{{ asset.available_quantity }} / {{ asset.total_quantity }}</dd>
            </div>
          </dl>

          <div v-if="asset.status === 'available' && canAssign" class="border-t pt-4">
            <h3 class="font-medium mb-2">Assign Asset</h3>
            <div class="grid grid-cols-2 gap-4">
              <select v-model="assignForm.assigned_to" class="border rounded px-2 py-1">
                <option value="">Assign to User</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
              <select v-model="assignForm.assigned_to_account" class="border rounded px-2 py-1">
                <option value="">Assign to Account</option>
                <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</option>
              </select>
              <input v-model="assignForm.assignment_start_date" type="date" class="border rounded px-2 py-1" />
              <input v-model="assignForm.expected_return_date" type="date" placeholder="Expected return" class="border rounded px-2 py-1" />
            </div>
            <Button @click="submitAssign" class="mt-2">Assign</Button>
          </div>

          <div v-if="asset.status === 'assigned'" class="border-t pt-4">
            <h3 class="font-medium mb-2">Return Asset</h3>
            <div class="grid grid-cols-2 gap-4">
              <input v-model="returnForm.returned_at" type="date" class="border rounded px-2 py-1" />
              <input v-model="returnForm.condition_note" placeholder="Condition note" class="border rounded px-2 py-1" />
            </div>
            <Button @click="submitReturn" class="mt-2">Return Asset</Button>
          </div>
        </CardContent>
      </Card>

      <Card v-if="asset.assignments?.length">
        <CardHeader><CardTitle>Assignment History</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Assigned To</TableHead>
                <TableHead>Start Date</TableHead>
                <TableHead>Expected Return</TableHead>
                <TableHead>Returned At</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="assignment in asset.assignments" :key="assignment.id">
                <TableCell>
                  <span v-if="assignment.assignee">{{ assignment.assignee.name }}</span>
                  <span v-else-if="assignment.assigned_account">{{ assignment.assigned_account.name }}</span>
                </TableCell>
                <TableCell>{{ assignment.assignment_start_date }}</TableCell>
                <TableCell>{{ assignment.expected_return_date || '—' }}</TableCell>
                <TableCell>{{ assignment.returned_at || '—' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>