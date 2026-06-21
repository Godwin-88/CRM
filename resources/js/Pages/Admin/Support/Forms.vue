<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'
import { ref } from 'vue'

const props = defineProps<{
  forms: {
    data: Array<{
      id: string
      name: string
      category: { id: string; name: string }
      fields: Array<{
        id: string
        name: string
        type: string
        required: boolean
      }>
    }>
    links: any
  }
  categories: Array<{ id: string; name: string }>
}>()

const showCreateModal = ref(false)
const showEditModal = ref<string | null>(null)

const createForm = useForm({
  ticket_category_id: '',
  name: '',
  fields: [] as Array<{ name: string; type: string; required: boolean; options?: string[] }>,
})

const editForm = useForm({
  ticket_category_id: '',
  name: '',
  fields: [] as Array<{ name: string; type: string; required: boolean; options?: string[] }>,
})

const fieldTypes = [
  { value: 'text', label: 'Text' },
  { value: 'textarea', label: 'Text Area' },
  { value: 'number', label: 'Number' },
  { value: 'date', label: 'Date' },
  { value: 'dropdown', label: 'Dropdown' },
  { value: 'checkbox', label: 'Checkbox' },
  { value: 'file_upload', label: 'File Upload' },
]

const addField = (form: typeof createForm | typeof editForm) => {
  form.fields.push({ name: '', type: 'text', required: false })
}

const removeField = (index: number, form: typeof createForm | typeof editForm) => {
  form.fields.splice(index, 1)
}

const submitCreate = () => {
  createForm.post(route('admin.support.forms.store'), {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    }
  })
}

const submitEdit = (id: string) => {
  editForm.put(route('admin.support.forms.update', id), {
    onSuccess: () => {
      showEditModal.value = null
      editForm.reset()
    }
  })
}

const openEditModal = (form: typeof props.forms.data[0]) => {
  showEditModal.value = form.id
  editForm.ticket_category_id = form.category?.id || ''
  editForm.name = form.name
  editForm.fields = form.fields || []
}
</script>

<template>
  <AppLayout>
    <Head title="Ticket Forms" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Ticket Forms</h1>
          <p class="text-gray-500">Manage custom intake forms for support categories.</p>
        </div>
        <Button @click="showCreateModal = true">Add Form</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Custom Forms</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHead>
              <TableRow>
                <TableHead>Form Name</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Fields</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow v-for="form in forms.data" :key="form.id">
                <TableCell class="font-medium">{{ form.name }}</TableCell>
                <TableCell>{{ form.category?.name || '-' }}</TableCell>
                <TableCell>
                  <span class="text-sm text-gray-600">
                    {{ form.fields?.length || 0 }} fields
                  </span>
                </TableCell>
                <TableCell>
                  <Button @click="openEditModal(form)" variant="outline" size="sm">
                    Edit
                  </Button>
                </TableCell>
              </TableRow>
              <tr v-if="forms.data.length === 0">
                <td colspan="4" class="p-4 text-center text-gray-500">No forms found.</td>
              </tr>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div class="flex justify-center" v-if="forms.links">
        <div v-html="forms.links" />
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-bold mb-4">Create Form</h2>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Category *</label>
            <select v-model="createForm.ticket_category_id" required class="w-full p-2 border rounded">
              <option value="">Select category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Form Name *</label>
            <input v-model="createForm.name" required class="w-full p-2 border rounded" />
          </div>
          
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium">Form Fields</label>
              <Button @click="addField(createForm)" type="button" variant="outline" size="sm">
                Add Field
              </Button>
            </div>
            <div v-for="(field, index) in createForm.fields" :key="index" class="border rounded p-3 mb-2">
              <div class="grid grid-cols-12 gap-2">
                <input v-model="field.name" placeholder="Field name" class="col-span-4 p-2 border rounded" />
                <select v-model="field.type" class="col-span-3 p-2 border rounded">
                  <option v-for="ft in fieldTypes" :key="ft.value" :value="ft.value">
                    {{ ft.label }}
                  </option>
                </select>
                <label class="col-span-3 flex items-center gap-1 text-sm">
                  <input v-model="field.required" type="checkbox" />
                  Required
                </label>
                <Button @click="removeField(index, createForm)" variant="ghost" size="sm" class="col-span-2">
                  Remove
                </Button>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end gap-2">
            <Button @click="showCreateModal = false" variant="outline">Cancel</Button>
            <Button type="submit" :disabled="createForm.processing">Create</Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-bold mb-4">Edit Form</h2>
        <form @submit.prevent="submitEdit(showEditModal!)" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Category *</label>
            <select v-model="editForm.ticket_category_id" required class="w-full p-2 border rounded">
              <option value="">Select category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Form Name *</label>
            <input v-model="editForm.name" required class="w-full p-2 border rounded" />
          </div>
          
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium">Form Fields</label>
              <Button @click="addField(editForm)" type="button" variant="outline" size="sm">
                Add Field
              </Button>
            </div>
            <div v-for="(field, index) in editForm.fields" :key="index" class="border rounded p-3 mb-2">
              <div class="grid grid-cols-12 gap-2">
                <input v-model="field.name" placeholder="Field name" class="col-span-4 p-2 border rounded" />
                <select v-model="field.type" class="col-span-3 p-2 border rounded">
                  <option v-for="ft in fieldTypes" :key="ft.value" :value="ft.value">
                    {{ ft.label }}
                  </option>
                </select>
                <label class="col-span-3 flex items-center gap-1 text-sm">
                  <input v-model="field.required" type="checkbox" />
                  Required
                </label>
                <Button @click="removeField(index, editForm)" variant="ghost" size="sm" class="col-span-2">
                  Remove
                </Button>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end gap-2">
            <Button @click="showEditModal = null" variant="outline">Cancel</Button>
            <Button type="submit" :disabled="editForm.processing">Save</Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>