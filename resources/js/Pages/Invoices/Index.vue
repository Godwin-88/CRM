<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps<{
  invoices: any;
  accounts: any[];
  statuses: string[];
  filters: any;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Invoices" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Invoices</h1>
        <Link href="/invoices/create">
          <Button><PlusIcon class="w-4 h-4 mr-2" />New Invoice</Button>
        </Link>
      </div>

      <Card>
        <CardContent class="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Invoice #</TableHead>
                <TableHead>Account</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Due Date</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="invoice in invoices.data" :key="invoice.id">
                <TableCell>{{ invoice.invoice_number }}</TableCell>
                <TableCell>
                  <Link :href="`/accounts/${invoice.account?.id}`" class="text-blue-600 hover:underline" v-if="invoice.account">
                    {{ invoice.account.name }}
                  </Link>
                  <span v-else>—</span>
                </TableCell>
                <TableCell>{{ invoice.created_at }}</TableCell>
                <TableCell>{{ invoice.due_date }}</TableCell>
                <TableCell>${{ Number(invoice.total).toLocaleString() }}</TableCell>
                <TableCell>
                  <Badge :variant="invoice.status === 'paid' ? 'default' : 'outline'">
                    {{ invoice.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Link :href="`/invoices/${invoice.id}`" class="text-blue-600 hover:underline text-sm">View</Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>