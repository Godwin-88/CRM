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

interface LegalMatter {
  id: string;
  subject: string;
  description: string;
  status: string;
  type: string;
  assigned_to: string | null;
  account: { id: string; name: string } | null;
  contact: { id: string; first_name: string; last_name: string } | null;
  creator: { id: string; name: string };
  assignee: { id: string; name: string } | null;
  created_at: string;
}

const props = defineProps<{
  matters: { data: LegalMatter[] };
  statuses: string[];
  types: string[];
  users: { id: string; name: string }[];
  filters: { search?: string; status?: string; type?: string; assigned_to?: string };
}>();

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  type: props.filters?.type || '',
  assigned_to: props.filters?.assigned_to || '',
});

watch(filters, (newFilters) => {
  router.get('/legal', {
    ...newFilters,
    status: newFilters.status === 'all' ? '' : newFilters.status,
    type: newFilters.type === 'all' ? '' : newFilters.type,
    assigned_to: newFilters.assigned_to === 'all' ? '' : newFilters.assigned_to,
  }, { preserveState: true, replace: true });
}, { deep: true });

const getTypeBadge = (type: string) => {
  const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    dispute: 'destructive',
    correspondence: 'outline',
    regulatory: 'default',
    advisory: 'secondary',
    custom: 'outline',
  };
  return map[type] || 'secondary';
};

const getStatusBadge = (status: string) => {
  const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    open: 'outline',
    in_progress: 'default',
    pending_external: 'secondary',
    resolved: 'default',
    closed: 'secondary',
  };
  return map[status] || 'secondary';
};
</script>

<template>
  <AppLayout>
    <Head title="Legal Matters" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Legal Matters</h1>
          <p class="text-gray-500">Track disputes, correspondence, and regulatory issues.</p>
        </div>
        <Link href="/legal/create">
          <Button>Create Matter</Button>
        </Link>
      </div>

      <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
        <div class="p-4 border-b flex gap-4 flex-wrap">
          <Input v-model="filters.search" placeholder="Search matters..." class="max-w-xs" />
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
               <SelectItem v-for="type in types" :key="type" :value="type">
                 {{ type }}
               </SelectItem>
             </SelectContent>
           </Select>
           <Select v-model="filters.assigned_to">
             <SelectTrigger class="w-[180px]">
               <SelectValue placeholder="All assignees" />
             </SelectTrigger>
             <SelectContent>
               <SelectItem value="all">All Assignees</SelectItem>
              <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Subject</TableHead>
              <TableHead>Account</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Assignee</TableHead>
              <TableHead>Created</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="matter in matters.data" :key="matter.id" class="cursor-pointer hover:bg-gray-50" @click="router.get(`/legal/${matter.id}`)">
              <TableCell class="font-medium">{{ matter.subject }}</TableCell>
              <TableCell>{{ matter.account?.name || '—' }}</TableCell>
              <TableCell>
                <Badge :variant="getStatusBadge(matter.status)">{{ matter.status }}</Badge>
              </TableCell>
              <TableCell>
                <Badge :variant="getTypeBadge(matter.type)" class="capitalize">{{ matter.type }}</Badge>
              </TableCell>
              <TableCell>{{ matter.assignee?.name || 'Unassigned' }}</TableCell>
              <TableCell>{{ new Date(matter.created_at).toLocaleDateString() }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
