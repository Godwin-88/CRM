<template>
    <div class="max-w-7xl mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">Integration Marketplace</h1>

        <div class="mb-4">
            <Input v-model="search" placeholder="Search connectors..." class="w-64" />
            <Select v-model="category">
                <SelectTrigger class="ml-2">
                    <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All categories</SelectItem>
                    <SelectItem value="communications">Communications</SelectItem>
                    <SelectItem value="finance">Finance</SelectItem>
                    <SelectItem value="productivity">Productivity</SelectItem>
                    <SelectItem value="identity">Identity</SelectItem>
                    <SelectItem value="e-signature">E-signature</SelectItem>
                    <SelectItem value="payments">Payments</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="connector in filteredCatalog"
                :key="connector.provider"
                class="border rounded-lg p-4"
            >
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-gray-200 rounded mr-3"></div>
                    <div>
                        <h3 class="font-semibold">{{ connector.name }}</h3>
                        <span class="text-xs text-gray-500">{{ connector.category }}</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-3">{{ connector.description }}</p>
                <Button
                    @click="connect(connector)"
                    :disabled="isConnected(connector.provider)"
                    class="w-full"
                >
                    {{ isConnected(connector.provider) ? "Connected" : "Connect" }}
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

defineProps({
    catalog: Array,
    connected: Array,
});

const search = ref("");
const category = ref("");

const filteredCatalog = computed(() => {
    return catalog.filter((c) => {
        const matchesSearch =
            c.name.toLowerCase().includes(search.value.toLowerCase()) ||
            c.description.toLowerCase().includes(search.value.toLowerCase());
        const matchesCategory = !category.value || c.category === category.value;
        return matchesSearch && matchesCategory;
    });
});

function isConnected(provider) {
    return connected.includes(provider);
}

function connect(connector) {
    router.post(
        route("admin.integrations.connect", connector.provider),
        {},
        { preserveScroll: true },
    );
}
</script>
