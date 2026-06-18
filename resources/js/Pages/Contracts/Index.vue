<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

interface Contract {
  id: string;
  title: string;
  type: string;
  status: string;
  value: number;
  currency: string;
  start_date: string;
  end_date: string;
  account: { id: string; name: string } | null;
  contact: { id: string; first_name: string; last_name: string } | null;
}

const props = defineProps<{
  contracts: { data: Contract[] };
  contractTypes: string[];
  statuses: string[];
  filters: { search?: string; status?: string; type?: string; account_id?: string; account_manager_id?: string };
}>();

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  type: props.filters?.type || '',
});

watch(filters, (newFilters) => {
  router.get('/contracts', {
    ...newFilters,
    status: newFilters.status === 'all' ? '' : newFilters.status,
    type: newFilters.type === 'all' ? '' : newFilters.type,
  }, { preserveState: true, replace: true });
}, { deep: true });

const getStatusVariant = (status: string) => {
  const map: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline',
    sent: 'secondary',
    signed: 'default',
    active: 'default',
    expiring: 'outline',
    expired: 'destructive',
    declined: 'destructive',
    terminated: 'destructive',
  };
  return map[status] || 'secondary';
};
</script>

<template>
  <AppLayout>
    <Head title="Contracts" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Contracts</h1>
          <p class="text-gray-500">Manage agreements, templates, and e-signature status.</p>
        </div>
        <Link href="/contracts/create">
          <Button>Create Contract</Button>
        </Link>
      </div>

      <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
        <div class="p-4 border-b flex gap-4 flex-wrap">
          <Input v-model="filters.search" placeholder="Search contracts..." class="max-w-xs" />
          <Select v-model="filters.status">
            <SelectTrigger class="w-[180px]">
              <SelectValue placeholder="All statuses" />
            </SelectTrigger>
<SelectContent>
               <SelectItem value="all">All Statuses</SelectItem>
               <SelectItem v-for="status in statuses" :key="status" :value="status">
                 {{ status }}
               </SelectItem>
             </SelectContent>
           </Select>
           <Select v-model="filters.type">
             <SelectTrigger class="w-[180px]">
               <SelectValue placeholder="All types" />
             </SelectTrigger>
             <SelectContent>
               <SelectItem value="all">All Types</SelectItem>
              <SelectItem v-for="type in contractTypes" :key="type" :value="type">
                {{ type }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Contract</TableHead>
              <TableHead>Account</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Value</TableHead>
              <TableHead>End Date</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="contract in contracts.data" :key="contract.id" class="cursor-pointer hover:bg-gray-50" @click="router.get(`/contracts/${contract.id}`)">
              <TableCell class="font-medium">{{ contract.title }}</TableCell>
              <TableCell>{{ contract.account?.name || '—' }}</TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(contract.status)">{{ contract.status }}</Badge>
              </TableCell>
              <TableCell class="capitalize">{{ contract.type }}</TableCell>
              <TableCell>{{ contract.value ? `${contract.currency || 'USD'} ${Number(contract.value).toLocaleString()}` : '—' }}</TableCell>
              <TableCell>{{ contract.end_date ? new Date(contract.end_date).toLocaleDateString() : '—' }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
