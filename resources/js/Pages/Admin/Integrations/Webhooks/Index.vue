<template>
    <div class="max-w-7xl mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">Webhooks</h1>

        <div class="flex justify-end mb-4">
            <button
                @click="showCreateModal = true"
                class="bg-blue-600 text-white px-4 py-2 rounded"
            >
                Create Webhook
            </button>
        </div>

        <table class="min-w-full bg-white border">
            <thead>
                <tr class="border-b">
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">URL</th>
                    <th class="px-4 py-2 text-left">Events</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Last Success</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="webhook in webhooks.data"
                    :key="webhook.id"
                    class="border-b"
                >
                    <td class="px-4 py-2">{{ webhook.name }}</td>
                    <td class="px-4 py-2">{{ webhook.url }}</td>
                    <td class="px-4 py-2">
                        <span
                            v-for="event in webhook.events"
                            :key="event"
                            class="mr-1 text-xs bg-gray-200 px-2 py-1 rounded"
                        >
                            {{ event }}
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <span
                            :class="
                                webhook.is_active
                                    ? 'text-green-600'
                                    : 'text-gray-600'
                            "
                        >
                            {{ webhook.is_active ? "Active" : "Paused" }}
                        </span>
                    </td>
                    <td class="px-4 py-2">{{ webhook.last_success_at }}</td>
                    <td class="px-4 py-2">
                        <button
                            @click="toggleStatus(webhook)"
                            class="text-sm mr-2"
                        >
                            {{ webhook.is_active ? "Pause" : "Resume" }}
                        </button>
                        <button
                            @click="deleteWebhook(webhook)"
                            class="text-sm text-red-600"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

defineProps({
    webhooks: Object,
});

const showCreateModal = ref(false);

function toggleStatus(webhook) {
    const routeName = webhook.is_active
        ? "admin.integrations.webhooks.pause"
        : "admin.integrations.webhooks.resume";
    router.post(route(routeName, webhook.id), {}, { preserveScroll: true });
}

function deleteWebhook(webhook) {
    if (confirm("Are you sure?")) {
        router.delete(
            route("admin.integrations.webhooks.destroy", webhook.id),
            { preserveScroll: true },
        );
    }
}
</script>
<style scoped>
table {
    border-collapse: collapse;
}
</style>
