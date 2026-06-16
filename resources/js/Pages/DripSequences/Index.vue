<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Workflow, Zap, Target, Link as LinkIcon } from 'lucide-vue-next';

interface DripSequence {
  id: string;
  name: string;
  description: string;
  trigger: string;
  status: string;
  allow_re_enrolment: boolean;
  steps: any[];
}

const props = defineProps<{ sequences: DripSequence[] }>();
const sequences = ref(props.sequences);
const isCreateOpen = ref(false);

const newSequence = ref({
  name: '',
  description: '',
  trigger: 'contact_created',
  status: 'draft',
  allow_re_enrolment: false,
});

const createSequence = async () => {
  router.post('/api/v1/drip-sequences', newSequence.value, {
    onSuccess: () => {
      isCreateOpen.value = false;
      router.reload();
    }
  });
};

const statusColor = (status: string) => {
  const colors: Record<string, string> = {
    draft: 'secondary',
    active: 'default',
    inactive: 'outline',
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
    <Head title="Drip Sequences Engine" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Drip Sequences Engine</h1>
          <p class="text-gray-500">Design complex automated customer journeys.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button size="lg"><Plus class="h-5 w-5 mr-2" />New Sequence</Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
              <DialogTitle>Configure New Workflow</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-4">
              <div class="space-y-2">
                <Label>Name</Label>
                <Input v-model="newSequence.name" placeholder="e.g. Welcome Series" />
              </div>
              <div class="space-y-2">
                <Label>Description</Label>
                <Textarea v-model="newSequence.description" placeholder="Internal workflow notes..." />
              </div>
              <div class="space-y-2">
                <Label>Primary Trigger</Label>
                <Select v-model="newSequence.trigger">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="contact_created">Contact Created</SelectItem>
                    <SelectItem value="contact_stage_changed">Contact Stage Changed</SelectItem>
                    <SelectItem value="deal_stage_changed">Deal Stage Changed</SelectItem>
                    <SelectItem value="form_submission">Form Submission</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="flex items-center gap-2">
                <Checkbox v-model="newSequence.allow_re_enrolment" id="re-enrol" />
                <Label for="re-enrol" class="text-sm">Allow re-enrolment</Label>
              </div>
              <Button @click="createSequence" class="w-full">Create Workflow</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

          <Card>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Name</TableHead>
                    <TableHead class="p-4">Trigger</TableHead>
                    <TableHead class="p-4">Status</TableHead>
                    <TableHead class="p-4">Steps</TableHead>
                    <TableHead class="p-4">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="sequence in sequences" :key="sequence.id" class="border-b hover:bg-gray-50/50">
                    <TableCell class="p-4">
                      <div class="font-bold">{{ sequence.name }}</div>
                      <div class="text-xs text-gray-500">{{ sequence.description }}</div>
                    </TableCell>
                    <TableCell class="p-4 flex items-center gap-2 text-sm"><Target class="h-4 w-4 text-blue-500" /> {{ triggerLabel(sequence.trigger) }}</TableCell>
                    <TableCell class="p-4">
                      <Badge :variant="statusColor(sequence.status)">{{ sequence.status }}</Badge>
                    </TableCell>
                    <TableCell class="p-4 text-sm">{{ sequence.steps?.length ?? 0 }} steps</TableCell>
                    <TableCell class="p-4">
                      <Button variant="outline" size="sm" class="flex gap-2" @click="router.visit(`/admin/drip-sequences/${sequence.id}`)">
                        <Workflow class="h-4 w-4" /> Manage Steps
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
    </div>
  </AppLayout>
</template>