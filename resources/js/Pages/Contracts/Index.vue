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
import { Check, Download, FileText, Filter, RefreshCw, Search } from 'lucide-vue-next';

interface Contract {
  id: string;
  title: string;
  type: string;
  status: string;
  value: number;
  currency: string;
  start_date: string;
  end_date: string;
  days_remaining: number | null;
  days_since_expiry: number | null;
  account: { id: string; name: string } | null;
  contact: { id: string; first_name: string; last_name: string } | null;
  accountManager: { id: string; name: string } | null;
  tags: { id: string; name: string; color: string }[];
  current_version: number;
  created_at: string;
}

const props = defineProps<{
  contracts: { data: Contract[] };
  contractTypes: string[];
  statuses: string[];
  filters: { search?: string; status?: string; type?: string; account_id?: string; account_manager_id?: string; start_date_from?: string; start_date_to?: string; end_date_from?: string; end_date_to?: string; value_min?: string; value_max?: string; per_page?: string | number; sort?: string; direction?: string };
}>();

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  type: props.filters?.type || '',
  account_id: props.filters?.account_id || '',
  account_manager_id: props.filters?.account_manager_id || '',
  start_date_from: props.filters?.start_date_from || '',
  start_date_to: props.filters?.start_date_to || '',
  end_date_from: props.filters?.end_date_from || '',
  end_date_to: props.filters?.end_date_to || '',
  value_min: props.filters?.value_min || '',
  value_max: props.filters?.value_max || '',
  per_page: String(props.filters?.per_page || 25),
});

const sortKey = ref(props.filters?.sort || 'end_date');
const sortDirection = ref(props.filters?.direction || 'asc');
const selectedIds = ref<string[]>([]);
const previewContract = ref<Contract | null>(null);
const previewUrl = ref('');
const previewLoading = ref(false);
const previewError = ref('');

const perPageOptions = [25, 50, 100];

const sortedContracts = computed(() => {
  const rows = [...(props.contracts.data || [])];

  if (!sortKey.value) {
    return rows;
  }

  return rows.sort((a, b) => {
    const left = getSortValue(a, sortKey.value);
    const right = getSortValue(b, sortKey.value);

    if (left === right) {
      return 0;
    }

    return sortDirection.value === 'asc' ? (left > right ? 1 : -1) : (left < right ? 1 : -1);
  });
});

const totalValue = computed(() => sortedContracts.value.reduce((sum, contract) => sum + Number(contract.value || 0), 0));
const expiringCount = computed(() => sortedContracts.value.filter((contract) => contract.status === 'expiring' || (contract.days_remaining !== null && contract.days_remaining <= 90 && contract.days_remaining >= 0)).length);
const activeCount = computed(() => sortedContracts.value.filter((contract) => contract.status === 'active').length);
const draftCount = computed(() => sortedContracts.value.filter((contract) => contract.status === 'draft').length);
const selectedCount = computed(() => selectedIds.value.length);

watch(filters, (newFilters) => {
  router.get('/contracts', cleanFilters(newFilters), { preserveState: true, replace: true });
}, { deep: true });

const cleanFilters = (value: typeof filters.value) => {
  const query: Record<string, string> = { ...value };

  Object.keys(query).forEach((key) => {
    if (query[key] === 'all' || query[key] === '') {
      delete query[key];
    }
  });

  if (sortKey.value) {
    query.sort = sortKey.value;
    query.direction = sortDirection.value;
  }

  return query;
};

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    type: '',
    account_id: '',
    account_manager_id: '',
    start_date_from: '',
    start_date_to: '',
    end_date_from: '',
    end_date_to: '',
    value_min: '',
    value_max: '',
    per_page: '25',
  };
  sortKey.value = 'end_date';
  sortDirection.value = 'asc';
};

const toggleSelection = (id: string) => {
  const index = selectedIds.value.indexOf(id);
  if (index === -1) {
    selectedIds.value.push(id);
  } else {
    selectedIds.value.splice(index, 1);
  }
};

const toggleAllVisible = (checked: boolean) => {
  const ids = sortedContracts.value.map((contract) => contract.id);
  selectedIds.value = checked ? Array.from(new Set([...selectedIds.value, ...ids])) : selectedIds.value.filter((id) => !ids.includes(id));
};

const isRowSelected = (id: string) => selectedIds.value.includes(id);

const exportSelected = () => {
  if (!selectedIds.value.length) {
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/contracts/bulk-export';

  const token = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
  if (token) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_token';
    input.value = token.content;
    form.appendChild(input);
  }

  selectedIds.value.forEach((id) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'contract_ids[]';
    input.value = id;
    form.appendChild(input);
  });

  document.body.appendChild(form);
  form.submit();
  form.remove();
};

const setSort = (key: string) => {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDirection.value = 'asc';
  }

  router.get('/contracts', cleanFilters(filters.value), { preserveState: true, replace: true });
};

