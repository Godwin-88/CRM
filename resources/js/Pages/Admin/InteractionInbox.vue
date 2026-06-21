<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, MessageSquare, Phone, Mail, Inbox, Paperclip, Check, Facebook, Linkedin, Instagram, Music2, User, MapPin, PanelBottom } from 'lucide-vue-next'

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
  interactions: Interaction[] | { data: Interaction[]; links: any; meta: any }
  channels: Channel[]
  isTab?: boolean
}>()

const interactions = ref(Array.isArray(props.interactions) ? props.interactions : (props.interactions?.data || []))
const channels = ref(props.channels || [])

const iconFor = (name: string) => {
  const icons: Record<string, any> = {
    call: Phone,
    email: Mail,
    chat: MessageSquare,
    sms: MessageSquare,
    whatsapp: MessageSquare,
    facebook: Facebook,
    linkedin: Linkedin,
    instagram: Instagram,
    tiktok: Music2,
    in_person: User,
    field_visit: MapPin,
    kiosk: PanelBottom,
  }
  return icons[name?.toLowerCase()] || Inbox
}

const markReviewed = (id: string) => {
  router.patch(`/api/v1/interactions/${id}/mark-reviewed`, {}, {
    onSuccess: () => {
      const item = interactions.value.find((i: any) => i.id === id)
      if (item) item.is_reviewed = true
    }
  })
}
</script>

<template>
  <div>
    <Head v-if="!isTab" title="Unified Interaction Inbox" />
    <Card>
      <CardHeader v-if="!isTab">
        <CardTitle>Unified Interaction Inbox</CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <table class="w-full text-sm">
          <thead class="border-b bg-gray-50">
            <tr class="text-left text-gray-500">
              <th class="p-3">Channel</th>
              <th class="p-3">Contact</th>
              <th class="p-3">Subject</th>
              <th class="p-3">Direction</th>
              <th class="p-3">Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in interactions" :key="item.id" class="border-b hover:bg-gray-50">
              <td class="p-3">
                <div class="flex items-center gap-2">
                  <component :is="iconFor(item.channel?.name)" class="h-4 w-4" />
                  <span class="font-medium">{{ item.channel?.display_name || item.channel?.name || 'Unknown' }}</span>
                  <Badge v-if="!item.is_reviewed" variant="default" class="ml-2">NEW</Badge>
                </div>
              </td>
              <td class="p-3">
                <span v-if="item.contact">{{ item.contact.first_name }} {{ item.contact.last_name }}</span>
                <span v-else class="text-gray-400">Unknown</span>
              </td>
              <td class="p-3">
                <div class="flex items-center gap-2">
                  <span class="truncate max-w-xs">{{ item.subject }}</span>
                  <Paperclip v-if="item.attachments?.length" class="h-3 w-3 text-gray-400" />
                </div>
              </td>
              <td class="p-3">
                <Badge :variant="item.direction === 'inbound' ? 'secondary' : 'default'">
                  {{ item.direction }}
                </Badge>
              </td>
              <td class="p-3 text-gray-500">{{ item.created_at }}</td>
            </tr>
            <tr v-if="!interactions.length">
              <td colspan="5" class="p-8 text-center text-gray-500">No interactions found.</td>
            </tr>
          </tbody>
        </table>
      </CardContent>
    </Card>
  </div>
</template>