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
  name: '',
  type: 'hardware',
  identifier: '',
  purchase_date: '',
  purchase_cost: 0,
  currency: 'USD',
  status: 'available',
  useful_life_years: 5,
});

const submit = () => {
  form.post('/assets');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Asset" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/assets" class="text-blue-600 hover:underline text-sm">← Back to Assets</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Create Asset</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="name">Name</Label>
              <Input id="name" v-model="form.name" />
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
              <Label for="purchase_cost">Purchase Cost</Label>
              <Input id="purchase_cost" type="number" step="0.01" v-model="form.purchase_cost" />
            </div>

            <Button type="submit" :disabled="form.processing">Create Asset</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>