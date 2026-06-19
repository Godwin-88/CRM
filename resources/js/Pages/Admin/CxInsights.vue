<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, ClipboardList, Send, TrendingUp, Users, DollarSign, Activity } from 'lucide-vue-next'

interface Survey {
  id: string
  name: string
  type: string
  status: string
  channel?: string
  trigger_event?: string
}

interface SurveyResponseRow {
  id: string
  survey_id: string
  survey_name: string
  survey_type: string
  contact_name: string
  contact_email: string
  score: number
  nps_category?: string
  open_text_answer?: string
  channel: string
  responded_at: string
}

interface SurveyMeta { id: string; name: string; type: string }

interface ClvStats {
  avg_clv: number
  total_points_issued: number
  total_points_redeemed: number
  redemption_rate: number
  total_enrollments: number
  active_enrollments: number
  churn_risk_count: number
}

interface ClvContact {
  id: string
  first_name: string
  last_name: string
  email: string
  clv_score: number
  ltv: number
}

const page = usePage()
const surveys = ref<Survey[]>(page.props.surveys as Survey[])
const surveyResponses = ref<SurveyResponseRow[]>(page.props.surveyResponses as SurveyResponseRow[])
const surveyMeta = ref<SurveyMeta[]>(page.props.surveyMeta as SurveyMeta[])
const clvStats = ref<ClvStats>(page.props.clvStats as ClvStats)
const topContacts = ref<ClvContact[]>(page.props.topContacts as ClvContact[])

const showCreateDialog = ref(false)
const timeRange = ref<'30d' | '90d' | '1y'>('30d')
const surveySearchQuery = ref('')
const selectedSurvey = ref('')
const selectedChannel = ref('')
const surveyActiveTab = ref('all')

const form = ref({
  name: '',
  type: 'NPS',
  status: 'draft',
  segment_id: '',
  question_text: '',
  follow_up_question: '',
  channel: 'email',
  contact_ids: [] as string[],
  trigger_event: '',
  sent_at: '',
  closed_at: '',
})

const submitSurvey = () => {
  router.post('/admin/surveys', { ...form.value, type: String(form.value.type).toLowerCase(), status: String(form.value.status).toLowerCase() } as any, {
    onSuccess: () => { showCreateDialog.value = false, Object.assign(form.value, { name: '', type: 'NPS', status: 'draft', segment_id: '', question_text: '', follow_up_question: '', channel: 'email', contact_ids: [], trigger_event: '', sent_at: '', closed_at: '' }) }
  })
}

const filteredSurveyResponses = computed(() => {
  return surveyResponses.value.filter(r => {
    const q = surveySearchQuery.value.trim().toLowerCase()
    const matchesSearch = !q || r.contact_name.toLowerCase().includes(q) || r.survey_name.toLowerCase().includes(q)
    const matchesSurvey = !selectedSurvey.value || r.survey_id === selectedSurvey.value
    const matchesChannel = !selectedChannel.value || r.channel === selectedChannel.value
    const matchesTab = surveyActiveTab.value === 'all' || r.survey_type === surveyActiveTab.value
    return matchesSearch && matchesSurvey && matchesChannel && matchesTab
  })
})

const typeColors: Record<string, string> = {
  NPS: 'bg-indigo-100 text-indigo-800',
  CSAT: 'bg-emerald-100 text-emerald-800',
  CES: 'bg-amber-100 text-amber-800',
}

