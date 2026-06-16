<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { MessageSquare, Plus } from 'lucide-vue-next'

const props = defineProps<{ contacts: { id: string; first_name: string; last_name: string; phone?: string }[] }>()
const contacts = ref(props.contacts)

const showCompose = ref(false)
const contactId = ref('')
const message = ref('')
const sending = ref(false)
const segments = ref(1)
const charCount = ref(0)

const updateSegmentCount = () => {
  charCount.value = message.value.length
  segments.value = Math.ceil(charCount.value / 160) || 1
}

const submitSms = async () => {
  sending.value = true
  router.post(
    '/api/v1/sms/send',
    { contact_id: contactId.value, message: message.value },
    {
      onFinish: () => {
        sending.value = false
        showCompose.value = false
        contactId.value = ''
        message.value = ''
        charCount.value = 0
      },
    }
  )
}
</script>

<template>
  <AppLayout>
    <Head title="SMS Composer" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">SMS</h1>
          <p class="text-gray-500">Send and view SMS interactions.</p>
        </div>
        <Dialog v-model:open="showCompose">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New SMS</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Compose SMS</DialogTitle></DialogHeader>
            <form @submit.prevent="submitSms" class="space-y-4">
              <div class="space-y-2">
                <Label>Contact</Label>
                <Select v-model="contactId">
                  <SelectTrigger>
                    <SelectValue placeholder="Select contact" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">Select contact</SelectItem>
                    <SelectItem v-for="c in contacts" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }} {{ c.phone ? `(${c.phone})` : '' }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2">
                <Label>Message</Label>
                <Textarea v-model="message" placeholder="Type your message..." rows="4" @input="updateSegmentCount" />
                <p class="text-xs text-gray-500">{{ charCount }} characters · {{ segments }} segment{{ segments > 1 ? 's' : '' }}</p>
              </div>
              <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="showCompose = false">Cancel</Button>
                <Button type="submit" :disabled="sending || !contactId">Send</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader><CardTitle>SMS Interactions</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Message</TableHead>
                <TableHead class="p-4">Direction</TableHead>
                <TableHead class="p-4">Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="4" class="p-8 text-center text-gray-500">Send an SMS to get started.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
