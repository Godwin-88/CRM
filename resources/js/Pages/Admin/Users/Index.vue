<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableRow,
  TableHead,
  TableHeader,
  TableBody,
  TableCell,
} from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { ref } from 'vue'
import { Plus, Trash2 } from 'lucide-vue-next'

const props = defineProps<{
  users: {
    data: Array<{
      id: string
      name: string
      email: string
      roles: Array<{ id: string; name: string }>
    }>
    links: any
  }
  roles: Array<{ id: string; name: string }>
}>()

const showCreateModal = ref(false)
const editingId = ref<string | null>(null)
const search = ref('')

const createForm = useForm({
  name: '',
  email: '',
  password: '',
  role_ids: [] as string[],
})

const editForm = useForm({
  name: '',
  email: '',
  password: '',
  role_ids: [] as string[],
})

const openCreate = () => {
  createForm.reset()
  showCreateModal.value = true
}

const openEdit = (user: typeof props.users.data[0]) => {
  editingId.value = user.id
  editForm.name = user.name
  editForm.email = user.email
  editForm.password = ''
  editForm.role_ids = user.roles.map((r) => r.id)
}

const submitCreate = () => {
  createForm.post('/admin/users', {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    },
  })
}

const submitEdit = () => {
  editForm.put(`/admin/users/${editingId.value}`, {
    onSuccess: () => {
      editingId.value = null
      editForm.reset()
    },
  })
}

const deleteUser = (id: string) => {
  if (!confirm('Delete this user?')) return
  router.delete(`/admin/users/${id}`)
}

const searchUsers = () => {
  router.get('/admin/users', { search: search.value }, { preserveState: true })
}
</script>

<template>
  <AppLayout>
    <Head title="User Management" />

    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Users</h1>
          <p class="text-gray-500">Manage system users and role assignments.</p>
        </div>
        <Button @click="openCreate"><Plus class="h-4 w-4 mr-2" />Add User</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Users</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Roles</TableHead>
                <TableHead class="w-[140px]">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell>
                  <Input v-model="search" placeholder="Search users..." class="max-w-xs" @keyup.enter="searchUsers" />
                </TableCell>
                <TableCell colspan="3"></TableCell>
              </TableRow>
              <TableRow v-if="users.data.length === 0">
                <TableCell colspan="4" class="p-8 text-center text-gray-500">No users found.</TableCell>
              </TableRow>
              <TableRow v-for="user in users.data" :key="user.id">
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell>{{ user.email }}</TableCell>
                <TableCell>
                  <span v-for="role in user.roles" :key="role.id" class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded mr-1">
                    {{ role.name }}
                  </span>
                  <span v-if="!user.roles.length" class="text-gray-400 text-sm">No roles</span>
                </TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="openEdit(user)">Edit</Button>
                    <Button variant="destructive" size="sm" @click="deleteUser(user.id)">
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div class="flex justify-center" v-if="users.links">
        <div v-html="users.links" />
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
          <h2 class="text-lg font-bold mb-4">Add User</h2>
          <form @submit.prevent="submitCreate" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Name *</label>
              <input v-model="createForm.name" required class="w-full p-2 border rounded" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Email *</label>
              <input v-model="createForm.email" type="email" required class="w-full p-2 border rounded" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Password *</label>
              <input v-model="createForm.password" type="password" required class="w-full p-2 border rounded" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Roles</label>
              <select v-model="createForm.role_ids" multiple class="w-full p-2 border rounded h-24">
                <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                  {{ role.name }}
                </option>
              </select>
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
              <Button type="submit" :disabled="createForm.processing">Create</Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="editingId" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
          <h2 class="text-lg font-bold mb-4">Edit User</h2>
          <form @submit.prevent="submitEdit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Name *</label>
              <input v-model="editForm.name" required class="w-full p-2 border rounded" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Email *</label>
              <input v-model="editForm.email" type="email" required class="w-full p-2 border rounded" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Password (leave blank to keep)</label>
              <input v-model="editForm.password" type="password" class="w-full p-2 border rounded" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Roles</label>
              <select v-model="editForm.role_ids" multiple class="w-full p-2 border rounded h-24">
                <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                  {{ role.name }}
                </option>
              </select>
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="editingId = null">Cancel</Button>
              <Button type="submit" :disabled="editForm.processing">Save</Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
