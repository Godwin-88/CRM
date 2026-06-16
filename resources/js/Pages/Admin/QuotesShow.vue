<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const props = defineProps<{
  quote: {
    id: string
    contact: { email: string }
    deal?: { title: string }
    status: string
    valid_until: string
    notes?: string
    items: { description: string; quantity: number; unit_price: number; subtotal: number }[]
  }
}>()
</script>

<template>
  <AppLayout>
    <Head title="Quote" />
    <div class="max-w-5xl mx-auto space-y-6">
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
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Description</TableHead>
                <TableHead class="p-4">Qty</TableHead>
                <TableHead class="p-4">Unit Price</TableHead>
                <TableHead class="p-4">Subtotal</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in quote.items" :key="item.description" class="border-b">
                <TableCell class="p-4">{{ item.description }}</TableCell>
                <TableCell class="p-4">{{ item.quantity }}</TableCell>
                <TableCell class="p-4">{{ item.unit_price.toLocaleString() }}</TableCell>
                <TableCell class="p-4">{{ item.subtotal.toLocaleString() }}</TableCell>
              </TableRow>
              <TableRow class="font-semibold">
                <TableCell colspan="3" class="p-4 text-right">Total</TableCell>
                <TableCell class="p-4">{{ quote.items.reduce((a, b) => a + b.subtotal, 0).toLocaleString() }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
