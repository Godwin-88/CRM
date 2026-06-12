<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{ rules: any[] }>();
const emit = defineEmits(['update']);

const localRules = ref(props.rules || []);

const availableFields = [
  { value: 'type', label: 'Contact Type' },
  { value: 'loyalty_tier', label: 'Loyalty Tier' },
  { value: 'clv_score', label: 'CLV Score' },
  { value: 'source', label: 'Source Channel' },
  { value: 'status', label: 'Status' },
  { value: 'owner_name', label: 'Owner' },
  { value: 'created_date', label: 'Created Date' },
  { value: 'last_interaction_date', label: 'Last Interaction Date' },
  { value: 'custom_field', label: 'Custom Field' },
];

const operatorsForField = (field: string) => {
  switch (field) {
    case 'clv_score':
    case 'annual_revenue':
      return [
        { value: '=', label: '=' },
        { value: '>', label: '>' },
        { value: '>=', label: '>=' },
        { value: '<', label: '<' },
        { value: '<=', label: '<=' },
        { value: 'between', label: 'Between' },
      ];
    case 'type':
    case 'loyalty_tier':
    case 'status':
    case 'source':
      return [
        { value: '=', label: '=' },
        { value: '!=', label: '!=' },
        { value: 'in', label: 'In' },
        { value: 'not_in', label: 'Not In' },
      ];
    case 'created_date':
    case 'last_interaction_date':
      return [
        { value: '>=', label: 'On or After' },
        { value: '<=', label: 'On or Before' },
        { value: 'between', label: 'Between' },
      ];
    default:
      return [
        { value: '=', label: '=' },
        { value: '!=', label: '!=' },
        { value: 'contains', label: 'Contains' },
        { value: 'in', label: 'In' },
      ];
  }
};

const addRule = () => {
  localRules.value.push({ field: 'clv_score', operator: '>=', value: '' });
  emit('update', localRules.value);
};

const removeRule = (index: number) => {
  localRules.value.splice(index, 1);
  emit('update', localRules.value);
};

watch(localRules, (newRules) => {
  emit('update', newRules);
}, { deep: true });

watch(() => props.rules, (newRules) => {
  localRules.value = [...newRules];
}, { deep: true });
</script>

<template>
  <div class="space-y-3 border rounded-lg p-4 bg-gray-50">
    <label class="text-sm font-medium">Filter Rules</label>
    <div v-for="(rule, index) in localRules" :key="index" class="flex gap-2 items-start">
      <Select v-model="rule.field" class="flex-1">
        <SelectTrigger class="w-[180px]"><SelectValue placeholder="Field" /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="f in availableFields" :key="f.value" :value="f.value">{{ f.label }}</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="rule.operator" class="flex-1">
        <SelectTrigger class="w-[140px]"><SelectValue placeholder="Operator" /></SelectTrigger>
        <SelectContent>
          <SelectItem v-for="op in operatorsForField(rule.field)" :key="op.value" :value="op.value">{{ op.label }}</SelectItem>
        </SelectContent>
      </Select>
      <Input v-model="rule.value" placeholder="Value(s)" class="flex-1" />
      <Button variant="destructive" size="sm" @click="removeRule(index)">✕</Button>
    </div>
    <Button variant="outline" size="sm" @click="addRule">+ Add Rule</Button>
  </div>
</template>