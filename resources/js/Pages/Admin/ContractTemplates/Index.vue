<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Link } from '@inertiajs/vue3';
import { FileText, History, Search } from 'lucide-vue-next';

const props = defineProps<{
  templates: { data: any[] };
}>();

const search = ref('');
const statusFilter = ref('all');
const selectedTemplate = ref<any | null>(null);
const historyDialogOpen = ref(false);

const filteredTemplates = computed(() => {
  const term = search.value.toLowerCase();
  return (props.templates.data || []).filter((template) => {
    const matchesSearch = !term || template.name.toLowerCase().includes(term) || template.description.toLowerCase().includes(term) || template.type.toLowerCase().includes(term);
    const matchesStatus = statusFilter.value === 'all' || String(template.is_active) === statusFilter.value;
    return matchesSearch && matchesStatus;
  });
});

const activeCount = computed(() => (props.templates.data || []).filter((template) => template.is_active).length);
const inactiveCount = computed(() => (props.templates.data || []).filter((template) => !template.is_active).length);
const versionCount = computed(() => (props.templates.data || []).reduce((sum, template) => sum + (template.versions?.length || 0), 0));

const openTemplate = (id: string) => {
  router.visit(`/admin/contract-templates/${id}/edit`);
};

const openHistory = (template: any) => {
  selectedTemplate.value = template;
  historyDialogOpen.value = true;
};

const closeHistory = () => {
  historyDialogOpen.value = false;
  selectedTemplate.value = null;
};
</script>

<template>
  <AppLayout>
    <Head title="Contract Templates" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Contract Templates</h1>
          <p class="text-gray-500">Maintain reusable templates, clause libraries, mandatory rules, and version history.</p>
        </div>
        <Link href="/admin/contract-templates/create">
          <Button>Create Template</Button>
        </Link>
      </div>

      <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Templates</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.templates.data.length }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Active</div>
          <div class="mt-1 text-2xl font-semibold">{{ activeCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Inactive</div>
          <div class="mt-1 text-2xl font-semibold">{{ inactiveCount }}</div>
        </div>
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="text-sm text-gray-500">Versions</div>
          <div class="mt-1 text-2xl font-semibold">{{ versionCount }}</div>
        </div>
      </div>

      <div class="rounded-xl border bg-white shadow-sm">
        <div class="space-y-3 border-b p-4">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input v-model="search" class="flex h-10 w-full rounded-md border border-input bg-white px-3 py-2 pl-9 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" placeholder="Search templates..." />
          </div>
          <div class="flex gap-2">
            <Button type="button" size="sm" variant="outline" :class="{ 'bg-gray-100': statusFilter === 'all' }" @click="statusFilter = 'all'">All</Button>
            <Button type="button" size="sm" variant="outline" :class="{ 'bg-green-50 text-green-700': statusFilter === 'true' }" @click="statusFilter = 'true'">Active</Button>
            <Button type="button" size="sm" variant="outline" :class="{ 'bg-gray-50': statusFilter === 'false' }" @click="statusFilter = 'false'">Inactive</Button>
          </div>
        </div>

        <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
          <Card v-for="template in filteredTemplates" :key="template.id" class="cursor-pointer transition hover:shadow-md">
            <CardHeader>
              <div class="flex items-start justify-between gap-3">
                <CardTitle class="text-lg">{{ template.name }}</CardTitle>
                <Badge :variant="template.is_active ? 'default' : 'outline'">{{ template.is_active ? 'Active' : 'Inactive' }}</Badge>
              </div>
            </CardHeader>
            <CardContent class="space-y-4">
              <p class="text-sm text-gray-600 line-clamp-3">{{ template.description }}</p>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-md bg-gray-50 p-2">
                  <div class="text-xs text-gray-500">Type</div>
                  <div class="font-medium capitalize">{{ template.type }}</div>
                </div>
                <div class="rounded-md bg-gray-50 p-2">
                  <div class="text-xs text-gray-500">Clauses</div>
                  <div class="font-medium">{{ template.clauses?.length || 0 }}</div>
                </div>
              </div>
              <div class="flex gap-2">
                <Button size="sm" variant="outline" class="flex-1" @click="openTemplate(template.id)">Edit</Button>
                <Button size="sm" variant="ghost" class="flex-1" @click="openHistory(template)">
                  <History class="mr-1 h-4 w-4" />
                  History
                </Button>
              </div>
            </CardContent>
          </Card>

          <div v-if="!filteredTemplates.length" class="rounded-lg border border-dashed p-10 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">
            No templates match the current search.
          </div>
        </div>
      </div>

      <Dialog v-model:open="historyDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{{ selectedTemplate?.name }}</DialogTitle>
          </DialogHeader>
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <FileText class="h-4 w-4" />
              <span>{{ selectedTemplate?.clauses?.length || 0 }} clauses</span>
            </div>
            <div class="space-y-2">
              <div v-for="version in selectedTemplate?.versions" :key="version.id" class="rounded-lg border p-3">
                <div class="font-medium">v{{ version.version_number }}</div>
                <div class="text-xs text-gray-500">{{ new Date(version.created_at).toLocaleString() }}</div>
                <div v-if="version.change_summary?.length" class="mt-1 text-sm text-gray-600">{{ version.change_summary.join(', ') }}</div>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
