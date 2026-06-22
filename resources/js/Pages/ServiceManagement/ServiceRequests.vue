<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface User {
  id: string;
  name: string;
}

interface ServiceRequest {
  id: string;
  request_number?: string;
  status: string;
  priority: string;
  channel: string;
  contact?: { id: string; first_name?: string; last_name?: string };
  account?: { id: string; name?: string };
  catalog_item?: { id: string; name: string };
  assignee?: User | null;
  team?: { id: string; name: string } | null;
  created_at: string;
}

interface CatalogItem {
  id: string;
  name: string;
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
  links: { first?: string; last?: string; prev?: string | null; next?: string | null };
}

const page = usePage();
const serviceRequests = ref<ServiceRequest[]>([]);
const catalogItems = ref<CatalogItem[]>([]);
const isCreateOpen = ref(false);
const isLoading = ref(false);
const error = ref('');

const contactSearch = ref('');
const accountSearch = ref('');
const contactResults = ref<ContactOption[]>([]);
const accountResults = ref<AccountOption[]>([]);
const showContactDropdown = ref(false);
const showAccountDropdown = ref(false);
const selectedContactName = ref('');
const selectedAccountName = ref('');

const newRequest = ref({
  catalog_item_id: '',
  requester_id: page.props.user?.id ?? '',
  contact_id: '',
  account_id: '',
  channel: 'api',
  priority: 'medium',
  form_response: {},
  metadata: {},
});

const selectedRequest = ref<ServiceRequest | null>(null);
const statusReason = ref('');
const assigneeId = ref('');
const teamId = ref('');

const statusOptions = ['submitted', 'under_review', 'in_progress', 'pending_customer', 'completed', 'closed'];
const priorityOptions = ['low', 'medium', 'high', 'urgent'];

const contactName = (request: ServiceRequest) => [request.contact?.first_name, request.contact?.last_name].filter(Boolean).join(' ') || request.contact_id || 'Unknown contact';

const searchContacts = async (query: string) => {
  if (query.length < 1) { contactResults.value = []; return; }
  try {
    const res = await fetch(`/api/v1/contacts?per_page=10&search=${encodeURIComponent(query)}`);
    if (!res.ok) { contactResults.value = []; return; }
    const payload = await res.json();
    contactResults.value = payload.data ?? [];
    showContactDropdown.value = true;
  } catch { contactResults.value = []; }
};

const selectContact = (contact: ContactOption) => {
  newRequest.value.contact_id = contact.id;
  selectedContactName.value = [contact.first_name, contact.last_name].filter(Boolean).join(' ') || contact.id;
  contactSearch.value = selectedContactName.value;
  showContactDropdown.value = false;
};

const searchAccounts = async (query: string) => {
  if (query.length < 1) { accountResults.value = []; return; }
  try {
    const res = await fetch(`/api/v1/accounts?per_page=10&search=${encodeURIComponent(query)}`);
    if (!res.ok) { accountResults.value = []; return; }
    const payload = await res.json();
    accountResults.value = payload.data ?? [];
    showAccountDropdown.value = true;
  } catch { accountResults.value = []; }
};

const selectAccount = (account: AccountOption) => {
  newRequest.value.account_id = account.id;
  selectedAccountName.value = account.name || account.id;
  accountSearch.value = selectedAccountName.value;
  showAccountDropdown.value = false;
};

const loadServiceRequests = async () => {
  isLoading.value = true;
  try {
    const response = await fetch('/api/v1/service-requests?per_page=25');
    if (!response.ok) {
      error.value = `Failed to load service requests: ${response.status} ${response.statusText}`;
      serviceRequests.value = [];
      return;
    }
    const payload = await response.json() as Paginated<ServiceRequest>;
    serviceRequests.value = payload.data ?? [];
  } catch (e) {
    error.value = 'Failed to load service requests.';
    serviceRequests.value = [];
  } finally {
    isLoading.value = false;
  }
};

const loadCatalogItems = async () => {
  try {
    const response = await fetch('/api/v1/service-catalog-items?per_page=100&active=true');
    if (!response.ok) {
      catalogItems.value = [];
      return;
    }
    const payload = await response.json() as Paginated<CatalogItem>;
    catalogItems.value = (payload.data ?? []).filter((item) => item.name);
  } catch (e) {
    catalogItems.value = [];
  }
};

const createRequest = async () => {
  error.value = '';
  const response = await fetch('/api/v1/service-requests', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify(newRequest.value),
  });

  if (response.ok) {
    isCreateOpen.value = false;
    newRequest.value.catalog_item_id = '';
    newRequest.value.contact_id = '';
    await loadServiceRequests();
    return;
  }

  const payload = await response.json().catch(() => ({}));
  error.value = payload.message || 'Unable to create service request.';
};

const changeStatus = async (request: ServiceRequest, status: string) => {
  await fetch(`/api/v1/service-requests/${request.id}/status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ status, reason: statusReason.value }),
  });
  await loadServiceRequests();
};

const assignRequest = async (request: ServiceRequest) => {
  await fetch(`/api/v1/service-requests/${request.id}/assign`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ assigned_to: assigneeId.value || null, team_id: teamId.value || null }),
  });
  await loadServiceRequests();
};

const escalateRequest = async (request: ServiceRequest) => {
  await fetch(`/api/v1/service-requests/${request.id}/escalate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ reason: statusReason.value || 'Escalated from UI' }),
  });
  await loadServiceRequests();
};

