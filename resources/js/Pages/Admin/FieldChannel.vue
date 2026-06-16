<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
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
import { MapPin, RefreshCw, CheckCircle2, AlertTriangle } from 'lucide-vue-next'

defineProps<{
  snapshot: { last_sync: string; contacts: any[]; accounts: any[]; activities: any[] }
  pendingCount: number
}>()

const showFieldDialog = ref(false)
const fieldContactId = ref('')
const fieldAccountId = ref('')
const fieldNotes = ref('')
const fieldDate = ref(new Date().toISOString().slice(0, 10))
const fieldLat = ref('')
const fieldLng = ref('')
const submitting = ref(false)

const queueVisit = () => {
  submitting.value = true
  fetch('/api/v1/field-channel/queue', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (window as any).csrfToken || '' },
    body: JSON.stringify({
      contact_id: fieldContactId.value,
      account_id: fieldAccountId.value || undefined,
      notes: fieldNotes.value,
      visit_date: fieldDate.value,
      latitude: fieldLat.value ? parseFloat(fieldLat.value) : undefined,
      longitude: fieldLng.value ? parseFloat(fieldLng.value) : undefined,
    }),
  })
    .then((r) => r.json())
    .then(() => {
      showFieldDialog.value = false
      resetFieldForm()
    })
    .finally(() => {
      submitting.value = false
    })
}

const syncPending = async () => {
  await fetch('/api/v1/field-channel/sync', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (window as any).csrfToken || '' },
  })
  window.location.reload()
}

const resetFieldForm = () => {
  fieldContactId.value = ''
  fieldAccountId.value = ''
  fieldNotes.value = ''
  fieldDate.value = new Date().toISOString().slice(0, 10)
  fieldLat.value = ''
  fieldLng.value = ''
}
</script>

<template>
  <AppLayout>
    <Head title="Field Channel" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Field Agent Mobile</h1>
          <p class="text-gray-500">Offline-first field visits and pending sync.</p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="syncPending"><RefreshCw class="h-4 w-4 mr-2" />Sync Pending {{ pendingCount }}</Button>
          <Dialog v-model:open="showFieldDialog">
            <DialogTrigger as-child><Button><MapPin class="h-4 w-4 mr-2" />Log Field Visit</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Log Field Visit</DialogTitle></DialogHeader>
              <form @submit.prevent="queueVisit" class="space-y-4">
                <div class="space-y-2">
                  <Label>Contact</Label>
                  <Select v-model="fieldContactId">
                    <SelectTrigger>
                      <SelectValue placeholder="Select contact" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">None</SelectItem>
                      <SelectItem v-for="c in snapshot.contacts" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Account (optional)</Label>
                  <Select v-model="fieldAccountId">
                    <SelectTrigger>
                      <SelectValue placeholder="Select account" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">None</SelectItem>
                      <SelectItem v-for="a in snapshot.accounts" :key="a.id" :value="a.id">{{ a.name }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Visit Date</Label>
                  <Input v-model="fieldDate" type="date" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <Label>Latitude</Label>
                    <Input v-model="fieldLat" type="number" step="any" placeholder="Optional" />
                  </div>
                  <div class="space-y-2">
                    <Label>Longitude</Label>
                    <Input v-model="fieldLng" type="number" step="any" placeholder="Optional" />
                  </div>
                </div>
                <div class="space-y-2">
                  <Label>Notes</Label>
                  <Textarea v-model="fieldNotes" placeholder="Field visit notes..." rows="3" />
                </div>
                <div class="flex justify-end gap-2">
                  <Button type="button" variant="outline" @click="showFieldDialog = false">Cancel</Button>
                  <Button type="submit" :disabled="submitting">Save to Queue</Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card><CardContent class="pt-6 flex items-center gap-4">
          <AlertTriangle class="h-8 w-8 text-amber-500" />
          <div>
            <p class="text-sm text-gray-500">Pending Sync</p>
            <p class="text-2xl font-bold">{{ pendingCount }}</p>
          </div>
        </CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4">
          <CheckCircle2 class="h-8 w-8 text-emerald-500" />
          <div>
            <p class="text-sm text-gray-500">Last Sync</p>
            <p class="text-2xl font-bold">{{ new Date(snapshot.last_sync).toLocaleString() }}</p>
          </div>
        </CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Cached Contacts & Accounts</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Account</TableHead>
                <TableHead class="p-4">Phone</TableHead>
                <TableHead class="p-4">Updated</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="c in snapshot.contacts" :key="c.id" class="border-b">
                <TableCell class="p-4">{{ c.first_name }} {{ c.last_name }}</TableCell>
                <TableCell class="p-4">{{ c.account?.name ?? '-' }}</TableCell>
                <TableCell class="p-4">{{ c.phone ?? '-' }}</TableCell>
                <TableCell class="p-4">{{ new Date(c.updated_at).toLocaleDateString() }}</TableCell>
              </TableRow>
              <TableRow v-if="!snapshot.contacts.length">
                <TableCell colspan="4" class="p-8 text-center text-gray-500">No contacts cached.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
