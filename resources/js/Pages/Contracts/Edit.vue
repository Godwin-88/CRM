<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';

const props = defineProps<{
  contract: any;
  templates: { id: string; name: string }[];
  contractTypes: string[];
}>();

const form = useForm({
  title: props.contract?.title || '',
  type: props.contract?.type || '',
  template_id: props.contract?.template_id || '',
  value: props.contract?.value || '',
  currency: props.contract?.currency || 'USD',
  start_date: props.contract?.start_date || '',
  end_date: props.contract?.end_date || '',
  custom_variables: props.contract?.custom_variables || {},
});

const submit = () => {
  form.put(`/contracts/${props.contract.id}`, {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Edit Contract" />
    <div class="max-w-3xl mx-auto">
      <div class="mb-4">
        <Link href="/contracts" class="text-blue-600 hover:underline text-sm">← Back to Contracts</Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Edit Contract</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="title">Title</Label>
              <Input id="title" v-model="form.title" />
              <p v-if="form.errors.title" class="text-xs text-red-600 mt-1">{{ form.errors.title }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="type">Type</Label>
                <Select v-model="form.type">
                  <SelectTrigger>
                    <SelectValue placeholder="Select type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="type in contractTypes" :key="type" :value="type">{{ type }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label for="template_id">Template</Label>
                <Select v-model="form.template_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select template" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value=" ">None</SelectItem>
                    <SelectItem v-for="template in templates" :key="template.id" :value="template.id">
                      {{ template.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <Label for="value">Value</Label>
                <Input id="value" v-model="form.value" type="number" min="0" step="0.01" />
                <p v-if="form.errors.value" class="text-xs text-red-600 mt-1">{{ form.errors.value }}</p>
              </div>
              <div>
                <Label for="currency">Currency</Label>
                <Input id="currency" v-model="form.currency" maxlength="3" />
              </div>
              <div>
                <Label for="start_date">Start Date</Label>
                <Input id="start_date" v-model="form.start_date" type="date" />
              </div>
            </div>

            <div>
              <Label for="end_date">End Date</Label>
              <Input id="end_date" v-model="form.end_date" type="date" />
              <p v-if="form.errors.end_date" class="text-xs text-red-600 mt-1">{{ form.errors.end_date }}</p>
            </div>

            <div class="flex justify-end gap-2">
              <Link href="/contracts">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">Save Changes</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
