<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Plus, ClipboardList, Send, Calendar } from 'lucide-vue-next'
import { Textarea } from '@/components/ui/textarea'

interface Survey { id: string; name: string; type: string; status: string; channel?: string; trigger_event?: string; sent_at?: string; closed_at?: string }
const props = defineProps<{ surveys: Survey[]; segments: { id: string; name: string }[]; contacts: { id: string; first_name: string; last_name: string; email: string }[] }>()
const surveys = ref(props.surveys)
const showCreateDialog = ref(false)

const form = useForm({
  name: '',
  type: 'NPS',
  status: 'draft',
  segment_id: '',
  question_text: '',
  follow_up_question: '',
  channel: '',
  contact_ids: [] as string[],
  trigger_event: '',
  sent_at: '',
  closed_at: '',
})

const openCreate = () => {
  form.reset()
  showCreateDialog.value = true
}

const submit = () => {
  const payload = {
    ...form.data(),
    type: String(form.type).toLowerCase(),
    status: String(form.status).toLowerCase(),
  }
  router.post('/admin/surveys', (payload as any), {
    onSuccess: () => {
      showCreateDialog.value = false
    },
  })
}

const typeColors: Record<string, string> = {
  NPS: 'bg-indigo-100 text-indigo-800',
  CSAT: 'bg-emerald-100 text-emerald-800',
  CES: 'bg-amber-100 text-amber-800',
  custom: 'bg-gray-100 text-gray-800',
}
</script>

<template>
  <AppLayout>
    <Head title="Surveys" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Surveys</h1>
          <p class="text-gray-500">Create and manage NPS, CSAT, and CES surveys with full targeting and lifecycle control.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button @click="openCreate"><Plus class="h-4 w-4 mr-2" />New Survey</Button></DialogTrigger>
          <DialogContent class="sm:max-w-2xl">
            <DialogHeader><DialogTitle>Create Survey</DialogTitle></DialogHeader>
            <form @submit.prevent="submit" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Survey Name *</Label>
                  <Input v-model="form.name" placeholder="e.g. Post-Purchase CSAT" required />
                  <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                </div>
                <div class="space-y-2">
                  <Label>Type *</Label>
                  <Select v-model="form.type">
                    <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="NPS">NPS</SelectItem>
                      <SelectItem value="CSAT">CSAT</SelectItem>
                      <SelectItem value="CES">CES</SelectItem>
                      <SelectItem value="custom">Custom</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Status *</Label>
                  <Select v-model="form.status">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="draft">Draft</SelectItem>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Segment</Label>
                  <Select v-model="form.segment_id">
                    <SelectTrigger><SelectValue placeholder="All contacts" /></SelectTrigger>
<SelectContent>
                       <SelectItem value="all">All contacts</SelectItem>
                       <SelectItem v-for="seg in segments" :key="seg.id" :value="seg.id">{{ seg.name }}</SelectItem>
                     </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="space-y-2">
                <Label>Primary Question *</Label>
                <Textarea v-model="form.question_text" placeholder="How likely are you to recommend us to a friend or colleague?" rows="3" required />
                <span v-if="form.errors.question_text" class="text-sm text-red-500">{{ form.errors.question_text }}</span>
              </div>

              <div class="space-y-2">
                <Label>Follow-Up Question</Label>
                <Textarea v-model="form.follow_up_question" placeholder="What is the main reason for your score?" rows="2" />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Channel</Label>
                  <Select v-model="form.channel">
                    <SelectTrigger><SelectValue placeholder="Select channel" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="email">Email</SelectItem>
                      <SelectItem value="sms">SMS</SelectItem>
                      <SelectItem value="in_app">In App</SelectItem>
                      <SelectItem value="web">Web</SelectItem>
                      <SelectItem value="chat">Chat</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Trigger Event</Label>
                  <Input v-model="form.trigger_event" placeholder="e.g. deal_won, ticket_closed, onboarding_complete" />
                </div>
              </div>

              <div class="space-y-2">
                <Label>Target Contacts</Label>
                <p class="text-xs text-gray-500">Leave empty to use segment targeting only.</p>
                <div class="border rounded p-2 max-h-40 overflow-y-auto space-y-1">
                  <label v-for="contact in contacts" :key="contact.id" class="flex items-center gap-2 text-sm">
                    <input type="checkbox" :value="contact.id" v-model="form.contact_ids" class="rounded" />
                    <span>{{ contact.first_name }} {{ contact.last_name }} ({{ contact.email }})</span>
                  </label>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Send Date</Label>
                  <Input v-model="form.sent_at" type="date" />
                </div>
                <div class="space-y-2">
                  <Label>Close Date</Label>
                  <Input v-model="form.closed_at" type="date" />
                </div>
              </div>

              <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="showCreateDialog = false">Cancel</Button>
                <Button type="submit" :disabled="form.processing">Create Survey</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="survey in surveys" :key="survey.id" class="hover:shadow-md transition-shadow">
          <CardContent class="pt-6">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <ClipboardList class="h-5 w-5 text-blue-500" />
                <h3 class="font-semibold text-lg">{{ survey.name }}</h3>
              </div>
              <Badge :class="typeColors[survey.type] || 'bg-gray-100 text-gray-800'">{{ survey.type }}</Badge>
            </div>
            <p class="text-sm text-gray-500 mb-1">Status: <Badge :variant="survey.status === 'active' ? 'default' : 'secondary'">{{ survey.status }}</Badge></p>
            <p class="text-sm text-gray-500">Channel: {{ survey.channel || 'Not set' }}</p>
            <p class="text-sm text-gray-500">Trigger: {{ survey.trigger_event || 'None' }}</p>
          </CardContent>
        </Card>
        <Card v-if="!surveys.length">
          <CardContent class="py-12 text-center text-gray-500 italic">No surveys configured yet.</CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
