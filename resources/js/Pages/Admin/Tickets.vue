<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{ tickets: { id: string; subject: string; contact?: { first_name: string; last_name: string }; priority: string; status: string; created_at: string }[] }>()
const tickets = ref(props.tickets)
</script>

<template>
  <AppLayout>
    <Head title="Tickets" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Tickets</h1>
        <p class="text-gray-500">Support tickets across channels.</p>
      </div>
      <Card>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Ticket</th><th class="p-4">Contact</th><th class="p-4">Priority</th><th class="p-4">Status</th><th class="p-4">Created</th></tr></thead>
            <tbody>
              <tr v-for="t in tickets" :key="t.id" class="border-b hover:bg-gray-50">
                <td class="p-4 font-medium">{{ t.subject }}</td>
                <td class="p-4">{{ t.contact ? `${t.contact.first_name} ${t.contact.last_name}` : '-' }}</td>
                <td class="p-4"><Badge>{{ t.priority }}</Badge></td>
                <td class="p-4"><Badge :variant="t.status === 'open' ? 'destructive' : 'secondary'">{{ t.status }}</Badge></td>
                <td class="p-4">{{ t.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
