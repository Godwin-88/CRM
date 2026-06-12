<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import RuleBuilder from '@/Components/Segments/RuleBuilder.vue';

const emit = defineEmits(['update']);

const segmentName = ref('');
const segmentType = ref('behavioral');
const joinOperator = ref('and');
const rules = ref([{ field: 'clv_score', operator: '>=', value: '' }]);
const previewCount = ref<number | null>(null);
const previewLoading = ref(false);
const showPreview = ref(false);
const previewSample = ref<any[]>([]);

const criteria = computed(() => ({
  join_operator: joinOperator.value,
  rules: rules.value,
}));

const updateRules = (newRules: any[]) => {
  rules.value = newRules;
  emitUpdate();
};

const emitUpdate = () => {
  emit('update', {
    name: segmentName.value,
    type: segmentType.value,
    criteria: criteria.value,
  });
};

const loadPreview = async () => {
  previewLoading.value = true;
  try {
    const response = await fetch('/api/v1/segments/preview', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ criteria: criteria.value }),
    });
    const data = await response.json();
    previewCount.value = data.count;
    previewSample.value = data.sample || [];
    showPreview.value = true;
  } catch (e) {
    // ignore
  } finally {
    previewLoading.value = false;
  }
};
</script>

<template>
  <div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-sm font-medium">Segment Name</label>
        <Input v-model="segmentName" placeholder="e.g., High-value leads" @input="emitUpdate" />
      </div>
      <div class="space-y-2">
        <label class="text-sm font-medium">Type</label>
        <select v-model="segmentType" @change="emitUpdate" class="w-full border rounded-md px-3 py-2 text-sm">
          <option value="demographic">Demographic</option>
          <option value="psychographic">Psychographic</option>
          <option value="behavioral">Behavioral</option>
          <option value="geographic">Geographic</option>
        </select>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <label class="text-sm font-medium">Match:</label>
      <button
        v-for="op in ['and', 'or']"
        :key="op"
        @click="joinOperator = op; emitUpdate()"
        class="px-3 py-1 text-sm rounded-full"
        :class="joinOperator === op ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'"
      >
        {{ op === 'and' ? 'All' : 'Any' }}
      </button>
    </div>

    <RuleBuilder :rules="rules" @update="updateRules" />

    <div class="flex gap-2">
      <Button type="button" variant="outline" @click="loadPreview" :disabled="previewLoading">
        {{ previewLoading ? 'Previewing...' : 'Preview Contacts' }}
      </Button>
    </div>

    <Card v-if="showPreview" class="bg-gray-50">
      <CardContent class="pt-4">
        <div class="flex items-center gap-2 mb-2">
          <Badge variant="secondary" class="text-sm">~{{ previewCount }} matching contacts</Badge>
        </div>
        <div v-if="previewSample.length" class="space-y-1">
          <div v-for="contact in previewSample" :key="contact.id" class="text-sm">
            {{ contact.first_name }} {{ contact.last_name }} ({{ contact.email }}) — {{ contact.type }}
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">No matching contacts found.</p>
      </CardContent>
    </Card>
  </div>
</template>