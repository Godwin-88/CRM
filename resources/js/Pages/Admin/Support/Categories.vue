<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableRow,
  TableHead,
  TableBody,
  TableCell,
} from '@/components/ui/table'
import { ref, computed } from 'vue'

const props = defineProps<{
  categories: {
    data: Array<{
      id: string
      name: string
      description: string
      default_priority: string
      is_agent_only: boolean
      is_active: boolean
      parent?: { id: string; name: string }
      children?: Array<{ id: string; name: string }>
      slaPolicy?: { id: string; name: string }
      defaultTeam?: { id: string; name: string }
    }>
    links: any
  }
  sla_policies: Array<{ id: string; name: string }>
  teams: Array<{ id: string; name: string }>
}>()

const showCreateModal = ref(false)
const showEditModal = ref<string | null>(null)
const searchQuery = ref('')
const statusFilter = ref('all')

const filteredCategories = computed(() => {
  let data = props.categories.data

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    data = data.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        (c.description && c.description.toLowerCase().includes(q))
    )
  }

  if (statusFilter.value !== 'all') {
    data = data.filter((c) => c.is_active === (statusFilter.value === 'active'))
  }

  return data
})

const createForm = useForm({
  name: '',
  description: '',
  parent_id: '',
  default_priority: 'medium',
  default_team_id: '',
  sla_policy_id: '',
  is_agent_only: false,
  is_active: true,
})

const editForm = useForm({
  name: '',
  description: '',
  parent_id: '',
  default_priority: 'medium',
  default_team_id: '',
  sla_policy_id: '',
  is_agent_only: false,
  is_active: true,
})

const submitCreate = () => {
  createForm.post(route('admin.support.categories.store'), {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    }
  })
}

const submitEdit = (id: string) => {
  editForm.put(route('admin.support.categories.update', id), {
    onSuccess: () => {
      showEditModal.value = null
      editForm.reset()
    }
  })
}

const openEditModal = (category: typeof props.categories.data[0]) => {
  showEditModal.value = category.id
  editForm.name = category.name
  editForm.description = category.description || ''
  editForm.parent_id = category.parent?.id || ''
  editForm.default_priority = category.default_priority
  editForm.default_team_id = category.defaultTeam?.id || ''
  editForm.sla_policy_id = category.slaPolicy?.id || ''
  editForm.is_agent_only = category.is_agent_only
  editForm.is_active = category.is_active
}
</script>

<template>
  <AppLayout>
    <Head title="Support Categories" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Support Categories</h1>
          <p class="text-gray-500">Manage ticket categories and their configurations.</p>
        </div>
        <Button @click="showCreateModal = true">Add Category</Button>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Categories</CardTitle>
            <div class="flex items-center gap-3">
              <Input
                v-model="searchQuery"
                placeholder="Search categories..."
                class="w-64"
              />
              <select
                v-model="statusFilter"
                class="p-2 border rounded text-sm"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHead>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Default Priority</TableHead>
                <TableHead>Agent Only</TableHead>
                <TableHead>SLA Policy</TableHead>
                <TableHead>Default Team</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow v-for="category in filteredCategories" :key="category.id">
                <TableCell>
                  <div>
                    <p class="font-medium">{{ category.name }}</p>
                    <p v-if="category.children?.length" class="text-xs text-gray-500">
                      {{ category.children.length }} subcategories
                    </p>
                  </div>
                </TableCell>
                <TableCell>{{ category.description || '-' }}</TableCell>
                <TableCell>
                  <Badge variant="secondary">{{ category.default_priority }}</Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="category.is_agent_only ? 'default' : 'outline'">
                    {{ category.is_agent_only ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ category.slaPolicy?.name || '-' }}</TableCell>
                <TableCell>{{ category.defaultTeam?.name || '-' }}</TableCell>
                <TableCell>
                  <Badge :variant="category.is_active ? 'success' : 'outline'">
                    {{ category.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Button @click="openEditModal(category)" variant="outline" size="sm">
                    Edit
                  </Button>
                </TableCell>
              </TableRow>
              <tr v-if="filteredCategories.length === 0">
                <td colspan="8" class="p-4 text-center text-gray-500">No categories found.</td>
              </tr>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div class="flex justify-center" v-if="categories.links">
        <div v-html="categories.links" />
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-bold mb-4">Create Category</h2>
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Name *</label>
            <input v-model="createForm.name" required class="w-full p-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="createForm.description" class="w-full p-2 border rounded" rows="2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Parent Category</label>
            <select v-model="createForm.parent_id" class="w-full p-2 border rounded">
              <option value="">None (Top-level)</option>
              <option v-for="cat in categories.data" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Default Priority</label>
            <select v-model="createForm.default_priority" class="w-full p-2 border rounded">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Default Team</label>
            <select v-model="createForm.default_team_id" class="w-full p-2 border rounded">
              <option value="">None</option>
              <option v-for="team in teams" :key="team.id" :value="team.id">
                {{ team.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">SLA Policy</label>
            <select v-model="createForm.sla_policy_id" class="w-full p-2 border rounded">
              <option value="">None</option>
              <option v-for="policy in sla_policies" :key="policy.id" :value="policy.id">
                {{ policy.name }}
              </option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="createForm.is_agent_only" type="checkbox" id="is_agent_only" />
            <label for="is_agent_only" class="text-sm">Agent only (hidden from portal)</label>
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
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-bold mb-4">Edit Category</h2>
        <form @submit.prevent="submitEdit(showEditModal!)" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Name *</label>
            <input v-model="editForm.name" required class="w-full p-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="editForm.description" class="w-full p-2 border rounded" rows="2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Default Priority</label>
            <select v-model="editForm.default_priority" class="w-full p-2 border rounded">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Default Team</label>
            <select v-model="editForm.default_team_id" class="w-full p-2 border rounded">
              <option value="">None</option>
              <option v-for="team in teams" :key="team.id" :value="team.id">
                {{ team.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">SLA Policy</label>
            <select v-model="editForm.sla_policy_id" class="w-full p-2 border rounded">
              <option value="">None</option>
              <option v-for="policy in sla_policies" :key="policy.id" :value="policy.id">
                {{ policy.name }}
              </option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="editForm.is_agent_only" type="checkbox" id="edit_is_agent_only" />
            <label for="edit_is_agent_only" class="text-sm">Agent only</label>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="editForm.is_active" type="checkbox" id="edit_is_active" />
            <label for="edit_is_active" class="text-sm">Active</label>
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