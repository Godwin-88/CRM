<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps<{
  relationships: any;
  types: string[];
  filters: any;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Banking Relationships" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Banking Relationships</h1>
        <Link href="/banking/create">
          <Button><PlusIcon class="w-4 h-4 mr-2" />New Relationship</Button>
        </Link>
      </div>

      <Card>
        <CardContent class="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Institution</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Manager</TableHead>
                <TableHead>Credit Limit</TableHead>
                <TableHead>Expiry</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="rel in relationships.data" :key="rel.id">
                <TableCell>{{ rel.institution_name }}</TableCell>
                <TableCell>{{ rel.relationship_type }}</TableCell>
                <TableCell>{{ rel.relationship_manager_name }}</TableCell>
                <TableCell>{{ rel.credit_limit ? '$' + rel.credit_limit : '—' }}</TableCell>
                <TableCell>{{ rel.facility_expiry_date || '—' }}</TableCell>
                <TableCell>
                  <Link :href="`/banking/${rel.id}`" class="text-blue-600 hover:underline text-sm">View</Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>