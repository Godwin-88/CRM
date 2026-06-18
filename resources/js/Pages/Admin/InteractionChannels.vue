<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Phone, MessageSquare, Mail } from 'lucide-vue-next'

const props = defineProps<{ channels: { id: string; name: string; description?: string; is_active: boolean }[] }>()
const channels = ref(props.channels)

const iconFor = (name: string) => {
  if (name.toLowerCase().includes('phone')) return Phone
  if (name.toLowerCase().includes('chat')) return MessageSquare
  if (name.toLowerCase().includes('email')) return Mail
  return Phone
}
</script>

<template>
  <AppLayout>
    <Head title="Interaction Channels" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Interaction Channels</h1>
        <p class="text-gray-500">Manage channels for omni-channel customer engagement.</p>
      </div>
      <Card>
        <CardHeader><CardTitle>Channels</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Channel</th><th class="p-4">Description</th><th class="p-4">Status</th></tr></thead>
            <tbody>
              <tr v-for="ch in channels" :key="ch.id" class="border-b hover:bg-gray-50">
                <td class="p-4"><div class="flex items-center gap-2"><component :is="iconFor(ch.name)" class="h-4 w-4" />{{ ch.name }}</div></td>
                <td class="p-4 text-gray-600">{{ ch.description ?? '-' }}</td>
                <td class="p-4"><Badge :variant="ch.is_active ? 'default' : 'outline'">{{ ch.is_active ? 'Active' : 'Inactive' }}</Badge></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
