<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface User {
  id: string;
  name?: string;
}

interface CaseRecord {
  id: string;
  case_number: string;
  title: string;
  type: string;
  status: string;
  priority: string;
  primary_contact?: { id: string; first_name?: string; last_name?: string } | null;
  primary_account?: { id: string; name?: string } | null;
  owner?: User | null;
  signoff_required: boolean;
  signoff_status?: string | null;
  created_at: string;
}

interface ContactOption {
  id: string;
  first_name?: string;
  last_name?: string;
  email?: string;
}

interface AccountOption {
  id: string;
  name?: string;
}

interface Paginated<T> {
  data: T[];
  links: { prev?: string | null; next?: string | null };
}

const cases = ref<CaseRecord[]>([]);
const contacts = ref<ContactOption[]>([]);
const accounts = ref<AccountOption[]>([]);
const isCreateOpen = ref(false);
const isLoading = ref(false);
const error = ref('');
const selectedCase = ref<CaseRecord | null>(null);
const reason = ref('');
const status = ref('');
const linkType = ref('related');
const linkableType = ref('App\\Models\\Ticket');
const linkableId = ref('');

const filterStatus = ref('');
const filterType = ref('');
const filterPriority = ref('');
const filterSearch = ref('');

const newCase = ref({
  title: '',
  type: 'service_delivery',
  priority: 'medium',
  status: 'new',
  primary_contact_id: '',
  primary_account_id: '',
  signoff_required: false,
  metadata: {},
});

const statusOptions = ['new', 'triaged', 'in_progress', 'pending_customer', 'pending_internal', 'resolution_proposed', 'closed', 'reopened'];
const typeOptions = ['service_delivery', 'complaint', 'compliance', 'dispute', 'investigation', 'escalation', 'custom'];
const priorityOptions = ['low', 'medium', 'high', 'urgent'];

const contactName = (record: CaseRecord) => [record.primary_contact?.first_name, record.primary_contact?.last_name].filter(Boolean).join(' ') || record.primary_contact?.id || 'No contact';

const loadReferenceData = async () => {
  try {
    const [contactsRes, accountsRes, usersRes] = await Promise.all([
      fetch('/api/v1/contacts?per_page=200'),
      fetch('/api/v1/accounts?per_page=200'),
      fetch('/api/v1/users?per_page=200'),
    ]);
    if (contactsRes.ok) {
      const payload = await contactsRes.json();
      contacts.value = payload.data ?? [];
    }
    if (accountsRes.ok) {
      const payload = await accountsRes.json();
      accounts.value = payload.data ?? [];
    }
    if (usersRes.ok) {
      const payload = await usersRes.json();
      users.value = payload.data ?? [];
    }
  } catch {
    // ignore reference data load failures
  }
};

const loadCases = async () => {
  isLoading.value = true;
  error.value = '';
  try {
    const response = await fetch('/api/v1/cases?per_page=25');
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const payload = await response.json() as Paginated<CaseRecord>;
    cases.value = payload.data ?? [];
  } catch (e) {
    cases.value = [];
    error.value = 'Failed to load cases.';
  } finally {
    isLoading.value = false;
  }
};

const createCase = async () => {
  error.value = '';
  const response = await fetch('/api/v1/cases', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify(newCase.value),
  });

  if (response.ok) {
    isCreateOpen.value = false;
    newCase.value.title = '';
    newCase.value.primary_contact_id = '';
    newCase.value.primary_account_id = '';
    await loadCases();
    return;
  }

  const payload = await response.json().catch(() => ({}));
  error.value = payload.message || 'Unable to create case.';
};

