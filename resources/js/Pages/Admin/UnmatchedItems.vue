<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

const props = defineProps<{ items: { id: string; channel: { name: string }; created_at: string }[] }>()
const items = ref(props.items)
</script>

<template>
  <AppLayout>
    <Head title="Unmatched Items" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Unmatched Items</h1>
        <p class="text-gray-500">Interactions and records without a matched contact.</p>
      </div>
      <Card>
        <CardHeader><CardTitle>Unmatched Records</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">ID</th><th class="p-4">Channel</th><th class="p-4">Created</th></tr></thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" class="border-b hover:bg-gray-50">
                <td class="p-4">{{ item.id }}</td>
                <td class="p-4">{{ item.channel?.name ?? '-' }}</td>
                <td class="p-4">{{ item.created_at }}</td>
              </tr>
              <tr v-if="!items.length"><td colspan="3" class="p-8 text-center text-gray-500">No unmatched items.</td></tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
