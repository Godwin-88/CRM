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

const loadCatalogItems = async () => {
  isLoading.value = true;
  const response = await fetch('/api/v1/service-catalog-items?per_page=50');
  const payload = await response.json() as Paginated<CatalogItem>;
  catalogItems.value = payload.data;
  isLoading.value = false;
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
              <div>
                <Label>Category ID</Label>
                <Input v-model="newItem.category_id" />
              </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
              <div>
                <Label>Team ID</Label>
                <Input v-model="newItem.default_team_id" />
              </div>
              <div>
                <Label>SLA Policy ID</Label>
                <Input v-model="newItem.sla_policy_id" />
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
