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
import { Plus, Rocket, Users, CheckCircle2, Circle, BarChart3 } from 'lucide-vue-next'

interface Template { id: string; name: string; description: string; is_active: boolean }
interface Record { id: string; contact: { first_name: string; last_name: string }; status: string }
const props = defineProps<{ templates: Template[]; records: Record[] }>()
const templates = ref(props.templates)
const records = ref(props.records)
const showCreateDialog = ref(false)
const newTemplate = ref({ name: '', description: '', is_active: true, steps: [] })

const submitTemplate = async () => {
  router.post('/admin/onboarding/templates', { ...newTemplate.value, steps: JSON.stringify(newTemplate.value.steps) }, {
    onSuccess: () => { showCreateDialog.value = false },
  })
}

const progressPercent = (status: string) => status === 'completed' ? 100 : status === 'in_progress' ? 50 : 10
const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = { in_progress: 'default', completed: 'secondary', abandoned: 'outline' }
</script>

<template>
  <AppLayout>
    <Head title="Onboarding" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Onboarding</h1>
          <p class="text-gray-500">Track new contact adoption and onboarding workflows.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Template</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Template</DialogTitle></DialogHeader>
            <form @submit.prevent="submitTemplate" class="space-y-4">
              <Input v-model="newTemplate.name" placeholder="Template name" required />
              <Textarea v-model="newTemplate.description" placeholder="Description" />
              <Button type="submit" class="w-full">Create</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card v-for="template in templates" :key="template.id" class="hover:shadow-md transition-shadow">
          <CardContent class="pt-6">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold">{{ template.name }}</h3>
              <Badge :variant="template.is_active ? 'default' : 'outline'">{{ template.is_active ? 'Active' : 'Inactive' }}</Badge>
            </div>
            <p class="text-sm text-gray-500">{{ template.description || 'No description' }}</p>
          </CardContent>
        </Card>
        <Card v-if="!templates.length"><CardContent class="py-12 text-center text-gray-500 italic">No templates.</CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Onboarding Records</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Contact</th><th class="p-4">Progress</th><th class="p-4">Status</th></tr></thead>
            <tbody>
              <tr v-for="rec in records.slice(0, 20)" :key="rec.id" class="border-b hover:bg-gray-50">
                <td class="p-4">{{ rec.contact.first_name }} {{ rec.contact.last_name }}</td>
                <td class="p-4">
                  <div class="w-32"><div class="h-2 bg-gray-200 rounded-full overflow-hidden"><div class="h-2 bg-blue-500" :style="{ width: progressPercent(rec.status) + '%' }" /></div><p class="text-xs text-gray-500 mt-1">{{ progressPercent(rec.status) }}%</p></div>
                </td>
                <td class="p-4"><Badge :variant="statusVariant[rec.status]">{{ rec.status }}</Badge></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
