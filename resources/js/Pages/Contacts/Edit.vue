<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import { ArrowLeft } from 'lucide-vue-next';

interface Contact {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    type: string;
    status: string;
    source: string;
    owner_id: string | null;
    customFieldValues: { id: string; field_key: string; value: string }[];
}

interface User {
    id: string;
    name: string;
}

interface Account {
    id: string;
    name: string;
}

const props = defineProps<{
    contact: Contact;
    users: User[];
    accounts: Account[];
}>();

const form = useForm({
    first_name: props.contact.first_name,
    last_name: props.contact.last_name,
    email: props.contact.email,
    phone: props.contact.phone || '',
    type: props.contact.type,
    status: props.contact.status,
    source: props.contact.source || '',
    owner_id: props.contact.owner_id || '',
    custom_fields: {} as Record<string, any>,
});

const submit = () => {
    form.put(`/contacts/${props.contact.id}`);
};
</script>

<template>
  <Head :title="`Edit ${contact.first_name} ${contact.last_name}`" />
  
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
      <Button variant="ghost" size="icon" as-child>
        <a href="/contacts">
          <ArrowLeft class="h-4 w-4" />
        </a>
      </Button>
      <h1 class="text-2xl font-bold">Edit Contact</h1>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Contact Information</CardTitle>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="first_name">First Name *</Label>
              <Input
                id="first_name"
                v-model="form.first_name"
                required
                :class="{ 'border-red-500': form.errors.first_name }"
              />
              <p v-if="form.errors.first_name" class="text-sm text-red-600">{{ form.errors.first_name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="last_name">Last Name *</Label>
              <Input
                id="last_name"
                v-model="form.last_name"
                required
                :class="{ 'border-red-500': form.errors.last_name }"
              />
              <p v-if="form.errors.last_name" class="text-sm text-red-600">{{ form.errors.last_name }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="email">Email *</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              required
              :class="{ 'border-red-500': form.errors.email }"
            />
            <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="phone">Phone</Label>
              <Input id="phone" v-model="form.phone" type="tel" />
            </div>
            <div class="space-y-2">
              <Label for="type">Type *</Label>
              <Select v-model="form.type">
                <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                  <SelectValue />
                </SelectTrigger>
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
              <Label for="status">Status</Label>
              <Select v-model="form.status">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="churned">Churned</SelectItem>
                  <SelectItem value="reactivated">Reactivated</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="source">Source</Label>
              <Input id="source" v-model="form.source" placeholder="web, referral, social..." />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="owner_id">Owner</Label>
            <Select v-model="form.owner_id">
              <SelectTrigger>
                <SelectValue placeholder="Select owner" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Unassigned</SelectItem>
                <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="flex gap-3 pt-4">
            <Button type="submit" :disabled="form.processing">Save Changes</Button>
            <Button variant="outline" as-child>
              <a :href="`/contacts/${contact.id}`">Cancel</a>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>