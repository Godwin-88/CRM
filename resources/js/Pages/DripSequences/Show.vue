<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Plus, Trash2, ArrowLeft, Play, Pause, Users } from 'lucide-vue-next';

interface Step {
  id: string;
  position: number;
  action_type: string;
  delay_type: string;
  delay_value: number;
  email_template?: { id: string; name: string };
  sms_content?: string;
  activity_type?: string;
  field_key?: string;
  field_value?: string;
  segment_id?: string;
  agent_id?: string;
}

interface Sequence {
  id: string;
  name: string;
  description: string;
  trigger: string;
  status: string;
  allow_re_enrolment: boolean;
  steps: Step[];
}

interface Props {
  sequence: Sequence;
  templates: { id: string; name: string }[];
  contacts: { id: string; first_name: string; last_name: string }[];
}

const props = defineProps<Props>();
const sequence = ref(props.sequence);
const templates = ref(props.templates);
const contacts = ref(props.contacts);

const isCreateStepOpen = ref(false);
const isEnrolOpen = ref(false);
const enrollingContactIds = ref<string[]>([]);

const newStep = ref({
  action_type: 'send_email',
  email_template_id: '',
  delay_type: 'immediate',
  delay_value: 0,
  sms_content: '',
  activity_type: '',
  field_key: '',
  field_value: '',
  segment_id: '',
  agent_id: '',
});

const addStep = async () => {
  const payload: any = { ...newStep.value };
  if (!payload.email_template_id) delete payload.email_template_id;

  const res = await fetch(`/api/v1/drip-sequences/${sequence.value.id}/steps`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify(payload),
  });
  if (res.ok) {
    isCreateStepOpen.value = false;
    router.reload({ only: ['sequence'] });
  }
};

const removeStep = async (stepId: string) => {
  if (!confirm('Remove this step?')) return;
  const res = await fetch(`/api/v1/drip-sequences/${sequence.value.id}/steps/${stepId}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  if (res.ok) router.reload({ only: ['sequence'] });
};

const updateStep = async (step: Step, key: string, value: any) => {
  const res = await fetch(`/api/v1/drip-sequences/${sequence.value.id}/steps/${step.id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify({ [key]: value }),
  });
  if (res.ok) router.reload({ only: ['sequence'] });
};

const toggleStatus = async () => {
  const status = sequence.value.status === 'active' ? 'inactive' : 'active';
  const res = await fetch(`/api/v1/drip-sequences/${sequence.value.id}/status`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify({ status }),
  });
  if (res.ok) router.reload({ only: ['sequence'] });
};

const enrolContacts = async () => {
  const res = await fetch(`/api/v1/drip-sequences/${sequence.value.id}/enrol`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify({ contact_ids: enrollingContactIds.value }),
  });
  if (res.ok) {
    isEnrolOpen.value = false;
    enrollingContactIds.value = [];
  }
};

const actionLabel = (type: string) => {
  const labels: Record<string, string> = {
    send_email: 'Send Email',
    send_sms: 'Send SMS',
    send_in_app: 'In-App Message',
    create_activity: 'Create Activity',
    update_contact_field: 'Update Contact Field',
    add_to_segment: 'Add to Segment',
    remove_from_segment: 'Remove from Segment',
    notify_agent: 'Notify Agent',
  };
  return labels[type] || type;
};

