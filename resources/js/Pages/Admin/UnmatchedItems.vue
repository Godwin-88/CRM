<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
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
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">ID</TableHead>
                <TableHead class="p-4">Channel</TableHead>
                <TableHead class="p-4">Created</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in items" :key="item.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-4">{{ item.id }}</TableCell>
                <TableCell class="p-4">{{ item.channel?.name ?? '-' }}</TableCell>
                <TableCell class="p-4">{{ item.created_at }}</TableCell>
              </TableRow>
              <TableRow v-if="!items.length">
                <TableCell colspan="3" class="p-8 text-center text-gray-500">No unmatched items.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
