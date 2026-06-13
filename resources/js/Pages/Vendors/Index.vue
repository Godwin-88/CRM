<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps<{
  vendors: any;
  categories: string[];
  statuses: string[];
  filters: any;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Vendors" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Vendors</h1>
        <Link href="/vendors/create">
          <Button><PlusIcon class="w-4 h-4 mr-2" />New Vendor</Button>
        </Link>
      </div>

      <Card>
        <CardContent class="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Contact</TableHead>
                <TableHead>Rating</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="vendor in vendors.data" :key="vendor.id">
                <TableCell>{{ vendor.name }}</TableCell>
                <TableCell>{{ vendor.category }}</TableCell>
                <TableCell>{{ vendor.primary_contact_name }}</TableCell>
                <TableCell>
                  <span v-if="vendor.ratings?.length">{{ vendor.overall_rating }} / 5</span>
                  <span v-else class="text-gray-400">—</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="vendor.status === 'active' ? 'default' : 'secondary'">
                    {{ vendor.status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Link :href="`/vendors/${vendor.id}`" class="text-blue-600 hover:underline text-sm">View</Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>