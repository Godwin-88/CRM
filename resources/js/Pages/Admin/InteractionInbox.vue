<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Inbox, CheckCircle2, AlertTriangle } from 'lucide-vue-next'

const props = defineProps<{ interactions: any[] }>()
const items = ref(props.interactions)

const assign = (id: string) => {
  const contactId = prompt('Enter Contact ID to assign:')
  if (!contactId) return
  router.post(`/admin/interactions/unmatched/${id}/resolve`, { contact_id: contactId }, { onSuccess: () => router.reload() })
}
</script>

<template>
  <AppLayout>
    <Head title="Interaction Inbox" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Unmatched Inbox</h1>
        <p class="text-gray-500">Resolve interactions that have no linked contact.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card><CardContent class="pt-6 flex items-center gap-4"><Inbox class="h-8 w-8 text-blue-500" /><div><p class="text-sm text-gray-500">Unmatched</p><p class="text-2xl font-bold">{{ items.length }}</p></div></CardContent></Card>
      </div>
      <Card>
        <CardHeader><CardTitle>Items</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Channel</th><th class="p-4">Subject</th><th class="p-4">Date</th><th class="p-4"></th></tr></thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" class="border-b hover:bg-gray-50">
                <td class="p-4">{{ item.channel?.name ?? 'Unknown' }}</td>
                <td class="p-4">{{ item.subject }}</td>
                <td class="p-4 text-gray-500">{{ item.created_at }}</td>
                <td class="p-4"><Button size="sm" @click="assign(item.id)">Assign</Button></td>
              </tr>
              <tr v-if="!items.length"><td colspan="4" class="p-8 text-center text-gray-500">No unmatched items.</td></tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
