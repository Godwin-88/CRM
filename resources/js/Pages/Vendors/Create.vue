<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
  categories: string[];
  statuses: string[];
  canViewFinancials: boolean;
}>();

const form = useForm({
  name: '',
  category: 'services',
  primary_contact_name: '',
  primary_contact_email: '',
  primary_contact_phone: '',
  registration_number: '',
  tax_identification_number: '',
  account_name: '',
  account_number: '',
  bank_name: '',
  branch_code: '',
  swift_code: '',
  physical_address: '',
  website: '',
  status: 'active',
});

const submit = () => {
  form.post('/vendors');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Vendor" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/vendors" class="text-blue-600 hover:underline text-sm">← Back to Vendors</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Create Vendor</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="name">Name</Label>
              <Input id="name" v-model="form.name" />
            </div>

            <div>
              <Label for="category">Category</Label>
              <Select v-model="form.category">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="goods">Goods</SelectItem>
                  <SelectItem value="services">Services</SelectItem>
                  <SelectItem value="both">Both</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="primary_contact_name">Contact Name</Label>
                <Input id="primary_contact_name" v-model="form.primary_contact_name" />
              </div>
              <div>
                <Label for="primary_contact_email">Contact Email</Label>
                <Input id="primary_contact_email" type="email" v-model="form.primary_contact_email" />
              </div>
            </div>

            <div>
              <Label for="primary_contact_phone">Contact Phone</Label>
              <Input id="primary_contact_phone" v-model="form.primary_contact_phone" />
            </div>

            <div>
              <Label for="registration_number">Registration Number</Label>
              <Input id="registration_number" v-model="form.registration_number" />
            </div>

            <div>
              <Label for="tax_identification_number">Tax ID Number</Label>
              <Input id="tax_identification_number" v-model="form.tax_identification_number" />
            </div>

            <div v-if="canViewFinancials">
              <Label>Bank Details (encrypted)</Label>
              <div class="grid grid-cols-2 gap-4 mt-2">
                <div><Label for="account_name">Account Name</Label><Input id="account_name" v-model="form.account_name" /></div>
                <div><Label for="account_number">Account Number</Label><Input id="account_number" v-model="form.account_number" /></div>
                <div><Label for="bank_name">Bank Name</Label><Input id="bank_name" v-model="form.bank_name" /></div>
                <div><Label for="branch_code">Branch Code</Label><Input id="branch_code" v-model="form.branch_code" /></div>
                <div><Label for="swift_code">SWIFT Code</Label><Input id="swift_code" v-model="form.swift_code" /></div>
              </div>
            </div>

            <div>
              <Label for="physical_address">Physical Address</Label>
              <Textarea id="physical_address" v-model="form.physical_address" />
            </div>

            <div>
              <Label for="website">Website</Label>
              <Input id="website" type="url" v-model="form.website" placeholder="https://" />
            </div>

            <Button type="submit" :disabled="form.processing">Create Vendor</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>