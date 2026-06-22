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
import { Textarea } from '@/components/ui/textarea';

interface CatalogItem {
  id: string;
  name: string;
  slug: string;
  description?: string | null;
  default_priority: string;
  portal_visible: boolean;
  api_visible: boolean;
  is_active: boolean;
  category?: { id: string; name: string } | null;
  default_team?: { id: string; name: string } | null;
  sla_policy?: { id: string; name: string } | null;
}

interface Paginated<T> {
  data: T[];
  links: { prev?: string | null; next?: string | null };
}

const catalogItems = ref<CatalogItem[]>([]);
const isCreateOpen = ref(false);
const isLoading = ref(false);
const error = ref('');

const teamSearch = ref('');
const categorySearch = ref('');
const slaSearch = ref('');
const teamResults = ref<{ id: string; name: string }[]>([]);
const categoryResults = ref<{ id: string; name: string }[]>([]);
const slaResults = ref<{ id: string; name: string }[]>([]);
const showTeamDropdown = ref(false);
const showCategoryDropdown = ref(false);
const showSlaDropdown = ref(false);

const newItem = ref({
  name: '',
  slug: '',
  description: '',
  customer_instructions: '',
  default_priority: 'medium',
  category_id: '',
  default_team_id: '',
  sla_policy_id: '',
  portal_visible: true,
  api_visible: true,
  email_visible: false,
  kiosk_visible: false,
  is_active: true,
  fields: [{ name: 'details', label: 'Details', type: 'textarea', required: false }],
});

const priorityOptions = ['low', 'medium', 'high', 'urgent'];

const searchTeams = async (query: string) => {
  if (query.length < 1) { teamResults.value = []; return; }
  try {
    const res = await fetch(`/api/v1/teams?per_page=10&search=${encodeURIComponent(query)}`);
    if (!res.ok) { teamResults.value = []; return; }
    const { data } = await res.json();
    teamResults.value = data ?? [];
    showTeamDropdown.value = true;
  } catch { teamResults.value = []; }
};

const selectTeam = (team: { id: string; name: string }) => {
  newItem.value.default_team_id = team.id;
  teamSearch.value = team.name;
  showTeamDropdown.value = false;
};

const searchCategories = async (query: string) => {
  if (query.length < 1) { categoryResults.value = []; return; }
  try {
    const res = await fetch(`/api/v1/ticket-categories?per_page=10&search=${encodeURIComponent(query)}`);
    if (!res.ok) { categoryResults.value = []; return; }
    const { data } = await res.json();
    categoryResults.value = data ?? [];
    showCategoryDropdown.value = true;
  } catch { categoryResults.value = []; }
};

const selectCategory = (cat: { id: string; name: string }) => {
  newItem.value.category_id = cat.id;
  categorySearch.value = cat.name;
  showCategoryDropdown.value = false;
};

const searchSlaPolicies = async (query: string) => {
  if (query.length < 1) { slaResults.value = []; return; }
  try {
    const res = await fetch(`/api/v1/sla?per_page=10&search=${encodeURIComponent(query)}`);
    if (!res.ok) { slaResults.value = []; return; }
    const { data } = await res.json();
    slaResults.value = data ?? [];
    showSlaDropdown.value = true;
  } catch { slaResults.value = []; }
};

const selectSla = (sla: { id: string; name: string }) => {
  newItem.value.sla_policy_id = sla.id;
  slaSearch.value = sla.name;
  showSlaDropdown.value = false;
};

const loadCatalogItems = async () => {
  isLoading.value = true;
  try {
    const response = await fetch('/api/v1/service-catalog-items?per_page=50');
    if (!response.ok) {
      error.value = `Failed to load catalog items: ${response.status} ${response.statusText}`;
      catalogItems.value = [];
      return;
    }
    const payload = await response.json() as Paginated<CatalogItem>;
    catalogItems.value = payload.data ?? [];
  } catch (e) {
    error.value = 'Failed to load catalog items.';
    catalogItems.value = [];
  } finally {
    isLoading.value = false;
  }
};

const createCatalogItem = async () => {
  error.value = '';
  const response = await fetch('/api/v1/service-catalog-items', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: JSON.stringify(newItem.value),
  });

  if (response.ok) {
    isCreateOpen.value = false;
    newItem.value = {
      name: '',
      slug: '',
      description: '',
      customer_instructions: '',
      default_priority: 'medium',
      category_id: '',
      default_team_id: '',
      sla_policy_id: '',
      portal_visible: true,
      api_visible: true,
      email_visible: false,
      kiosk_visible: false,
      is_active: true,
      fields: [{ name: 'details', label: 'Details', type: 'textarea', required: false }],
    };
    await loadCatalogItems();
    return;
  }

  const payload = await response.json().catch(() => ({}));
  error.value = payload.message || 'Unable to create catalog item.';
};

