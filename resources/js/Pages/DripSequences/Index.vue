<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface DripSequence {
  id: string;
  name: string;
  description: string;
  trigger: string;
  status: string;
  allow_re_enrolment: boolean;
  created_by?: { name: string };
}

const props = defineProps<{
  sequences: DripSequence[];
}>();

const sequences = ref(props.sequences);
const isCreateOpen = ref(false);

const newSequence = ref({
  name: '',
  trigger: 'contact_created',
  allow_re_enrolment: false,
});

const createSequence = async () => {
  const response = await fetch('/api/v1/drip-sequences', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newSequence.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  }
};

const statusColor = (status: string) => {
  const colors: Record<string, string> = {
    draft: 'outline',
    active: 'default',
    inactive: 'secondary',
  };
  return colors[status] || 'outline';
};

const triggerLabel = (trigger: string) => {
  const labels: Record<string, string> = {
    contact_created: 'Contact Created',
    contact_stage_changed: 'Contact Stage Changed',
    deal_stage_changed: 'Deal Stage Changed',
    contact_field_changed: 'Contact Field Changed',
    form_submission: 'Form Submission',
    manual_enrolment: 'Manual Enrolment',
  };
  return labels[trigger] || trigger;
};
</script>

<template>
  <AppLayout>
    <Head title="Drip Sequences" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Drip Sequences</h1>
          <p class="text-gray-500">Automated multi-step workflows triggered by contact events.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ New Sequence</Button>
          </DialogTrigger>
          <DialogContent class="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create Drip Sequence</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium">Sequence Name</label>
                <Input v-model="newSequence.name" placeholder="e.g., Welcome Series" />
              </div>
              <div>
                <label class="text-sm font-medium">Trigger</label>
                <Select v-model="newSequence.trigger">
                  <SelectTrigger>
                    <SelectValue placeholder="Select trigger" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="contact_created">Contact Created</SelectItem>
                    <SelectItem value="contact_stage_changed">Contact Stage Changed</SelectItem>
                    <SelectItem value="deal_stage_changed">Deal Stage Changed</SelectItem>
                    <SelectItem value="contact_field_changed">Contact Field Changed</SelectItem>
                    <SelectItem value="form_submission">Form Submission</SelectItem>
                    <SelectItem value="manual_enrolment">Manual Enrolment</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="flex items-center gap-2">
                <input type="checkbox" v-model="newSequence.allow_re_enrolment" id="allow_re_enrolment" />
                <label for="allow_re_enrolment" class="text-sm">Allow re-enrolment after completion</label>
              </div>
              <Button @click="createSequence">Create Sequence</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent class="p-0">
          <table class="w-full">
            <thead class="border-b">
              <tr class="text-left">
                <th class="p-4">Name</th>
                <th class="p-4">Trigger</th>
                <th class="p-4">Status</th>
                <th class="p-4">Re-enrolment</th>
                <th class="p-4"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sequence in sequences" :key="sequence.id" class="border-b">
                <td class="p-4 font-medium">{{ sequence.name }}</td>
                <td class="p-4">{{ triggerLabel(sequence.trigger) }}</td>
                <td class="p-4">
                  <Badge :variant="statusColor(sequence.status)">{{ sequence.status }}</Badge>
                </td>
                <td class="p-4">{{ sequence.allow_re_enrolment ? 'Allowed' : 'Not allowed' }}</td>
                <td class="p-4">
                  <Button variant="ghost" size="sm">Manage Steps</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>