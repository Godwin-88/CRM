<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { MessageSquare, User } from 'lucide-vue-next'

const props = defineProps<{ sessions: { id: string; status: string; contact?: { first_name: string; last_name: string }; assignedAgent?: { name: string } }[]; isTab?: boolean }>()

const sessions = ref<any[]>([])

onMounted(async () => {
  try {
    const res = await fetch('/api/v1/chat/waiting', { headers: { 'Accept': 'application/json' } })
    if (res.ok) {
      sessions.value = await res.json()
    }
  } catch (e) {
    console.error('Failed to load chat sessions', e)
  }
})

const acceptSession = async (sessionId: string) => {
  await fetch(`/api/v1/chat/${sessionId}/accept`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (window as any).csrfToken || '', 'Accept': 'application/json' },
  })
  sessions.value = sessions.value.filter(s => s.id !== sessionId)
}

const statusBadge = (status: string) => {
  switch (status) {
    case 'waiting': return 'destructive'
    case 'active': return 'default'
    case 'closed': return 'secondary'
    default: return 'outline'
  }
}
</script>

<template>
  <component :is="isTab ? 'div' : AppLayout">
    <Head v-if="!isTab" title="Chat Inbox" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div v-if="!isTab">
        <h1 class="text-3xl font-bold text-gray-900">Chat Inbox</h1>
        <p class="text-gray-500">Live chat sessions awaiting agent response.</p>
      </div>

      <div class="grid gap-4">
        <Card v-for="session in sessions" :key="session.id">
          <CardContent class="pt-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <MessageSquare class="h-5 w-5 text-purple-500" />
                <div>
                  <p class="font-medium">
                    {{ session.contact ? `${session.contact.first_name} ${session.contact.last_name}` : 'Unknown Visitor' }}
                  </p>
                  <p class="text-xs text-gray-500">Session #{{ session.id.slice(0, 8) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Badge :variant="statusBadge(session.status)">{{ session.status }}</Badge>
                <Button v-if="session.status === 'waiting'" size="sm" @click="acceptSession(session.id)">Accept</Button>
                <Button v-if="session.status === 'active'" size="sm" variant="outline" disabled>In Progress</Button>
              </div>
            </div>
          </CardContent>
        </Card>
        <div v-if="!sessions.length" class="text-sm text-gray-500 text-center py-8">No waiting chat sessions.</div>
      </div>
    </div>
  </component>
</template>
