<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, X, Info } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const page = usePage();
const visible = ref(false);

const flash = computed(() => page.props.flash as { success?: string, error?: string, message?: string });

const type = computed(() => {
    if (flash.value?.success) return 'success';
    if (flash.value?.error) return 'error';
    return 'info';
});

const content = computed(() => {
    return flash.value?.success || flash.value?.error || flash.value?.message;
});

watch(() => flash.value, (newFlash) => {
    if (newFlash?.success || newFlash?.error || newFlash?.message) {
        visible.value = true;
        // Auto hide after 5 seconds unless it's an error
        if (!newFlash.error) {
            setTimeout(() => {
                visible.value = false;
            }, 5000);
        }
    }
}, { deep: true, immediate: true });

const close = () => {
    visible.value = false;
};
</script>

<template>
    <div 
        v-if="visible && content" 
        class="fixed bottom-6 right-6 z-[100] w-full max-w-sm transform transition-all duration-300 ease-in-out"
        :class="visible ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'"
    >
        <div 
            :class="cn(
                'relative p-4 rounded-lg border shadow-xl flex gap-3 items-start',
                type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                'bg-blue-50 border-blue-200 text-blue-800'
            )"
        >
            <CheckCircle2 v-if="type === 'success'" class="h-5 w-5 text-green-600 mt-0.5" />
            <AlertCircle v-else-if="type === 'error'" class="h-5 w-5 text-red-600 mt-0.5" />
            <Info v-else class="h-5 w-5 text-blue-600 mt-0.5" />

            <div class="flex-1">
                <p class="font-bold text-sm uppercase tracking-wider mb-1">
                    {{ type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice' }}
                </p>
                <p class="text-sm leading-relaxed">{{ content }}</p>
            </div>

            <button 
                @click="close" 
                class="text-gray-400 hover:text-gray-600 transition-colors"
            >
                <X class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
