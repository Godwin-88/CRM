<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  open: boolean;
}>();

const emit = defineEmits(['update:open']);

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white p-6 rounded shadow-lg w-full max-w-lg">
      <slot />
      <button @click="isOpen = false" class="mt-4 text-sm text-gray-500">Close</button>
    </div>
  </div>
</template>
