<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface ScoringRule {
  id: string;
  name: string;
  entity_type: 'contact' | 'account';
  field: string;
  operator: string;
  value: string;
  points: number;
  is_enabled: boolean;
}

const props = defineProps<{
  rules: ScoringRule[];
}>();

const rules = ref(props.rules);
const isCreateOpen = ref(false);

const newRule = ref({
  name: '',
  entity_type: 'contact',
  field: '',
  operator: '=',
  value: '',
  points: 0,
  is_enabled: true,
});

const createRule = async () => {
  const response = await fetch('/api/v1/scoring-rules', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newRule.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  }
};

const toggleRule = async (rule: ScoringRule) => {
  await fetch(`/api/v1/scoring-rules/${rule.id}/toggle`, {
    method: 'PATCH',
    headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  router.reload();
};
</script>

<template>
  <AppLayout>
    <Head title="Scoring Rules" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Scoring Rules</h1>
          <p class="text-gray-500">Define rules to automatically score contacts and accounts.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ Add Rule</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create Scoring Rule</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium">Name</label>
                <Input v-model="newRule.name" placeholder="e.g., Annual Revenue > 1M" />
              </div>
              <div>
                <label class="text-sm font-medium">Entity Type</label>
                <select v-model="newRule.entity_type" class="w-full p-2 border rounded">
                  <option value="contact">Contact</option>
                  <option value="account">Account</option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium">Field</label>
                  <Input v-model="newRule.field" placeholder="e.g., annual_revenue" />
                </div>
                <div>
                  <label class="text-sm font-medium">Operator</label>
                  <select v-model="newRule.operator" class="w-full p-2 border rounded">
                    <option>=</option>
                    <option>!=</option>
                    <option>&gt;</option>
                    <option>&gt;=</option>
                    <option>&lt;</option>
                    <option>&lt;=</option>
                    <option>contains</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="text-sm font-medium">Value</label>
                <Input v-model="newRule.value" placeholder="e.g., 1000000" />
              </div>
              <div>
                <label class="text-sm font-medium">Points</label>
                <Input v-model="newRule.points" type="number" placeholder="e.g., 50" />
              </div>
              <Button @click="createRule">Create Rule</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent class="p-0">
          <table class="w-full">
            <thead class="border-b">
              <tr class="text-left">
                <th class="p-4">Name</th>
                <th class="p-4">Entity</th>
                <th class="p-4">Condition</th>
                <th class="p-4">Points</th>
                <th class="p-4">Status</th>
                <th class="p-4"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rule in rules" :key="rule.id" class="border-b">
                <td class="p-4">{{ rule.name }}</td>
                <td class="p-4">
                  <Badge variant="outline">{{ rule.entity_type }}</Badge>
                </td>
                <td class="p-4 text-sm">{{ rule.field }} {{ rule.operator }} {{ rule.value }}</td>
                <td class="p-4 font-semibold">{{ rule.points }}</td>
                <td class="p-4">
                  <Badge :variant="rule.is_enabled ? 'default' : 'secondary'">
                    {{ rule.is_enabled ? 'Enabled' : 'Disabled' }}
                  </Badge>
                </td>
                <td class="p-4">
                  <Button variant="ghost" size="sm" @click="toggleRule(rule)">
                    {{ rule.is_enabled ? 'Disable' : 'Enable' }}
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