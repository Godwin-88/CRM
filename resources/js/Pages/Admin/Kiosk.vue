<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Plus } from 'lucide-vue-next'

const props = defineProps<{ integrations: any[] }>()
const integrations = ref(props.integrations)
const showCreateDialog = ref(false)
const newIntegration = ref({ name: '', api_key: '' })

const submitIntegration = () => {
  integrations.value.push({ ...newIntegration.value, id: Date.now(), is_active: true })
  showCreateDialog.value = false
  newIntegration.value = { name: '', api_key: '' }
}
</script>

<template>
  <AppLayout>
    <Head title="Kiosk" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Kiosk</h1>
          <p class="text-gray-500">Self-service kiosk registrations and keys.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Add Integration</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Add Kiosk Integration</DialogTitle></DialogHeader>
            <form @submit.prevent="submitIntegration" class="space-y-4">
              <div class="space-y-2"><Label>Name</Label><Input v-model="newIntegration.name" placeholder="Kiosk name" required /></div>
              <div class="space-y-2"><Label>API Key</Label><Input v-model="newIntegration.api_key" placeholder="API Key" required /></div>
              <Button type="submit" class="w-full">Create</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <Card>
        <CardHeader><CardTitle>Kiosks</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Name</TableHead>
                <TableHead class="p-4">API Key</TableHead>
                <TableHead class="p-4">Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in integrations" :key="item.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-4 font-medium">{{ item.name }}</TableCell>
                <TableCell class="p-4 font-mono text-xs">{{ item.api_key }}</TableCell>
                <TableCell class="p-4"><Badge :variant="item.is_active ? 'default' : 'outline'">{{ item.is_active ? 'Active' : 'Inactive' }}</Badge></TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
