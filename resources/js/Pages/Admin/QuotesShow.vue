<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { format } from 'date-fns'

const props = defineProps<{
  quote: {
    id: string
    status: string
    notes: string
    valid_until?: string
    contact: { first_name: string; last_name: string; email: string }
    deal?: { title: string }
    items: { description: string; quantity: number; unit_price: number; subtotal: number }[]
  }
}>()

const quote = computed(() => props.quote)
</script>

<template>
  <AppLayout>
    <Head title="Quote Details" />
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Quote {{ quote.id }}</h1>
        <p class="text-gray-500">{{ quote.contact.first_name }} {{ quote.contact.last_name }}</p>
      </div>

      <Card>
        <CardHeader><CardTitle>Details</CardTitle></CardHeader>
        <CardContent class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">Contact</span><span>{{ quote.contact.email }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">Deal</span><span>{{ quote.deal?.title ?? 'None' }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">Status</span><Badge>{{ quote.status }}</Badge></div>
          <div class="flex justify-between"><span class="text-gray-500">Valid Until</span><span>{{ quote.valid_until ? new Date(quote.valid_until).toLocaleDateString() : '-' }}</span></div>
          <div class="text-gray-700 mt-2">{{ quote.notes }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Line Items</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Description</th><th class="p-4">Qty</th><th class="p-4">Unit Price</th><th class="p-4">Subtotal</th></tr></thead>
            <tbody>
              <tr v-for="item in quote.items" :key="item.description" class="border-b">
                <td class="p-4">{{ item.description }}</td>
                <td class="p-4">{{ item.quantity }}</td>
                <td class="p-4">{{ item.unit_price.toLocaleString() }}</td>
                <td class="p-4">{{ item.subtotal.toLocaleString() }}</td>
              </tr>
              <tr class="font-semibold"><td colspan="3" class="p-4 text-right">Total</td><td class="p-4">{{ quote.items.reduce((a, b) => a + b.subtotal, 0).toLocaleString() }}</td></tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
