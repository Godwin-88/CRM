<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Star, TrendingUp, Search } from 'lucide-vue-next'

interface SurveyResponse {
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

interface Survey {
  id: string
  name: string
  type: string
}

const props = defineProps<{
  responses: SurveyResponse[]
  surveys: Survey[]
}>()

const responses = ref(props.responses)
const surveys = ref(props.surveys)
const searchQuery = ref('')
const selectedSurvey = ref('')
const selectedChannel = ref('')
const activeTab = ref('all')

const filteredResponses = computed(() => {
  return responses.value.filter(r => {
    const matchesSearch = !searchQuery.value ||
      r.contact_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.survey_name.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesSurvey = selectedSurvey.value === 'all' || !selectedSurvey.value || r.survey_id === selectedSurvey.value
    const matchesChannel = selectedChannel.value === 'all' || !selectedChannel.value || r.channel === selectedChannel.value
    const matchesTab = activeTab.value === 'all' || r.survey_type === activeTab.value
    return matchesSearch && matchesSurvey && matchesChannel && matchesTab
  })
})

const npsCategoryColor = (category?: string) => {
  switch (category) {
    case 'promoter': return 'bg-emerald-100 text-emerald-800'
    case 'passive': return 'bg-amber-100 text-amber-800'
    case 'detractor': return 'bg-rose-100 text-rose-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const npsScoreColor = (score: number, type: string) => {
  if (type === 'nps') {
    if (score >= 9) return 'text-emerald-600 font-bold'
    if (score >= 7) return 'text-amber-600'
    return 'text-rose-600'
  }
  if (type === 'csat') {
    if (score >= 4) return 'text-emerald-600 font-bold'
    return 'text-rose-600'
  }
  return 'text-gray-900'
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleString()
}

const analytics = computed(() => {
  const total = responses.value.length
  if (total === 0) return { avgScore: 0, npsScore: 0, csatScore: 0 }

  const avgScore = Math.round(responses.value.reduce((sum, r) => sum + r.score, 0) / total * 100) / 100

  const promoters = responses.value.filter(r => r.nps_category === 'promoter').length
  const detractors = responses.value.filter(r => r.nps_category === 'detractor').length
  const npsScore = Math.round(((promoters - detractors) / total) * 100)

  const satisfied = responses.value.filter(r => r.score >= 4).length
  const csatScore = Math.round((satisfied / total) * 100)

  return { avgScore, npsScore, csatScore }
})
</script>

<template>
  <AppLayout>
    <Head title="Survey Responses" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Survey Responses</h1>
        <p class="text-gray-500">View and analyze all customer survey feedback.</p>
      </div>

      <!-- Analytics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-blue-100 rounded-full"><Star class="h-5 w-5 text-blue-600" /></div>
              <div>
                <p class="text-sm text-gray-500">Average Score</p>
                <p class="text-2xl font-bold" :class="analytics.avgScore >= 7 ? 'text-emerald-600' : 'text-rose-600'">{{ analytics.avgScore.toFixed(2) }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-purple-100 rounded-full"><TrendingUp class="h-5 w-5 text-purple-600" /></div>
              <div>
                <p class="text-sm text-gray-500">NPS Score</p>
                <p class="text-2xl font-bold" :class="analytics.npsScore >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ analytics.npsScore }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-teal-100 rounded-full"><Star class="h-5 w-5 text-teal-600" /></div>
              <div>
                <p class="text-sm text-gray-500">CSAT Score</p>
                <p class="text-2xl font-bold" :class="analytics.csatScore >= 50 ? 'text-emerald-600' : 'text-rose-600'">{{ analytics.csatScore }}%</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input v-model="searchQuery" placeholder="Search by contact or survey..." class="pl-10" />
              </div>
            </div>
            <div class="w-56">
              <Select v-model="selectedSurvey">
                <SelectTrigger><SelectValue placeholder="All Surveys" /></SelectTrigger>
<SelectContent>
                   <SelectItem value="all">All Surveys</SelectItem>
                   <SelectItem v-for="survey in surveys" :key="survey.id" :value="survey.id">{{ survey.name }}</SelectItem>
                 </SelectContent>
               </Select>
             </div>
             <div class="w-40">
               <Select v-model="selectedChannel">
                 <SelectTrigger><SelectValue placeholder="All Channels" /></SelectTrigger>
                 <SelectContent>
                   <SelectItem value="all">All Channels</SelectItem>
                  <SelectItem value="email">Email</SelectItem>
                  <SelectItem value="sms">SMS</SelectItem>
                  <SelectItem value="portal">Portal</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Tabs -->
      <Tabs v-model="activeTab">
        <TabsList>
          <TabsTrigger value="all">All Responses</TabsTrigger>
          <TabsTrigger value="nps">NPS</TabsTrigger>
          <TabsTrigger value="csat">CSAT</TabsTrigger>
        </TabsList>
        <TabsContent value="all" class="space-y-4">
          <ResponsesTable :responses="filteredResponses" :nps-category-color="npsCategoryColor" :nps-score-color="npsScoreColor" :format-date="formatDate" />
        </TabsContent>
        <TabsContent value="nps" class="space-y-4">
          <ResponsesTable :responses="filteredResponses.filter(r => r.survey_type === 'nps')" :nps-category-color="npsCategoryColor" :nps-score-color="npsScoreColor" :format-date="formatDate" />
        </TabsContent>
        <TabsContent value="csat" class="space-y-4">
          <ResponsesTable :responses="filteredResponses.filter(r => r.survey_type === 'csat')" :nps-category-color="npsCategoryColor" :nps-score-color="npsScoreColor" :format-date="formatDate" />
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>

<script lang="ts">
export default {
  components: {
    ResponsesTable: {
      props: ['responses', 'npsCategoryColor', 'npsScoreColor', 'formatDate'],
      template: `
        <Card>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="p-4">Date</TableHead>
                  <TableHead class="p-4">Survey</TableHead>
                  <TableHead class="p-4">Contact</TableHead>
                  <TableHead class="p-4">Type</TableHead>
                  <TableHead class="p-4">Score</TableHead>
                  <TableHead class="p-4">Category</TableHead>
                  <TableHead class="p-4">Channel</TableHead>
                  <TableHead class="p-4">Response</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="response in responses" :key="response.id" class="border-b hover:bg-gray-50">
                  <TableCell class="p-4 text-sm">{{ formatDate(response.responded_at) }}</TableCell>
                  <TableCell class="p-4 font-medium">{{ response.survey_name }}</TableCell>
                  <TableCell class="p-4">
                    <div>
                      <p class="font-medium text-sm">{{ response.contact_name }}</p>
                      <p class="text-xs text-gray-500">{{ response.contact_email }}</p>
                    </div>
                  </TableCell>
                  <TableCell class="p-4"><Badge variant="outline" class="uppercase text-xs">{{ response.survey_type }}</Badge></TableCell>
                  <TableCell class="p-4">
                    <span class="text-lg font-bold" :class="npsScoreColor(response.score, response.survey_type)">{{ response.score }}</span>
                    <span v-if="response.survey_type === 'nps'" class="text-xs text-gray-400">/10</span>
                    <span v-if="response.survey_type === 'csat'" class="text-xs text-gray-400">/5</span>
                  </TableCell>
                  <TableCell class="p-4" v-if="response.survey_type === 'nps'">
                    <Badge :class="npsCategoryColor(response.nps_category)" class="capitalize">{{ response.nps_category || '—' }}</Badge>
                  </TableCell>
                  <TableCell class="p-4" v-else><span class="text-gray-400">—</span></TableCell>
                  <TableCell class="p-4 text-sm text-gray-600">{{ response.channel }}</TableCell>
                  <TableCell class="p-4 text-sm max-w-[200px] truncate" :title="response.open_text_answer || ''">
                    {{ response.open_text_answer || '—' }}
                  </TableCell>
                </TableRow>
                <TableRow v-if="!responses.length">
                  <TableCell colspan="8" class="p-8 text-center text-gray-500 italic">No responses found.</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      `,
    },
  },
}
</script>
