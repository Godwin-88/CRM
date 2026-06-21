<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'

const props = defineProps<{
  tickets: {
    data: Array<{
      id: string
      subject: string
      priority: string
      status: string
      sla_breached_at: string
      created_at: string
      contact?: { first_name: string; last_name: string }
      category?: { name: string }
    }>
    links: any
  }
}>()

const sortField = ref('sla_breached_at')
const sortDir = ref('desc')

const sort = (field: string) => {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'asc'
  }
  router.get('/admin/support/sla-breaches', {
    sort: sortField.value,
    dir: sortDir.value,
  })
}
</script>

<template>
  <AppLayout>
    <Head title="SLA Breaches" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">SLA Breaches</h1>
          <p class="text-gray-500">Tickets that have exceeded their SLA deadlines.</p>
        </div>
        <Button @click="router.get('/admin/support/sla-breaches', { sort: 'sla_breached_at', dir: 'desc' })">
          Refresh
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Breached Tickets</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHead>
              <TableRow>
                <TableHead @click="sort('subject')" class="cursor-pointer">Ticket</TableHead>
                <TableHead>Contact</TableHead>
                <TableHead>Category</TableHead>
                <TableHead @click="sort('priority')" class="cursor-pointer">Priority</TableHead>
                <TableHead @click="sort('sla_breached_at')" class="cursor-pointer">Breach Time</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow v-for="ticket in tickets.data" :key="ticket.id">
                <TableCell>
                  <a :href="`/support/tickets/${ticket.id}`" class="text-blue-600 hover:underline">
                    {{ ticket.subject }}
                  </a>
                </TableCell>
                <TableCell>{{ ticket.contact ? `${ticket.contact.first_name} ${ticket.contact.last_name}` : '-' }}</TableCell>
                <TableCell>{{ ticket.category?.name || '-' }}</TableCell>
                <TableCell>
                  <Badge :variant="ticket.priority === 'urgent' ? 'destructive' : 'secondary'">
                    {{ ticket.priority }}
                  </Badge>
                </TableCell>
                <TableCell>{{ ticket.sla_breached_at }}</TableCell>
                <TableCell>
                  <a :href="`/support/tickets/${ticket.id}`" class="text-blue-600 hover:underline text-sm">
                    View Ticket
                  </a>
                </TableCell>
              </TableRow>
              <tr v-if="tickets.data.length === 0">
                <td colspan="6" class="p-4 text-center text-gray-500">No SLA breaches found.</td>
              </tr>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <div class="flex justify-center" v-if="tickets.links">
        <div v-html="tickets.links" />
      </div>
    </div>
  </AppLayout>
</template>