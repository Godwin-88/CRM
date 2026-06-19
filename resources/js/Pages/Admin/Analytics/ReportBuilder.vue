<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, Filter, BarChart3 } from 'lucide-vue-next'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

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

const reportForm = useForm({
  name: '',
  description: '',
  entity_type: 'deals',
  visibility: 'private',
  fields: '',
  group_by: '',
  chart_type: 'table',
})

const runReport = (reportId: string) => {
  router.post(`/api/v1/reports/${reportId}/run`, {}, { preserveState: true })
}

const createReport = () => {
  reportForm
    .transform((data) => ({
      ...data,
      fields: data.fields.split(',').map((field) => field.trim()).filter(Boolean),
    }))
    .post('/admin/analytics/report-builder', {
      preserveScroll: true,
      onSuccess: () => {
        showNewReportModal.value = false
        reportForm.reset()
      },
    })
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

      <Dialog v-model:open="showNewReportModal">
        <DialogContent class="sm:max-w-[560px]">
          <DialogHeader>
            <DialogTitle>Create report</DialogTitle>
            <DialogDescription>Save a reusable report definition that can be run, scheduled, and exported.</DialogDescription>
          </DialogHeader>

          <form @submit.prevent="createReport" class="space-y-4">
            <div>
              <Label for="report-name">Name</Label>
              <Input id="report-name" v-model="reportForm.name" placeholder="Pipeline velocity" />
              <p v-if="reportForm.errors.name" class="mt-1 text-sm text-red-600">{{ reportForm.errors.name }}</p>
            </div>

            <div>
              <Label for="report-description">Description</Label>
              <Input id="report-description" v-model="reportForm.description" placeholder="Open deals grouped by owner" />
              <p v-if="reportForm.errors.description" class="mt-1 text-sm text-red-600">{{ reportForm.errors.description }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <Label for="report-entity">Entity</Label>
                <Select v-model="reportForm.entity_type">
                  <SelectTrigger>
                    <SelectValue placeholder="Select entity" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="deals">Deals</SelectItem>
                    <SelectItem value="accounts">Accounts</SelectItem>
                    <SelectItem value="contacts">Contacts</SelectItem>
                    <SelectItem value="tickets">Tickets</SelectItem>
                    <SelectItem value="contracts">Contracts</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="reportForm.errors.entity_type" class="mt-1 text-sm text-red-600">{{ reportForm.errors.entity_type }}</p>
              </div>

              <div>
                <Label for="report-visibility">Visibility</Label>
                <Select v-model="reportForm.visibility">
                  <SelectTrigger>
                    <SelectValue placeholder="Select visibility" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="private">Private</SelectItem>
                    <SelectItem value="shared">Shared</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="reportForm.errors.visibility" class="mt-1 text-sm text-red-600">{{ reportForm.errors.visibility }}</p>
              </div>
            </div>

            <div>
              <Label for="report-fields">Fields</Label>
              <Input id="report-fields" v-model="reportForm.fields" placeholder="title, value, stage, owner" />
              <p class="mt-1 text-xs text-gray-500">Comma-separated fields to include in the report output.</p>
              <p v-if="reportForm.errors.fields" class="mt-1 text-sm text-red-600">{{ reportForm.errors.fields }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <Label for="report-group">Group by</Label>
                <Input id="report-group" v-model="reportForm.group_by" placeholder="owner" />
                <p v-if="reportForm.errors.group_by" class="mt-1 text-sm text-red-600">{{ reportForm.errors.group_by }}</p>
              </div>

              <div>
                <Label for="report-chart">Default chart</Label>
                <Select v-model="reportForm.chart_type">
                  <SelectTrigger>
                    <SelectValue placeholder="Select chart" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="table">Table</SelectItem>
                    <SelectItem value="bar">Bar</SelectItem>
                    <SelectItem value="line">Line</SelectItem>
                    <SelectItem value="pie">Pie</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="reportForm.errors.chart_type" class="mt-1 text-sm text-red-600">{{ reportForm.errors.chart_type }}</p>
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" @click="showNewReportModal = false">Cancel</Button>
              <Button type="submit" :disabled="reportForm.processing">Create report</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

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
