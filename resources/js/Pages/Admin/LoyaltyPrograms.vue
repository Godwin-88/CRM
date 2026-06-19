<script setup lang="ts">
import { ref } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, Award, Star, BookOpen, Settings, Coins, Calendar, ShieldCheck, Zap, Mail } from 'lucide-vue-next'

interface Program {
  id: string
  name: string
  program_type: string
  currency_label: string
  currency_symbol: string
  earn_rate: number
  min_redemption_threshold: number
  is_active: boolean
  expiry_policy: string
  expiry_inactivity_months?: number
  expiry_fixed_date?: string
  tiers: any[]
  rules: any[]
}

interface Template {
  id: string
  name: string
  subject: string
  body: string
  variables: string[]
  is_active: boolean
  creator_name?: string
  created_at: string
}

const page = usePage()
const programs = ref<Program[]>(page.props.programs as Program[])
const templates = ref<Template[]>(page.props.templates as Template[])

const showCreateDialog = ref(false)
const showTierDialog = ref(false)
const showRuleDialog = ref(false)
const showTemplateDialog = ref(false)
const selectedProgramId = ref(programs.value[0]?.id ?? '')

const editingTemplate = ref<Template | null>(null)
const newTemplate = ref({
  name: '',
  subject: '',
  body: '',
  variables: '["contact_name", "contact_email", "company_name"]',
  is_active: true,
})

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

const openCreateTemplate = () => {
  editingTemplate.value = null
  newTemplate.value = {
    name: '', subject: '', body: '',
    variables: '["contact_name", "contact_email", "company_name"]',
    is_active: true,
  }
  showTemplateDialog.value = true
}
const openEditTemplate = (tpl: Template) => {
  editingTemplate.value = tpl
  newTemplate.value = {
    name: tpl.name,
    subject: tpl.subject,
    body: tpl.body,
    variables: JSON.stringify(tpl.variables),
    is_active: tpl.is_active,
  }
  showTemplateDialog.value = true
}
const submitTemplate = () => {
  const payload = { ...newTemplate.value, variables: JSON.parse(newTemplate.value.variables as unknown as string) }
  if (editingTemplate.value) {
    router.put('/admin/welcome-email-templates/' + editingTemplate.value.id, payload, { onSuccess: () => { showTemplateDialog.value = false } })
  } else {
    router.post('/admin/welcome-email-templates', payload, {
      onSuccess: () => {
        showTemplateDialog.value = false
        newTemplate.value = { name: '', subject: '', body: '', variables: '["contact_name", "contact_email", "company_name"]', is_active: true }
      }
    })
  }
}
const deleteTemplate = (id: string) => {
  if (confirm('Delete this template?')) router.delete('/admin/welcome-email-templates/' + id)
}
const selectedProgram = () => programs.value.find(p => p.id === selectedProgramId.value) ?? programs.value[0]
</script>