const npsCategoryColor = (category?: string) => {
  switch (category) {
    case 'promoter': return 'bg-emerald-100 text-emerald-800'
    case 'passive': return 'bg-amber-100 text-amber-800'
    case 'detractor': return 'bg-rose-100 text-rose-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const npsScoreColor = (score: number, type: string) => {
  if (type === 'nps') return score >= 9 ? 'text-emerald-600 font-bold' : score >= 7 ? 'text-amber-600' : 'text-rose-600'
  if (type === 'csat') return score >= 4 ? 'text-emerald-600 font-bold' : 'text-rose-600'
  return 'text-gray-900'
}

const formatDate = (dateStr: string) => new Date(dateStr).toLocaleString()

const surveyAnalytics = computed(() => {
  const total = surveyResponses.value.length
  if (total === 0) return { avgScore: 0, npsScore: 0, csatScore: 0 }
  const avgScore = Math.round((surveyResponses.value.reduce((sum, r) => sum + r.score, 0) / total) * 100) / 100
  const promoters = surveyResponses.value.filter(r => r.nps_category === 'promoter').length
  const detractors = surveyResponses.value.filter(r => r.nps_category === 'detractor').length
  const npsScore = Math.round(((promoters - detractors) / total) * 100)
  const satisfied = surveyResponses.value.filter(r => r.score >= 4).length
  const csatScore = Math.round((satisfied / total) * 100)
  return { avgScore, npsScore, csatScore }
})
</script>

<template>
  <AppLayout>
    <Head title="Loyalty & CX – Insights" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Loyalty & CX – Insights</h1>
        <p class="text-gray-500 mt-1">Survey management &amp; response analytics, plus CLV and churn intelligence.</p>
      </div>

      <Tabs default-value="surveys" class="space-y-6">
        <TabsList class="w-full justify-start">
          <TabsTrigger value="surveys">Surveys</TabsTrigger>
          <TabsTrigger value="clv">CLV &amp; Churn</TabsTrigger>
        </TabsList>

        <TabsContent value="surveys" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold">Survey Engine</h2>
              <p class="text-sm text-gray-500">Create NPS, CSAT, and CES surveys and review responses.</p>
            </div>
            <Dialog v-model:open="showCreateDialog">
              <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Survey</Button></DialogTrigger>
              <DialogContent class="sm:max-w-2xl">
                <DialogHeader><DialogTitle>Create Survey</DialogTitle></DialogHeader>
                <form @submit.prevent="submitSurvey" class="space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2"><Label>Survey Name *</Label><Input v-model="form.name" placeholder="e.g. Post-Purchase CSAT" required /></div>
                    <div class="space-y-2"><Label>Type *</Label><Select v-model="form.type"><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="NPS">NPS</SelectItem><SelectItem value="CSAT">CSAT</SelectItem><SelectItem value="CES">CES</SelectItem></SelectContent></Select></div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2"><Label>Segment</Label><Select v-model="form.segment_id"><SelectTrigger><SelectValue placeholder="All contacts" /></SelectTrigger><SelectContent><SelectItem value="all">All contacts</SelectItem></SelectContent></Select></div>
                    <div class="space-y-2"><Label>Channel</Label><Select v-model="form.channel"><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="email">Email</SelectItem><SelectItem value="sms">SMS</SelectItem><SelectItem value="in_app">In App</SelectItem></SelectContent></Select></div>
                  </div>
                  <div class="space-y-2"><Label>Primary Question *</Label><Textarea v-model="form.question_text" required rows="2" /></div>
                  <div class="space-y-2"><Label>Follow-Up Question</Label><Textarea v-model="form.follow_up_question" rows="2" /></div>
                  <Button type="submit" class="w-full">Create Survey</Button>
                </form>
              </DialogContent>
            </Dialog>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card v-for="survey in surveys" :key="survey.id" class="hover:shadow-md transition-shadow">
              <CardContent class="pt-6">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2"><ClipboardList class="h-5 w-5 text-blue-500" /><h3 class="font-semibold text-lg">{{ survey.name }}</h3></div>
                  <Badge :class="typeColors[survey.type] || 'bg-gray-100 text-gray-800'">{{ survey.type }}</Badge>
                </div>
                <p class="text-sm text-gray-500 mb-1">Status: <Badge :variant="survey.status === 'active' ? 'default' : 'secondary'">{{ survey.status }}</Badge></p>
                <p class="text-sm text-gray-500">Channel: {{ survey.channel || 'Not set' }}</p>
              </CardContent>
            </Card>
            <Card v-if="!surveys.length"><CardContent class="py-12 text-center text-gray-500 italic">No surveys configured yet.</CardContent></Card>
          </div>

          <Card>
            <CardHeader><CardTitle>Survey Responses</CardTitle></CardHeader>
            <CardContent class="space-y-4">
              <div class="flex flex-wrap items-center gap-4">
                <div class="relative flex-1 min-w-[200px]"><Input v-model="surveySearchQuery" placeholder="Search by contact or survey..." /></div>
                <div class="w-56"><Select v-model="selectedSurvey"><SelectTrigger><SelectValue placeholder="All Surveys" /></SelectTrigger><SelectContent><SelectItem value="all">All Surveys</SelectItem><SelectItem v-for="s in surveyMeta" :key="s.id" :value="s.id">{{ s.name }}</SelectItem></SelectContent></Select></div>
                <div class="w-40"><Select v-model="selectedChannel"><SelectTrigger><SelectValue placeholder="All Channels" /></SelectTrigger><SelectContent><SelectItem value="all">All Channels</SelectItem><SelectItem value="email">Email</SelectItem><SelectItem value="sms">SMS</SelectItem><SelectItem value="portal">Portal</SelectItem></SelectContent></Select></div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card><CardContent class="pt-6"><p class="text-xs text-gray-500">Average Score</p><p class="text-2xl font-bold" :class="surveyAnalytics.avgScore >= 7 ? 'text-emerald-600' : 'text-rose-600'">{{ surveyAnalytics.avgScore.toFixed(2) }}</p></CardContent></Card>
                <Card><CardContent class="pt-6"><p class="text-xs text-gray-500">NPS Score</p><p class="text-2xl font-bold" :class="surveyAnalytics.npsScore >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ surveyAnalytics.npsScore }}</p></CardContent></Card>
                <Card><CardContent class="pt-6"><p class="text-xs text-gray-500">CSAT Score</p><p class="text-2xl font-bold" :class="surveyAnalytics.csatScore >= 50 ? 'text-emerald-600' : 'text-rose-600'">{{ surveyAnalytics.csatScore }}%</p></CardContent></Card>
              </div>

              <Tabs v-model="surveyActiveTab">
                <TabsList><TabsTrigger value="all">All Responses</TabsTrigger><TabsTrigger value="nps">NPS</TabsTrigger><TabsTrigger value="csat">CSAT</TabsTrigger></TabsList>
                <TabsContent value="all" class="space-y-4">
                  <Card><CardContent class="p-0 overflow-x-auto"><table class="w-full text-sm"><thead class="border-b"><tr class="text-left text-gray-500"><th class="p-3">Date</th><th class="p-3">Contact</th><th class="p-3">Type</th><th class="p-3">Score</th><th class="p-3">Category</th></tr></thead><tbody>
                    <tr v-for="r in filteredSurveyResponses" :key="r.id" class="border-b hover:bg-gray-50">
                      <td class="p-3 text-xs">{{ formatDate(r.responded_at) }}</td>
                      <td class="p-3 font-medium">{{ r.contact_name }}</td>
                      <td class="p-3 uppercase text-xs">{{ r.survey_type }}</td>
                      <td class="p-3 font-bold" :class="npsScoreColor(r.score, r.survey_type)">{{ r.score }}</td>
                      <td class="p-3" v-if="r.survey_type === 'nps'"><Badge :class="npsCategoryColor(r.nps_category)" class="capitalize">{{ r.nps_category }}</Badge></td>
                      <td class="p-3" v-else><span class="text-gray-400">—</span></td>
                    </tr>
                  </tbody></table></CardContent></Card>
                </TabsContent>
                <TabsContent value="nps" class="space-y-4">
                  <Card><CardContent class="p-0 overflow-x-auto"><table class="w-full text-sm"><thead class="border-b"><tr class="text-left text-gray-500"><th class="p-3">Contact</th><th class="p-3">Score</th><th class="p-3">Category</th></tr></thead><tbody>
                    <tr v-for="r in filteredSurveyResponses.filter(x => x.survey_type === 'nps')" :key="r.id" class="border-b hover:bg-gray-50">
                      <td class="p-3 font-medium">{{ r.contact_name }}</td>
                      <td class="p-3 font-bold">{{ r.score }}/10</td>
                      <td class="p-3"><Badge :class="npsCategoryColor(r.nps_category)" class="capitalize">{{ r.nps_category }}</Badge></td>
                    </tr>
                  </tbody></table></CardContent></Card>
                </TabsContent>
                <TabsContent value="csat" class="space-y-4">
                  <Card><CardContent class="p-0 overflow-x-auto"><table class="w-full text-sm"><thead class="border-b"><tr class="text-left text-gray-500"><th class="p-3">Contact</th><th class="p-3">Score</th></tr></thead><tbody>
                    <tr v-for="r in filteredSurveyResponses.filter(x => x.survey_type === 'csat')" :key="r.id" class="border-b hover:bg-gray-50">
                      <td class="p-3 font-medium">{{ r.contact_name }}</td>
                      <td class="p-3 font-bold" :class="npsScoreColor(r.score, r.survey_type)">{{ r.score }}/5</td>
                    </tr>
                  </tbody></table></CardContent></Card>
                </TabsContent>
              </Tabs>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="clv" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold">CLV &amp; Churn Analytics</h2>
              <p class="text-sm text-gray-500">Customer lifetime value, segmentation, and churn risk scores.</p>
            </div>
            <div class="flex gap-2">
              <Button v-for="range in ['30d','90d','1y']" :key="range" :variant="timeRange === range ? 'default' : 'outline'" size="sm" @click="timeRange = range">{{ range }}</Button>
              <Button variant="outline" size="sm" @click="router.post('/admin/clv-analytics/recalculate')">Recalculate</Button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <Card><CardContent class="pt-6 flex items-center gap-3"><DollarSign class="h-5 w-5 text-emerald-500" /><div><p class="text-xs text-gray-500">Avg CLV</p><p class="text-lg font-bold">{{ Number(clvStats.avg_clv ?? 0).toLocaleString() }}</p></div></CardContent></Card>
            <Card><CardContent class="pt-6 flex items-center gap-3"><Activity class="h-5 w-5 text-blue-500" /><div><p class="text-xs text-gray-500">Points Issued</p><p class="text-lg font-bold">{{ Number(clvStats.total_points_issued ?? 0).toLocaleString() }}</p></div></CardContent></Card>
            <Card><CardContent class="pt-6 flex items-center gap-3"><TrendingUp class="h-5 w-5 text-purple-500" /><div><p class="text-xs text-gray-500">Redeemed</p><p class="text-lg font-bold">{{ Number(clvStats.total_points_redeemed ?? 0).toLocaleString() }}</p></div></CardContent></Card>
            <Card><CardContent class="pt-6 flex items-center gap-3"><TrendingUp class="h-5 w-5 text-amber-500" /><div><p class="text-xs text-gray-500">Redemption Rate</p><p class="text-lg font-bold">{{ clvStats.redemption_rate ?? 0 }}%</p></div></CardContent></Card>
            <Card><CardContent class="pt-6 flex items-center gap-3"><Users class="h-5 w-5 text-teal-500" /><div><p class="text-xs text-gray-500">Enrollments</p><p class="text-lg font-bold">{{ clvStats.active_enrollments }} / {{ clvStats.total_enrollments }}</p></div></CardContent></Card>
            <Card><CardContent class="pt-6 flex items-center gap-3"><Activity class="h-5 w-5 text-rose-500" /><div><p class="text-xs text-gray-500">Churn Risk</p><p class="text-lg font-bold">{{ clvStats.churn_risk_count ?? 0 }}</p></div></CardContent></Card>
          </div>

          <Card>
            <CardHeader><CardTitle>Top Contacts by CLV</CardTitle></CardHeader>
            <CardContent class="p-0">
              <table class="w-full text-sm">
                <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Contact</th><th class="p-4">Email</th><th class="p-4">CLV Score</th><th class="p-4">LTV</th></tr></thead>
                <tbody>
                  <tr v-for="c in topContacts" :key="c.id" class="border-b hover:bg-gray-50">
                    <td class="p-4 font-medium">{{ c.first_name }} {{ c.last_name }}</td>
                    <td class="p-4 text-gray-600">{{ c.email }}</td>
                    <td class="p-4"><Badge variant="default">{{ Number(c.clv_score ?? 0).toLocaleString() }}</Badge></td>
                    <td class="p-4">{{ Number(c.ltv ?? 0).toLocaleString() }}</td>
                  </tr>
                  <tr v-if="!topContacts.length"><td colspan="4" class="p-8 text-center text-gray-500 italic">No CLV data yet. Run a recalculation.</td></tr>
                </tbody>
              </table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
