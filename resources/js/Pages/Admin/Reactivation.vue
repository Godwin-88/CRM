<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Plus, UserPlus, Mail, Clock, TrendingUp, Settings, Filter, Zap } from 'lucide-vue-next'

interface Config {
  id: string
  name: string
  description: string
  criteria: any
  actions: any
  is_active: boolean
  contacts_count: number
}
interface Contact {
  id: string
  contact: { first_name: string; last_name: string; email: string }
  status: string
  config: { name: string }
}
const props = defineProps<{ 
  configs: Config[]; 
  contacts?: Contact[]; 
  stats?: Record<string, number>;
  segments: {id: string, name: string}[];
  tiers: {id: string, name: string}[];
  sequences: {id: string, name: string}[];
}>()
const configs = ref(props.configs)
const contacts = ref(props.contacts ?? [])
const stats = ref(props.stats ?? {})
const showCreateDialog = ref(false)

const newConfig = ref({ 
  name: '', 
  description: '', 
  criteria: {
    min_inactivity_days: 90,
    segment_id: '',
    loyalty_tier_id: ''
  }, 
  actions: {
    drip_sequence_id: '',
    dormant_tag: 'inactive-dormant'
  }, 
  is_active: true 
})

const submitConfig = async () => {
  router.post('/admin/reactivation', newConfig.value, {
    onSuccess: () => { 
      showCreateDialog.value = false 
      // Reset form
      newConfig.value = { 
        name: '', description: '', 
        criteria: { min_inactivity_days: 90, segment_id: '', loyalty_tier_id: '' },
        actions: { drip_sequence_id: '', dormant_tag: 'inactive-dormant' },
        is_active: true 
      }
    },
  })
}

const runCampaign = async (id: string) => {
  if (!confirm('This will evaluate all contacts and queue those matching the criteria. Continue?')) return
  router.post(`/admin/reactivation/${id}/run`, {}, {
    onSuccess: () => router.reload(),
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Reactivation Center" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Reactivation Center</h1>
          <p class="text-gray-500">Intelligently re-engage dormant customers based on behavior and value.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button size="lg" class="shadow-lg"><Plus class="h-5 w-5 mr-2" />New Strategy</Button></DialogTrigger>
          <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
              <DialogTitle class="text-xl">Create Reactivation Strategy</DialogTitle>
              <p class="text-sm text-gray-500">Define who to target and what actions to take when they become inactive.</p>
            </DialogHeader>
            <form @submit.prevent="submitConfig" class="space-y-6 py-4">
              <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2 col-span-2">
                    <Label for="name">Campaign Name</Label>
                    <Input id="name" v-model="newConfig.name" placeholder="e.g. 90-Day High Value Win-back" required />
                  </div>
                  <div class="space-y-2 col-span-2">
                    <Label for="desc">Internal Description</Label>
                    <Textarea id="desc" v-model="newConfig.description" placeholder="Goal of this campaign..." />
                  </div>
                </div>

                <div class="border-t pt-4">
                  <h3 class="text-sm font-semibold mb-3 flex items-center gap-2 text-blue-700">
                    <Filter class="h-4 w-4" /> Targeting Criteria
                  </h3>
                  <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                      <Label>Inactivity Threshold</Label>
                      <div class="flex items-center gap-2">
                        <Input type="number" v-model="newConfig.criteria.min_inactivity_days" class="w-24" />
                        <span class="text-sm text-gray-500">days inactive</span>
                      </div>
                    </div>
                    <div class="space-y-2">
                      <Label>Loyalty Tier</Label>
                      <Select v-model="newConfig.criteria.loyalty_tier_id">
                        <SelectTrigger><SelectValue placeholder="All Tiers" /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="">All Tiers</SelectItem>
                          <SelectItem v-for="tier in tiers" :key="tier.id" :value="tier.id">{{ tier.name }}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div class="space-y-2 col-span-2">
                      <Label>Target Segment (Optional)</Label>
                      <Select v-model="newConfig.criteria.segment_id">
                        <SelectTrigger><SelectValue placeholder="Select a specific segment..." /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="">All Contacts</SelectItem>
                          <SelectItem v-for="seg in segments" :key="seg.id" :value="seg.id">{{ seg.name }}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                </div>

                <div class="border-t pt-4">
                  <h3 class="text-sm font-semibold mb-3 flex items-center gap-2 text-emerald-700">
                    <Zap class="h-4 w-4" /> Automation Actions
                  </h3>
                  <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2 col-span-2">
                      <Label>Enroll in Drip Sequence</Label>
                      <Select v-model="newConfig.actions.drip_sequence_id">
                        <SelectTrigger><SelectValue placeholder="Choose a re-engagement flow..." /></SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="seq in sequences" :key="seq.id" :value="seq.id">{{ seq.name }}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div class="space-y-2">
                      <Label>Apply "Dormant" Tag</Label>
                      <Input v-model="newConfig.actions.dormant_tag" placeholder="tag-name" />
                    </div>
                    <div class="flex items-end pb-2">
                      <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                        <Checkbox :checked="newConfig.is_active" @update:checked="v => newConfig.is_active = v" />
                        Enable Strategy Immediately
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="flex justify-end gap-3 pt-2">
                <Button type="button" variant="ghost" @click="showCreateDialog = false">Cancel</Button>
                <Button type="submit" class="px-8">Launch Strategy</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card><CardContent class="pt-6 flex items-center gap-4"><Clock class="h-8 w-8 text-amber-500" /><div><p class="text-sm text-gray-500">Queued</p><p class="text-2xl font-bold">{{ stats.queued ?? 0 }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><Mail class="h-8 w-8 text-blue-500" /><div><p class="text-sm text-gray-500">Sent</p><p class="text-2xl font-bold">{{ stats.sent ?? 0 }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><UserPlus class="h-8 w-8 text-emerald-500" /><div><p class="text-sm text-gray-500">Responded</p><p class="text-2xl font-bold">{{ stats.responded ?? 0 }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><TrendingUp class="h-8 w-8 text-purple-500" /><div><p class="text-sm text-gray-500">Reactivated</p><p class="text-2xl font-bold">{{ stats.reactivated ?? 0 }}</p></div></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Campaigns</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Name</th><th class="p-4">Status</th><th class="p-4">Queue</th><th class="p-4"></th></tr></thead>
            <tbody>
              <tr v-for="config in configs" :key="config.id" class="border-b hover:bg-gray-50">
                <td class="p-4 font-medium">{{ config.name }}</td>
                <td class="p-4"><Badge :variant="config.is_active ? 'default' : 'outline'">{{ config.is_active ? 'Active' : 'Inactive' }}</Badge></td>
                <td class="p-4">{{ config.contacts_count }}</td>
                <td class="p-4"><Button size="sm" @click="runCampaign(config.id)">Run</Button></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
