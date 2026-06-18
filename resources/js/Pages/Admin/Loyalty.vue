<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Plus, Award, Star, BookOpen, Settings, Coins, Calendar, ShieldCheck, Zap } from 'lucide-vue-next'

interface Program { 
  id: string; 
  name: string; 
  program_type: string;
  currency_label: string; 
  currency_symbol: string;
  earn_rate: number;
  min_redemption_threshold: number;
  is_active: boolean; 
  expiry_policy: string;
  expiry_inactivity_months?: number;
  expiry_fixed_date?: string;
  tiers: any[]; 
  rules: any[] 
}
const props = defineProps<{ programs: Program[] }>()
const programs = ref(props.programs)
const showCreateDialog = ref(false)
const showTierDialog = ref(false)
const showRuleDialog = ref(false)
const selectedProgramId = ref(programs.value[0]?.id ?? '')

const newProgram = ref({ 
  name: '', 
  program_type: 'points_based',
  description: '',
  currency_label: 'Points', 
  currency_symbol: 'pts',
  earn_rate: 1.0,
  min_redemption_threshold: 100,
  is_active: true, 
  expiry_policy: 'never',
  expiry_inactivity_months: 12,
  expiry_fixed_date: ''
})

const newTier = ref({ program_id: '', name: '', min_points_threshold: 0 })
const newRule = ref({ program_id: '', event_type: '', points: 0 })

