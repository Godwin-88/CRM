<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
  matter: {
    id: string;
    subject: string;
    description: string;
    assigned_to?: string | null;
    account_id?: string | null;
    contact_id?: string | null;
    resolution_notes?: string;
  };
  users: { id: string; name: string }[];
  accounts: { id: string; name: string }[];
  contacts: { id: string; first_name: string; last_name: string }[];
}>();

const form = useForm({
  subject: props.matter?.subject || '',
  description: props.matter?.description || '',
  assigned_to: props.matter?.assigned_to || '',
  account_id: props.matter?.account_id || '',
  contact_id: props.matter?.contact_id || '',
  resolution_notes: props.matter?.resolution_notes || '',
});

const submit = () => {
  form.put(`/legal/${props.matter.id}`, {
    onSuccess: () => {
      form.reset();
    },
  });
};

const matter = props.matter;
</script>

<template>
  <AppLayout>
    <Head title="Edit Legal Matter" />
    <div class="max-w-3xl mx-auto">
      <div class="mb-4">
        <Link href="/legal" class="text-blue-600 hover:underline text-sm">← Back to Legal Matters</Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Edit Legal Matter</CardTitle>
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
                    <SelectItem value="">None</SelectItem>
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
                    <SelectItem value="">None</SelectItem>
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
              <Link :href="`/legal/${matter.id}`">
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
