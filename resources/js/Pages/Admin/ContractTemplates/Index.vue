<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  templates: { data: any[] };
}>();

const openTemplate = (id: string) => {
  router.visit(`/admin/contract-templates/${id}/edit`);
};
</script>

<template>
  <AppLayout>
    <Head title="Contract Templates" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Contract Templates</h1>
          <p class="text-gray-500">Manage reusable contract templates and clause libraries.</p>
        </div>
        <Link href="/admin/contract-templates/create">
          <Button>Create Template</Button>
        </Link>
      </div>

      <div class="grid gap-4">
        <Card v-for="template in templates.data" :key="template.id" class="cursor-pointer hover:shadow-md" @click="openTemplate(template.id)">
          <CardHeader>
            <div class="flex justify-between items-start">
              <CardTitle>{{ template.name }}</CardTitle>
              <Badge :variant="template.is_active ? 'default' : 'outline'">
                {{ template.is_active ? 'Active' : 'Inactive' }}
              </Badge>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-gray-600 line-clamp-2">{{ template.description }}</p>
            <div class="mt-3 text-xs text-gray-500">
              Type: {{ template.type }} • Clauses: {{ template.clauses?.length || 0 }} • Versions: {{ template.versions?.length || 0 }}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
