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
  templates: { id: string; name: string }[];
  accounts: { id: string; name: string }[];
  contacts: { id: string; first_name: string; last_name: string; email: string }[];
  contractTypes: string[];
}>();

const form = useForm({
  title: '',
  type: '',
  template_id: '',
  account_id: '',
  contact_id: '',
  value: '',
  currency: 'USD',
  start_date: '',
  end_date: '',
});

const submit = () => {
  form.post('/contracts', {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Create Contract" />
    <div class="max-w-3xl mx-auto">
      <div class="mb-4">
        <Link href="/contracts" class="text-blue-600 hover:underline text-sm">← Back to Contracts</Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Create Contract</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="title">Title</Label>
              <Input id="title" v-model="form.title" :error="form.errors.title" />
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
                     <SelectItem value="none">None</SelectItem>
                     <SelectItem v-for="template in templates" :key="template.id" :value="template.id">
                       {{ template.name }}
                     </SelectItem>
                   </SelectContent>
                 </Select>
               </div>
             </div>

             <div class="grid grid-cols-2 gap-4">
               <div>
                 <Label for="account_id">Account</Label>
                 <Select v-model="form.account_id">
                   <SelectTrigger>
                     <SelectValue placeholder="Select account" />
                   </SelectTrigger>
                   <SelectContent>
                     <SelectItem value="none">None</SelectItem>
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
                     <SelectItem value="none">None</SelectItem>
                     <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                       {{ contact.first_name }} {{ contact.last_name }}
                     </SelectItem>
                   </SelectContent>
                 </Select>
               </div>
             </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <Label for="value">Value</Label>
                <Input id="value" v-model="form.value" type="number" min="0" step="0.01" />
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
            </div>

            <div class="flex justify-end gap-2">
              <Link href="/contracts">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">Create Contract</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
