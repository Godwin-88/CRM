<template>
  <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-3 space-y-3 shadow-sm">
    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ message }}</p>
    <div v-if="toolDetails" class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
      <p><span class="font-medium">Tool:</span> {{ toolDetails.tool }}</p>
      <p v-if="Object.keys(toolDetails.arguments).length > 0">
        <span class="font-medium">Args:</span>
        <code class="ml-1 rounded bg-gray-100 dark:bg-gray-800 px-1 py-0.5">{{ prettyArgs }}</code>
      </p>
    </div>
    <div class="flex items-center justify-end gap-2">
      <Button variant="secondary" size="sm" @click="$emit('cancel')">Cancel</Button>
      <Button size="sm" @click="$emit('confirm', tool, arguments)">Confirm</Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';

const props = defineProps<{
  message: string;
  tool: string;
  arguments: Record<string, any>;
}>();

defineEmits<{
  (e: 'confirm', tool: string, arguments: Record<string, any>): void;
  (e: 'cancel'): void;
}>();

const toolDetails = computed(() => {
  if (!props.tool && !props.arguments) return null;
  return { tool: props.tool, arguments: props.arguments };
});

const prettyArgs = computed(() => {
  try {
    return JSON.stringify(props.arguments);
  } catch {
    return JSON.stringify({});
  }
});
</script>
