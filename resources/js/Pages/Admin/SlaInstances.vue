<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'

const props = defineProps<{
  instances: {
    id: string
    ticket_id: string
    ticket?: { subject: string; priority: string }
    sla_definition_id: string
    sla_definition?: { name: string }
    first_response_deadline: string
    resolution_deadline: string
    first_response_breached: boolean
    resolution_breached: boolean
  }[]
}>()
</script>

<template>
  <AppLayout>
    <Head title="SLA Instances" />
    
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">SLA Instances</h1>
        <p class="text-gray-500">Monitor active and expired SLAs.</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>SLA Tracking</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHead>
              <TableRow>
                <TableHead>Ticket</TableHead>
                <TableHead>SLA Policy</TableHead>
                <TableHead>First Response Deadline</TableHead>
                <TableHead>Resolution Deadline</TableHead>
                <TableHead>Breach Status</TableHead>
              </TableRow>
            </TableHead>
            <TableBody>
              <TableRow v-for="instance in instances" :key="instance.id">
                <TableCell>
                  <a :href="`/support/tickets/${instance.ticket_id}`" class="text-blue-600 hover:underline">
                    {{ instance.ticket?.subject }}
                  </a>
                </TableCell>
                <TableCell>{{ instance.sla_definition?.name }}</TableCell>
                <TableCell>{{ instance.first_response_deadline }}</TableCell>
                <TableCell>{{ instance.resolution_deadline }}</TableCell>
                <TableCell>
                  <div class="space-x-1">
                    <Badge v-if="instance.first_response_breached" variant="destructive">Response Breached</Badge>
                    <Badge v-if="instance.resolution_breached" variant="destructive">Resolution Breached</Badge>
                    <Badge v-if="!instance.first_response_breached && !instance.resolution_breached" variant="success">On Track</Badge>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>