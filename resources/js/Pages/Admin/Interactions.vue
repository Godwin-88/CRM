<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Plus, MessageSquare, Phone, Mail, Inbox, Search, Filter, Check, X, FileText, Paperclip, Facebook, Linkedin, Instagram, Music2, User, MapPin, PanelBottom } from 'lucide-vue-next'

interface Interaction {
  id: string
  channel: { id: string; name: string; display_name: string; icon: string }
  contact?: { id: string; first_name: string; last_name: string; email: string; interactions?: Interaction[] }
  deal?: { id: string; title: string }
  ticket?: { id: string; subject: string }
  agent?: { id: string; name: string }
  direction: 'inbound' | 'outbound'
  subject: string
  body?: string
  created_at: string
  is_reviewed: boolean
  attachments?: Array<{ id: string; filename: string; mime_type: string; size_bytes: number }>
}

interface Contact {
  id: string
  first_name: string
  last_name: string
}

interface Agent {
  id: string
  name: string
}

interface Channel {
  id: string
  name: string
  display_name: string
  icon: string
}

const props = defineProps<{
  interactions: { data: Interaction[]; links: any; meta: any }
  channels: Channel[]
  contacts: Contact[]
  agents: Agent[]
  filters: Record<string, any>
  teamView: boolean
}>()

const interactions = ref(props.interactions.data)
const filters = ref({ ...props.filters })
const showFilters = ref(false)
const showDetail = ref(false)
const selectedInteraction = ref<Interaction | null>(null)
const showCreateDialog = ref(false)
const newInteraction = ref({ channel: '', direction: 'inbound', subject: '', body: '', contact_id: '', occurred_at: new Date().toISOString().slice(0, 10) })