const closeRequest = async (request: ServiceRequest) => {
  await fetch(`/api/v1/service-requests/${request.id}/close`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify({ reason: statusReason.value || 'Closed from UI' }),
  });
  await loadServiceRequests();
};

const openRequest = (request: ServiceRequest) => {
  selectedRequest.value = request;
  statusReason.value = '';
  assigneeId.value = '';
  teamId.value = '';
};

onMounted(() => {
  loadServiceRequests();
  loadCatalogItems();
});
</script>

<template>
  <AppLayout>
    <Head title="Service Requests" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-start gap-4">
        <div>
          <h1 class="text-3xl font-bold">Service Requests</h1>
          <p class="text-gray-500">Create, assign, update, and close service requests.</p>
        </div>
        <Button @click="isCreateOpen = true">New Service Request</Button>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <Card>
        <CardHeader>
          <CardTitle>Open Requests</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="text-sm text-gray-500">Loading...</div>
          <div v-else-if="serviceRequests.length === 0" class="text-sm text-gray-500">No service requests found.</div>
          <div v-else class="space-y-3">
            <div v-for="request in serviceRequests" :key="request.id" class="rounded-lg border p-4">
              <div class="flex flex-wrap justify-between gap-3">
                <div class="space-y-1">
                  <div class="flex items-center gap-2">
                    <Badge>{{ request.priority }}</Badge>
                    <Badge variant="secondary">{{ request.status }}</Badge>
                    <span class="text-sm font-medium">{{ request.catalog_item?.name || 'Uncatalogued request' }}</span>
                  </div>
                  <p class="text-sm text-gray-600">{{ contactName(request) }} · {{ request.account?.name || 'No account' }}</p>
                  <p class="text-xs text-gray-500">Channel: {{ request.channel }} · Created: {{ request.created_at }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                  <Button variant="outline" size="sm" @click="openRequest(request)">Actions</Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Dialog v-model:open="isCreateOpen">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Create Service Request</DialogTitle>
          </DialogHeader>
          <div class="grid gap-4">
            <div>
              <Label>Catalog Item</Label>
              <select v-model="newRequest.catalog_item_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                <option value="">Select a catalog item</option>
                <option v-for="item in catalogItems" :key="item.id" :value="item.id">{{ item.name }}</option>
              </select>
            </div>
            <div>
              <Label>Requester User ID</Label>
              <Input v-model="newRequest.requester_id" />
            </div>
            <div class="relative">
              <Label>Contact</Label>
              <Input v-model="contactSearch" placeholder="Search contacts..." @input="searchContacts(contactSearch)" @focus="searchContacts(contactSearch)" @blur="setTimeout(() => showContactDropdown = false, 200)" />
              <ul v-if="showContactDropdown && contactResults.length" class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                <li v-for="c in contactResults" :key="c.id" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100" @mousedown="selectContact(c)">
                  {{ c.first_name }} {{ c.last_name }} ({{ c.email || c.id }})
                </li>
              </ul>
            </div>
            <div class="relative">
              <Label>Account</Label>
              <Input v-model="accountSearch" placeholder="Search accounts..." @input="searchAccounts(accountSearch)" @focus="searchAccounts(accountSearch)" @blur="setTimeout(() => showAccountDropdown = false, 200)" />
              <ul v-if="showAccountDropdown && accountResults.length" class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                <li v-for="a in accountResults" :key="a.id" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100" @mousedown="selectAccount(a)">
                  {{ a.name || a.id }}
                </li>
              </ul>
            </div>
            <div>
              <Label>Priority</Label>
              <select v-model="newRequest.priority" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                <option v-for="priority in priorityOptions" :key="priority" :value="priority">{{ priority }}</option>
              </select>
            </div>
            <div>
              <Label>Intake Details</Label>
              <Textarea v-model="newRequest.form_response.details" placeholder="Optional free-form details" />
            </div>
            <Button @click="createRequest">Create</Button>
          </div>
        </DialogContent>
      </Dialog>

      <Dialog :open="!!selectedRequest" @update:open="value => { if (!value) selectedRequest = null }">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Service Request Actions</DialogTitle>
          </DialogHeader>
          <div v-if="selectedRequest" class="space-y-4">
            <div>
              <Label>Status</Label>
              <div class="flex gap-2 flex-wrap mt-2">
                <Button v-for="status in statusOptions" :key="status" size="sm" variant="outline" @click="changeStatus(selectedRequest, status)">
                  {{ status }}
                </Button>
              </div>
            </div>
            <div>
              <Label>Reason</Label>
              <Textarea v-model="statusReason" placeholder="Optional transition reason" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <Label>Assignee User ID</Label>
                <Input v-model="assigneeId" />
              </div>
              <div>
                <Label>Team ID</Label>
                <Input v-model="teamId" />
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <Button @click="assignRequest(selectedRequest)">Assign</Button>
              <Button variant="secondary" @click="escalateRequest(selectedRequest)">Escalate</Button>
              <Button variant="destructive" @click="closeRequest(selectedRequest)">Close</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
