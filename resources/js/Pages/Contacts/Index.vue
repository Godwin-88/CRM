<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import ContactForm from '@/Components/Contacts/ContactForm.vue';
import ImportWizard from '@/Components/Contacts/ImportWizard.vue';

const props = defineProps<{
  contacts: { data: any[] };
  filters: { first_name: string; last_name: string; email: string; type: string; status: string };
}>();

const filters = ref({
    first_name: props.filters?.first_name || '',
    last_name: props.filters?.last_name || '',
    email: props.filters?.email || '',
    type: props.filters?.type || '',
    status: props.filters?.status || '',
});

watch(filters, (newFilters) => {
    router.get('/contacts', newFilters, { preserveState: true, replace: true });
}, { deep: true });

const isCreateModalOpen = ref(false);
const isImportOpen = ref(false);
const selectedContacts = ref<string[]>([]);

const selectAll = computed({
  get: () => selectedContacts.value.length === props.contacts.data.length && props.contacts.data.length > 0,
  set: (value: boolean) => {
    selectedContacts.value = value ? props.contacts.data.map((c: any) => c.id) : [];
  }
});

const hasSelection = computed(() => selectedContacts.value.length > 0);

const getTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    lead: 'bg-blue-100 text-blue-800',
    prospect: 'bg-purple-100 text-purple-800',
    customer: 'bg-green-100 text-green-800',
    partner: 'bg-amber-100 text-amber-800',
  };
  return colors[type] || 'bg-gray-100 text-gray-800';
};

const getScoreBadgeClass = (score: number): string => {
  if (score > 80) return 'bg-green-100 text-green-800 border-green-200';
  if (score >= 50) return 'bg-amber-100 text-amber-800 border-amber-200';
  return 'bg-red-100 text-red-800 border-red-200';
};

const exportContacts = () => {
  const params = new URLSearchParams();
  if (selectedContacts.value.length > 0) {
    selectedContacts.value.forEach(id => params.append('ids[]', id));
    router.get('/api/v1/contacts/export?' + params.toString());
  } else {
    router.get('/api/v1/contacts/export');
  }
};

const deleteSelected = async () => {
  if (confirm(`Delete ${selectedContacts.value.length} selected contacts?`)) {
    const response = await fetch('/api/v1/contacts/bulk-delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
      body: JSON.stringify({ ids: selectedContacts.value }),
    });
    if (response.ok) {
      selectedContacts.value = [];
      router.reload();
    }
  }
};
</script>

<template>
  <AppLayout>
    <Head title="Contacts" />
    <div class="max-w-7xl mx-auto">
      <!-- Bulk Action Toolbar -->
      <div v-if="hasSelection" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-blue-800">{{ selectedContacts.length }} contacts selected</span>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="exportContacts">Export Selected</Button>
          <Button variant="destructive" size="sm" @click="deleteSelected">Delete Selected</Button>
        </div>
      </div>

      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold">Contacts</h1>
          <p class="text-gray-500">Manage your stakeholder segments.</p>
        </div>
        <div class="flex gap-2">
          <Dialog v-model:open="isImportOpen">
            <DialogTrigger as-child>
              <Button variant="outline">Import/Export</Button>
            </DialogTrigger>
            <DialogContent class="max-w-3xl max-h-[80vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>Bulk Import/Export Contacts</DialogTitle>
              </DialogHeader>
              <div class="space-y-6">
                <div class="p-4 border rounded-lg bg-gray-50">
                    <h4 class="font-medium mb-2">Bulk Import</h4>
                    <p class="text-sm text-gray-500 mb-4">Download the template first to ensure correct formatting.</p>
                    <a href="/contacts/template" class="block w-full text-center py-2 border rounded hover:bg-gray-100 bg-white mb-4">Download CSV Template</a>
                    <ImportWizard />
                </div>
              </div>
            </DialogContent>
          </Dialog>
          <Dialog v-model:open="isCreateModalOpen">
              <DialogTrigger as-child>
                  <Button>+ Create Contact</Button>
              </DialogTrigger>
            <DialogContent class="max-w-2xl">
              <DialogHeader>
                <DialogTitle>Create New Contact</DialogTitle>
              </DialogHeader>
              <ContactForm @close="isCreateModalOpen = false" />
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div class="bg-white shadow-sm border rounded-lg overflow-hidden">
        <div class="p-4 border-b flex gap-4 flex-wrap">
          <Input v-model="filters.first_name" placeholder="Filter by First Name..." class="max-w-xs" />
          <Input v-model="filters.last_name" placeholder="Filter by Last Name..." class="max-w-xs" />
          <Input v-model="filters.email" placeholder="Filter by Email..." class="max-w-xs" />
          <Select v-model="filters.type">
            <SelectTrigger class="w-[180px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              <SelectItem value="lead">Lead</SelectItem>
              <SelectItem value="prospect">Prospect</SelectItem>
              <SelectItem value="customer">Customer</SelectItem>
              <SelectItem value="partner">Partner</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12">
                <input type="checkbox" :checked="selectAll" @change="selectAll = ($event.target as HTMLInputElement).checked" class="rounded border-gray-300" />
              </TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Score</TableHead>
              <TableHead>Owner</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="contact in contacts.data" :key="contact.id" class="cursor-pointer hover:bg-gray-50" @click="router.get(`/contacts/${contact.id}`)">
              <TableCell class="w-12" @click.stop>
                <input type="checkbox" :checked="selectedContacts.includes(contact.id)" @change="(e) => { const checked = (e.target as HTMLInputElement).checked; if (checked) { selectedContacts.push(contact.id); } else { selectedContacts = selectedContacts.filter(id => id !== contact.id); } }" class="rounded border-gray-300" />
              </TableCell>
              <TableCell class="font-medium">{{ contact.first_name }} {{ contact.last_name }}</TableCell>
              <TableCell>{{ contact.email }}</TableCell>
              <TableCell>
                <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getTypeColor(contact.type)}`">
                  {{ contact.type }}
                </span>
              </TableCell>
              <TableCell><Badge :variant="contact.status === 'active' ? 'default' : 'secondary'">{{ contact.status }}</Badge></TableCell>
              <TableCell>
                <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${getScoreBadgeClass(contact.score || 0)}`">
                  {{ contact.score || 0 }}
                </span>
              </TableCell>
              <TableCell class="text-sm text-gray-500">{{ contact.owner?.name || '—' }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>