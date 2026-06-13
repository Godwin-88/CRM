<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps<{
  assets: any;
  types: string[];
  statuses: string[];
  filters: any;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Assets" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Assets</h1>
        <Link href="/assets/create">
          <Button><PlusIcon class="w-4 h-4 mr-2" />New Asset</Button>
        </Link>
      </div>

      <Card>
        <CardContent class="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Assigned To</TableHead>
                <TableHead>Book Value</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="asset in assets.data" :key="asset.id">
                <TableCell>{{ asset.name }}</TableCell>
                <TableCell>{{ asset.type }}</TableCell>
                <TableCell>
                  <Badge :variant="asset.status === 'available' ? 'default' : 'secondary'">
                    {{ asset.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <span v-if="asset.assignee">{{ asset.assignee.name }}</span>
                  <span v-else-if="asset.assigned_account">{{ asset.assigned_account.name }}</span>
                  <span v-else>—</span>
                </TableCell>
                <TableCell>${{ Number(asset.book_value || 0).toLocaleString() }}</TableCell>
                <TableCell>
                  <Link :href="`/assets/${asset.id}`" class="text-blue-600 hover:underline text-sm">View</Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>