<template>
  <AppLayout>
    <Head title="Loyalty & CX – Programs" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Loyalty & CX – Programs</h1>
        <p class="text-gray-500 mt-1">Configure programs, rules, redemptions, and incentive communications.</p>
      </div>

      <Tabs default-value="setup" class="space-y-6">
        <TabsList class="w-full justify-start">
          <TabsTrigger value="setup">Program Setup</TabsTrigger>
          <TabsTrigger value="rules">Rules &amp; Tiers</TabsTrigger>
          <TabsTrigger value="redemption">Redemption Rules</TabsTrigger>
          <TabsTrigger value="incentive">Incentive Templates</TabsTrigger>
        </TabsList>

        <TabsContent value="setup" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold">Program Configuration</h2>
              <p class="text-sm text-gray-500">Create and manage loyalty program definitions.</p>
            </div>
            <Dialog v-model:open="showCreateDialog">
              <DialogTrigger as-child><Button size="lg" class="shadow-md"><Plus class="h-5 w-5 mr-2" />New Program</Button></DialogTrigger>
              <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle class="text-2xl">Create Loyalty Program</DialogTitle>
                  <p class="text-sm text-gray-500 italic">Define the economics and rules of your new loyalty strategy.</p>
                </DialogHeader>
                <form @submit.prevent="submitProgram" class="space-y-8 py-6">
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
                        <Label>Min. Redemption Threshold</Label>
                        <div class="flex items-center gap-2">
                          <Input type="number" v-model="newProgram.min_redemption_threshold" class="w-24" />
                          <span class="text-sm text-gray-500">{{ newProgram.currency_label }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
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
        </TabsContent>

        <TabsContent value="rules" class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2"><Star class="h-5 w-5 text-amber-500" /> Tier &amp; Rules Management</CardTitle>
              <p class="text-sm text-gray-500">Manage tier thresholds and earning rules for the selected program.</p>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Selected Program</h3>
                <Select v-model="selectedProgramId">
                  <SelectTrigger class="w-64"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <h4 class="font-semibold">Tiers</h4>
                    <Dialog v-model:open="showTierDialog">
                      <DialogTrigger as-child><Button size="sm" variant="outline"><Plus class="h-4 w-4 mr-1" />Add Tier</Button></DialogTrigger>
                      <DialogContent>
                        <DialogHeader><DialogTitle>Define Membership Tier</DialogTitle></DialogHeader>
                        <form @submit.prevent="submitTier" class="space-y-4 py-2">
                          <div class="space-y-2"><Label>Tier Name</Label><Input v-model="newTier.name" placeholder="e.g. Platinum Elite" required /></div>
                          <div class="space-y-2"><Label>Minimum Points Required</Label><Input v-model="newTier.min_points_threshold" type="number" placeholder="0" required /></div>
                          <Button type="submit" class="w-full mt-4">Save Tier</Button>
                        </form>
                      </DialogContent>
                    </Dialog>
                  </div>
                  <div class="space-y-2">
                    <div v-for="tier in selectedProgram()?.tiers" :key="tier.id" class="border rounded-lg p-4 flex items-center justify-between hover:border-blue-200">
                      <span class="font-medium">{{ tier.name }}</span>
                      <Badge variant="outline" class="font-mono">≥ {{ tier.min_points_threshold }} {{ selectedProgram()?.currency_symbol }}</Badge>
                    </div>
                    <div v-if="!selectedProgram()?.tiers.length" class="text-center py-8 border-2 border-dashed rounded-lg text-gray-400 text-sm">No tiers defined.</div>
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <h4 class="font-semibold">Earning Rules</h4>
                    <Dialog v-model:open="showRuleDialog">
                      <DialogTrigger as-child><Button size="sm" variant="outline"><Plus class="h-4 w-4 mr-1" />Add Rule</Button></DialogTrigger>
                      <DialogContent>
                        <DialogHeader><DialogTitle>Create Earning Rule</DialogTitle></DialogHeader>
                        <form @submit.prevent="submitRule" class="space-y-4 py-2">
                          <div class="space-y-2"><Label>Event Trigger</Label><Input v-model="newRule.event_type" placeholder="e.g. newsletter_signup" required /></div>
                          <div class="space-y-2"><Label>Points Reward</Label><Input v-model="newRule.points" type="number" placeholder="50" required /></div>
                          <Button type="submit" class="w-full mt-4">Save Rule</Button>
                        </form>
                      </DialogContent>
                    </Dialog>
                  </div>
                  <div class="space-y-2">
                    <div v-for="rule in selectedProgram()?.rules" :key="rule.id" class="border rounded-lg p-4 flex items-center justify-between hover:border-blue-200">
                      <span class="font-medium">{{ rule.description || rule.event_type.replace(/_/g, ' ') }}</span>
                      <Badge class="bg-blue-600 font-bold">+{{ rule.points }} {{ selectedProgram()?.currency_symbol }}</Badge>
                    </div>
                    <div v-if="!selectedProgram()?.rules.length" class="text-center py-8 border-2 border-dashed rounded-lg text-gray-400 text-sm">No earning rules defined.</div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="redemption" class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2"><Settings class="h-5 w-5 text-purple-500" /> Redemption Rules</CardTitle>
              <p class="text-sm text-gray-500">Configure how points are exchanged for value.</p>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Select Program</h3>
                <Select v-model="selectedProgramId">
                  <SelectTrigger class="w-64"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="border rounded-lg divide-y">
                <div class="p-4 flex items-center justify-between">
                  <div>
                    <p class="font-medium">Discount Voucher</p>
                    <p class="text-xs text-gray-500">Generate vouchers redeemable as discounts</p>
                  </div>
                  <Badge variant="outline">{{ selectedProgram()?.min_redemption_threshold }} {{ selectedProgram()?.currency_symbol }} min</Badge>
                </div>
                <div class="p-4 flex items-center justify-between">
                  <div>
                    <p class="font-medium">Free Product Assignment</p>
                    <p class="text-xs text-gray-500">Redeem points for complimentary products</p>
                  </div>
                  <Badge variant="outline">Per reward config</Badge>
                </div>
                <div class="p-4 flex items-center justify-between">
                  <div>
                    <p class="font-medium">Tier Upgrade Credit</p>
                    <p class="text-xs text-gray-500">Apply points toward tier eligibility</p>
                  </div>
                  <Badge variant="outline">Per threshold</Badge>
                </div>
              </div>
              <p class="text-sm text-gray-500 italic">Full redemption rule editor available per reward type. These defaults apply to all active programs.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="incentive" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold flex items-center gap-2"><Mail class="h-5 w-5 text-blue-500" /> Incentive Communication Templates</h2>
              <p class="text-sm text-gray-500">Configure automated messages triggered by loyalty events.</p>
            </div>
            <Dialog v-model:open="showTemplateDialog">
              <DialogTrigger as-child><Button @click="openCreateTemplate"><Plus class="h-4 w-4 mr-2" />New Template</Button></DialogTrigger>
              <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader><DialogTitle>{{ editingTemplate ? 'Edit' : 'Create' }} Incentive Template</DialogTitle></DialogHeader>
                <form @submit.prevent="submitTemplate" class="space-y-4 py-4">
                  <div class="space-y-2"><Label>Template Name *</Label><Input v-model="newTemplate.name" placeholder="e.g. Tier Upgrade Congratulations" required /></div>
                  <div class="space-y-2"><Label>Subject Line *</Label><Input v-model="newTemplate.subject" placeholder="You reached {{next_tier_name}}!" required /></div>
                  <div class="space-y-2"><Label>Body (HTML supported)</Label><Textarea v-model="newTemplate.body" placeholder="<p>Hi {{contact_name}},</p>" rows="8" /></div>
                  <div class="space-y-2"><Label>Available Variables (JSON array)</Label><Input v-model="newTemplate.variables" placeholder='["contact_name", "current_tier", "points_balance"]' /></div>
                  <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="newTemplate.is_active" /> Active</label>
                  <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="showTemplateDialog = false">Cancel</Button>
                    <Button type="submit">{{ editingTemplate ? 'Update' : 'Create' }} Template</Button>
                  </div>
                </form>
              </DialogContent>
            </Dialog>
          </div>

          <Card>
            <CardContent class="p-0">
              <table class="w-full text-sm">
                <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Name</th><th class="p-4">Subject</th><th class="p-4">Status</th><th class="p-4">Created</th><th class="p-4 text-right">Actions</th></tr></thead>
                <tbody>
                  <tr v-for="template in templates" :key="template.id" class="border-b hover:bg-gray-50">
                    <td class="p-4 font-medium">{{ template.name }}</td>
                    <td class="p-4 text-sm text-gray-600 max-w-xs truncate">{{ template.subject }}</td>
                    <td class="p-4"><Badge :variant="template.is_active ? 'default' : 'secondary'">{{ template.is_active ? 'Active' : 'Inactive' }}</Badge></td>
                    <td class="p-4 text-sm text-gray-500">{{ new Date(template.created_at).toLocaleDateString() }}</td>
                    <td class="p-4">
                      <div class="flex items-center justify-end gap-2">
                        <Button variant="ghost" size="sm" @click="openEditTemplate(template)">Edit</Button>
                        <Button variant="ghost" size="sm" @click="deleteTemplate(template.id)" class="text-rose-500">Delete</Button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!templates.length"><td colspan="5" class="p-8 text-center text-gray-500 italic">No templates configured.</td></tr>
                </tbody>
              </table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
