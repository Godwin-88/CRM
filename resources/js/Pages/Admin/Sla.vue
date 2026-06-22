<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'

const props = defineProps<{
  slaDefinitions: {
    id: string
    name: string
    description: string
    support_category?: string
    priority: string
    first_response_time_business_hours: number
    resolution_time_business_hours: number
    is_default: boolean
    businessHours?: any[]
  }[]
  businessHours: any[]
  prefill?: {
    name?: string
    priority?: string
    first_response_time_business_hours?: number | null
    resolution_time_business_hours?: number | null
  }
}>()

const showCreateModal = ref(false)

const newSla = useForm({
  name: props.prefill?.name || '',
  description: '',
  priority: props.prefill?.priority || '',
  support_category_id: '',
  loyalty_tier_id: '',
  account_type: '',
  first_response_time_business_hours: props.prefill?.first_response_time_business_hours || 4,
  resolution_time_business_hours: props.prefill?.resolution_time_business_hours || 24,
  is_default: false,
})

const categories = [
  { value: 'general', label: 'General' },
  { value: 'billing', label: 'Billing' },
  { value: 'technical', label: 'Technical' },
  { value: 'account', label: 'Account' },
]

const submitSla = () => {
  newSla.post('/admin/sla', {
    onSuccess: () => {
      showCreateModal.value = false
      newSla.reset()
    }
  })
}

onMounted(() => {
  if (props.prefill?.name || props.prefill?.priority || props.prefill?.first_response_time_business_hours || props.prefill?.resolution_time_business_hours) {
    showCreateModal.value = true
  } else {
    try {
      const prefill = JSON.parse(localStorage.getItem('assistant_navigation_prefill:/admin/sla') || 'null')
      if (prefill) {
        newSla.name = prefill.name || ''
        newSla.first_response_time_business_hours = prefill.first_response_time_business_hours || 4
        newSla.resolution_time_business_hours = prefill.resolution_time_business_hours || 24
        localStorage.removeItem('assistant_navigation_prefill:/admin/sla')
        showCreateModal.value = true
      }
    } catch {
      localStorage.removeItem('assistant_navigation_prefill:/admin/sla')
    }
  }
})
</script>

<template>
  <AppLayout>
    <Head title="Service Level Agreements" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Service Level Agreements</h1>
          <p class="text-gray-500">Define response and resolution targets for support tickets.</p>
          <p v-if="props.prefill?.name || props.prefill?.priority || props.prefill?.first_response_time_business_hours || props.prefill?.resolution_time_business_hours" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
            Assistant prefill: {{ props.prefill.name || 'New SLA' }}
            <span v-if="props.prefill.priority">Priority: {{ props.prefill.priority }}</span>
            <span v-if="props.prefill.first_response_time_business_hours">First Response: {{ props.prefill.first_response_time_business_hours }}h</span>
            <span v-if="props.prefill.resolution_time_business_hours">Resolution: {{ props.prefill.resolution_time_business_hours }}h</span>
          </p>
        </div>
        <Button @click="showCreateModal = true">New SLA</Button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent class="pt-6">
            <p class="text-sm text-gray-500">Active SLAs</p>
            <p class="text-3xl font-bold">{{ slaDefinitions.length }}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <p class="text-sm text-gray-500">Avg Response Time</p>
            <p class="text-3xl font-bold">
              {{ slaDefinitions.length ? Math.round(slaDefinitions.reduce((a: any, b: any) => a + b.first_response_time_business_hours, 0) / slaDefinitions.length) : 0 }} hrs
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <p class="text-sm text-gray-500">Avg Resolution Time</p>
            <p class="text-3xl font-bold">
              {{ slaDefinitions.length ? Math.round(slaDefinitions.reduce((a: any, b: any) => a + b.resolution_time_business_hours, 0) / slaDefinitions.length) : 0 }} hrs
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>SLA Definitions</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHead>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Priority</TableHead>
                <TableHead>First Response</TableHead>
                <TableHead>Resolution</TableHead>
                <TableHead>Default</TableHead>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow v-for="sla in slaDefinitions" :key="sla.id">
                <TableCell>{{ sla.name }}</TableCell>
                <TableCell>{{ sla.support_category || '-' }}</TableCell>
                <TableCell>
                  <Badge :variant="sla.priority === 'critical' ? 'destructive' : 'secondary'">
                    {{ sla.priority }}
                  </Badge>
                </TableCell>
                <TableCell>{{ sla.first_response_time_business_hours }} hrs</TableCell>
                <TableCell>{{ sla.resolution_time_business_hours }} hrs</TableCell>
                <TableCell>
                  <Badge :variant="sla.is_default ? 'success' : 'outline'">
                    {{ sla.is_default ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>

    <!-- Create SLA Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-lg max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-bold mb-4">Create SLA Definition</h2>
        <form @submit.prevent="submitSla" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Name *</label>
            <input v-model="newSla.name" required class="w-full p-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea v-model="newSla.description" class="w-full p-2 border rounded" rows="2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Priority *</label>
            <select v-model="newSla.priority" class="w-full p-2 border rounded">
              <option value="">Select priority</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
              <option value="critical">Critical</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">First Response Time (business hours) *</label>
            <input v-model.number="newSla.first_response_time_business_hours" type="number" min="1" required class="w-full p-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Resolution Time (business hours) *</label>
            <input v-model.number="newSla.resolution_time_business_hours" type="number" min="1" required class="w-full p-2 border rounded" />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="newSla.is_default" type="checkbox" id="is_default" />
            <label for="is_default" class="text-sm">Set as default SLA</label>
          </div>
          <div class="flex justify-end gap-2">
            <Button @click="showCreateModal = false" variant="outline">Cancel</Button>
            <Button type="submit" :disabled="newSla.processing">Create</Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>