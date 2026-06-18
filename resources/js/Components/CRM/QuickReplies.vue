<template>
  <div v-if="suggestions.length" class="flex flex-wrap gap-2">
    <button
      v-for="suggestion in suggestions"
      :key="suggestion"
      type="button"
      @click="$emit('select', suggestion)"
      class="rounded-full border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/40 px-3 py-1 text-xs text-blue-700 dark:text-blue-200 hover:bg-blue-100"
    >
      {{ suggestion }}
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  suggestions: string[];
}>();

defineEmits<{
  (e: 'select', value: string): void;
}>();
</script>