const delayLabel = (step: Step) => {
  if (step.delay_type === 'immediate') return 'Immediately';
  return `Wait ${step.delay_value} ${step.delay_type.replace('n_', '')}`;
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

const statusColor = (status: string) => {
  const colors: Record<string, string> = {
    draft: 'secondary',
    active: 'default',
    inactive: 'outline',
  };
  return colors[status] || 'outline';
};

const sortedSteps = computed(() => [...(sequence.value.steps || [])].sort((a, b) => a.position - b.position));
</script>

<template>
  <AppLayout>
    <Head title="Drip Sequence Builder" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" @click="router.visit('/admin/drip-sequences')"><ArrowLeft class="h-4 w-4 mr-2" /> Back</Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ sequence.name }}</h1>
            <p class="text-muted-foreground">{{ sequence.description }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Badge :variant="statusColor(sequence.status)">{{ sequence.status }}</Badge>
          <Button variant="outline" size="sm" @click="toggleStatus">
            <span v-if="sequence.status !== 'active'"><Play class="h-4 w-4 mr-2" />Activate</span>
            <span v-else><Pause class="h-4 w-4 mr-2" />Pause</span>
          </Button>
          <Dialog v-model:open="isEnrolOpen">
            <DialogTrigger as-child><Button size="sm"><Users class="h-4 w-4 mr-2" />Enrol Contacts</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Enrol Contacts</DialogTitle></DialogHeader>
              <div class="space-y-4 py-4">
                <div class="space-y-2"><Label>Contacts</Label>
                  <div class="max-h-60 overflow-auto border rounded p-2">
                    <label v-for="c in contacts" :key="c.id" class="flex items-center gap-2 py-1">
                      <Checkbox :model-value="enrollingContactIds.includes(c.id)" @update:model-value="(v: boolean) => {
                        if (v) enrollingContactIds.push(c.id);
                        else enrollingContactIds = enrollingContactIds.filter(id => id !== c.id);
                      }" />
                      <span class="text-sm">{{ c.first_name }} {{ c.last_name }}</span>
                    </label>
                  </div>
                </div>
                <Button @click="enrolContacts" class="w-full">Enrol Selected</Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <Card><CardContent class="pt-6"><p class="text-sm text-muted-foreground">Trigger</p>
          <p class="font-medium">{{ triggerLabel(sequence.trigger) }}</p></CardContent></Card>
        <Card><CardContent class="pt-6"><p class="text-sm text-muted-foreground">Steps</p>
          <p class="font-medium">{{ sequence.steps?.length ?? 0 }} configured</p></CardContent></Card>
        <Card><CardContent class="pt-6"><p class="text-sm text-muted-foreground">Re-enrolment</p>
          <p class="font-medium">{{ sequence.allow_re_enrolment ? 'Allowed' : 'Not allowed' }}</p></CardContent></Card>
      </div>

      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Workflow Steps</h2>
        <Dialog v-model:open="isCreateStepOpen">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Add Step</Button></DialogTrigger>
          <DialogContent class="sm:max-w-2xl">
            <DialogHeader><DialogTitle>Add Step</DialogTitle></DialogHeader>
            <div class="space-y-4 py-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2"><Label>Action Type</Label>
                  <Select v-model="newStep.action_type">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="send_email">Send Email</SelectItem>
                      <SelectItem value="send_sms">Send SMS</SelectItem>
                      <SelectItem value="send_in_app">In-App Message</SelectItem>
                      <SelectItem value="create_activity">Create Activity</SelectItem>
                      <SelectItem value="update_contact_field">Update Contact Field</SelectItem>
                      <SelectItem value="add_to_segment">Add to Segment</SelectItem>
                      <SelectItem value="remove_from_segment">Remove from Segment</SelectItem>
                      <SelectItem value="notify_agent">Notify Agent</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2"><Label>Delay Type</Label>
                  <Select v-model="newStep.delay_type">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="immediate">Immediate</SelectItem>
                      <SelectItem value="n_hours">Hours</SelectItem>
                      <SelectItem value="n_days">Days</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div class="space-y-2"><Label>Delay Value ({{ delayLabel({ delay_type: newStep.delay_type, delay_value: newStep.delay_value } as any) }})</Label>
                <Input type="number" v-model.number="newStep.delay_value" min="0" /></div>
              <div v-if="newStep.action_type === 'send_email'" class="space-y-2"><Label>Email Template</Label>
                <Select v-model="newStep.email_template_id">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div v-if="newStep.action_type === 'send_sms'" class="space-y-2"><Label>SMS Content</Label>
                <Textarea v-model="newStep.sms_content" placeholder="SMS message" /></div>
              <div v-if="newStep.action_type === 'create_activity'" class="space-y-2"><Label>Activity Type</Label>
                <Input v-model="newStep.activity_type" placeholder="e.g. follow_up" /></div>
              <div v-if="newStep.action_type === 'update_contact_field'" class="grid grid-cols-2 gap-4">
                <div class="space-y-2"><Label>Field Key</Label><Input v-model="newStep.field_key" placeholder="e.g. lead_score" /></div>
                <div class="space-y-2"><Label>Field Value</Label><Input v-model="newStep.field_value" /></div>
              </div>
              <Button @click="addStep" class="w-full">Add Step</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div class="space-y-4">
        <template v-for="(step, index) in sortedSteps" :key="step.id">
          <Card>
            <CardContent class="py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-bold">{{ index + 1 }}</div>
                  <div>
                    <p class="font-medium">{{ actionLabel(step.action_type) }}</p>
                    <p class="text-xs text-muted-foreground">{{ delayLabel(step) }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge variant="secondary">Step {{ step.position }}</Badge>
                  <Button variant="ghost" size="sm" @click="removeStep(step.id)"><Trash2 class="h-4 w-4 text-red-500" /></Button>
                </div>
              </div>
              <div class="mt-3 grid grid-cols-3 gap-4 text-xs text-muted-foreground">
                <div v-if="step.email_template?.name">Template: <span class="text-gray-900">{{ step.email_template.name }}</span></div>
                <div v-if="step.sms_content">SMS: <span class="text-gray-900">{{ step.sms_content.slice(0, 60) }}</span></div>
                <div v-if="step.activity_type">Activity: <span class="text-gray-900">{{ step.activity_type }}</span></div>
                <div v-if="step.field_key">Field <span class="text-gray-900">{{ step.field_key }}</span> → <span class="text-gray-900">{{ step.field_value }}</span></div>
                <div v-if="step.segment_id">Segment: <span class="text-gray-900">{{ step.segment_id }}</span></div>
                <div v-if="step.agent_id">Agent: <span class="text-gray-900">{{ step.agent_id }}</span></div>
              </div>
            </CardContent>
          </Card>
          <div class="flex justify-center"><div class="h-6 w-px bg-gray-300" /></div>
        </template>

        <div v-if="!sortedSteps.length" class="text-center py-8 text-muted-foreground">No steps configured yet. Add your first step above.</div>
      </div>
    </div>
  </AppLayout>
</template>