const getSortValue = (contract: Contract, key: string) => {
  switch (key) {
    case 'title':
      return contract.title.toLowerCase();
    case 'account':
      return contract.account?.name.toLowerCase() || '';
    case 'status':
      return contract.status;
    case 'type':
      return contract.type;
    case 'value':
      return Number(contract.value || 0);
    case 'start_date':
      return contract.start_date || '';
    case 'end_date':
      return contract.end_date || '';
    case 'created_at':
      return contract.created_at || '';
    default:
      return contract.end_date || '';
  }
};

const formatDate = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (contract: Contract) => {
  if (!contract.value) return '—';
  return `${contract.currency || 'USD'} ${Number(contract.value).toLocaleString()}`;
};

const formatDays = (contract: Contract) => {
  if (contract.days_remaining === null || contract.days_remaining === undefined) return '—';
  if (contract.days_remaining < 0) return `${Math.abs(contract.days_remaining)} days overdue`;
  if (contract.days_remaining === 0) return 'Due today';
  return `${contract.days_remaining} days remaining`;
};

const capitalize = (value?: string) => {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const statusClass = (status: string) => {
  const map: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 ring-gray-200',
    sent: 'bg-blue-50 text-blue-700 ring-blue-200',
    signed: 'bg-teal-50 text-teal-700 ring-teal-200',
    active: 'bg-green-50 text-green-700 ring-green-200',
    expiring: 'bg-amber-50 text-amber-700 ring-amber-200',
    expired: 'bg-red-50 text-red-700 ring-red-200',
    declined: 'bg-rose-50 text-rose-700 ring-rose-200',
    terminated: 'bg-slate-100 text-slate-700 ring-slate-200',
  };

  return map[status] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const sortLabel = (key: string) => {
  const active = sortKey.value === key;
  return `${capitalize(key)}${active ? (sortDirection.value === 'asc' ? ' ↑' : ' ↓') : ''}`;
};

const loadPreview = async (contract: Contract) => {
  previewError.value = '';

  if (previewContract.value?.id === contract.id && previewUrl.value) {
    return;
  }

  previewContract.value = contract;
  previewLoading.value = true;

  try {
    const response = await fetch(`/contracts/${contract.id}/download`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
      },
    });

    if (!response.ok) {
      throw new Error('Unable to load signed PDF URL.');
    }

    const data = await response.json();
    previewUrl.value = data.url;
  } catch (error) {
    previewError.value = error instanceof Error ? error.message : 'Unable to load signed PDF URL.';
  } finally {
    previewLoading.value = false;
  }
};

const clearPreview = () => {
  previewContract.value = null;
  previewUrl.value = '';
  previewError.value = '';
};
</script>

