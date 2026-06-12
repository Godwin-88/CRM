<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Plus } from 'lucide-vue-next'

const props = defineProps<{ integrations: { id: string; name: string; is_active: boolean; api_key: string }[] }>()
const integrations = ref(props.integrations)
const showCreateDialog = ref(false)
const newIntegration = ref({ name: '', api_key: '', is_active: true })

const submitIntegration = async () => {
  router.post('/admin/omni/kiosk', newIntegration.value, {
    onSuccess: () => { showCreateDialog.value = false },
  })
}
</script>

<template>
  <AppLayout>
    <Head title="Kiosk Integrations" />
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Kiosk Integrations</h1>
          <p class="text-gray-500">Self-service kiosk registrations and keys.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Add Integration</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Add Kiosk Integration</DialogTitle></DialogHeader>
            <form @submit.prevent="submitIntegration" class="space-y-4">
              <Input v-model="newIntegration.name" placeholder="Kiosk name" required />
              <Input v-model="newIntegration.api_key" placeholder="API Key" required />
              <Button type="submit" class="w-full">Create</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>
      <Card>
        <CardHeader><CardTitle>Kiosks</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-sm">
            <thead class="border-b"><tr class="text-left text-gray-500"><th class="p-4">Name</th><th class="p-4">API Key</th><th class="p-4">Status</th></tr></thead>
            <tbody>
              <tr v-for="item in integrations" :key="item.id" class="border-b hover:bg-gray-50">
                <td class="p-4 font-medium">{{ item.name }}</td>
                <td class="p-4 font-mono text-xs">{{ item.api_key }}</td>
                <td class="p-4"><Badge :variant="item.is_active ? 'default' : 'outline'">{{ item.is_active ? 'Active' : 'Inactive' }}</Badge></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
