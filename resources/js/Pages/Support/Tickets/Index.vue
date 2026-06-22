<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ref, onMounted } from 'vue'

const props = defineProps<{
  tickets: {
    data: any[]
    links: any
    meta: any
  }
  categories: { id: string; name: string }[]
  filters: Record<string, string>
}>()

const contacts = ref<Array<{ id: string; first_name?: string; last_name?: string }>>([])
const loadingOptions = ref(false)

const form = useForm({
  search: props.filters.search || '',
  status: props.filters.status || '',
  priority: props.filters.priority || '',
  category_id: props.filters.category_id || '',
  assigned_to: props.filters.assigned_to || '',
  contact_id: props.filters.contact_id || '',
})

const statusColors: Record<string, "default" | "outline" | "success" | "destructive" | "secondary"> = {
  open: 'destructive',
  in_progress: 'default',
  waiting_on_customer: 'secondary',
  resolved: 'success',
  closed: 'outline',
}

const priorityColors: Record<string, "default" | "outline" | "success" | "destructive" | "secondary"> = {
  low: 'outline',
  medium: 'secondary',
  high: 'default',
  urgent: 'destructive',
}

const submitFilter = () => {
  router.get('/support/tickets', {
    search: form.search || undefined,
    status: form.status || undefined,
    priority: form.priority || undefined,
    category_id: form.category_id || undefined,
    assigned_to: form.assigned_to || undefined,
    contact_id: form.contact_id || undefined,
  }, { preserveState: true })
}

const clearFilters = () => {
  form.reset()
  form.get('/support/tickets')
}

onMounted(async () => {
  loadingOptions.value = true
  try {
    const res = await fetch('/api/v1/contacts?per_page=200')
    if (res.ok) {
      const payload = await res.json()
      contacts.value = (payload.data ?? []).filter((c: any) => c.first_name || c.last_name)
    }
  } catch {
    // ignore
  } finally {
    loadingOptions.value = false
  }
})
</script>

<template>
  <AppLayout>
    <Head title="Support Tickets" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Support Tickets</h1>
          <p class="text-gray-500">Manage and track customer support tickets.</p>
        </div>
        <Button @click="$inertia.visit('/support/tickets/create')">
          Create Ticket
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <Label>Search (Ticket)</Label>
              <Input v-model="form.search" placeholder="Search tickets..." class="mt-1" @keyup.enter="submitFilter" />
            </div>

            <div>
              <Label>Status</Label>
              <Select v-model="form.status" class="mt-1" @update:modelValue="submitFilter">
                <SelectTrigger>
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All statuses</SelectItem>
                  <SelectItem value="open">Open</SelectItem>
                  <SelectItem value="in_progress">In Progress</SelectItem>
                  <SelectItem value="waiting_on_customer">Waiting on Customer</SelectItem>
                  <SelectItem value="resolved">Resolved</SelectItem>
                  <SelectItem value="closed">Closed</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Priority</Label>
              <Select v-model="form.priority" class="mt-1" @update:modelValue="submitFilter">
                <SelectTrigger>
                  <SelectValue placeholder="All priorities" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All priorities</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Category</Label>
              <Select v-model="form.category_id" class="mt-1" @update:modelValue="submitFilter">
                <SelectTrigger>
                  <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All categories</SelectItem>
                  <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Assigned To</Label>
              <Select v-model="form.assigned_to" class="mt-1" @update:modelValue="submitFilter">
                <SelectTrigger>
                  <SelectValue placeholder="All assignees" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All assignees</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label>Contact</Label>
              <Select v-model="form.contact_id" class="mt-1" :disabled="loadingOptions" @update:modelValue="submitFilter">
                <SelectTrigger>
                  <SelectValue placeholder="All contacts" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All contacts</SelectItem>
                  <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                    {{ contact.first_name }} {{ contact.last_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="flex items-end">
              <Button variant="outline" @click="clearFilters" class="w-full">Clear Filters</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b">
              <tr class="text-left text-gray-500">
                <th class="p-4">Ticket</th>
                <th class="p-4">Contact</th>
                <th class="p-4">Priority</th>
                <th class="p-4">Status</th>
                <th class="p-4">Assigned</th>
                <th class="p-4">Created</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tickets.data" :key="t.id" class="border-b hover:bg-gray-50">
                <td class="p-4 font-medium">
                  <a :href="`/support/tickets/${t.id}`" class="text-blue-600 hover:underline">
                    {{ t.subject }}
                  </a>
                </td>
                <td class="p-4">{{ t.contact?.first_name }} {{ t.contact?.last_name }}</td>
                <td class="p-4">
                  <Badge :variant="priorityColors[t.priority as keyof typeof priorityColors]">
                    {{ t.priority }}
                  </Badge>
                </td>
                <td class="p-4">
                  <Badge :variant="statusColors[t.status as keyof typeof statusColors]">
                    {{ t.status }}
                  </Badge>
                </td>
                <td class="p-4">{{ t.assignee?.name ?? '-' }}</td>
                <td class="p-4">{{ t.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>

      <div class="flex justify-center">
        <div v-html="tickets.links" />
      </div>
    </div>
  </AppLayout>
</template>
