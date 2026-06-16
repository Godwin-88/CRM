<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
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
import { UserPlus, Mail, TrendingUp, Users, RefreshCw } from 'lucide-vue-next'

interface ContactRecord {
  id: string
  contact_name: string
  contact_email: string
  status: string
  enrolled_at: string
  re_engaged_at?: string
  config_name?: string
}

const props = defineProps<{
  analytics: {
    total_enrolled: number
    total_re_engaged: number
    total_completed: number
    total_dormant: number
    re_engagement_rate: number
    by_config: { contact_type: string; total: number }[]
  }
  contacts: ContactRecord[]
}>()

const analytics = ref(props.analytics)
const contacts = ref(props.contacts)

const currentPage = ref(1)
const perPage = 25

const paginatedContacts = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return contacts.value.slice(start, start + perPage)
})

const totalPages = computed(() => Math.ceil(contacts.value.length / perPage))

const statusVariant = (status: string) => {
  switch (status) {
    case 'enrolled': return 'default'
    case 're_engaged': return 'default'
    case 'completed': return 'secondary'
    case 'dormant': return 'outline'
    default: return 'secondary'
  }
}

const statusIcon = (status: string) => {
  switch (status) {
    case 'enrolled': return '📧'
    case 're_engaged': return '✅'
    case 'completed': return '🎉'
    case 'dormant': return '😴'
    default: return '•'
  }
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString()
}

const refreshAnalytics = async () => {
  await router.get('/admin/reactivation/analytics', {}, {
    only: ['analytics', 'contacts'],
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Reactivation Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Reactivation Analytics</h1>
          <p class="text-gray-500">Track re-engagement campaign performance and ROI.</p>
        </div>
        <Button variant="outline" size="sm" @click="refreshAnalytics"><RefreshCw class="h-4 w-4 mr-2" />Refresh</Button>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <div class="p-2 bg-blue-100 rounded-full"><Mail class="h-5 w-5 text-blue-600" /></div>
            <div>
              <p class="text-sm text-gray-500">Total Enrolled</p>
              <p class="text-xl font-bold">{{ analytics.total_enrolled }}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-full"><TrendingUp class="h-5 w-5 text-emerald-600" /></div>
            <div>
              <p class="text-sm text-gray-500">Re-engaged</p>
              <p class="text-xl font-bold">{{ analytics.total_re_engaged }}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <div class="p-2 bg-purple-100 rounded-full"><UserPlus class="h-5 w-5 text-purple-600" /></div>
            <div>
              <p class="text-sm text-gray-500">Completed</p>
              <p class="text-xl font-bold">{{ analytics.total_completed }}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <div class="p-2 bg-gray-100 rounded-full"><Users class="h-5 w-5 text-gray-600" /></div>
            <div>
              <p class="text-sm text-gray-500">Dormant</p>
              <p class="text-xl font-bold">{{ analytics.total_dormant }}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6 flex items-center gap-3">
            <div class="p-2 bg-teal-100 rounded-full"><Mail class="h-5 w-5 text-teal-600" /></div>
            <div>
              <p class="text-sm text-gray-500">Re-engagement Rate</p>
              <p class="text-xl font-bold text-emerald-600">{{ analytics.re_engagement_rate }}%</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- By Config Breakdown -->
      <Card v-if="analytics.by_config && analytics.by_config.length">
        <CardHeader><CardTitle>By Campaign</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Contact Type</TableHead>
                <TableHead class="p-4">Total in Campaign</TableHead>
                <TableHead class="p-4">Percentage</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in analytics.by_config" :key="item.contact_type" class="border-b">
                <TableCell class="p-4 font-medium capitalize">{{ item.contact_type }}</TableCell>
                <TableCell class="p-4">{{ item.total }}</TableCell>
                <TableCell class="p-4">{{ analytics.total_enrolled ? Math.round((item.total / analytics.total_enrolled) * 100) : 0 }}%</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Contacts Table -->
      <Card>
        <CardHeader><CardTitle>Reactivation Contacts</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Status</TableHead>
                <TableHead class="p-4">Campaign</TableHead>
                <TableHead class="p-4">Enrolled Date</TableHead>
                <TableHead class="p-4">Re-engaged Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="contact in paginatedContacts" :key="contact.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-4">
                  <div>
                    <p class="font-medium">{{ contact.contact_name }}</p>
                    <p class="text-xs text-gray-500">{{ contact.contact_email }}</p>
                  </div>
                </TableCell>
                <TableCell class="p-4">
                  <Badge :variant="statusVariant(contact.status)" class="flex items-center gap-1 w-fit">
                    <span>{{ statusIcon(contact.status) }}</span>
                    <span class="capitalize">{{ contact.status }}</span>
                  </Badge>
                </TableCell>
                <TableCell class="p-4 text-sm text-gray-600">{{ contact.config_name || '—' }}</TableCell>
                <TableCell class="p-4 text-sm">{{ formatDate(contact.enrolled_at) }}</TableCell>
                <TableCell class="p-4 text-sm">{{ contact.re_engaged_at ? formatDate(contact.re_engaged_at) : '—' }}</TableCell>
              </TableRow>
              <TableRow v-if="!contacts.length">
                <TableCell colspan="5" class="p-8 text-center text-gray-500 italic">No reactivation contacts yet.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-2">
        <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="currentPage--">Previous</Button>
        <span class="text-sm text-gray-500">Page {{ currentPage }} of {{ totalPages }}</span>
        <Button variant="outline" size="sm" :disabled="currentPage === totalPages" @click="currentPage++">Next</Button>
      </div>
    </div>
  </AppLayout>
</template>
