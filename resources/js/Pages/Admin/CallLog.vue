<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
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
import { Phone, Plus } from 'lucide-vue-next'

const props = defineProps<{ contacts: { id: string; first_name: string; last_name: string }[]; deals: { id: string; title: string }[]; tickets: { id: string; subject: string }[] }>()
const contacts = ref(props.contacts)
const deals = ref(props.deals)
const tickets = ref(props.tickets)

const showLogDialog = ref(false)
const logContactId = ref('')
const logDirection = ref('outbound')
const logDate = ref(new Date().toISOString().slice(0, 10))
const logDuration = ref('')
const logOutcome = ref('')
const logNotes = ref('')
const logDealId = ref('')
const logTicketId = ref('')
const logSubmitting = ref(false)

const submitLog = async () => {
  logSubmitting.value = true
  router.post(
    '/api/v1/interactions/call',
    {
      contact_id: logContactId.value,
      direction: logDirection.value,
      call_date: logDate.value,
      duration_seconds: logDuration.value ? parseInt(logDuration.value) : 0,
      outcome: logOutcome.value || undefined,
      notes: logNotes.value || undefined,
      deal_id: logDealId.value && logDealId.value !== 'none' ? logDealId.value : undefined,
      ticket_id: logTicketId.value && logTicketId.value !== 'none' ? logTicketId.value : undefined,
    },
    {
      onFinish: () => {
        logSubmitting.value = false
        showLogDialog.value = false
      },
    }
  )
}
</script>

<template>
  <AppLayout>
    <Head title="Call Log" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Call Log</h1>
          <p class="text-gray-500">Log manual calls and view CTI recordings.</p>
        </div>
        <Dialog v-model:open="showLogDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Log Call</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Log Call</DialogTitle></DialogHeader>
<form @submit.prevent="submitLog" class="space-y-4">
              <div class="space-y-2">
                <Label>Contact</Label>
                <Select v-model="logContactId">
                  <SelectTrigger>
                    <SelectValue placeholder="Select contact" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="c in contacts" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Direction</Label>
                  <Select v-model="logDirection">
                    <SelectTrigger>
                      <SelectValue placeholder="Direction" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="inbound">Inbound</SelectItem>
                      <SelectItem value="outbound">Outbound</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Date</Label>
                  <Input v-model="logDate" type="date" required />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Duration (seconds)</Label>
                  <Input v-model="logDuration" type="number" min="0" placeholder="0" />
                </div>
                <div class="space-y-2">
                  <Label>Outcome</Label>
                  <Input v-model="logOutcome" placeholder="e.g. Interested, Voicemail" />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Related Deal (optional)</Label>
                  <Select v-model="logDealId">
                    <SelectTrigger>
                      <SelectValue placeholder="None" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">None</SelectItem>
                      <SelectItem v-for="d in deals" :key="d.id" :value="d.id">{{ d.title }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Related Ticket (optional)</Label>
                  <Select v-model="logTicketId">
                    <SelectTrigger>
                      <SelectValue placeholder="None" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">None</SelectItem>
                      <SelectItem v-for="t in tickets" :key="t.id" :value="t.id">{{ t.subject }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div class="space-y-2">
                <Label>Notes</Label>
                <Textarea v-model="logNotes" placeholder="Call notes..." rows="3" />
              </div>
              <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="showLogDialog = false">Cancel</Button>
                <Button type="submit" :disabled="logSubmitting">Log Call</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader><CardTitle>Recent Calls</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Direction</TableHead>
                <TableHead class="p-4">Date</TableHead>
                <TableHead class="p-4">Duration</TableHead>
                <TableHead class="p-4">Outcome</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="5" class="p-8 text-center text-gray-500">No calls logged yet.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