const changeStatus = async (caseRecord: CaseRecord, nextStatus: string) => {
  await fetch(`/api/v1/cases/${caseRecord.id}/status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ status: nextStatus, reason: reason.value }),
  });
  await loadCases();
};

const requestSignoff = async (caseRecord: CaseRecord) => {
  await fetch(`/api/v1/cases/${caseRecord.id}/signoff`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ reason: reason.value || 'Sign-off requested from UI' }),
  });
  await loadCases();
};

const closeCase = async (caseRecord: CaseRecord) => {
  await fetch(`/api/v1/cases/${caseRecord.id}/close`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ reason: reason.value || 'Closed from UI' }),
  });
  await loadCases();
};

const addNote = async (caseRecord: CaseRecord) => {
  await fetch(`/api/v1/cases/${caseRecord.id}/notes`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ body: reason.value }),
  });
  reason.value = '';
  await loadCases();
};

const addLink = async (caseRecord: CaseRecord) => {
  await fetch(`/api/v1/cases/${caseRecord.id}/links`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ linkable_type: linkableType.value, linkable_id: linkableId.value, link_type: linkType.value }),
  });
  linkableId.value = '';
  await loadCases();
};

const openCase = (caseRecord: CaseRecord) => {
  selectedCase.value = caseRecord;
  status.value = caseRecord.status;
  reason.value = '';
};

onMounted(() => {
  loadReferenceData();
  loadCases();
});
</script>

<template>
  <AppLayout>
    <Head title="Cases" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-start gap-4">
        <div>
          <h1 class="text-3xl font-bold">Cases</h1>
          <p class="text-gray-500">Manage service delivery, complaint, compliance, and escalation cases.</p>
        </div>
        <Button @click="isCreateOpen = true">New Case</Button>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <!-- Filters -->
      <Card>
        <CardHeader><CardTitle class="text-base">Case Queue Filters</CardTitle></CardHeader>
        <CardContent>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <Label>Status</Label>
              <Select v-model="filterStatus">
                <SelectTrigger><SelectValue placeholder="All statuses" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All statuses</SelectItem>
                  <SelectItem v-for="item in statusOptions" :key="item" :value="item">{{ item }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Type</Label>
              <Select v-model="filterType">
                <SelectTrigger><SelectValue placeholder="All types" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All types</SelectItem>
                  <SelectItem v-for="item in typeOptions" :key="item" :value="item">{{ item }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Priority</Label>
              <Select v-model="filterPriority">
                <SelectTrigger><SelectValue placeholder="All priorities" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All priorities</SelectItem>
                  <SelectItem v-for="item in priorityOptions" :key="item" :value="item">{{ item }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Search</Label>
              <Input v-model="filterSearch" placeholder="Search case number, title, contact, account..." />
            </div>
          </div>
          <div class="mt-3 flex justify-end">
            <Button size="sm" variant="outline" @click="loadCases">Apply Filters</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Case Queue</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="text-sm text-gray-500">Loading...</div>
          <div v-else-if="cases.length === 0" class="text-sm text-gray-500">No cases found.</div>
          <div v-else class="space-y-3">
            <div v-for="caseRecord in cases" :key="caseRecord.id" class="rounded-lg border p-4">
              <div class="flex flex-wrap justify-between gap-3">
                <div class="space-y-1">
                  <div class="flex items-center gap-2">
                    <Badge>{{ caseRecord.case_number }}</Badge>
                    <Badge variant="secondary">{{ caseRecord.status }}</Badge>
                    <Badge variant="outline">{{ caseRecord.priority }}</Badge>
                  </div>
                  <p class="text-sm font-medium">{{ caseRecord.title }}</p>
                  <p class="text-sm text-gray-600">{{ contactName(caseRecord) }} · {{ caseRecord.primary_account?.name || 'No account' }}</p>
                  <p class="text-xs text-gray-500">Type: {{ caseRecord.type }} · Owner: {{ caseRecord.owner?.name || 'Unassigned' }}</p>
                </div>
                <Button variant="outline" size="sm" @click="openCase(caseRecord)">Actions</Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Dialog v-model:open="isCreateOpen">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Create Case</DialogTitle>
          </DialogHeader>
          <div class="grid gap-4">
            <div>
              <Label>Title</Label>
              <Input v-model="newCase.title" />
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
              <div>
                <Label>Type</Label>
                <select v-model="newCase.type" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option v-for="type in typeOptions" :key="type" :value="type">{{ type }}</option>
                </select>
              </div>
              <div>
                <Label>Priority</Label>
                <select v-model="newCase.priority" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option v-for="priority in priorityOptions" :key="priority" :value="priority">{{ priority }}</option>
                </select>
              </div>
              <div>
                <Label>Status</Label>
                <select v-model="newCase.status" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option v-for="item in statusOptions" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <Label>Contact</Label>
                <select v-model="newCase.primary_contact_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option value="">Select contact</option>
                  <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                    {{ contact.first_name }} {{ contact.last_name }} ({{ contact.email || contact.id }})
                  </option>
                </select>
              </div>
              <div>
                <Label>Account</Label>
                <select v-model="newCase.primary_account_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option value="">Select account</option>
                  <option v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.name || account.id }}
                  </option>
                </select>
              </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input v-model="newCase.signoff_required" type="checkbox" />
              Requires sign-off before closure
            </label>
            <Button @click="createCase">Create</Button>
          </div>
        </DialogContent>
      </Dialog>

      <Dialog :open="!!selectedCase" @update:open="value => { if (!value) selectedCase = null }">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Case Actions</DialogTitle>
          </DialogHeader>
          <div v-if="selectedCase" class="space-y-4">
            <div>
              <Label>Status</Label>
              <div class="flex gap-2 flex-wrap mt-2">
                <Button v-for="item in statusOptions" :key="item" size="sm" variant="outline" @click="changeStatus(selectedCase, item)">
                  {{ item }}
                </Button>
              </div>
            </div>
            <div>
              <Label>Reason / Note</Label>
              <Textarea v-model="reason" />
            </div>
            <div class="border-t pt-4">
              <Label>Link Related Record</Label>
              <div class="grid gap-3 sm:grid-cols-3 mt-2">
                <Input v-model="linkableType" placeholder="App\\Models\\Ticket" />
                <Input v-model="linkableId" placeholder="ULID" />
                <Input v-model="linkType" placeholder="related" />
              </div>
              <Button class="mt-3" @click="addLink(selectedCase)">Add Link</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
