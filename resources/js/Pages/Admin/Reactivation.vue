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
import { Plus, UserPlus, Mail, Clock, TrendingUp } from 'lucide-vue-next'

interface Config {
  id: string
  name: string
  description: string
  criteria: string
  actions: string
  is_active: boolean
  reactivation_contacts_count: number
}
interface Contact {
  id: string
  contact: { first_name: string; last_name: string; email: string }
  status: string
  config: { name: string }
}
const props = defineProps<{ configs: Config[]; contacts?: Contact[]; stats?: Record<string, number> }>()
const configs = ref(props.configs)
const contacts = ref(props.contacts ?? [])
const stats = ref(props.stats ?? {})
const showCreateDialog = ref(false)
const newConfig = ref({ name: '', description: '', criteria: '{}', actions: '[]', is_active: true })

const submitConfig = async () => {
  router.post('/admin/reactivation', newConfig.value, {
    onSuccess: () => { showCreateDialog.value = false },
  })
}

const runCampaign = async (id: string) => {
  if (!confirm('Run this campaign?')) return
  router.post(`/admin/reactivation/${id}/run`, {}, {
    onSuccess: () => router.reload(),
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Reactivation" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Reactivation</h1>
          <p class="text-gray-500">Re-engage inactive contacts with tailored campaigns.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Campaign</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Campaign</DialogTitle></DialogHeader>
            <form @submit.prevent="submitConfig" class="space-y-4">
              <Input v-model="newConfig.name" placeholder="Campaign name" required />
              <Textarea v-model="newConfig.description" placeholder="Description" />
              <Textarea v-model="newConfig.criteria" placeholder='Criteria JSON' rows="3" />
              <Textarea v-model="newConfig.actions" placeholder='Actions JSON array' rows="3" />
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="newConfig.is_active" class="rounded" /> Active</label>
              <Button type="submit" class="w-full">Create</Button>
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
                <td class="p-4">{{ config.reactivation_contacts_count }}</td>
                <td class="p-4"><Button size="sm" @click="runCampaign(config.id)">Run</Button></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
