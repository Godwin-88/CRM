<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2 } from 'lucide-vue-next';

interface CustomField {
  id: string;
  name: string;
  type: 'text' | 'number' | 'date' | 'select' | 'boolean';
  options: string[] | null;
  entity_type: 'contact' | 'account';
}

const fields = ref<CustomField[]>([]);
const isCreateOpen = ref(false);
const isDeleteOpen = ref(false);
const selectedField = ref<CustomField | null>(null);

const newField = ref({
  name: '',
  type: 'text' as CustomField['type'],
  entity_type: 'contact' as CustomField['entity_type'],
  options: '',
});

const typeLabel = (type: string) => {
  const map: Record<string, string> = { text: 'Text', number: 'Number', date: 'Date', select: 'Dropdown', boolean: 'Boolean' };
  return map[type] || type;
};

const openCreate = () => {
  newField.value = { name: '', type: 'text', entity_type: 'contact', options: '' };
  isCreateOpen.value = true;
};

const openDelete = (field: CustomField) => {
  selectedField.value = field;
  isDeleteOpen.value = true;
};

const createField = async () => {
  const payload: any = {
    name: newField.value.name,
    type: newField.value.type,
    entity_type: newField.value.entity_type,
  };
  if (newField.value.type === 'select' && newField.value.options) {
    payload.options = newField.value.options.split(',').map((o: string) => o.trim()).filter(Boolean);
  }
  await fetch('/api/v1/custom-fields', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify(payload),
  });
  isCreateOpen.value = false;
  loadFields();
};

const deleteField = async () => {
  if (!selectedField.value) return;
  await fetch(`/api/v1/custom-fields/${selectedField.value.id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  isDeleteOpen.value = false;
  selectedField.value = null;
  loadFields();
};

const loadFields = async () => {
  const res = await fetch('/api/v1/custom-fields?per_page=100', {
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  const data = await res.json();
  fields.value = data.data || [];
};

onMounted(loadFields);
</script>

<template>
  <AppLayout>
    <Head title="Custom Fields" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Custom Fields</h1>
          <p class="text-gray-500">Manage custom fields for contacts and accounts.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button><Plus class="h-4 w-4 mr-2" />Add Field</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create Custom Field</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <Label>Field Name</Label>
                <Input v-model="newField.name" placeholder="e.g., Annual Revenue" />
              </div>
              <div>
                <Label>Entity Type</Label>
                <Select v-model="newField.entity_type">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="contact">Contact</SelectItem>
                    <SelectItem value="account">Account</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Field Type</Label>
                <Select v-model="newField.type">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="text">Text</SelectItem>
                    <SelectItem value="number">Number</SelectItem>
                    <SelectItem value="date">Date</SelectItem>
                    <SelectItem value="select">Dropdown</SelectItem>
                    <SelectItem value="boolean">Boolean</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div v-if="newField.type === 'select'">
                <Label>Options (comma separated)</Label>
                <Input v-model="newField.options" placeholder="Option 1, Option 2, Option 3" />
              </div>
              <Button @click="createField" class="w-full">Create Field</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Entity</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Options</TableHead>
                <TableHead class="w-24"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="field in fields" :key="field.id">
                <TableCell class="font-medium">{{ field.name }}</TableCell>
                <TableCell><Badge variant="outline">{{ field.entity_type }}</Badge></TableCell>
                <TableCell>{{ typeLabel(field.type) }}</TableCell>
                <TableCell class="text-sm text-gray-500">
                  <span v-if="field.options?.length">{{ field.options.join(', ') }}</span>
                  <span v-else>—</span>
                </TableCell>
                <TableCell>
                  <Button variant="ghost" size="sm" @click="openDelete(field)">
                    <Trash2 class="h-4 w-4 text-red-500" />
                  </Button>
                </TableCell>
              </TableRow>
              <TableRow v-if="!fields.length">
                <TableCell colspan="5" class="text-center text-gray-500 py-8">No custom fields defined.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog v-model:open="isDeleteOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Custom Field</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <p class="text-sm text-gray-600">
              Delete <strong>{{ selectedField?.name }}</strong>? All values for this field will be removed.
            </p>
            <div class="flex gap-2">
              <Button variant="destructive" @click="deleteField">Delete</Button>
              <Button variant="outline" @click="isDeleteOpen = false">Cancel</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
