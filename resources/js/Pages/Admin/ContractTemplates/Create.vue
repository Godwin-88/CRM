<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
  clauses: { id: string; name: string; category: string }[];
  contractTypes: string[];
}>();

const form = useForm({
  name: '',
  description: '',
  type: '',
  is_active: true,
  clauses: [] as any[],
});

const submit = () => {
  form.post('/admin/contract-templates', {
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Create Contract Template" />
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/admin/contract-templates" class="text-blue-600 hover:underline text-sm">
          ← Back to Templates
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Create Contract Template</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="name">Name</Label>
              <Input id="name" v-model="form.name" :error="form.errors.name" />
              <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
              <Label for="description">Description</Label>
              <Textarea id="description" v-model="form.description" rows="3" />
              <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">{{ form.errors.description }}</p>
            </div>

            <div>
              <Label for="type">Type</Label>
              <Select v-model="form.type">
                <SelectTrigger>
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="type in contractTypes" :key="type" :value="type">
                    {{ type }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.type" class="text-xs text-red-600 mt-1">{{ form.errors.type }}</p>
            </div>

            <div>
              <Label>Clauses</Label>
              <div class="mt-2 space-y-2 max-h-60 overflow-y-auto border rounded p-2">
                <div v-for="clause in clauses" :key="clause.id" class="flex items-center gap-2">
                  <input
                    :id="`clause-${clause.id}`"
                    type="checkbox"
                    :value="clause.id"
                    @change="
                      (e: any) => {
                        if (e.target.checked) {
                          form.clauses.push({ id: clause.id, is_mandatory: true });
                        } else {
                          form.clauses = form.clauses.filter((c: any) => c.id !== clause.id);
                        }
                      }
                    "
                  />
                  <label :for="`clause-${clause.id}`" class="text-sm">
                    {{ clause.name }}
                    <span class="text-gray-500">({{ clause.category }})</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2">
              <Link href="/admin/contract-templates">
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" :disabled="form.processing">Create Template</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>