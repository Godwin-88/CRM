<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft } from 'lucide-vue-next';

interface Pipeline {
    id: string;
    name: string;
    stages: { id: string; name: string; position: number; probability?: number }[];
}

interface Account {
    id: string;
    name: string;
}

interface Contact {
    id: string;
    first_name: string;
    last_name: string;
}

const props = defineProps<{
    pipelines: Pipeline[];
    accounts: Account[];
    contacts?: Contact[];
    preselectedContactId?: string;
    preselectedAccountId?: string;
}>();

const selectedPipelineId = ref(props.pipelines?.[0]?.id || '');
const selectedStage = ref('');

const availableStages = computed(() => {
    const pipeline = props.pipelines?.find(p => p.id === selectedPipelineId.value);
    return pipeline?.stages || [];
});

const form = useForm({
    title: '',
    contact_id: props.preselectedContactId || '',
    account_id: props.preselectedAccountId || '',
    pipeline_id: selectedPipelineId.value,
    stage: '',
    value: '',
    currency: 'USD',
    expected_close_date: '',
});

watch(selectedPipelineId, (newPipelineId) => {
    form.pipeline_id = newPipelineId;
    const pipeline = props.pipelines?.find(p => p.id === newPipelineId);
    if (pipeline?.stages?.length) {
        selectedStage.value = pipeline.stages[0].name;
        form.stage = pipeline.stages[0].name;
    }
});

const submit = () => {
    form.post('/deals', {
        onSuccess: () => {
            // Will redirect via controller
        },
    });
};
</script>

<template>
  <div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
      <Button variant="ghost" size="icon" as-child>
        <a :href="preselectedContactId ? `/contacts/${preselectedContactId}` : '/deals'">
          <ArrowLeft class="h-4 w-4" />
        </a>
      </Button>
      <h1 class="text-2xl font-bold">Create New Deal</h1>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Deal Details</CardTitle>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          <div class="space-y-2">
            <Label for="title">Deal Title *</Label>
            <Input id="title" v-model="form.title" required />
            <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <div class="space-y-2">
            <Label for="account_id">Account</Label>
            <Select v-model="form.account_id">
              <SelectTrigger id="account_id">
                <SelectValue placeholder="Select account" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                  {{ account.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2" v-if="contacts?.length && !preselectedContactId">
            <Label for="contact_id">Contact</Label>
            <Select v-model="form.contact_id">
              <SelectTrigger id="contact_id">
                <SelectValue placeholder="Select contact (optional)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                  {{ contact.first_name }} {{ contact.last_name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="pipeline_id">Pipeline *</Label>
              <Select v-model="selectedPipelineId" :disabled="pipelines.length === 0">
                <SelectTrigger>
                  <SelectValue placeholder="Select pipeline" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="pipeline in pipelines" :key="pipeline.id" :value="pipeline.id">
                    {{ pipeline.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="pipelines.length === 0" class="text-sm text-amber-600">No pipelines configured. <a href="/admin/pipelines" class="text-blue-600 underline">Create one</a></p>
            </div>
            <div class="space-y-2">
              <Label for="stage">Stage *</Label>
              <Select v-model="form.stage" :disabled="availableStages.length === 0">
                <SelectTrigger>
                  <SelectValue placeholder="Select stage" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="stage in availableStages" :key="stage.id" :value="stage.name">
                    {{ stage.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="value">Value</Label>
              <Input id="value" v-model="form.value" type="number" step="0.01" />
            </div>
            <div class="space-y-2">
              <Label for="expected_close_date">Expected Close Date</Label>
              <Input id="expected_close_date" v-model="form.expected_close_date" type="date" />
            </div>
          </div>

          <div class="flex gap-3 pt-4">
            <Button type="submit" :disabled="form.processing || pipelines.length === 0">Create Deal</Button>
            <Button variant="outline" as-child>
              <a :href="preselectedContactId ? `/contacts/${preselectedContactId}` : '/deals'">Cancel</a>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>