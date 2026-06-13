<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps<{
  purchaseOrders: any;
  vendors: { id: string; name: string }[];
  statuses: string[];
  filters: any;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Purchase Orders" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Purchase Orders</h1>
        <Link href="/purchase-orders/create">
          <Button><PlusIcon class="w-4 h-4 mr-2" />New PO</Button>
        </Link>
      </div>

      <Card>
        <CardContent class="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>PO #</TableHead>
                <TableHead>Vendor</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Required By</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="po in purchaseOrders.data" :key="po.id">
                <TableCell>{{ po.po_number }}</TableCell>
                <TableCell>{{ po.vendor?.name }}</TableCell>
                <TableCell>{{ po.created_at }}</TableCell>
                <TableCell>{{ po.required_by_date }}</TableCell>
                <TableCell>${{ Number(po.total).toLocaleString() }}</TableCell>
                <TableCell>
                  <Badge :variant="po.status === 'approved' || po.status === 'received' ? 'default' : 'outline'">
                    {{ po.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Link :href="`/purchase-orders/${po.id}`" class="text-blue-600 hover:underline text-sm">View</Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>