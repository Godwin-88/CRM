<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, Rocket, Users, CheckCircle2, Circle, BarChart3, Compass, RotateCcw } from 'lucide-vue-next'

interface Template { id: string; name: string; description: string; is_active: boolean }
interface OnboardingRecord { id: string; contact: { first_name: string; last_name: string }; status: string }
interface Journey { id: string; name: string; journey_type: string; is_published: boolean; completions: number; starts: number }
interface ReactivationConfig { id: string; name: string; inactivity_days: number; is_active: boolean }

const page = usePage()
const templates = ref<Template[]>(page.props.onboardingTemplates as Template[])
const records = ref<OnboardingRecord[]>(page.props.onboardingRecords as OnboardingRecord[])
const journeys = ref<Journey[]>(page.props.journeys as Journey[])
const reactivationConfigs = ref<ReactivationConfig[]>(page.props.reactivationConfigs as ReactivationConfig[])

const showTemplateDialog = ref(false)
const showJourneyDialog = ref(false)
const showReactivationDialog = ref(false)

const newTemplate = ref({ name: '', description: '', is_active: true, steps: [] })
const newJourney = ref({ name: '', journey_type: 'onboarding', description: '', is_published: true, trigger: 'portal_menu', notify_agent: true })
const newReactivation = ref({ name: '', inactivity_days: 90, sequence_template_id: '', is_active: true })

const progressPercent = (status: string) => status === 'completed' ? 100 : status === 'in_progress' ? 50 : 10
const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = { in_progress: 'default', completed: 'secondary', abandoned: 'outline' }

const journeyTypeLabel = (type: string) => {
  const map: Record<string, string> = { onboarding: 'Onboarding', guided: 'Guided Self-Service', reactivation: 'Reactivation' }
  return map[type] || type
}

const submitTemplate = () => {
  router.post('/admin/onboarding/templates', { ...newTemplate.value, steps: JSON.stringify(newTemplate.value.steps) }, {
    onSuccess: () => { showTemplateDialog.value = false, newTemplate.value = { name: '', description: '', is_active: true, steps: [] } }
  })
}
const submitJourney = () => {
  router.post('/admin/journeys', newJourney.value, { onSuccess: () => { showJourneyDialog.value = false } })
}
const submitReactivation = () => {
  router.post('/admin/reactivation', newReactivation.value, { onSuccess: () => { showReactivationDialog.value = false } })
}
const completionRate = (j: Journey) => j.starts > 0 ? Math.round((j.completions / j.starts) * 100) : 0
</script>

