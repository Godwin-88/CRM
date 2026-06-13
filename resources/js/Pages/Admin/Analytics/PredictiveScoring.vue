<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Zap, Settings, Filter } from 'lucide-vue-next'

const props = defineProps<{
  weights?: Record<string, number>
}>()

const timeRange = ref<'30d' | '90d' | '1y'>('30d')
</script>

<template>
  <AppLayout>
    <Head title="Predictive Deal Scoring" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Predictive Deal Scoring</h1>
          <p class="text-gray-500">AI-powered deal likelihood to close</p>
        </div>
        <div class="flex gap-2">
          <Button variant="ghost" size="sm">
            <Filter class="h-4 w-4" />
          </Button>
          <Button variant="outline" size="sm">
            <Settings class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Scoring Weights</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-for="(weight, signal) in props.weights" :key="signal" class="flex items-center justify-between">
            <span class="text-sm text-gray-600 capitalize">{{ signal.replace('_', ' ') }}</span>
            <Badge>{{ weight }}%</Badge>
          </div>
        </CardContent>
      </Card>

      <div class="grid grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500">Cold (0-25)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-gray-400" />
          <p class="text-sm">Low priority</p>
        </div>
        <div class="text-center p-4 bg-blue-50 rounded-lg">
          <p class="text-xs text-gray-500">Warm (26-50)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-blue-400" />
          <p class="text-sm">Medium priority</p>
        </div>
        <div class="text-center p-4 bg-amber-50 rounded-lg">
          <p class="text-xs text-gray-500">Hot (51-75)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-amber-500" />
          <p class="text-sm">High priority</p>
        </div>
        <div class="text-center p-4 bg-red-50 rounded-lg">
          <p class="text-xs text-gray-500">Very Hot (76-100)</p>
          <Zap class="h-8 w-8 mx-auto my-2 text-red-500" />
          <p class="text-sm">Critical priority</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>