const iconFor = (name: string) => {
  const icons: Record<string, any> = {
    call: Phone,
    phone: Phone,
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

const fetchInteractions = () => {
  router.get(route('admin.interactions.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
    only: ['interactions'],
    onSuccess: (page: any) => {
      interactions.value = page.props.interactions.data
    }
  })
}

const applyFilters = () => {
  fetchInteractions()
}

const clearFilters = () => {
  filters.value = { type: '', direction: '', channel: '', contact_id: '', agent_id: '', date_from: '', date_to: '', is_reviewed: '' }
  fetchInteractions()
}

const openDetail = (interaction: Interaction) => {
  selectedInteraction.value = interaction
  showDetail.value = true
}

const closeDetail = () => {
  showDetail.value = false
  selectedInteraction.value = null
}

const markReviewed = (id: string) => {
  router.patch(`/api/v1/interactions/${id}/mark-reviewed`, {}, {
    onSuccess: () => {
      const item = interactions.value.find((i: any) => i.id === id)
      if (item) item.is_reviewed = true
    }
  })
}

const submitInteraction = () => {
  router.post(route('admin.interactions.store'), newInteraction.value, {
    onSuccess: () => {
      showCreateDialog.value = false
      newInteraction.value = { channel: '', direction: 'inbound', subject: '', body: '', contact_id: '', occurred_at: new Date().toISOString().slice(0, 10) }
      fetchInteractions()
    }
  })
}

const handleWebSocket = () => {
  if (typeof (window as any).Echo !== 'undefined') {
    const props = (usePage().props as any)
    const user = props.auth.user as { id: string }
    (window as any).Echo.private(`interactions.${user.id}`)
      .listen('NewInteractionNotification', (e: { interaction: Interaction }) => {
        interactions.value.unshift(e.interaction)
        interactions.value = interactions.value.slice(0, 50)
      })
  }
}

onMounted(() => {
  handleWebSocket()
})

onUnmounted(() => {
  if (typeof (window as any).Echo !== 'undefined') {
    const props = (usePage().props as any)
    const user = props.auth.user as { id: string }
    (window as any).Echo.leave(`interactions.${user.id}`)
  }
})
</script>

<template>
  <AppLayout>
    <Head title="Unified Interaction Inbox" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Unified Interaction Inbox</h1>
          <p class="text-gray-500">View and manage all customer interactions across every channel.</p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="showFilters = !showFilters">
            <Filter class="h-4 w-4 mr-2" />
            Filters
          </Button>
          <Dialog v-model:open="showCreateDialog">
            <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Log Interaction</Button></DialogTrigger>
            <DialogContent class="max-w-md">
              <DialogHeader><DialogTitle>Log Interaction</DialogTitle></DialogHeader>
              <form @submit.prevent="submitInteraction" class="space-y-4">
                <Select v-model="newInteraction.channel">
                  <SelectTrigger><SelectValue placeholder="Select channel" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="channel in channels" :key="channel.id" :value="channel.id">
                      {{ channel.display_name || channel.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <select v-model="newInteraction.direction" class="w-full p-2 border rounded">
                  <option value="inbound">Inbound</option>
                  <option value="outbound">Outbound</option>
                </select>
                <Input v-model="newInteraction.subject" placeholder="Subject" required />
                <Textarea v-model="newInteraction.body" placeholder="Body / Notes" rows="3" />
                <Input v-model="newInteraction.contact_id" placeholder="Contact ID (optional)" />
                <Input v-model="newInteraction.occurred_at" type="date" required />
                <Button type="submit" class="w-full">Log</Button>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <!-- Filters Panel -->
      <Card v-if="showFilters" class="p-4">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
          <Input v-model="filters.type" placeholder="Type (call, email, chat...)" @keyup.enter="applyFilters" />
          <Select v-model="filters.direction">
            <SelectTrigger><SelectValue placeholder="Direction" /></SelectTrigger>
            <SelectContent>
              <SelectItem value="inbound">Inbound</SelectItem>
              <SelectItem value="outbound">Outbound</SelectItem>
            </SelectContent>
          </Select>
          <Select v-model="filters.channel">
            <SelectTrigger><SelectValue placeholder="Channel" /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="channel in channels" :key="channel.id" :value="channel.id">
                {{ channel.display_name || channel.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Input v-model="filters.date_from" type="date" placeholder="Date From" />
          <Input v-model="filters.date_to" type="date" placeholder="Date To" />
          <div class="flex gap-2">
            <Button size="sm" @click="applyFilters"><Search class="h-4 w-4" /></Button>
            <Button size="sm" variant="outline" @click="clearFilters"><X class="h-4 w-4" /></Button>
          </div>
        </div>
      </Card>

      <!-- Interactions Table -->
      <Card>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b bg-gray-50">
              <tr class="text-left text-gray-500">
                <th class="p-4">Channel</th>
                <th class="p-4">Contact</th>
                <th class="p-4">Subject</th>
                <th class="p-4">Agent</th>
                <th class="p-4">Direction</th>
                <th class="p-4">Date</th>
                <th class="p-4 w-32">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in interactions" :key="item.id" 
                  class="border-b hover:bg-gray-50 cursor-pointer" 
                  :class="{ 'bg-blue-50': !item.is_reviewed }"
                  @click="openDetail(item)">
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <component :is="iconFor(item.channel?.name)" class="h-4 w-4" />
                    <span class="font-medium">{{ item.channel?.display_name || item.channel?.name || 'Unknown' }}</span>
                    <Badge v-if="!item.is_reviewed" variant="default" class="ml-2">NEW</Badge>
                  </div>
                </td>
                <td class="p-4">
                  <span v-if="item.contact">{{ item.contact.first_name }} {{ item.contact.last_name }}</span>
                  <span v-else class="text-gray-400">Unknown</span>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <span class="truncate max-w-xs">{{ item.subject }}</span>
                    <Paperclip v-if="item.attachments?.length" class="h-3 w-3 text-gray-400" />
                  </div>
                </td>
                <td class="p-4">{{ item.agent?.name || '—' }}</td>
                <td class="p-4">
                  <Badge :variant="item.direction === 'inbound' ? 'secondary' : 'default'">
                    {{ item.direction }}
                  </Badge>
                </td>
                <td class="p-4 text-gray-500">{{ item.created_at }}</td>
                <td class="p-4">
                  <Button v-if="!item.is_reviewed" size="sm" variant="ghost" @click.stop="markReviewed(item.id)">
                    <Check class="h-4 w-4" />
                  </Button>
                </td>
              </tr>
              <tr v-if="!interactions.length">
                <td colspan="7" class="p-8 text-center text-gray-500">No interactions found.</td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div class="flex justify-center" v-if="props.interactions.links">
        <div class="flex gap-2">
          <Button v-for="link in props.interactions.links" :key="link.url" 
                  size="sm" 
                  :variant="link.active ? 'default' : 'outline'"
                  :disabled="!link.url"
                  @click="router.get(link.url)">
            <span v-html="link.label"></span>
          </Button>
        </div>
      </div>

      <!-- Detail Panel -->
      <Dialog v-model:open="showDetail">
        <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Interaction Details</DialogTitle>
          </DialogHeader>
          <div v-if="selectedInteraction" class="space-y-4">
            <div class="flex items-center gap-2">
              <component :is="iconFor(selectedInteraction.channel?.name)" class="h-5 w-5" />
              <span class="font-semibold">{{ selectedInteraction.channel?.display_name || selectedInteraction.channel?.name }}</span>
              <Badge :variant="selectedInteraction.direction === 'inbound' ? 'secondary' : 'default'">
                {{ selectedInteraction.direction }}
              </Badge>
              <Badge v-if="!selectedInteraction.is_reviewed" variant="default">UNREAD</Badge>
            </div>
            <div>
              <p class="text-sm text-gray-500">Subject</p>
              <p class="font-medium">{{ selectedInteraction.subject }}</p>
            </div>
            <div v-if="selectedInteraction.contact">
              <p class="text-sm text-gray-500">Contact</p>
              <p class="font-medium">{{ selectedInteraction.contact.first_name }} {{ selectedInteraction.contact.last_name }}</p>
            </div>
            <div v-if="selectedInteraction.body">
              <p class="text-sm text-gray-500">Body</p>
              <p class="whitespace-pre-wrap">{{ selectedInteraction.body }}</p>
            </div>
            <div v-if="selectedInteraction.deal">
              <p class="text-sm text-gray-500">Linked Deal</p>
              <p class="font-medium">{{ selectedInteraction.deal.title }}</p>
            </div>
            <div v-if="selectedInteraction.ticket">
              <p class="text-sm text-gray-500">Linked Ticket</p>
              <p class="font-medium">{{ selectedInteraction.ticket.subject }}</p>
            </div>
            <div v-if="selectedInteraction.attachments?.length">
              <p class="text-sm text-gray-500">Attachments</p>
              <ul class="list-disc list-inside text-sm">
                <li v-for="att in selectedInteraction.attachments" :key="att.id">
                  {{ att.filename }} ({{ (att.size_bytes / 1024).toFixed(1) }} KB)
                </li>
              </ul>
            </div>
<div>
               <p class="text-sm text-gray-500">Last 3 Interactions with this Contact</p>
               <div v-if="selectedInteraction.contact?.interactions" class="space-y-2 mt-2">
                 <div v-for="int in selectedInteraction.contact.interactions" :key="int.id" class="p-2 bg-gray-50 rounded text-sm">
                   <span class="font-medium">{{ int.subject }}</span>
                   <span class="text-gray-500 ml-2">{{ int.created_at }}</span>
                 </div>
               </div>
               <p v-else class="text-sm text-gray-400">No other interactions.</p>
             </div>
            <div class="flex justify-end gap-2 pt-4">
              <Button variant="outline" @click="closeDetail">Close</Button>
              <Button v-if="!selectedInteraction.is_reviewed" @click="markReviewed(selectedInteraction.id); closeDetail()">Mark Reviewed</Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>