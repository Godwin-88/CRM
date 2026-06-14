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
  accounts: { id: string; name: string }[];
  nextInvoiceNumber: string;
}>();

const form = useForm({
  account_id: '',
  contact_id: '',
  invoice_number: props.nextInvoiceNumber,
  due_date: '',
  currency: 'USD',
  line_items: [{ description: '', quantity: 1, unit_price: 0, tax_rate: 0 }],
});

const addLineItem = () => {
  form.line_items.push({ description: '', quantity: 1, unit_price: 0, tax_rate: 0 });
};

const removeLineItem = (index: number) => {
  form.line_items.splice(index, 1);
};

const submit = () => {
  form.post('/invoices');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Invoice" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/invoices" class="text-blue-600 hover:underline text-sm">← Back to Invoices</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Create Invoice</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="account_id">Account</Label>
                <Select v-model="form.account_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select account" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                      {{ account.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.account_id" class="text-xs text-red-600 mt-1">{{ form.errors.account_id }}</p>
              </div>

              <div>
                <Label for="due_date">Due Date</Label>
                <Input id="due_date" type="date" v-model="form.due_date" />
                <p v-if="form.errors.due_date" class="text-xs text-red-600 mt-1">{{ form.errors.due_date }}</p>
              </div>
            </div>

            <div>
              <Label>Line Items</Label>
              <div v-for="(item, index) in form.line_items" :key="index" class="grid grid-cols-5 gap-2 mt-2">
                <Input v-model="item.description" placeholder="Description" />
                <Input type="number" v-model="item.quantity" placeholder="Qty" step="0.01" />
                <Input type="number" v-model="item.unit_price" placeholder="Unit Price" step="0.01" />
                <Input type="number" v-model="item.tax_rate" placeholder="Tax Rate %" step="0.01" />
                <Button type="button" variant="ghost" @click="removeLineItem(index)" v-if="form.line_items.length > 1">Remove</Button>
              </div>
              <Button type="button" variant="outline" @click="addLineItem" class="mt-2">Add Line Item</Button>
            </div>

            <div class="flex justify-end gap-2">
              <Link href="/invoices">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">Create Invoice</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>