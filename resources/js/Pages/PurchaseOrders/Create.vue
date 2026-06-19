<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  vendors: { id: string; name: string; status: string }[];
  nextPoNumber: string;
  categories: string[];
}>();

const form = useForm({
  vendor_id: '',
  required_by_date: '',
  currency: 'KES',
  category: '',
  line_items: [{ description: '', quantity: 1, unit_price: 0, tax_rate: 0 }],
});

const addLineItem = () => {
  form.line_items.push({ description: '', quantity: 1, unit_price: 0, tax_rate: 0 });
};

const removeLineItem = (index: number) => {
  form.line_items.splice(index, 1);
};

const subtotal = computed(() => {
  return form.line_items.reduce((sum, item) => {
    return sum + ((item.quantity || 0) * (item.unit_price || 0));
  }, 0);
});

const totalTax = computed(() => {
  return form.line_items.reduce((sum, item) => {
    const lineTotal = (item.quantity || 0) * (item.unit_price || 0);
    return sum + (lineTotal * ((item.tax_rate || 0) / 100));
  }, 0);
});

const total = computed(() => subtotal.value + totalTax.value);

const submit = () => {
  form.post('/purchase-orders');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Purchase Order" />
    
    <div class="max-w-4xl mx-auto">
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

            <div class="grid grid-cols-3 gap-4">
              <div>
                <Label for="required_by_date">Required By</Label>
                <Input id="required_by_date" type="date" v-model="form.required_by_date" />
              </div>
              <div>
                <Label for="currency">Currency</Label>
                <Select v-model="form.currency">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="KES">KES</SelectItem>
                    <SelectItem value="USD">USD</SelectItem>
                    <SelectItem value="EUR">EUR</SelectItem>
                    <SelectItem value="GBP">GBP</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label for="category">Category</Label>
                <Select v-model="form.category">
                  <SelectTrigger><SelectValue placeholder="Select category" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                      {{ cat }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label>Line Items</Label>
              <div v-for="(item, index) in form.line_items" :key="index" class="grid grid-cols-5 gap-2 mt-2">
                <Input v-model="item.description" placeholder="Description" />
                <Input type="number" v-model="item.quantity" placeholder="Qty" step="0.01" min="0" />
                <Input type="number" v-model="item.unit_price" placeholder="Unit Price" step="0.01" min="0" />
                <Input type="number" v-model="item.tax_rate" placeholder="Tax %" step="0.01" min="0" />
                <Button type="button" variant="ghost" @click="removeLineItem(index)" v-if="form.line_items.length > 1">Remove</Button>
              </div>
              <Button type="button" variant="outline" @click="addLineItem" class="mt-2">Add Line Item</Button>
            </div>

            <div class="border-t pt-4 space-y-2">
              <div class="flex justify-between"><span>Subtotal:</span><span>{{ form.currency }} {{ subtotal.toLocaleString() }}</span></div>
              <div class="flex justify-between"><span>Total Tax:</span><span>{{ form.currency }} {{ totalTax.toLocaleString() }}</span></div>
              <div class="flex justify-between font-bold"><span>Total:</span><span>{{ form.currency }} {{ total.toLocaleString() }}</span></div>
            </div>

            <div class="flex justify-end gap-2">
              <Link href="/purchase-orders">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">Create PO</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>