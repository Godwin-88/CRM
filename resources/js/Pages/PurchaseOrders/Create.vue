<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  vendors: { id: string; name: string }[];
  nextPoNumber: string;
}>();

const form = useForm({
  vendor_id: '',
  required_by_date: '',
  currency: 'KES',
  category: '',
  line_items: [{ description: '', quantity: 1, unit_price: 0, tax_rate: 0 }],
});

const submit = () => {
  form.post('/purchase-orders');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Purchase Order" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/purchase-orders" class="text-blue-600 hover:underline text-sm">← Back to POs</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Create Purchase Order</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="vendor_id">Vendor</Label>
              <Select v-model="form.vendor_id">
                <SelectTrigger><SelectValue placeholder="Select vendor" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                    {{ vendor.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="required_by_date">Required By</Label>
              <Input id="required_by_date" type="date" v-model="form.required_by_date" />
            </div>

            <Button type="submit" :disabled="form.processing">Create PO</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>