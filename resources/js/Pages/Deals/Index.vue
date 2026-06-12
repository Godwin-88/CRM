<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

interface Deal {
  id: string;
  title: string;
  value: number;
  stage: string;
  expected_close_date: string | null;
  owner: { id: string; name: string } | null;
  account_name: string;
}

const props = defineProps<{
  deals: { data: Deal[] };
  pipelines: { id: string; name: string }[];
  filters: { search?: string; stage?: string; pipeline_id?: string };
}>();

const filters = ref({
  search: props.filters?.search || '',
  stage: props.filters?.stage || '',
  pipeline_id: props.filters?.pipeline_id || '',
});

watch(filters, (newFilters) => {
  router.get('/deals', newFilters, { preserveState: true, replace: true });
}, { deep: true });
</script>

<template>
  <AppLayout>
    <Head title="Deals" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Deals</h1>
          <p class="text-gray-500">Manage your sales opportunities.</p>
        </div>
        <div class="flex gap-2">
          <Link href="/deals/create">
            <Button>Create Deal</Button>
          </Link>
          <Link href="/pipelines">
            <Button variant="outline">Board View</Button>
          </Link>
        </div>
      </div>

      <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
        <div class="p-4 border-b flex gap-4">
          <Input v-model="filters.search" placeholder="Search deals..." class="max-w-xs" />
          <Select v-model="filters.stage">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="All stages" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value=" ">All Stages</SelectItem>
              <SelectItem value="qualification">Qualification</SelectItem>
              <SelectItem value="demo">Demo</SelectItem>
              <SelectItem value="proposal">Proposal</SelectItem>
              <SelectItem value="negotiation">Negotiation</SelectItem>
              <SelectItem value="closed_won">Closed Won</SelectItem>
              <SelectItem value="closed_lost">Closed Lost</SelectItem>
            </SelectContent>
          </Select>
          <Select v-model="filters.pipeline_id">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="All pipelines" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value=" ">All Pipelines</SelectItem>
              <SelectItem v-for="pipeline in pipelines" :key="pipeline.id" :value="pipeline.id">
                {{ pipeline.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Deal</TableHead>
              <TableHead>Account</TableHead>
              <TableHead>Stage</TableHead>
              <TableHead>Value</TableHead>
              <TableHead>Expected Close</TableHead>
              <TableHead>Owner</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="deal in deals.data" :key="deal.id" class="cursor-pointer hover:bg-gray-50" @click="router.get(`/deals/${deal.id}`)">
              <TableCell class="font-medium">{{ deal.title }}</TableCell>
              <TableCell>{{ deal.account_name }}</TableCell>
              <TableCell><Badge>{{ deal.stage }}</Badge></TableCell>
              <TableCell>${{ Number(deal.value || 0).toLocaleString() }}</TableCell>
              <TableCell>{{ deal.expected_close_date ? new Date(deal.expected_close_date).toLocaleDateString() : '—' }}</TableCell>
              <TableCell>{{ deal.owner?.name || '—' }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>