<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const props = defineProps<{
  templates: { id: string; name: string }[]
  contacts: { id: string; first_name: string; last_name: string; email: string }[]
  deals: { id: string; title: string; contact: { first_name: string; last_name: string } }[]
}>()

const form = ref({
  contact_id: '',
  deal_id: '',
  status: 'draft',
  notes: '',
  valid_until: '',
  items: [{ description: '', quantity: 1, unit_price: 0 }],
})

const addItem = () => {
  form.value.items.push({ description: '', quantity: 1, unit_price: 0 })
}
const removeItem = (index: number) => {
  if (form.value.items.length > 1) form.value.items.splice(index, 1)
}

const total = computed(() => form.value.items.reduce((a, b) => a + (Number(b.quantity) * Number(b.unit_price)), 0))

const submit = () => {
  router.post('/admin/quotes', {
    ...form.value,
    items: form.value.items.map((it) => ({ ...it, quantity: Number(it.quantity), unit_price: Number(it.unit_price) })),
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Create Quote" />
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Create Quote</h1>
        <p class="text-gray-500">Build a quote with line items and send for approval.</p>
      </div>
      <Card>
        <CardHeader><CardTitle>Quote Details</CardTitle></CardHeader>
        <CardContent class="space-y-4">
          <select v-model="form.contact_id" class="w-full p-2 border rounded"><option value="">Select contact</option><option v-for="c in contacts" :key="c.id" :value="c.id">{{ c.first_name }} {{ c.last_name }} ({{ c.email }})</option></select>
          <select v-model="form.deal_id" class="w-full p-2 border rounded"><option value="">Select deal (optional)</option><option v-for="d in deals" :key="d.id" :value="d.id">{{ d.title }}</option></select>
          <select v-model="form.status" class="w-full p-2 border rounded">
            <option value="draft">Draft</option><option value="sent">Sent</option><option value="accepted">Accepted</option><option value="rejected">Rejected</option>
          </select>
          <textarea v-model="form.notes" class="w-full p-2 border rounded" rows="3" placeholder="Notes"></textarea>
          <Input v-model="form.valid_until" type="date" />
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Line Items</CardTitle></CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-3">
              <Input v-model="item.description" placeholder="Description" class="col-span-5" />
              <Input v-model="item.quantity" type="number" placeholder="Qty" class="col-span-2" />
              <Input v-model="item.unit_price" type="number" placeholder="Unit Price" class="col-span-3" />
              <Button variant="ghost" class="col-span-2" @click="removeItem(idx)">Remove</Button>
            </div>
            <Button variant="outline" size="sm" @click="addItem">Add Line</Button>
            <div class="text-right text-sm text-gray-600">Total: {{ total.toLocaleString() }}</div>
          </div>
        </CardContent>
      </Card>

      <Button @click="submit">Create Quote</Button>
    </div>
  </AppLayout>
</template>
