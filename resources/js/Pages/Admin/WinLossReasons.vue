<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface WinLossReason {
  id: string;
  label: string;
  type: 'won' | 'lost';
  is_active: boolean;
}

const props = defineProps<{
  reasons: WinLossReason[];
}>();

const reasons = ref(props.reasons);
const isCreateOpen = ref(false);

const newReason = ref({
  label: '',
  type: 'won',
});

const createReason = async () => {
  const response = await fetch('/api/v1/win-loss-reasons', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newReason.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  }
};

const toggleActive = async (reason: WinLossReason) => {
  await fetch(`/api/v1/win-loss-reasons/${reason.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify({ is_active: !reason.is_active }),
  });
  router.reload();
};
</script>

<template>
  <AppLayout>
    <Head title="Win/Loss Reasons" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Win/Loss Reasons</h1>
          <p class="text-gray-500">Configure reasons for won and lost deals.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ Add Reason</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create Reason</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium">Label</label>
                <Input v-model="newReason.label" placeholder="e.g., Price Too High" />
              </div>
              <div>
                <label class="text-sm font-medium">Type</label>
                <select v-model="newReason.type" class="w-full p-2 border rounded">
                  <option value="won">Win</option>
                  <option value="lost">Loss</option>
                </select>
              </div>
              <Button @click="createReason">Create Reason</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent class="p-0">
          <table class="w-full">
            <thead class="border-b">
              <tr class="text-left">
                <th class="p-4">Label</th>
                <th class="p-4">Type</th>
                <th class="p-4">Status</th>
                <th class="p-4"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="reason in reasons" :key="reason.id" class="border-b">
                <td class="p-4">{{ reason.label }}</td>
                <td class="p-4">
                  <Badge :variant="reason.type === 'won' ? 'default' : 'secondary'">
                    {{ reason.type }}
                  </Badge>
                </td>
                <td class="p-4">
                  <Badge :variant="reason.is_active ? 'default' : 'outline'">
                    {{ reason.is_active ? 'Active' : 'Inactive' }}
                  </Badge>
                </td>
                <td class="p-4">
                  <Button variant="ghost" size="sm" @click="toggleActive(reason)">
                    {{ reason.is_active ? 'Deactivate' : 'Activate' }}
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>