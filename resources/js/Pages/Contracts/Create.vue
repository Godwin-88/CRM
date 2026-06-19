<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';

const props = defineProps<{
  templates: { id: string; name: string }[];
  accounts: { id: string; name: string }[];
  contacts: { id: string; first_name: string; last_name: string; email: string }[];
  contractTypes: string[];
  preselectedAccountId?: string | null;
  preselectedContactId?: string | null;
  assistantStep?: string | null;
  assistantType?: string | null;
}>();

const form = useForm({
  title: props.assistantStep === 'variables' ? 'Variable fill' : '',
  type: props.assistantType || '',
  template_id: props.assistantStep === 'template' ? '' : '',
  account_id: props.preselectedAccountId || '',
  contact_id: props.preselectedContactId || '',
  value: '',
  currency: 'USD',
  start_date: '',
  end_date: '',
});

const submit = () => {
  const templateId = form.template_id;
  const accountId = form.account_id;
  const contactId = form.contact_id;

  form.transform((data) => ({
    ...data,
    template_id: templateId === 'manual' ? null : templateId,
    account_id: accountId === 'none' ? null : accountId,
    contact_id: contactId === 'none' ? null : contactId,
  })).post('/contracts', {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Create Contract" />
    <div class="max-w-4xl mx-auto">
      <div class="mb-4 flex items-center justify-between">
        <Link href="/contracts" class="text-blue-600 hover:underline text-sm">← Back to Contracts</Link>
        <Button type="submit" form="contract-form" :disabled="form.processing">Create Draft</Button>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <Card>
          <CardHeader>
            <CardTitle>Contract generation</CardTitle>
            <p class="text-sm text-gray-500">Create a draft contract linked to a deal, account, contact, and approved template.</p>
          </CardHeader>
          <CardContent>
            <form id="contract-form" @submit.prevent="submit" class="space-y-4">
              <div>
                <Label for="title">Title</Label>
                <Input id="title" v-model="form.title" />
                <p v-if="form.errors.title" class="text-xs text-red-600 mt-1">{{ form.errors.title }}</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
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
                      <SelectItem value="manual">Manual</SelectItem>
                      <SelectItem v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <Label for="account_id">Account</Label>
                  <Select v-model="form.account_id">
                    <SelectTrigger>
                      <SelectValue placeholder="Select account" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No account</SelectItem>
                      <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</SelectItem>
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
                      <SelectItem value="none">No contact</SelectItem>
                      <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">{{ contact.first_name }} {{ contact.last_name }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-3">
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
                <Button type="submit">Create Draft</Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <aside class="space-y-4">
          <Card>
            <CardHeader><CardTitle>Generation checklist</CardTitle></CardHeader>
            <CardContent class="space-y-3 text-sm text-gray-600">
              <p>Use an active template to prefill approved clauses.</p>
              <p>Link account and contact so variables resolve automatically.</p>
              <p>Drafts can be regenerated before sending for signature.</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader><CardTitle>From CRM record</CardTitle></CardHeader>
            <CardContent class="space-y-2 text-sm">
              <p class="text-gray-500">When opened from a deal or account, the linked account/contact fields are prefilled.</p>
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>
