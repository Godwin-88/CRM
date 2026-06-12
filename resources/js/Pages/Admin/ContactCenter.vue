<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{ channels: { id: string; name: string; is_active: boolean }[]; agents: { id: string; name: string }[] }>()
const channels = ref(props.channels)
const agents = ref(props.agents)
</script>

<template>
  <AppLayout>
    <Head title="Contact Center" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Contact Center</h1>
        <p class="text-gray-500">Configure staffing and channels.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>Channels</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="ch in channels" :key="ch.id" class="flex items-center justify-between border rounded-lg p-3">
                <span class="font-medium">{{ ch.name }}</span>
                <Badge :variant="ch.is_active ? 'default' : 'outline'">{{ ch.is_active ? 'Active' : 'Inactive' }}</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Agents</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div v-for="agent in agents" :key="agent.id" class="border rounded-lg p-3">
                <span class="font-medium">{{ agent.name }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
