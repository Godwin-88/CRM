<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Plus, Award, Star, BookOpen } from 'lucide-vue-next'

interface Program { id: string; name: string; currency_label: string; is_active: boolean; tiers: any[]; rules: any[] }
const props = defineProps<{ programs: Program[] }>()
const programs = ref(props.programs)
const showCreateDialog = ref(false)
const showTierDialog = ref(false)
const showRuleDialog = ref(false)
const selectedProgramId = ref(programs.value[0]?.id ?? '')

const newProgram = ref({ name: '', currency_label: 'Points', is_active: true, expiry_inactivity_months: 12 })
const newTier = ref({ program_id: '', name: '', min_points: 0 })
const newRule = ref({ program_id: '', event_type: '', points: 0 })

const submitProgram = () => {
  router.post('/admin/loyalty', newProgram.value, { onSuccess: () => { showCreateDialog.value = false } })
}
const submitTier = () => {
  newTier.value.program_id = selectedProgramId.value
  router.post('/admin/loyalty/tiers', newTier.value, { onSuccess: () => { showTierDialog.value = false } })
}
const submitRule = () => {
  newRule.value.program_id = selectedProgramId.value
  router.post('/admin/loyalty/rules', newRule.value, { onSuccess: () => { showRuleDialog.value = false } })
}
const selectedProgram = () => programs.value.find(p => p.id === selectedProgramId.value) ?? programs.value[0]
</script>

<template>
  <AppLayout>
    <Head title="Loyalty &amp; Rewards" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Loyalty &amp; Rewards</h1>
          <p class="text-gray-500">Manage loyalty programs, tiers, and point rules.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Program</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Program</DialogTitle></DialogHeader>
            <form @submit.prevent="submitProgram" class="space-y-4">
              <Input v-model="newProgram.name" placeholder="Program name" required />
              <Input v-model="newProgram.currency_label" placeholder="Currency label (e.g. Points)" required />
              <Button type="submit" class="w-full">Create</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <Card class="lg:col-span-1">
          <CardHeader><CardTitle class="text-base">Programs</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <Button v-for="prog in programs" :key="prog.id"
                :variant="selectedProgramId === prog.id ? 'default' : 'ghost'"
                class="w-full justify-start" @click="selectedProgramId = prog.id">
                <Award class="h-4 w-4 mr-2" />
                <span class="truncate">{{ prog.name }}</span>
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card class="lg:col-span-3">
          <CardHeader class="flex flex-row items-center justify-between">
            <div>
              <CardTitle>{{ selectedProgram()?.name }}</CardTitle>
              <p class="text-sm text-gray-500">Currency: {{ selectedProgram()?.currency_label }}</p>
            </div>
            <div class="flex gap-2">
              <Dialog v-model:open="showTierDialog">
                <DialogTrigger as-child><Button variant="outline"><Star class="h-4 w-4 mr-2" />Add Tier</Button></DialogTrigger>
                <DialogContent>
                  <DialogHeader><DialogTitle>Add Tier</DialogTitle></DialogHeader>
                  <form @submit.prevent="submitTier" class="space-y-4">
                    <Input v-model="newTier.name" placeholder="Tier name" required />
                    <Input v-model="newTier.min_points" type="number" placeholder="Min points" required />
                    <Button type="submit" class="w-full">Create</Button>
                  </form>
                </DialogContent>
              </Dialog>
              <Dialog v-model:open="showRuleDialog">
                <DialogTrigger as-child><Button variant="outline"><BookOpen class="h-4 w-4 mr-2" />Add Rule</Button></DialogTrigger>
                <DialogContent>
                  <DialogHeader><DialogTitle>Add Rule</DialogTitle></DialogHeader>
                  <form @submit.prevent="submitRule" class="space-y-4">
                    <Input v-model="newRule.event_type" placeholder="Event type" required />
                    <Input v-model="newRule.points" type="number" placeholder="Points" required />
                    <Button type="submit" class="w-full">Create</Button>
                  </form>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="font-semibold mb-3">Tiers</h3>
                <div class="space-y-3">
                  <div v-for="tier in selectedProgram()?.tiers" :key="tier.id" class="border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                      <span class="font-medium">{{ tier.name }}</span>
                      <Badge variant="secondary">{{ tier.min_points }} pts</Badge>
                    </div>
                  </div>
                  <div v-if="!selectedProgram()?.tiers.length" class="text-sm text-gray-500 italic">No tiers.</div>
                </div>
              </div>
              <div>
                <h3 class="font-semibold mb-3">Earning Rules</h3>
                <div class="space-y-3">
                  <div v-for="rule in selectedProgram()?.rules" :key="rule.id" class="border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                      <span class="font-medium">{{ rule.description || rule.event_type }}</span>
                      <Badge variant="outline">+{{ rule.points }} pts</Badge>
                    </div>
                  </div>
                  <div v-if="!selectedProgram()?.rules.length" class="text-sm text-gray-500 italic">No rules.</div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