<template>
  <AppLayout>
    <Head title="Loyalty & CX – Journeys" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Loyalty & CX – Journeys</h1>
        <p class="text-gray-500 mt-1">Configure onboarding workflows, guided self-service journeys, and reactivation campaigns.</p>
      </div>

      <Tabs default-value="onboarding" class="space-y-6">
        <TabsList class="w-full justify-start">
          <TabsTrigger value="onboarding">Onboarding</TabsTrigger>
          <TabsTrigger value="guided">Guided Journeys</TabsTrigger>
          <TabsTrigger value="reactivation">Reactivation</TabsTrigger>
        </TabsList>

        <TabsContent value="onboarding" class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2"><Rocket class="h-5 w-5 text-blue-500" /> Onboarding Templates</CardTitle>
              <p class="text-sm text-gray-500">Step-by-step welcome workflows for new contacts.</p>
            </CardHeader>
            <CardContent>
              <div class="flex justify-end mb-4">
                <Dialog v-model:open="showTemplateDialog">
                  <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Template</Button></DialogTrigger>
                  <DialogContent>
                    <DialogHeader><DialogTitle>Create Onboarding Template</DialogTitle></DialogHeader>
                    <form @submit.prevent="submitTemplate" class="space-y-4">
                      <div class="space-y-2"><Label>Template Name</Label><Input v-model="newTemplate.name" placeholder="e.g. Enterprise Onboarding" required /></div>
                      <div class="space-y-2"><Label>Description</Label><Textarea v-model="newTemplate.description" placeholder="Target audience and goals..." /></div>
                      <Button type="submit" class="w-full">Create Template</Button>
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
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle class="flex items-center gap-2"><Users class="h-5 w-5 text-teal-500" /> Active Onboarding Records</CardTitle></CardHeader>
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
        </TabsContent>

        <TabsContent value="guided" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold flex items-center gap-2"><Compass class="h-5 w-5 text-purple-500" /> Guided Journeys</h2>
              <p class="text-sm text-gray-500">Step-by-step self-service flows for customer portal.</p>
            </div>
            <Dialog v-model:open="showJourneyDialog">
              <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Journey</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Create Guided Journey</DialogTitle></DialogHeader>
                <form @submit.prevent="submitJourney" class="space-y-4">
                  <div class="space-y-2"><Label>Journey Name</Label><Input v-model="newJourney.name" placeholder="e.g. Billing Update" required /></div>
                  <div class="space-y-2"><Label>Description</Label><Textarea v-model="newJourney.description" /></div>
                  <Button type="submit" class="w-full">Create Journey</Button>
                </form>
              </DialogContent>
            </Dialog>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card v-for="journey in journeys" :key="journey.id" class="hover:shadow-md transition-shadow">
              <CardContent class="pt-6 space-y-3">
                <div class="flex items-center justify-between">
                  <h3 class="font-semibold">{{ journey.name }}</h3>
                  <Badge :variant="journey.is_published ? 'default' : 'secondary'">{{ journey.is_published ? 'Published' : 'Draft' }}</Badge>
                </div>
                <p class="text-xs text-gray-500 uppercase tracking-wider">{{ journeyTypeLabel(journey.journey_type) }}</p>
                <div class="flex items-center gap-4 text-sm text-gray-600">
                  <span>Starts: {{ journey.starts }}</span>
                  <span>Completions: {{ journey.completions }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-purple-500" :style="{ width: completionRate(journey) + '%' }"></div>
                  </div>
                  <span class="text-xs font-medium">{{ completionRate(journey) }}%</span>
                </div>
              </CardContent>
            </Card>
            <Card v-if="!journeys.length"><CardContent class="py-12 text-center text-gray-500 italic">No journeys configured.</CardContent></Card>
          </div>
        </TabsContent>

        <TabsContent value="reactivation" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold flex items-center gap-2"><RotateCcw class="h-5 w-5 text-amber-500" /> Reactivation Campaigns</h2>
              <p class="text-sm text-gray-500">Re-engage dormant contacts with automated drip sequences.</p>
            </div>
            <Dialog v-model:open="showReactivationDialog">
              <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Campaign</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Create Reactivation Campaign</DialogTitle></DialogHeader>
                <form @submit.prevent="submitReactivation" class="space-y-4">
                  <div class="space-y-2"><Label>Campaign Name</Label><Input v-model="newReactivation.name" placeholder="e.g. 90-Day Dormant Winback" required /></div>
                  <div class="space-y-2"><Label>Inactivity Threshold (days)</Label><Input type="number" v-model="newReactivation.inactivity_days" /></div>
                  <div class="space-y-2"><Label>Sequence Template</Label><Input v-model="newReactivation.sequence_template_id" placeholder="Drip sequence ID" /></div>
                  <div class="flex items-center gap-2"><Checkbox v-model:checked="newReactivation.is_active" /><span class="text-sm">Active</span></div>
                  <Button type="submit" class="w-full">Create Campaign</Button>
                </form>
              </DialogContent>
            </Dialog>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card v-for="config in reactivationConfigs" :key="config.id" class="hover:shadow-md transition-shadow">
              <CardContent class="pt-6 space-y-2">
                <div class="flex items-center justify-between">
                  <h3 class="font-semibold">{{ config.name }}</h3>
                  <Badge :variant="config.is_active ? 'default' : 'secondary'">{{ config.is_active ? 'Active' : 'Paused' }}</Badge>
                </div>
                <p class="text-sm text-gray-500">Trigger: {{ config.inactivity_days }} days of inactivity</p>
              </CardContent>
            </Card>
            <Card v-if="!reactivationConfigs.length"><CardContent class="py-12 text-center text-gray-500 italic">No reactivation campaigns configured.</CardContent></Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
