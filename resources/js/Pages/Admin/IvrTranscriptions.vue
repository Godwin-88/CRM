<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { AlertTriangle } from 'lucide-vue-next'

defineProps<{
  interactions: { id: string; type: string; contact?: { first_name: string; last_name: string }; subject: string; created_at: string; metadata: any }[]
}>()

const interactions = ref<any[]>([])

const formatPath = (metadata: any) => {
  if (!metadata || !metadata.ivr_path) return 'No path data'
  return metadata.ivr_path.map((step: any) => `${step.menu} → ${step.selection}`).join(' → ')
}
</script>

<template>
  <AppLayout>
    <Head title="IVR Interactions" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center gap-2">
        <AlertTriangle class="h-6 w-6 text-amber-500" />
        <div>
          <h1 class="text-3xl font-bold text-gray-900">IVR Transcripts</h1>
          <p class="text-gray-500">Ingested IVR call transcripts linked to contacts.</p>
        </div>
      </div>

      <div class="space-y-4">
        <Card v-for="interaction in interactions" :key="interaction.id">
          <CardContent class="pt-6 space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium">
                  {{ interaction.contact ? `${interaction.contact.first_name} ${interaction.contact.last_name}` : 'Unmatched' }}
                </p>
                <p class="text-xs text-gray-500">{{ interaction.created_at }}</p>
              </div>
              <Badge variant="outline">IVR</Badge>
            </div>
            <p class="text-sm text-gray-700">{{ interaction.subject }}</p>
            <div class="bg-gray-50 border rounded p-3">
              <p class="text-xs font-medium text-gray-500 mb-1">IVR Path</p>
              <p class="text-sm">{{ formatPath(interaction.metadata) }}</p>
            </div>
            <p class="text-sm text-gray-600">{{ interaction.body }}</p>
          </CardContent>
        </Card>
        <div v-if="!interactions.length" class="text-sm text-gray-500 text-center py-8">No IVR interactions ingested yet.</div>
      </div>
    </div>
  </AppLayout>
</template>
