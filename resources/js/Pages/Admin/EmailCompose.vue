<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Send, Mail } from 'lucide-vue-next'

const props = defineProps<{
  contacts: { id: string; first_name: string; last_name: string; email: string }[]
  emailTemplates: { id: string; name: string; subject: string }[]
  deals: { id: string; title: string }[]
  tickets: { id: string; subject: string }[]
}>()

const showCompose = ref(false)
const templateId = ref('')
const contactId = ref('')
const dealId = ref('')
const ticketId = ref('')
const subject = ref('')
const body = ref('')
const sending = ref(false)

const submitEmail = async () => {
  sending.value = true
  router.post(
    '/api/v1/email/send',
    {
      template_id: templateId.value,
      contact_id: contactId.value,
      deal_id: dealId.value || undefined,
      ticket_id: ticketId.value || undefined,
      variables: { body: body.value },
    },
    {
      onFinish: () => {
        sending.value = false
        showCompose.value = false
        resetForm()
      },
    }
  )
}

const resetForm = () => {
  templateId.value = ''
  contactId.value = ''
  dealId.value = ''
  ticketId.value = ''
  subject.value = ''
  body.value = ''
}

const selectContact = (id: string) => {
  contactId.value = id
}

const filteredDeals = computed(() => {
  return props.deals || []
})

const filteredTickets = computed(() => {
  return props.tickets || []
})
</script>

<template>
  <AppLayout>
    <Head title="Email Compose" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Email</h1>
          <p class="text-gray-500">Compose and send emails to contacts.</p>
        </div>
        <Button @click="showCompose = true"><Send class="h-4 w-4 mr-2" />Compose</Button>
      </div>

      <Dialog v-model:open="showCompose">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Compose Email</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="submitEmail" class="space-y-4">
            <div class="space-y-2">
              <Label>Template</Label>
              <Select v-model="templateId">
                <SelectTrigger>
                  <SelectValue placeholder="Select template" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="t in emailTemplates" :key="t.id" :value="String(t.id)">{{ t.name }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Contact</Label>
              <Select v-model="contactId">
                <SelectTrigger>
                  <SelectValue placeholder="Select contact" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="c in contacts" :key="c.id" :value="String(c.id)">{{ c.first_name }} {{ c.last_name }} &lt;{{ c.email }}&gt;</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Related Deal (optional)</Label>
                <Select v-model="dealId">
                  <SelectTrigger>
                    <SelectValue placeholder="Select deal" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="d in filteredDeals" :key="d.id" :value="String(d.id)">{{ d.title }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2">
                <Label>Related Ticket (optional)</Label>
                <Select v-model="ticketId">
                  <SelectTrigger>
                    <SelectValue placeholder="Select ticket" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="t in filteredTickets" :key="t.id" :value="String(t.id)">{{ t.subject }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div class="space-y-2">
              <Label>Message Body</Label>
              <Textarea v-model="body" placeholder="Compose your message..." rows="6" />
            </div>
            <div class="flex justify-end gap-2">
              <Button type="button" variant="outline" @click="showCompose = false">Cancel</Button>
              <Button type="submit" :disabled="sending"><Send class="h-4 w-4 mr-2" />{{ sending ? 'Sending...' : 'Send' }}</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <Card>
        <CardHeader><CardTitle>Email Interactions</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Subject</TableHead>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Direction</TableHead>
                <TableHead class="p-4">Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="4" class="p-8 text-center text-gray-500">Compose an email to get started.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
