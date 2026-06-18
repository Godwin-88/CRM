<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
import { Briefcase, Clock, Filter, Search, ShieldCheck } from 'lucide-vue-next';

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
  resolved_at: string | null;
  closed_at: string | null;
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

const sortKey = ref('created_at');
const sortDirection = ref('desc');

const sortedMatters = computed(() => {
  const rows = [...(props.matters.data || [])];
  return rows.sort((a, b) => {
    const left = getSortValue(a, sortKey.value);
    const right = getSortValue(b, sortKey.value);
    return sortDirection.value === 'asc' ? (left > right ? 1 : -1) : (left < right ? 1 : -1);
  });
});

const openCount = computed(() => sortedMatters.value.filter((matter) => ['open', 'in_progress', 'pending_external'].includes(matter.status)).length);
const resolvedCount = computed(() => sortedMatters.value.filter((matter) => ['resolved', 'closed'].includes(matter.status)).length);
const disputeCount = computed(() => sortedMatters.value.filter((matter) => matter.type === 'dispute').length);
const unassignedCount = computed(() => sortedMatters.value.filter((matter) => !matter.assignee).length);

watch(filters, (newFilters) => {
  router.get('/legal', cleanFilters(newFilters), { preserveState: true, replace: true });
}, { deep: true });

const cleanFilters = (value: typeof filters.value) => {
  const query: Record<string, string> = { ...value };

  Object.keys(query).forEach((key) => {
    if (query[key] === 'all' || query[key] === '') {
      delete query[key];
    }
  });

  query.sort = sortKey.value;
  query.direction = sortDirection.value;

  return query;
};

const resetFilters = () => {
  filters.value = { search: '', status: '', type: '', assigned_to: '' };
  sortKey.value = 'created_at';
  sortDirection.value = 'desc';
};

const setSort = (key: string) => {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDirection.value = 'asc';
  }

  router.get('/legal', cleanFilters(filters.value), { preserveState: true, replace: true });
};

const getSortValue = (matter: LegalMatter, key: string) => {
  switch (key) {
    case 'subject':
      return matter.subject.toLowerCase();
    case 'account':
      return matter.account?.name.toLowerCase() || '';
    case 'status':
      return matter.status;
    case 'type':
      return matter.type;
    case 'assignee':
      return matter.assignee?.name.toLowerCase() || 'unassigned';
    case 'created_at':
      return matter.created_at;
    default:
      return matter.created_at;
  }
};

