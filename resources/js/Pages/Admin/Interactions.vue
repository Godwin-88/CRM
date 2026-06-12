<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Plus, MessageSquare, Phone, Mail, Inbox } from 'lucide-vue-next'

interface Interaction {
  id: string
  channel: { id: string; name: string }
  contact?: { first_name: string; last_name: string }
  direction: 'inbound' | 'outbound'
  subject: string
  content?: string
  created_at: string
}
const props = defineProps<{ interactions: Interaction[] }>()
const interactions = ref(props.interactions)
const showCreateDialog = ref(false)
const newInteraction = ref({ channel: '', direction: 'inbound', subject: '', content: '', contact_id: '', occurred_at: new Date().toISOString().slice(0, 10) })

const submitInteraction = async () => {
  router.post('/admin/interactions', newInteraction.value, {
    onSuccess: () => { showCreateDialog.value = false },
  })
}

const iconFor = (name: string) => {
  if (name.toLowerCase().includes('phone')) return Phone
  if (name.toLowerCase().includes('chat') || name.toLowerCase().includes('live')) return MessageSquare
  if (name.toLowerCase().includes('email')) return Mail
  return Inbox
}
</script>

<template>
  <AppLayout>
    <Head title="Interactions" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Interactions</h1>
          <p class="text-gray-500">Unified view of all customer communications.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Log Interaction</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Log Interaction</DialogTitle></DialogHeader>
            <form @submit.prevent="submitInteraction" class="space-y-4">
              <Input v-model="newInteraction.channel" placeholder="Channel ID" required />
              <select v-model="newInteraction.direction" class="w-full p-2 border rounded">
                <option value="inbound">Inbound</option><option value="outbound">Outbound</option>
              </select>
              <Input v-model="newInteraction.subject" placeholder="Subject" required />
              <Textarea v-model="newInteraction.content" placeholder="Content" rows="3" />
              <Input v-model="newInteraction.contact_id" placeholder="Contact ID (optional)" />
              <Input v-model="newInteraction.occurred_at" type="date" required />
              <Button type="submit" class="w-full">Log</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <Card>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Channel</th><th class="p-4">Contact</th><th class="p-4">Subject</th><th class="p-4">Direction</th><th class="p-4">Date</th></tr></thead>
            <tbody>
              <tr v-for="item in interactions" :key="item.id" class="border-b hover:bg-gray-50">
                <td class="p-4"><div class="flex items-center gap-2"><component :is="iconFor(item.channel.name)" class="h-4 w-4" />{{ item.channel.name }}</div></td>
                <td class="p-4">{{ item.contact ? `${item.contact.first_name} ${item.contact.last_name}` : 'Unknown' }}</td>
                <td class="p-4">{{ item.subject }}</td>
                <td class="p-4"><Badge :variant="item.direction === 'inbound' ? 'secondary' : 'default'">{{ item.direction }}</Badge></td>
                <td class="p-4 text-gray-500">{{ item.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
