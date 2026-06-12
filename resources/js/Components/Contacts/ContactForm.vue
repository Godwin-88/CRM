<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref } from 'vue';

const props = defineProps<{ contact?: any; accountId?: string }>();
const emit = defineEmits(['close']);

const showDuplicateWarning = ref(false);
const duplicates = ref<any[]>([]);

const form = useForm({
  first_name: props.contact?.first_name || '',
  last_name: props.contact?.last_name || '',
  email: props.contact?.email || '',
  phone: props.contact?.phone || '',
  type: props.contact?.type || 'lead',
  status: props.contact?.status || 'active',
  source: props.contact?.source || '',
  account_id: props.accountId || '',
  custom_fields: {} as Record<string, any>,
});

const submit = async () => {
  // Check duplicates before submit if creating
  if (!props.contact) {
    try {
      const response = await fetch('/api/v1/contacts/check-duplicates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
        body: JSON.stringify({
          email: form.email,
          first_name: form.first_name,
          last_name: form.last_name,
          phone: form.phone,
        }),
      });
      const data = await response.json();
      if (data.has_duplicates) {
        duplicates.value = data.duplicates;
        showDuplicateWarning.value = true;
        return;
      }
    } catch (e) {
      // proceed anyway if check fails
    }
  }

  if (props.contact) {
    form.put(`/api/v1/contacts/${props.contact.id}`, { 
      onSuccess: () => emit('close') 
    });
  } else {
    form.post('/api/v1/contacts', { 
      onSuccess: () => emit('close') 
    });
  }
};

const proceedAnyway = () => {
  showDuplicateWarning.value = false;
  duplicates.value = [];
  if (props.contact) {
    form.put(`/api/v1/contacts/${props.contact.id}`, { 
      onSuccess: () => emit('close') 
    });
  } else {
    form.post('/api/v1/contacts', { 
      onSuccess: () => emit('close') 
    });
  }
};

const cancelDuplicate = () => {
  showDuplicateWarning.value = false;
  duplicates.value = [];
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <!-- Duplicate Warning -->
    <div v-if="showDuplicateWarning && duplicates.length" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
      <h4 class="font-semibold text-amber-800">Potential duplicates found</h4>
      <p class="text-sm text-amber-600 mb-2">The following contacts already exist with matching information:</p>
      <div v-for="dup in duplicates" :key="dup.id" class="bg-white p-2 rounded mb-1 text-sm">
        {{ dup.first_name }} {{ dup.last_name }} ({{ dup.email }})
      </div>
      <div class="flex gap-2 mt-3">
        <Button type="button" variant="outline" @click="proceedAnyway">Create anyway</Button>
        <Button type="button" variant="destructive" @click="cancelDuplicate">Cancel</Button>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label>First Name *</Label>
        <Input v-model="form.first_name" required />
        <span v-if="form.errors.first_name" class="text-sm text-red-500">{{ form.errors.first_name }}</span>
      </div>
      <div class="space-y-2">
        <Label>Last Name *</Label>
        <Input v-model="form.last_name" required />
        <span v-if="form.errors.last_name" class="text-sm text-red-500">{{ form.errors.last_name }}</span>
      </div>
    </div>
    
    <div class="space-y-2">
      <Label>Email *</Label>
      <Input v-model="form.email" type="email" required />
      <span v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</span>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label>Phone</Label>
        <Input v-model="form.phone" type="tel" />
      </div>
      <div class="space-y-2">
        <Label>Type *</Label>
        <Select v-model="form.type">
          <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="lead">Lead</SelectItem>
            <SelectItem value="prospect">Prospect</SelectItem>
            <SelectItem value="customer">Customer</SelectItem>
            <SelectItem value="partner">Partner</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <Label>Status</Label>
        <Select v-model="form.status">
          <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="inactive">Inactive</SelectItem>
            <SelectItem value="churned">Churned</SelectItem>
            <SelectItem value="reactivated">Reactivated</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div class="space-y-2">
        <Label>Source</Label>
        <Input v-model="form.source" placeholder="web, referral, social..." />
      </div>
    </div>

    <Button type="submit" :disabled="form.processing" class="w-full">
      {{ props.contact ? 'Update Contact' : 'Create Contact' }}
    </Button>
  </form>
</template>