const formatDate = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const capitalize = (value?: string) => {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const statusClass = (status: string) => {
  const map: Record<string, string> = {
    open: 'bg-gray-100 text-gray-700 ring-gray-200',
    in_progress: 'bg-blue-50 text-blue-700 ring-blue-200',
    pending_external: 'bg-amber-50 text-amber-700 ring-amber-200',
    resolved: 'bg-green-50 text-green-700 ring-green-200',
    closed: 'bg-slate-100 text-slate-700 ring-slate-200',
  };

  return map[status] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const typeClass = (type: string) => {
  const map: Record<string, string> = {
    dispute: 'bg-rose-50 text-rose-700 ring-rose-200',
    correspondence: 'bg-blue-50 text-blue-700 ring-blue-200',
    regulatory: 'bg-purple-50 text-purple-700 ring-purple-200',
    advisory: 'bg-green-50 text-green-700 ring-green-200',
    custom: 'bg-gray-100 text-gray-700 ring-gray-200',
  };

  return map[type] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const sortLabel = (key: string) => `${capitalize(key)}${sortKey.value === key ? (sortDirection.value === 'asc' ? ' ↑' : ' ↓') : ''}`;
</script>

<template>
  <AppLayout>
    <Head title="Legal Matters" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Legal Matters</h1>
          <p class="text-gray-500">Immutable case records, notes, attachments, and linked contracts.</p>
        </div>
        <Link href="/legal/create">
          <Button>New Matter</Button>
        </Link>
      </div>

      <div class="grid gap-3 md:grid-cols-5">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Open matters</div>
          <div class="mt-1 text-2xl font-semibold">{{ openCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Resolved / closed</div>
          <div class="mt-1 text-2xl font-semibold">{{ resolvedCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Disputes</div>
          <div class="mt-1 text-2xl font-semibold">{{ disputeCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Unassigned</div>
          <div class="mt-1 text-2xl font-semibold" :class="unassignedCount ? 'text-amber-600' : ''">{{ unassignedCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Total</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.matters.data.length }}</div>
        </div>
      </div>

      <div class="rounded-xl border bg-white shadow-sm">
        <div class="space-y-3 border-b p-4">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="relative flex-1">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <Input v-model="filters.search" class="pl-9" placeholder="Search subject, description, account, or contact..." />
            </div>
            <Button variant="ghost" size="sm" @click="resetFilters">
              <Filter class="mr-2 h-4 w-4" />
              Reset
            </Button>
          </div>

          <div class="grid gap-3 md:grid-cols-4">
            <Select v-model="filters.status">
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem v-for="status in statuses" :key="status" :value="status">{{ capitalize(status) }}</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="filters.type">
              <SelectTrigger>
                <SelectValue placeholder="All types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Types</SelectItem>
                <SelectItem v-for="type in types" :key="type" :value="type">{{ capitalize(type) }}</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="filters.assigned_to">
              <SelectTrigger>
                <SelectValue placeholder="All assignees" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Assignees</SelectItem>
                <SelectItem v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</SelectItem>
              </SelectContent>
            </Select>

            <div class="flex items-center rounded-md border bg-gray-50 px-3 text-sm text-gray-500">
              <ShieldCheck class="mr-2 h-4 w-4" />
              Notes are append-only
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead><button type="button" class="font-medium" @click="setSort('subject')">{{ sortLabel('subject') }}</button></TableHead>
                <TableHead><button type="button" class="font-medium" @click="setSort('account')">{{ sortLabel('account') }}</button></TableHead>
                <TableHead><button type="button" class="font-medium" @click="setSort('status')">{{ sortLabel('status') }}</button></TableHead>
                <TableHead><button type="button" class="font-medium" @click="setSort('type')">{{ sortLabel('type') }}</button></TableHead>
                <TableHead><button type="button" class="font-medium" @click="setSort('assignee')">{{ sortLabel('assignee') }}</button></TableHead>
                <TableHead><button type="button" class="font-medium" @click="setSort('created_at')">{{ sortLabel('created_at') }}</button></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="matter in sortedMatters" :key="matter.id" class="cursor-pointer transition-colors hover:bg-gray-50" @click="router.get(`/legal/${matter.id}`)">
                <TableCell>
                  <div class="font-medium">{{ matter.subject }}</div>
                  <div class="text-xs text-gray-500 line-clamp-1">{{ matter.description || 'No description' }}</div>
                </TableCell>
                <TableCell>
                  <div>{{ matter.account?.name || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ matter.contact ? `${matter.contact.first_name} ${matter.contact.last_name}` : 'No contact' }}</div>
                </TableCell>
                <TableCell><Badge class="capitalize ring-1" :class="statusClass(matter.status)">{{ capitalize(matter.status) }}</Badge></TableCell>
                <TableCell><Badge class="capitalize ring-1" :class="typeClass(matter.type)">{{ capitalize(matter.type) }}</Badge></TableCell>
                <TableCell>{{ matter.assignee?.name || 'Unassigned' }}</TableCell>
                <TableCell>
                  <div>{{ formatDate(matter.created_at) }}</div>
                  <div v-if="matter.resolved_at || matter.closed_at" class="text-xs text-gray-500">
                    <Clock class="mr-1 inline h-3 w-3" />
                    {{ formatDate(matter.resolved_at || matter.closed_at) }}
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="!sortedMatters.length">
                <TableCell colspan="6" class="py-10 text-center text-sm text-gray-500">No legal matters match the current filters.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <div class="rounded-xl border bg-blue-50 p-4 text-sm text-blue-800">
        <div class="flex gap-3">
          <Briefcase class="h-5 w-5 shrink-0" />
          <div>
            <div class="font-semibold">Legal workspace standard</div>
            <p>Use matter detail pages for append-only notes, R2-backed attachments, linked contracts, and status transitions.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
