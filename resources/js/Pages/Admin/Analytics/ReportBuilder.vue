<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, Filter, BarChart3 } from 'lucide-vue-next'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const props = defineProps<{
  role?: string
  report_definitions?: {
    data?: Array<{
      id: string
      name: string
      description?: string | null
      owner?: { name?: string } | null
      visibility: 'private' | 'shared'
      entity_type: string
      chart_type?: string | null
      group_by?: string | null
      created_at: string
    }>
    links?: Array<{ url?: string | null; label: string; active: boolean }>
  }
  export_formats?: string[]
}>()

const showNewReportModal = ref(false)

const runReport = (reportId: string) => {
  router.post(`/api/v1/reports/${reportId}/run`, {}, { preserveState: true })
}
</script>

<template>
  <AppLayout>
    <Head title="Report Builder" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Report Builder</h1>
          <p class="text-gray-500">Create, save, and schedule custom reports</p>
        </div>
        <div class="flex gap-2">
          <Button variant="ghost" size="sm">
            <Filter class="h-4 w-4" />
          </Button>
          <Button variant="default" size="sm" @click="showNewReportModal = true">
            <Plus class="h-4 w-4 mr-2" />
            New Report
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><BarChart3 class="h-5 w-5" /> Report Library</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-3">Name</TableHead>
                <TableHead class="p-3">Entity</TableHead>
                <TableHead class="p-3">Owner</TableHead>
                <TableHead class="p-3">Visibility</TableHead>
                <TableHead class="p-3">Chart</TableHead>
                <TableHead class="p-3">Created</TableHead>
                <TableHead class="p-3">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="report in props.report_definitions?.data ?? []" :key="report.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-3 font-medium">{{ report.name }}</TableCell>
                <TableCell class="p-3">{{ report.entity_type }}</TableCell>
                <TableCell class="p-3">{{ report.owner?.name ?? '-' }}</TableCell>
                <TableCell class="p-3">
                  <Badge :variant="report.visibility === 'shared' ? 'default' : 'secondary'">
                    {{ report.visibility }}
                  </Badge>
                </TableCell>
                <TableCell class="p-3">{{ report.chart_type ?? report.group_by ?? '-' }}</TableCell>
                <TableCell class="p-3">{{ report.created_at }}</TableCell>
                <TableCell class="p-3">
                  <div class="flex gap-2">
                    <Button variant="ghost" size="sm" @click="runReport(report.id)">Run</Button>
                    <Link :href="`/api/v1/reports/${report.id}/export/csv`" class="rounded border px-3 py-1 text-sm">CSV</Link>
                    <Link :href="`/api/v1/reports/${report.id}/export/pdf`" class="rounded border px-3 py-1 text-sm">PDF</Link>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-if="!(props.report_definitions?.data ?? []).length" class="p-4 text-sm text-gray-500">No reports found.</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
