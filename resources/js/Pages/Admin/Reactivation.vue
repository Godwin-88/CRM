<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Plus, Clock, Mail, UserPlus, TrendingUp, Play } from 'lucide-vue-next'

const props = defineProps<{
  configs: any[]
  stats: { queued: number; sent: number; responded: number; reactivated: number }
}>()

const showCreateOpen = ref(false)
const selectedConfig = ref<any | null>(null)
const isRunning = ref(false)

const newCampaign = ref({
  name: '',
  description: '',
  segment_id: '',
  template_id: '',
  channels: [] as string[],
  max_retries: 3,
  delay_hours: 24,
})

const createCampaign = async () => {
   router.post('/admin/reactivation', newCampaign.value, {
     onSuccess: () => {
       showCreateOpen.value = false
       newCampaign.value = {
         name: '',
         description: '',
         segment_id: '',
         template_id: '',
         channels: [],
         max_retries: 3,
         delay_hours: 24,
       }
     },
   })
 }
 
 const runCampaign = async (id: number) => {
   isRunning.value = true
   await router.post(`/admin/reactivation/${id}/run`)
   isRunning.value = false
 }

const channelColor = (channel: string) => {
  const colors: Record<string, string> = {
    email: 'default',
    sms: 'secondary',
    push: 'outline',
    in_app: 'destructive',
  }
  return colors[channel] || 'outline'
}
</script>

<template>
  <AppLayout>
    <Head title="Reactivation" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Reactivation</h1>
          <p class="text-muted-foreground">Win back inactive contacts.</p>
        </div>
        <Dialog v-model:open="showCreateOpen">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Campaign</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Reactivation Campaign</DialogTitle></DialogHeader>
            <form @submit.prevent="createCampaign" class="space-y-4 py-4">
              <div class="space-y-2"><Label>Name</Label><Input v-model="newCampaign.name" placeholder="e.g. Win-Back Q4" /></div>
              <div class="space-y-2"><Label>Segment</Label>
                <Select v-model="newCampaign.segment_id">
                  <SelectTrigger><SelectValue placeholder="Select segment" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="inactive_30">Inactive 30 days</SelectItem>
                    <SelectItem value="inactive_90">Inactive 90 days</SelectItem>
                    <SelectItem value="churned_365">Churned 365 days</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2"><Label>Template</Label>
                <Select v-model="newCampaign.template_id">
                  <SelectTrigger><SelectValue placeholder="Select template" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="win-back-email">Win-Back Email</SelectItem>
                    <SelectItem value="discount-sms">Discount SMS</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2"><Label>Channels</Label>
                <Select v-model="newCampaign.channels" multiple>
                  <SelectTrigger><SelectValue placeholder="Select channels" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="email">Email</SelectItem>
                    <SelectItem value="sms">SMS</SelectItem>
                    <SelectItem value="push">Push</SelectItem>
                    <SelectItem value="in_app">In App</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2"><Label>Max Retries</Label><Input type="number" v-model.number="newCampaign.max_retries" min="1" max="10" /></div>
                <div class="space-y-2"><Label>Delay (hours)</Label><Input type="number" v-model.number="newCampaign.delay_hours" min="1" /></div>
              </div>
              <Button type="submit" class="w-full">Create Campaign</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card><CardContent class="pt-6 flex items-center gap-4"><Clock class="h-8 w-8 text-amber-500" /><div><p class="text-sm text-gray-500">Queued</p><p class="text-2xl font-bold">{{ stats?.queued ?? 0 }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><Mail class="h-8 w-8 text-blue-500" /><div><p class="text-sm text-gray-500">Sent</p><p class="text-2xl font-bold">{{ stats?.sent ?? 0 }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><UserPlus class="h-8 w-8 text-emerald-500" /><div><p class="text-sm text-gray-500">Responded</p><p class="text-2xl font-bold">{{ stats?.responded ?? 0 }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><TrendingUp class="h-8 w-8 text-purple-500" /><div><p class="text-sm text-gray-500">Reactivated</p><p class="text-2xl font-bold">{{ stats?.reactivated ?? 0 }}</p></div></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Campaigns</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Name</TableHead>
                <TableHead class="p-4">Status</TableHead>
                <TableHead class="p-4">Queue</TableHead>
                <TableHead class="p-4"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="config in configs" :key="config.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-4 font-medium">{{ config.name }}</TableCell>
                <TableCell class="p-4"><Badge :variant="config.is_active ? 'default' : 'outline'">{{ config.is_active ? 'Active' : 'Inactive' }}</Badge></TableCell>
                <TableCell class="p-4">{{ config.contacts_count }}</TableCell>
                <TableCell class="p-4"><Button size="sm" @click="runCampaign(config.id)" :disabled="isRunning"><Play class="h-4 w-4 mr-2" />Run</Button></TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
