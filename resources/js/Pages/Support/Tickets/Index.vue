<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Select } from '@/components/ui/select'
import { Input } from '@/components/ui/input'

const props = defineProps<{
  tickets: {
    data: any[]
    links: any
    meta: any
  }
  categories: { id: string; name: string }[]
  filters: Record<string, string>
}>()

const form = useForm({
  search: props.filters.search || '',
  status: props.filters.status || '',
  priority: props.filters.priority || '',
  category_id: props.filters.category_id || '',
  account_id: props.filters.account_id || '',
  contact_id: props.filters.contact_id || '',
  sla: props.filters.sla || '',
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
    account_id: form.account_id || undefined,
    contact_id: form.contact_id || undefined,
    sla: form.sla || undefined,
  }, { preserveState: true })
}

const clearFilters = () => {
  form.reset()
  form.get('/support/tickets')
}
</script>

<template>
  <AppLayout>
    <Head title="Support Tickets" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Support Tickets</h1>
          <p class="text-gray-500">Manage and track customer support tickets.</p>
          <p v-if="props.filters.account_name || props.filters.contact_name || props.filters.sla" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
            Assistant prefill:
            <span v-if="props.filters.account_name">Account: {{ props.filters.account_name }}</span>
            <span v-if="props.filters.contact_name">Contact: {{ props.filters.contact_name }}</span>
            <span v-if="props.filters.sla">SLA: {{ props.filters.sla }}</span>
          </p>
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
        <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
          <Input v-model="form.search" placeholder="Search tickets..." @change="submitFilter" />

          <Select v-model="form.status" @change="submitFilter">
              <option value="">All Statuses</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="waiting_on_customer">Waiting on Customer</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </Select>

            <Select v-model="form.priority" @change="submitFilter">
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </Select>

            <Select v-model="form.category_id" @change="submitFilter">
              <option value="">All Categories</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </Select>

            <Select v-model="form.sla" @change="submitFilter">
              <option value="">All SLA States</option>
              <option value="breached">Breached</option>
            </Select>

            <Input v-model="form.account_id" placeholder="Account ID" @change="submitFilter" />

            <Input v-model="form.contact_id" placeholder="Contact ID" @change="submitFilter" />

            <div class="flex gap-2">
              <Button @click="clearFilters" variant="outline">Clear</Button>
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