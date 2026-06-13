<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
  accounts: { id: string; name: string }[];
  contacts: { id: string; first_name: string; last_name: string }[];
  users: { id: string; name: string }[];
  statuses: string[];
  types: string[];
  preselectedAccountId?: string;
  preselectedContactId?: string;
}>();

const form = useForm({
  subject: '',
  description: '',
  status: 'open',
  type: 'advisory',
  assigned_to: '',
  account_id: props.preselectedAccountId || '',
  contact_id: props.preselectedContactId || '',
  resolution_notes: '',
});

const submit = () => {
  form.post('/legal', {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Create Legal Matter" />
    <div class="max-w-3xl mx-auto">
      <div class="mb-4">
        <Link href="/legal" class="text-blue-600 hover:underline text-sm">← Back to Legal Matters</Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Create Legal Matter</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="subject">Subject</Label>
              <Input id="subject" v-model="form.subject" />
              <p v-if="form.errors.subject" class="text-xs text-red-600 mt-1">{{ form.errors.subject }}</p>
            </div>

            <div>
              <Label for="description">Description</Label>
              <Textarea id="description" v-model="form.description" rows="4" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="status in statuses" :key="status" :value="status">{{ status }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label for="type">Type</Label>
                <Select v-model="form.type">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="type in types" :key="type" :value="type">{{ type }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label for="assigned_to">Assigned To</Label>
              <Select v-model="form.assigned_to">
                <SelectTrigger>
                  <SelectValue placeholder="Select user" />
                </SelectTrigger>
                <SelectContent>
<SelectItem value="">Unassigned</SelectItem>
                    <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }}
                    </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="account_id">Account</Label>
                <Select v-model="form.account_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select account" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value=" ">None</SelectItem>
                    <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                      {{ account.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label for="contact_id">Contact</Label>
                <Select v-model="form.contact_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select contact" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value=" ">None</SelectItem>
                    <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                      {{ contact.first_name }} {{ contact.last_name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label for="resolution_notes">Resolution Notes</Label>
              <Textarea id="resolution_notes" v-model="form.resolution_notes" rows="3" />
            </div>

            <div class="flex justify-end gap-2">
              <Link href="/legal">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">Create Matter</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