<template>
  <AppLayout>
    <Head title="Contracts" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Contracts</h1>
          <p class="text-gray-500">Searchable repository for agreements, signatures, renewals, and compliance activity.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link href="/contracts/create">
            <Button>Generate Contract</Button>
          </Link>
          <Link href="/admin/contract-templates">
            <Button variant="outline">Templates</Button>
          </Link>
          <Button v-if="selectedCount" variant="secondary" @click="exportSelected">
            <Download class="mr-2 h-4 w-4" />
            Export {{ selectedCount }}
          </Button>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Portfolio value</div>
          <div class="mt-1 text-2xl font-semibold">{{ (props.contracts.data || []).length ? formatCurrency({ ...props.contracts.data[0], value: totalValue, currency: props.contracts.data[0].currency || 'USD' }) : '—' }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Active contracts</div>
          <div class="mt-1 text-2xl font-semibold">{{ activeCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Expiring soon</div>
          <div class="mt-1 text-2xl font-semibold" :class="expiringCount ? 'text-amber-600' : ''">{{ expiringCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Drafts</div>
          <div class="mt-1 text-2xl font-semibold">{{ draftCount }}</div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_390px]">
        <div class="space-y-4">
          <div class="rounded-xl border bg-white shadow-sm">
            <div class="space-y-3 border-b p-4">
              <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="relative flex-1">
                  <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                  <Input v-model="filters.search" class="pl-9" placeholder="Search title, account, or contact..." />
                </div>
                <Button variant="ghost" size="sm" @click="resetFilters">
                  <RefreshCw class="mr-2 h-4 w-4" />
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
                    <SelectItem v-for="type in contractTypes" :key="type" :value="type">{{ capitalize(type) }}</SelectItem>
                  </SelectContent>
                </Select>

                <Select v-model="filters.per_page">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="option in perPageOptions" :key="option" :value="String(option)">{{ option }} rows</SelectItem>
                  </SelectContent>
                </Select>

                <Input v-model="filters.account_id" placeholder="Account ID" />
              </div>

              <div class="grid gap-3 md:grid-cols-4">
                <Input v-model="filters.start_date_from" type="date" />
                <Input v-model="filters.start_date_to" type="date" />
                <Input v-model="filters.end_date_from" type="date" />
                <Input v-model="filters.end_date_to" type="date" />
                <Input v-model="filters.value_min" type="number" placeholder="Min value" />
                <Input v-model="filters.value_max" type="number" placeholder="Max value" />
                <Input v-model="filters.account_manager_id" placeholder="Account manager ID" />
                <div class="flex items-center rounded-md border bg-gray-50 px-3 text-sm text-gray-500">
                  <Filter class="mr-2 h-4 w-4" />
                  Filters apply instantly
                </div>
              </div>
            </div>

            <div class="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-10">
                      <input
                        type="checkbox"
                        :checked="sortedContracts.length > 0 && sortedContracts.every((contract) => selectedIds.includes(contract.id))"
                        @change="(event: Event) => toggleAllVisible((event.target as HTMLInputElement).checked)"
                      />
                    </TableHead>
                    <TableHead><button type="button" class="font-medium" @click="setSort('title')">{{ sortLabel('title') }}</button></TableHead>
                    <TableHead><button type="button" class="font-medium" @click="setSort('account')">{{ sortLabel('account') }}</button></TableHead>
                    <TableHead><button type="button" class="font-medium" @click="setSort('status')">{{ sortLabel('status') }}</button></TableHead>
                    <TableHead><button type="button" class="font-medium" @click="setSort('type')">{{ sortLabel('type') }}</button></TableHead>
                    <TableHead><button type="button" class="font-medium" @click="setSort('value')">{{ sortLabel('value') }}</button></TableHead>
                    <TableHead><button type="button" class="font-medium" @click="setSort('end_date')">{{ sortLabel('end_date') }}</button></TableHead>
                    <TableHead class="text-right">Preview</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="contract in sortedContracts"
                    :key="contract.id"
                    class="cursor-pointer transition-colors hover:bg-gray-50"
                    :class="{ 'bg-blue-50/70': isRowSelected(contract.id) }"
                    @click="router.get(`/contracts/${contract.id}`)"
                  >
                    <TableCell @click.stop>
                      <input type="checkbox" :checked="isRowSelected(contract.id)" @change="(event: Event) => toggleSelection(contract.id)" />
                    </TableCell>
                    <TableCell>
                      <div class="font-medium">{{ contract.title }}</div>
                      <div class="text-xs text-gray-500">v{{ contract.current_version || 1 }}</div>
                    </TableCell>
                    <TableCell>
                      <div>{{ contract.account?.name || '—' }}</div>
                      <div class="text-xs text-gray-500">{{ contract.contact ? `${contract.contact.first_name} ${contract.contact.last_name}` : 'No contact' }}</div>
                    </TableCell>
                    <TableCell>
                      <Badge class="capitalize ring-1" :class="statusClass(contract.status)">{{ capitalize(contract.status) }}</Badge>
                    </TableCell>
                    <TableCell class="capitalize">{{ capitalize(contract.type) }}</TableCell>
                    <TableCell>{{ formatCurrency(contract) }}</TableCell>
                    <TableCell>
                      <div>{{ formatDate(contract.end_date) }}</div>
                      <div class="text-xs" :class="contract.days_remaining !== null && contract.days_remaining <= 30 ? 'text-amber-600' : 'text-gray-500'">
                        {{ formatDays(contract) }}
                      </div>
                    </TableCell>
                    <TableCell class="text-right" @click.stop>
                      <Button size="sm" variant="ghost" @click="loadPreview(contract)">
                        <FileText class="mr-1 h-4 w-4" />
                        Preview
                      </Button>
                    </TableCell>
                  </TableRow>
                  <TableRow v-if="!sortedContracts.length">
                    <TableCell colspan="8" class="py-10 text-center text-sm text-gray-500">
                      No contracts match the current filters.
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>

          <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ selectedCount }} selected</span>
            <span>{{ props.contracts.data.length }} contracts on this page</span>
          </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
              <div>
                <div class="text-sm font-semibold">PDF Preview</div>
                <div class="text-xs text-gray-500">Fresh signed URL on each load</div>
              </div>
              <Button v-if="previewContract" size="sm" variant="ghost" @click="clearPreview">Clear</Button>
            </div>

            <div v-if="!previewContract" class="rounded-lg border border-dashed p-6 text-center text-sm text-gray-500">
              Select Preview on any contract to inspect the active PDF without leaving the repository.
            </div>

            <div v-else class="space-y-3">
              <div>
                <div class="font-medium">{{ previewContract.title }}</div>
                <div class="text-xs text-gray-500">{{ previewContract.account?.name || 'No account' }} · {{ capitalize(previewContract.status) }}</div>
              </div>
              <Button class="w-full" variant="outline" :disabled="previewLoading" @click="loadPreview(previewContract)">
                <RefreshCw v-if="previewLoading" class="mr-2 h-4 w-4 animate-spin" />
                <Check v-else class="mr-2 h-4 w-4" />
                Refresh signed URL
              </Button>
              <p v-if="previewError" class="text-sm text-red-600">{{ previewError }}</p>
              <iframe v-if="previewUrl" :src="previewUrl" class="h-[720px] w-full rounded-lg border bg-gray-50" title="Contract PDF preview" />
            </div>
          </div>

          <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="mb-3 text-sm font-semibold">Repository guidance</div>
            <ul class="space-y-2 text-sm text-gray-600">
              <li>Use status, type, date, and value filters to isolate renewals.</li>
              <li>Preview uses a fresh 15-minute signed URL every time.</li>
              <li>Select multiple contracts to export PDFs as a batch.</li>
            </ul>
          </div>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>
