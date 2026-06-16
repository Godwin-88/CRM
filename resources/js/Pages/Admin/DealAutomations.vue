<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2, Zap } from 'lucide-vue-next';

interface Stage {
  id: string;
  name: string;
  pipeline: { id: string; name: string } | null;
}

const props = defineProps<{
  stages: Stage[];
}>();

const automations = ref<any[]>([]);
const selectedStageId = ref('');
const isCreateOpen = ref(false);
const isSaving = ref(false);

const newAutomation = ref({
  pipeline_stage_id: '',
  name: '',
  description: '',
  is_active: true,
  actions: [
    { type: 'activity', config: {}, delay: 'immediately', position: 0 },
  ] as any[],
});

const loadAutomations = async () => {
  if (!selectedStageId.value) return;
  const response = await fetch(`/api/v1/deal-automations?pipeline_stage_id=${selectedStageId.value}`);
  automations.value = (await response.json()).data || [];
};

onMounted(() => {});

watch(selectedStageId, () => {
  loadAutomations();
});

const addAction = () => {
  newAutomation.value.actions.push({
    type: 'activity',
    config: {},
    delay: 'immediately',
    position: newAutomation.value.actions.length,
  });
};

const removeAction = (index: number) => {
  newAutomation.value.actions.splice(index, 1);
};

const createAutomation = async () => {
  isSaving.value = true;
  try {
    const response = await fetch('/api/v1/deal-automations', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
      },
      body: JSON.stringify(newAutomation.value),
    });
    if (response.ok) {
      isCreateOpen.value = false;
      newAutomation.value = {
        pipeline_stage_id: selectedStageId.value,
        name: '',
        description: '',
        is_active: true,
        actions: [
          { type: 'activity', config: {}, delay: 'immediately', position: 0 },
        ],
      };
      await loadAutomations();
    }
  } finally {
    isSaving.value = false;
  }
};

const toggleAutomation = async (automation: any) => {
  await fetch(`/api/v1/deal-automations/${automation.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify({ is_active: !automation.is_active }),
  });
  automation.is_active = !automation.is_active;
};

const deleteAutomation = async (id: string) => {
  if (!confirm('Delete this automation?')) return;
  await fetch(`/api/v1/deal-automations/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
  });
  automations.value = automations.value.filter((a) => a.id !== id);
};
</script>

<template>
  <AppLayout>
    <Head title="Deal Automations" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold">Deal Automations</h1>
        <p class="text-gray-500">Configure actions that trigger when a deal enters a stage.</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Select Pipeline Stage</CardTitle>
        </CardHeader>
        <CardContent>
          <Select v-model="selectedStageId">
            <SelectTrigger class="w-[320px]">
              <SelectValue placeholder="Choose a pipeline stage" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="stage in stages" :key="stage.id" :value="stage.id">
                {{ stage.name }} {{ stage.pipeline ? `(${stage.pipeline.name})` : '' }}
              </SelectItem>
            </SelectContent>
          </Select>
        </CardContent>
      </Card>

      <div v-if="selectedStageId" class="space-y-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold">Configured Automations</h2>
          <Button @click="isCreateOpen = true">
            <Plus class="h-4 w-4 mr-2" /> New Automation
          </Button>
        </div>

        <div v-if="!automations.length" class="text-center py-12 text-gray-500">
          <Zap class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>No automations for this stage yet.</p>
        </div>

        <div v-for="automation in automations" :key="automation.id" class="border rounded-lg p-4 space-y-3">
          <div class="flex justify-between items-start">
            <div>
              <div class="font-semibold">{{ automation.name }}</div>
              <p class="text-sm text-gray-500">{{ automation.description }}</p>
            </div>
            <div class="flex gap-2">
              <Button variant="outline" size="sm" @click="toggleAutomation(automation)">
                <Badge :variant="automation.is_active ? 'default' : 'secondary'">
                  {{ automation.is_active ? 'Active' : 'Paused' }}
                </Badge>
              </Button>
              <Button variant="destructive" size="sm" @click="deleteAutomation(automation.id)">
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div v-for="(action, index) in automation.actions" :key="index" class="bg-gray-50 rounded p-2 text-sm">
              <div class="font-medium">{{ action.type }}</div>
              <div class="text-xs text-gray-500">Delay: {{ action.delay }}</div>
              <div class="text-xs text-gray-400">Position: {{ action.position }}</div>
            </div>
            <div v-if="!automation.actions?.length" class="col-span-3 text-xs text-gray-400">No actions configured.</div>
          </div>
        </div>
      </div>

      <Dialog v-model:open="isCreateOpen">
        <DialogContent class="max-w-lg">
          <DialogHeader>
            <DialogTitle>Create Stage Automation</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <div>
              <Label>Stage</Label>
              <Select v-model="newAutomation.pipeline_stage_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select stage" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="stage in stages" :key="stage.id" :value="stage.id">
                    {{ stage.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Automation Name</Label>
              <Input v-model="newAutomation.name" placeholder="e.g. Send follow-up email" />
            </div>
            <div>
              <Label>Description</Label>
              <Textarea v-model="newAutomation.description" placeholder="What this automation does..." />
            </div>
            <div>
              <div class="flex justify-between items-center mb-2">
                <Label>Actions</Label>
                <Button type="button" variant="outline" size="sm" @click="addAction">
                  <Plus class="h-3 w-3 mr-1" /> Add Action
                </Button>
              </div>
              <div v-for="(action, index) in newAutomation.actions" :key="index" class="grid grid-cols-3 gap-2 mb-2">
                <Select v-model="action.type">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="activity">Activity</SelectItem>
                    <SelectItem value="email">Email</SelectItem>
                    <SelectItem value="webhook">Webhook</SelectItem>
                  </SelectContent>
                </Select>
                <Select v-model="action.delay">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="immediately">Immediately</SelectItem>
                    <SelectItem value="1h">After 1 hour</SelectItem>
                    <SelectItem value="1d">After 1 day</SelectItem>
                    <SelectItem value="3d">After 3 days</SelectItem>
                    <SelectItem value="5d">After 5 days</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="sm" @click="removeAction(index)">
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
            <Button class="w-full" :disabled="isSaving" @click="createAutomation">
              {{ isSaving ? 'Creating...' : 'Create Automation' }}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
