<template>
  <button
    type="button"
    @click="toggle"
    class="relative rounded-full p-2 text-purple-700 transition-colors hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-950"
    title="AI Assistant"
  >
    <BotMessageSquare class="h-5 w-5" />
    <span
      v-if="hasUnreadProactive"
      class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-900"
    />
  </button>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { BotMessageSquare } from 'lucide-vue-next';
import { useAssistantStore } from '@/stores/assistant';

const store = useAssistantStore();
const toggle = () => store.toggle();
const hasUnreadProactive = ref(false);
</script>
