<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Plus, Filter, BarChart3 } from 'lucide-vue-next'

const props = defineProps<{
  reports?: Array
}>()

const showNewReportModal = ref(false)
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
          <CardTitle>Report Library</CardTitle>
        </CardHeader>
        <CardContent>
          <table class="w-full text-sm">
            <thead class="border-b">
              <tr class="text-left text-gray-500">
                <th class="p-3">Name</th>
                <th class="p-3">Entity</th>
                <th class="p-3">Owner</th>
                <th class="p-3">Visibility</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="report in props.reports?.data ?? []" :key="report.id" class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">{{ report.name }}</td>
                <td class="p-3">{{ report.entity_type }}</td>
                <td class="p-3">{{ report.owner?.name ?? '-' }}</td>
                <td class="p-3">
                  <Badge :variant="report.visibility === 'shared' ? 'default' : 'secondary'">
                    {{ report.visibility }}
                  </Badge>
                </td>
                <td class="p-3">
                  <Link :href="`/admin/analytics/report-builder/${report.id}`" class="text-blue-600 hover:underline">
                    Run
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>