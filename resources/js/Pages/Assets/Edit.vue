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
    asset: any;
    types: string[];
    statuses: string[];
}>();

const form = useForm({
    name: props.asset.name || '',
    type: props.asset.type || 'hardware',
    identifier: props.asset.identifier || '',
    purchase_date: props.asset.purchase_date || '',
    purchase_cost: props.asset.purchase_cost !== null && props.asset.purchase_cost !== undefined ? Number(props.asset.purchase_cost) : 0,
    currency: props.asset.currency || 'USD',
    status: props.asset.status || 'available',
    useful_life_years: props.asset.useful_life_years !== null && props.asset.useful_life_years !== undefined ? Number(props.asset.useful_life_years) : null,
    total_quantity: props.asset.total_quantity ? Number(props.asset.total_quantity) : '',
    available_quantity: props.asset.available_quantity ? Number(props.asset.available_quantity) : '',
    minimum_threshold: props.asset.minimum_threshold ? Number(props.asset.minimum_threshold) : '',
});

const isStockTrackable = computed(() => {
    return form.type && ['hardware', 'furniture', 'custom'].includes(form.type);
});

const bookValue = computed(() => {
    const cost = Number(form.purchase_cost) || 0;
    const lifeYears = Number(form.useful_life_years) || 0;
    
    if (!cost || !lifeYears || !form.purchase_date) {
        return cost;
    }
    const yearsSincePurchase = new Date().getFullYear() - new Date(form.purchase_date).getFullYear();
    const annualDepreciation = cost / lifeYears;
    return Math.max(0, cost - (annualDepreciation * yearsSincePurchase));
});

const submit = () => {
    form.put(`/assets/${props.asset.id}`);
};
</script>

<template>
  <AppLayout>
    <Head title="Edit Asset" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/assets" class="text-blue-600 hover:underline text-sm">← Back to Assets</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Edit Asset</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="name">Name</Label>
              <Input id="name" v-model="form.name" />
              <p v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</p>
            </div>

            <div>
              <Label for="type">Type</Label>
              <Select v-model="form.type">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="type in types" :key="type" :value="type" class="capitalize">
                    {{ type }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="identifier">Identifier / Serial Number</Label>
              <Input id="identifier" v-model="form.identifier" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="purchase_date">Purchase Date</Label>
                <Input id="purchase_date" type="date" v-model="form.purchase_date" />
              </div>
              <div>
                <Label for="purchase_cost">Purchase Cost</Label>
                <Input id="purchase_cost" type="number" step="0.01" v-model="form.purchase_cost" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="useful_life_years">Useful Life (years)</Label>
                <Input id="useful_life_years" type="number" min="1" v-model="form.useful_life_years" />
              </div>
              <div>
                <Label>Book Value (computed)</Label>
                <p class="font-medium mt-2">${{ Number(bookValue).toLocaleString() }}</p>
              </div>
            </div>

            <div v-if="isStockTrackable" class="border-t pt-4">
              <Label class="mb-2">Stock Level Tracking</Label>
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <Label for="total_quantity">Total Quantity</Label>
                  <Input id="total_quantity" type="number" step="0.01" v-model="form.total_quantity" />
                </div>
                <div>
                  <Label for="available_quantity">Available</Label>
                  <Input id="available_quantity" type="number" step="0.01" v-model="form.available_quantity" />
                </div>
                <div>
                  <Label for="minimum_threshold">Min Threshold</Label>
                  <Input id="minimum_threshold" type="number" step="0.01" v-model="form.minimum_threshold" />
                </div>
              </div>
            </div>

            <Button type="submit" :disabled="form.processing">Update Asset</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>