const submitProgram = () => {
  router.post('/admin/loyalty', newProgram.value, { 
    onSuccess: () => { 
      showCreateDialog.value = false 
      // Reset form
      newProgram.value = {
        name: '', program_type: 'points_based', description: '',
        currency_label: 'Points', currency_symbol: 'pts', earn_rate: 1.0,
        min_redemption_threshold: 100, is_active: true, expiry_policy: 'never',
        expiry_inactivity_months: 12, expiry_fixed_date: ''
      }
    } 
  })
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
    <Head title="Loyalty &amp; Rewards Engine" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Loyalty &amp; Rewards Engine</h1>
          <p class="text-gray-500">Configure customer retention strategies and value-based rewards.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button size="lg" class="shadow-md"><Plus class="h-5 w-5 mr-2" />New Program</Button></DialogTrigger>
          <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle class="text-2xl">Create Loyalty Program</DialogTitle>
              <p class="text-sm text-gray-500 italic">Define the economics and rules of your new loyalty strategy.</p>
            </DialogHeader>
            <form @submit.prevent="submitProgram" class="space-y-8 py-6">
              <!-- Section 1: Basics -->
              <div class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 flex items-center gap-2">
                  <ShieldCheck class="h-4 w-4" /> Program Identity
                </h3>
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2 col-span-2">
                    <Label for="name">Public Program Name</Label>
                    <Input id="name" v-model="newProgram.name" placeholder="e.g. VIP Insider Rewards" required />
                  </div>
                  <div class="space-y-2">
                    <Label>Program Type</Label>
                    <Select v-model="newProgram.program_type">
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="points_based">Points Based</SelectItem>
                        <SelectItem value="cashback">Cashback %</SelectItem>
                        <SelectItem value="tiered">Tiered Only</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label>Status</Label>
                    <div class="flex items-center gap-2 pt-2">
                      <Checkbox :checked="newProgram.is_active" @update:checked="(v: boolean | 'indeterminate') => newProgram.is_active = v as boolean" />
                      <span class="text-sm font-medium">Active &amp; Enrolling</span>
                    </div>
                  </div>
                  <div class="space-y-2 col-span-2">
                    <Label>Description</Label>
                    <Textarea v-model="newProgram.description" placeholder="Internal notes on program goals..." rows="2" />
                  </div>
                </div>
              </div>

              <!-- Section 2: Economics -->
              <div class="space-y-4 border-t pt-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-blue-500 flex items-center gap-2">
                  <Coins class="h-4 w-4" /> Point Economics
                </h3>
                <div class="grid grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <Label>Currency Label</Label>
                    <Input v-model="newProgram.currency_label" placeholder="e.g. Stars, Points, Miles" required />
                  </div>
                  <div class="space-y-2">
                    <Label>Currency Symbol (Short)</Label>
                    <Input v-model="newProgram.currency_symbol" placeholder="e.g. ★, pts" required />
                  </div>
                  <div class="space-y-2">
                    <Label>Earning Rate</Label>
                    <div class="flex items-center gap-2">
                      <Input type="number" step="0.1" v-model="newProgram.earn_rate" class="w-24" />
                      <span class="text-sm text-gray-500">per $1.00 spent</span>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <Label>Min. Redemption</Label>
                    <div class="flex items-center gap-2">
                      <Input type="number" v-model="newProgram.min_redemption_threshold" class="w-24" />
                      <span class="text-sm text-gray-500">{{ newProgram.currency_label }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section 3: Expiry -->
              <div class="space-y-4 border-t pt-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-amber-500 flex items-center gap-2">
                  <Calendar class="h-4 w-4" /> Expiry Policy
                </h3>
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <Label>Policy</Label>
                    <Select v-model="newProgram.expiry_policy">
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="never">Never Expire</SelectItem>
                        <SelectItem value="inactivity_months">Rolling Inactivity</SelectItem>
                        <SelectItem value="fixed_date">Hard Fixed Date</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div v-if="newProgram.expiry_policy === 'inactivity_months'" class="space-y-2">
                    <Label>Months of Inactivity</Label>
                    <Input type="number" v-model="newProgram.expiry_inactivity_months" />
                  </div>
                  <div v-if="newProgram.expiry_policy === 'fixed_date'" class="space-y-2">
                    <Label>Expiry Date</Label>
                    <Input type="date" v-model="newProgram.expiry_fixed_date" />
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-3 pt-4">
                <Button type="button" variant="ghost" @click="showCreateDialog = false">Cancel</Button>
                <Button type="submit" class="px-10">Launch Program</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <Card class="lg:col-span-1 border-blue-100 shadow-sm">
          <CardHeader><CardTitle class="text-sm font-bold text-gray-400 uppercase tracking-widest">Active Programs</CardTitle></CardHeader>
          <CardContent class="px-2">
            <div class="space-y-1">
              <Button v-for="prog in programs" :key="prog.id"
                :variant="selectedProgramId === prog.id ? 'default' : 'ghost'"
                class="w-full justify-start py-6 relative overflow-hidden group" @click="selectedProgramId = prog.id">
                <Award class="h-5 w-5 mr-3 z-10" />
                <div class="flex flex-col items-start z-10">
                  <span class="truncate font-bold">{{ prog.name }}</span>
                  <span class="text-[10px] uppercase opacity-70 tracking-tighter">{{ prog.program_type?.replace('_', ' ') }}</span>
                </div>
                <div v-if="selectedProgramId === prog.id" class="absolute inset-0 bg-blue-600 opacity-10"></div>
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card class="lg:col-span-3 shadow-lg border-blue-50">
          <CardHeader class="flex flex-row items-center justify-between border-b pb-6">
            <div>
              <div class="flex items-center gap-3">
                <CardTitle class="text-2xl">{{ selectedProgram()?.name }}</CardTitle>
                <Badge :variant="selectedProgram()?.is_active ? 'default' : 'secondary'">
                  {{ selectedProgram()?.is_active ? 'ACTIVE' : 'INACTIVE' }}
                </Badge>
              </div>
              <p class="text-sm text-gray-500 flex items-center gap-1 mt-1">
                <Coins class="h-3 w-3" /> 1 USD = {{ selectedProgram()?.earn_rate }} {{ selectedProgram()?.currency_label }} ({{ selectedProgram()?.currency_symbol }})
              </p>
            </div>
            <div class="flex gap-2">
              <Dialog v-model:open="showTierDialog">
                <DialogTrigger as-child><Button variant="outline" size="sm"><Star class="h-4 w-4 mr-2" />Add Tier</Button></DialogTrigger>
                <DialogContent>
                  <DialogHeader><DialogTitle>Define Membership Tier</DialogTitle></DialogHeader>
                  <form @submit.prevent="submitTier" class="space-y-4 py-2">
                    <div class="space-y-2">
                      <Label>Tier Name</Label>
                      <Input v-model="newTier.name" placeholder="e.g. Platinum Elite" required />
                    </div>
                    <div class="space-y-2">
                      <Label>Minimum Points Required</Label>
                      <Input v-model="newTier.min_points_threshold" type="number" placeholder="0" required />
                    </div>
                    <Button type="submit" class="w-full mt-4">Save Tier</Button>
                  </form>
                </DialogContent>
              </Dialog>
              <Dialog v-model:open="showRuleDialog">
                <DialogTrigger as-child><Button variant="outline" size="sm"><Zap class="h-4 w-4 mr-2" />Add Rule</Button></DialogTrigger>
                <DialogContent>
                  <DialogHeader><DialogTitle>Create Earning Rule</DialogTitle></DialogHeader>
                  <form @submit.prevent="submitRule" class="space-y-4 py-2">
                    <div class="space-y-2">
                      <Label>Event Trigger</Label>
                      <Input v-model="newRule.event_type" placeholder="e.g. newsletter_signup" required />
                    </div>
                    <div class="space-y-2">
                      <Label>Points Reward</Label>
                      <Input v-model="newRule.points" type="number" placeholder="50" required />
                    </div>
                    <Button type="submit" class="w-full mt-4">Active Rule</Button>
                  </form>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent class="pt-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
              <div class="space-y-6">
                <div class="flex items-center justify-between">
                  <h3 class="font-bold text-lg flex items-center gap-2"><Star class="h-5 w-5 text-amber-500" /> Progression Tiers</h3>
                  <span class="text-xs text-gray-400 font-medium">{{ selectedProgram()?.tiers.length }} Levels</span>
                </div>
                <div class="space-y-3">
                  <div v-for="tier in selectedProgram()?.tiers" :key="tier.id" class="border bg-gray-50/50 rounded-xl p-5 hover:border-blue-200 transition-colors shadow-sm">
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-gray-700 tracking-tight">{{ tier.name }}</span>
                      <Badge variant="outline" class="bg-white border-gray-200 font-mono text-[11px]">
                        ≥ {{ tier.min_points_threshold }} {{ selectedProgram()?.currency_symbol }}
                      </Badge>
                    </div>
                  </div>
                  <div v-if="!selectedProgram()?.tiers.length" class="text-center py-10 border-2 border-dashed rounded-xl">
                    <p class="text-sm text-gray-400">No tiers defined yet.</p>
                  </div>
                </div>
              </div>
              <div class="space-y-6">
                <div class="flex items-center justify-between">
                  <h3 class="font-bold text-lg flex items-center gap-2"><Zap class="h-5 w-5 text-blue-500" /> Multiplier Rules</h3>
                  <span class="text-xs text-gray-400 font-medium">{{ selectedProgram()?.rules.length }} Active</span>
                </div>
                <div class="space-y-3">
                  <div v-for="rule in selectedProgram()?.rules" :key="rule.id" class="border bg-blue-50/20 rounded-xl p-5 hover:border-blue-200 transition-colors">
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-gray-700 tracking-tight">{{ rule.description || rule.event_type.replace('_', ' ') }}</span>
                      <Badge class="bg-blue-600 font-bold">+{{ rule.points }} {{ selectedProgram()?.currency_symbol }}</Badge>
                    </div>
                  </div>
                  <div v-if="!selectedProgram()?.rules.length" class="text-center py-10 border-2 border-dashed rounded-xl">
                    <p class="text-sm text-gray-400">No earning rules defined.</p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
