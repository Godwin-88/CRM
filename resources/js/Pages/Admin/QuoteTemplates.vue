<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface QuoteTemplate {
  id: string;
  name: string;
  content: string;
  variables: string[];
  is_active: boolean;
}

const props = defineProps<{
  templates: QuoteTemplate[];
}>();

const templates = ref(props.templates);
const isCreateOpen = ref(false);
const isPreviewOpen = ref(false);

const newTemplate = ref({
  name: '',
  content: '',
});

const createTemplate = async () => {
  const response = await fetch('/api/v1/quote-templates', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newTemplate.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  }
};

const previewTemplate = ref<QuoteTemplate | null>(null);

watch(previewTemplate, (v) => {
  isPreviewOpen.value = !!v;
});
</script>

<template>
  <AppLayout>
    <Head title="Quote Templates" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Quote Templates</h1>
          <p class="text-gray-500">Manage PDF templates for quotes and proposals.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ New Template</Button>
          </DialogTrigger>
          <DialogContent class="max-w-3xl">
            <DialogHeader>
              <DialogTitle>Create Template</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium">Name</label>
                <Input v-model="newTemplate.name" placeholder="e.g., Standard Quote" />
              </div>
              <div>
                <label class="text-sm font-medium">Content (HTML)</label>
                <Textarea v-model="newTemplate.content" rows="10" placeholder="Use {{deal_value}}, {{contact_name}}, etc." />
              </div>
              <div class="text-xs text-gray-500">
                Available placeholders: {{deal_value}}, {{contact_name}}, {{account_name}}, {{validity_date}}, {{agent_name}}, {{agent_signature}}
              </div>
              <Button @click="createTemplate">Create Template</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid gap-4">
        <Card v-for="template in templates" :key="template.id">
          <CardHeader>
            <div class="flex justify-between items-start">
              <CardTitle>{{ template.name }}</CardTitle>
              <Badge :variant="template.is_active ? 'default' : 'outline'">
                {{ template.is_active ? 'Active' : 'Inactive' }}
              </Badge>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-gray-600 line-clamp-3">{{ template.content }}</p>
            <div class="flex gap-2 mt-4">
              <Button variant="outline" size="sm" @click="previewTemplate = template">Preview</Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <Dialog v-model:open="isPreviewOpen">
        <DialogContent class="max-w-4xl">
          <DialogHeader>
            <DialogTitle>Template Preview</DialogTitle>
          </DialogHeader>
          <div class="border rounded p-4 max-h-[600px] overflow-y-auto">
            <div v-html="previewTemplate?.content" />
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>