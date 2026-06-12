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
import { Plus, Route, Users, CheckCircle2, BarChart3 } from 'lucide-vue-next'

interface Journey {
  id: string
  name: string
  description: string
  is_published: boolean
  completions: { id: string; status: string }[]
  creator?: { name: string }
}
interface Completion { id: string; journey_id: string; contact: { first_name: string; last_name: string }; status: string; started_at: string; completed_at?: string }
const props = defineProps<{ journeys: Journey[]; completions?: Completion[] }>()
const journeys = ref(props.journeys)
const showCreateDialog = ref(false)
const newJourney = ref({ name: '', description: '', steps: '[]', is_published: false })

const submitJourney = async () => {
  router.post('/admin/journeys', { ...newJourney.value, steps: JSON.parse(newJourney.value.steps) }, {
    onSuccess: () => { showCreateDialog.value = false },
  })
}

const completionRate = () => {
  const all = journeys.value.flatMap(j => j.completions)
  return all.length ? Math.round(all.filter(c => c.status === 'completed').length / all.length * 100) : 0
}

const activeCount = journeys.value.filter(j => j.is_published && j.completions.some(c => c.status === 'in_progress')).length
</script>

<template>
  <AppLayout>
    <Head title="Guided Journeys" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Guided Journeys</h1>
          <p class="text-gray-500">Design experiences for contacts to follow over time.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Journey</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Journey</DialogTitle></DialogHeader>
            <form @submit.prevent="submitJourney" class="space-y-4">
              <Input v-model="newJourney.name" placeholder="Journey name" required />
              <Textarea v-model="newJourney.description" placeholder="Description" />
              <Textarea v-model="newJourney.steps" placeholder='Steps JSON array' rows="4" />
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="newJourney.is_published" class="rounded" /> Publish</label>
              <Button type="submit" class="w-full">Create</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card><CardContent class="pt-6 flex items-center gap-4"><Route class="h-8 w-8 text-blue-500" /><div><p class="text-sm text-gray-500">Total Journeys</p><p class="text-2xl font-bold">{{ journeys.length }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><Users class="h-8 w-8 text-emerald-500" /><div><p class="text-sm text-gray-500">Active</p><p class="text-2xl font-bold">{{ activeCount }}</p></div></CardContent></Card>
        <Card><CardContent class="pt-6 flex items-center gap-4"><BarChart3 class="h-8 w-8 text-purple-500" /><div><p class="text-sm text-gray-500">Completion Rate</p><p class="text-2xl font-bold">{{ completionRate() }}%</p></div></CardContent></Card>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="journey in journeys" :key="journey.id" class="hover:shadow-md transition-shadow">
          <CardContent class="pt-6">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold">{{ journey.name }}</h3>
              <Badge :variant="journey.is_published ? 'default' : 'outline'">{{ journey.is_published ? 'Published' : 'Draft' }}</Badge>
            </div>
            <p class="text-sm text-gray-500 mb-3">{{ journey.description || 'No description' }}</p>
            <div class="flex items-center gap-4 text-sm text-gray-500">
              <span class="flex items-center gap-1"><CheckCircle2 class="h-4 w-4" /> {{ journey.completions.filter(c => c.status === 'completed').length }} completed</span>
              <span class="flex items-center gap-1"><Users class="h-4 w-4" /> {{ journey.completions.filter(c => c.status === 'in_progress').length }} active</span>
            </div>
          </CardContent>
        </Card>
        <Card v-if="!journeys.length"><CardContent class="py-12 text-center text-gray-500 italic">No journeys.</CardContent></Card>
      </div>
    </div>
  </AppLayout>
</template>
