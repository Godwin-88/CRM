<script setup lang="ts">
import { ref, defineAsyncComponent } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Inbox, MessageSquare } from 'lucide-vue-next'

const InteractionInbox = defineAsyncComponent(() => import('@/Pages/Admin/InteractionInbox.vue'))
const ChatInbox = defineAsyncComponent(() => import('@/Pages/Admin/ChatInbox.vue'))

interface Interaction {
  id: string
  channel: { id: string; name: string; display_name: string }
  contact?: { id: string; first_name: string; last_name: string }
  agent?: { id: string; name: string }
  direction: 'inbound' | 'outbound'
  subject: string
  body?: string
  created_at: string
  is_reviewed: boolean
  attachments?: Array<{ id: string; filename: string; size_bytes: number }>
}

interface Channel {
  id: string
  name: string
  display_name: string
}

const props = defineProps<{
  interactions: Interaction[]
  channels: Channel[]
}>()

const interactions = ref(props.interactions || [])
const channels = ref(props.channels || [])
const activeTab = ref('inbox')
const chatSessions = ref<any[]>([])
</script>

<template>
  <AppLayout>
    <Head title="OmniChannel Workspace" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Workspace</h1>
        <p class="text-gray-500">Manage customer communications across all channels.</p>
      </div>

      <Tabs v-model:model-value="activeTab" default-value="inbox">
        <TabsList class="grid w-full grid-cols-2">
          <TabsTrigger value="inbox" class="flex items-center gap-2">
            <Inbox class="h-4 w-4" />
            Unified Inbox
          </TabsTrigger>
          <TabsTrigger value="chat" class="flex items-center gap-2">
            <MessageSquare class="h-4 w-4" />
            Live Chat Queue
          </TabsTrigger>
        </TabsList>
        <TabsContent value="inbox" class="mt-4">
          <InteractionInbox :interactions="interactions" :channels="channels" :is-tab="true" />
        </TabsContent>
        <TabsContent value="chat" class="mt-4">
          <ChatInbox :sessions="chatSessions" :is-tab="true" />
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>