const deactivate = async (item: CatalogItem) => {
  await fetch(`/api/v1/service-catalog-items/${item.id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
  });
  await loadCatalogItems();
};

onMounted(loadCatalogItems);
</script>

<template>
  <AppLayout>
    <Head title="Service Catalog" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-start gap-4">
        <div>
          <h1 class="text-3xl font-bold">Service Catalog</h1>
          <p class="text-gray-500">Admin configuration for service request intake offerings.</p>
        </div>
        <Button @click="isCreateOpen = true">New Catalog Item</Button>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <Card>
        <CardHeader>
          <CardTitle>Catalog Items</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="text-sm text-gray-500">Loading...</div>
          <div v-else-if="catalogItems.length === 0" class="text-sm text-gray-500">No service catalog items found.</div>
          <div v-else class="grid gap-4 md:grid-cols-2">
            <div v-for="item in catalogItems" :key="item.id" class="rounded-lg border p-4 space-y-3">
              <div class="flex justify-between gap-3">
                <div>
                  <h3 class="font-semibold">{{ item.name }}</h3>
                  <p class="text-sm text-gray-500">{{ item.slug }}</p>
                </div>
                <div class="flex gap-2">
                  <Badge :variant="item.is_active ? 'success' : 'destructive'">{{ item.is_active ? 'Active' : 'Inactive' }}</Badge>
                </div>
              </div>
              <p class="text-sm text-gray-600">{{ item.description || 'No description' }}</p>
              <div class="flex flex-wrap gap-2 text-xs">
                <Badge variant="outline">Priority: {{ item.default_priority }}</Badge>
                <Badge variant="secondary">Portal: {{ item.portal_visible ? 'Yes' : 'No' }}</Badge>
                <Badge variant="secondary">API: {{ item.api_visible ? 'Yes' : 'No' }}</Badge>
              </div>
              <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" @click="deactivate(item)">Deactivate</Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Dialog v-model:open="isCreateOpen">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Create Catalog Item</DialogTitle>
          </DialogHeader>
          <div class="grid gap-4">
            <div>
              <Label>Name</Label>
              <Input v-model="newItem.name" />
            </div>
            <div>
              <Label>Slug</Label>
              <Input v-model="newItem.slug" />
            </div>
            <div>
              <Label>Description</Label>
              <Textarea v-model="newItem.description" />
            </div>
            <div>
              <Label>Customer Instructions</Label>
              <Textarea v-model="newItem.customer_instructions" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <Label>Default Priority</Label>
                <select v-model="newItem.default_priority" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                  <option v-for="priority in priorityOptions" :key="priority" :value="priority">{{ priority }}</option>
                </select>
              </div>
              <div class="relative">
                <Label>Category</Label>
                <Input v-model="categorySearch" placeholder="Search categories..." @input="searchCategories(categorySearch)" @focus="searchCategories(categorySearch)" @blur="setTimeout(() => showCategoryDropdown = false, 200)" />
                <ul v-if="showCategoryDropdown && categoryResults.length" class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                  <li v-for="cat in categoryResults" :key="cat.id" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100" @mousedown="selectCategory(cat)">
                    {{ cat.name }}
                  </li>
                </ul>
              </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
              <div class="relative">
                <Label>Team</Label>
                <Input v-model="teamSearch" placeholder="Search teams..." @input="searchTeams(teamSearch)" @focus="searchTeams(teamSearch)" @blur="setTimeout(() => showTeamDropdown = false, 200)" />
                <ul v-if="showTeamDropdown && teamResults.length" class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                  <li v-for="t in teamResults" :key="t.id" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100" @mousedown="selectTeam(t)">
                    {{ t.name }}
                  </li>
                </ul>
              </div>
              <div class="relative">
                <Label>SLA Policy</Label>
                <Input v-model="slaSearch" placeholder="Search SLA policies..." @input="searchSlaPolicies(slaSearch)" @focus="searchSlaPolicies(slaSearch)" @blur="setTimeout(() => showSlaDropdown = false, 200)" />
                <ul v-if="showSlaDropdown && slaResults.length" class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-48 overflow-y-auto">
                  <li v-for="s in slaResults" :key="s.id" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100" @mousedown="selectSla(s)">
                    {{ s.name }}
                  </li>
                </ul>
              </div>
              <label class="flex items-center gap-2 text-sm">
                <input v-model="newItem.portal_visible" type="checkbox" />
                Portal visible
              </label>
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input v-model="newItem.api_visible" type="checkbox" />
              API visible
            </label>
            <Button @click="createCatalogItem">Create</Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
