<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface Pipeline {
  id: string;
  name: string;
  description: string | null;
  is_default: boolean;
  is_archived: boolean;
  stages: { id: string; name: string; probability: number; position: number }[];
}

const props = defineProps<{
  pipelines: Pipeline[];
}>();

const pipelines = ref(props.pipelines);
const isCreateOpen = ref(false);
const isEditOpen = ref(false);

const newPipeline = ref({
  name: '',
  description: '',
  is_default: false,
  stages: [{ name: '', probability: 0 }] as { name: string; probability: number }[],
});

const editPipeline = ref<Pipeline | null>(null);

const addStage = () => {
  newPipeline.value.stages.push({ name: '', probability: 0 });
};

const removeStage = (index: number) => {
  newPipeline.value.stages.splice(index, 1);
};

const createPipeline = async () => {
  const response = await fetch('/api/v1/pipelines', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newPipeline.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  }
};

const archivePipeline = async (pipeline: Pipeline) => {
  await fetch(`/api/v1/pipelines/${pipeline.id}/archive`, {
    method: 'PATCH',
    headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  router.reload();
};
</script>

<template>
  <AppLayout>
    <Head title="Pipelines" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Pipelines</h1>
          <p class="text-gray-500">Manage your sales pipelines and stages.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ New Pipeline</Button>
          </DialogTrigger>
          <DialogContent class="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create Pipeline</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium">Name</label>
                <Input v-model="newPipeline.name" placeholder="e.g., New Business" />
              </div>
              <div>
                <label class="text-sm font-medium">Description</label>
                <Textarea v-model="newPipeline.description" placeholder="Optional description..." />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Stages</label>
                <div v-for="(stage, index) in newPipeline.stages" :key="index" class="flex gap-2">
                  <Input v-model="stage.name" placeholder="Stage name" class="flex-1" />
                  <Input v-model="stage.probability" type="number" min="0" max="100" placeholder="Probability" class="w-24" />
                  <Button variant="ghost" size="sm" @click="removeStage(index)">×</Button>
                </div>
                <Button variant="outline" size="sm" @click="addStage">Add Stage</Button>
              </div>
              <Button @click="createPipeline">Create Pipeline</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid gap-4">
        <Card v-for="pipeline in pipelines" :key="pipeline.id">
          <CardHeader>
            <div class="flex justify-between items-start">
              <div>
                <CardTitle>{{ pipeline.name }}</CardTitle>
                <p class="text-sm text-gray-500 mt-1">{{ pipeline.description }}</p>
              </div>
              <div class="flex gap-2">
                <Badge v-if="pipeline.is_default" variant="default">Default</Badge>
                <Badge v-if="pipeline.is_archived" variant="secondary">Archived</Badge>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div class="flex flex-wrap gap-2 mb-3">
              <Badge v-for="stage in pipeline.stages" :key="stage.id" variant="outline">
                {{ stage.name }} ({{ stage.probability }}%)
              </Badge>
            </div>
            <div class="flex gap-2">
              <Link :href="`/pipelines/${pipeline.id}/board`">
                <Button size="sm">View Board</Button>
              </Link>
              <Button v-if="!pipeline.is_archived" variant="outline" size="sm" @click="archivePipeline(pipeline)">
                Archive
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>