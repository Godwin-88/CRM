<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  categories: string[];
  statuses: string[];
}>();

const form = useForm({
  name: '',
  category: 'services',
  primary_contact_name: '',
  primary_contact_email: '',
  primary_contact_phone: '',
  registration_number: '',
  tax_identification_number: '',
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

            <div>
              <Label for="primary_contact_name">Contact Name</Label>
              <Input id="primary_contact_name" v-model="form.primary_contact_name" />
            </div>

            <div>
              <Label for="primary_contact_email">Contact Email</Label>
              <Input id="primary_contact_email" type="email" v-model="form.primary_contact_email" />
            </div>

            <div>
              <Label for="primary_contact_phone">Contact Phone</Label>
              <Input id="primary_contact_phone" v-model="form.primary_contact_phone" />
            </div>

            <Button type="submit" :disabled="form.processing">Create Vendor</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>