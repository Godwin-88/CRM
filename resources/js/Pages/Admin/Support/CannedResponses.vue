<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'
import { Textarea } from '@/components/ui/textarea'
import { ref } from 'vue'

const props = defineProps<{
  responses: {
    data: Array<{
      id: string
      title: string
      body: string
      category_tag: string
      is_active: boolean
      usage_count: number
    }>
    links: any
  }
  categories: string[]
  filters: { category?: string }
}>()

const showCreateModal = ref(false)
const showEditModal = ref<string | null>(null)

const createForm = useForm({
  title: '',
  body: '',
  category_tag: '',
})

const editForm = useForm({
  title: '',
  body: '',
  category_tag: '',
})

const submitCreate = () => {
  createForm.post(route('admin.support.canned-responses.store'), {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    }
  })
}

const submitEdit = (id: string) => {
  editForm.put(route('admin.support.canned-responses.update', id), {
    onSuccess: () => {
      showEditModal.value = null
      editForm.reset()
    }
  })
}

const openEditModal = (response: typeof props.responses.data[0]) => {
  showEditModal.value = response.id
  editForm.title = response.title
  editForm.body = response.body
  editForm.category_tag = response.category_tag || ''
}
</script>

<template>
  <AppLayout>
    <Head title="Canned Responses" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Canned Responses</h1>
          <p class="text-gray-500">Manage pre-written responses for common queries.</p>
        </div>
        <Button @click="showCreateModal = true">Add Response</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Canned Responses</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHead>
              <TableRow>
                <TableHead>Title</TableHead>
                <TableHead>Category Tag</TableHead>
                <TableHead>Usage Count</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow v-for="response in responses.data" :key="response.id">
                <TableCell class="font-medium">{{ response.title }}</TableCell>
                <TableCell>
                  <Badge v-if="response.category_tag" variant="secondary">
                    {{ response.category_tag }}
                  </Badge>
                  <span v-else class="text-gray-500">-</span>
                </TableCell>
                <TableCell>{{ response.usage_count }}</TableCell>
                <TableCell>
                  <Badge :variant="response.is_active ? 'success' : 'outline'">
                    {{ response.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Button @click="openEditModal(response)" variant="outline" size="sm">
                    Edit
                  </Button>
                </TableCell>
              </TableRow>
              <tr v-if="responses.data.length === 0">
                <td colspan="5" class="p-4 text-center text-gray-500">No canned responses found.</td>
              </tr>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div class="flex justify-center" v-if="responses.links">
        <div v-html="responses.links" />
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-lg w-full p-6">
        <h2 class="text-lg font-bold mb-4">Create Canned Response</h2>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title *</label>
            <input v-model="createForm.title" required class="w-full p-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Category Tag</label>
            <select v-model="createForm.category_tag" class="w-full p-2 border rounded">
              <option value="">None</option>
              <option v-for="cat in categories" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Body (supports {first_name}, {last_name}, {ticket_number}, {agent_name}, {account_name}) *</label>
            <Textarea v-model="createForm.body" required rows="6" />
          </div>
          <div class="flex justify-end gap-2">
            <Button @click="showCreateModal = false" variant="outline">Cancel</Button>
            <Button type="submit" :disabled="createForm.processing">Create</Button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-lg w-full p-6">
        <h2 class="text-lg font-bold mb-4">Edit Canned Response</h2>
        <form @submit.prevent="submitEdit(showEditModal!)" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Title *</label>
            <input v-model="editForm.title" required class="w-full p-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Category Tag</label>
            <select v-model="editForm.category_tag" class="w-full p-2 border rounded">
              <option value="">None</option>
              <option v-for="cat in categories" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Body *</label>
            <Textarea v-model="editForm.body" required rows="6" />
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