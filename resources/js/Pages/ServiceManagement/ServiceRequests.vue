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
  name: string;
}

interface Team {
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
  links: { prev?: string | null; next?: string | null };
}

const serviceRequests = ref<ServiceRequest[]>([]);
const catalogItems = ref<CatalogItem[]>([]);
const contacts = ref<ContactOption[]>([]);
const accounts = ref<AccountOption[]>([]);
const users = ref<User[]>([]);
const teams = ref<Team[]>([]);
const isCreateOpen = ref(false);
const isLoading = ref(false);
const error = ref('');

const filterStatus = ref('');
const filterPriority = ref('');
const filterChannel = ref('');
const filterCatalogItem = ref('');

const selectedRequest = ref<ServiceRequest | null>(null);
const statusReason = ref('');
const assigneeId = ref('');
const teamId = ref('');

const newRequest = ref({
  catalog_item_id: '',
  requester_id: '',
  contact_id: '',
  account_id: '',
  channel: 'api',
  priority: 'medium',
  form_response: {},
  metadata: {},
});

const statusOptions = ['submitted', 'under_review', 'in_progress', 'pending_customer', 'completed', 'closed'];
const priorityOptions = ['low', 'medium', 'high', 'urgent'];
const channelOptions = ['api', 'portal', 'self_service_portal', 'email', 'kiosk', 'agent', 'phone', 'chat', 'ivr'];

const contactName = (request: ServiceRequest) => [request.contact?.first_name, request.contact?.last_name].filter(Boolean).join(' ') || request.contact?.id || 'Unknown contact';

const loadReferenceData = async () => {
  try {
    const [contactsRes, accountsRes, usersRes, teamsRes] = await Promise.all([
      fetch('/api/v1/contacts?per_page=200'),
      fetch('/api/v1/accounts?per_page=200'),
      fetch('/api/v1/users?per_page=200'),
      fetch('/api/v1/teams?per_page=200'),
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
    if (teamsRes.ok) {
      const payload = await teamsRes.json();
      teams.value = payload.data ?? [];
    }
  } catch {
    // ignore reference data load failures
  }
};

const loadServiceRequests = async () => {
  isLoading.value = true;
  try {
    const params = new URLSearchParams();
    if (filterStatus.value) params.set('status', filterStatus.value);
    if (filterPriority.value) params.set('priority', filterPriority.value);
    if (filterChannel.value) params.set('channel', filterChannel.value);
    if (filterCatalogItem.value) params.set('catalog_item_id', filterCatalogItem.value);

    const response = await fetch(`/api/v1/service-requests?${params.toString()}&per_page=25`);
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
    newRequest.value.requester_id = '';
    newRequest.value.contact_id = '';
    newRequest.value.account_id = '';
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
  loadReferenceData();
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

      <!-- Filters -->
      <Card>
        <CardHeader><CardTitle class="text-base">Service Request Filters</CardTitle></CardHeader>
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
              <Label>Channel</Label>
              <Select v-model="filterChannel">
                <SelectTrigger><SelectValue placeholder="All channels" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All channels</SelectItem>
                  <SelectItem v-for="ch in channelOptions" :key="ch" :value="ch">{{ ch }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Catalog Item</Label>
              <Select v-model="filterCatalogItem">
                <SelectTrigger><SelectValue placeholder="All catalog items" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All catalog items</SelectItem>
                  <SelectItem v-for="item in catalogItems" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="mt-3 flex justify-end">
            <Button size="sm" variant="outline" @click="loadServiceRequests">Apply Filters</Button>
          </div>
        </CardContent>
      </Card>

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
              <Label>Requester</Label>
              <select v-model="newRequest.requester_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                <option value="">Select requester</option>
                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
              </select>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <Label>Contact</Label>
                <select v-model="newRequest.contact_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option value="">Select contact</option>
                  <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                    {{ contact.first_name }} {{ contact.last_name }} ({{ contact.email || contact.id }})
                  </option>
                </select>
              </div>
              <div>
                <Label>Account</Label>
                <select v-model="newRequest.account_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option value="">Select account</option>
                  <option v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.name || account.id }}
                  </option>
                </select>
              </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <Label>Channel</Label>
                <select v-model="newRequest.channel" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option v-for="ch in channelOptions" :key="ch" :value="ch">{{ ch }}</option>
                </select>
              </div>
              <div>
                <Label>Priority</Label>
                <select v-model="newRequest.priority" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option v-for="priority in priorityOptions" :key="priority" :value="priority">{{ priority }}</option>
                </select>
              </div>
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
                <Label>Assignee</Label>
                <select v-model="assigneeId" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option value="">Unassigned</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
              </div>
              <div>
                <Label>Team</Label>
                <select v-model="teamId" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option value="">No team</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                </select>
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
