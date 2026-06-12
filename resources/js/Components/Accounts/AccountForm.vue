<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{ account?: any }>();
const emit = defineEmits(['close']);

const form = useForm({
  name: props.account?.name || '',
  type: props.account?.type || '',
  industry: props.account?.industry || '',
  status: props.account?.status || 'active',
  website: props.account?.website || '',
  phone: props.account?.phone || '',
  city: props.account?.city || '',
  state: props.account?.state || '',
  country: props.account?.country || '',
  annual_revenue: props.account?.annual_revenue || '',
  employee_count: props.account?.employee_count || '',
  parent_account_id: props.account?.parent_account_id || '',
  account_manager_id: props.account?.account_manager_id || '',
  custom_fields: {} as Record<string, any>,
});

const submit = () => {
  if (props.account) {
    form.put(`/api/v1/accounts/${props.account.id}`, { onSuccess: () => emit('close') });
  } else {
    form.post('/api/v1/accounts', { onSuccess: () => emit('close') });
  }
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div class="space-y-2">
      <Label>Name *</Label>
      <Input v-model="form.name" required />
      <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label>Type</Label>
        <Select v-model="form.type">
          <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="prospect">Prospect</SelectItem>
            <SelectItem value="customer">Customer</SelectItem>
            <SelectItem value="partner">Partner</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div class="space-y-2">
        <Label>Industry</Label>
        <Input v-model="form.industry" placeholder="Technology, Finance..." />
      </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
      <div class="space-y-2">
        <Label>City</Label>
        <Input v-model="form.city" />
      </div>
      <div class="space-y-2">
        <Label>State</Label>
        <Input v-model="form.state" />
      </div>
      <div class="space-y-2">
        <Label>Country</Label>
        <Input v-model="form.country" />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label>Website</Label>
        <Input v-model="form.website" placeholder="https://..." />
      </div>
      <div class="space-y-2">
        <Label>Phone</Label>
        <Input v-model="form.phone" type="tel" />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label>Annual Revenue</Label>
        <Input v-model="form.annual_revenue" type="number" step="0.01" />
      </div>
      <div class="space-y-2">
        <Label>Employee Count</Label>
        <Input v-model="form.employee_count" type="number" />
      </div>
    </div>

    <Button type="submit" :disabled="form.processing" class="w-full">
      {{ props.account ? 'Update Account' : 'Create Account' }}
    </Button>
  </form>
</template>