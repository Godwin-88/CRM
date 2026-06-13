<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  types: string[];
}>();

const form = useForm({
  institution_name: '',
  relationship_type: 'current_account',
  relationship_manager_name: '',
  relationship_manager_email: '',
  relationship_manager_phone: '',
  credit_limit: '',
  facility_expiry_date: '',
});

const submit = () => {
  form.post('/banking');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Banking Relationship" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/banking" class="text-blue-600 hover:underline text-sm">← Back to Banking</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Create Banking Relationship</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="institution_name">Institution Name</Label>
              <Input id="institution_name" v-model="form.institution_name" />
            </div>

            <div>
              <Label for="relationship_type">Type</Label>
              <Select v-model="form.relationship_type">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="current_account">Current Account</SelectItem>
                  <SelectItem value="credit_facility">Credit Facility</SelectItem>
                  <SelectItem value="overdraft">Overdraft</SelectItem>
                  <SelectItem value="trade_finance">Trade Finance</SelectItem>
                  <SelectItem value="treasury">Treasury</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="relationship_manager_name">Manager Name</Label>
              <Input id="relationship_manager_name" v-model="form.relationship_manager_name" />
            </div>

            <div>
              <Label for="relationship_manager_email">Manager Email</Label>
              <Input id="relationship_manager_email" type="email" v-model="form.relationship_manager_email" />
            </div>

            <Button type="submit" :disabled="form.processing">Create Relationship</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>