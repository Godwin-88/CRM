<template>
    <div class="max-w-7xl mx-auto py-6">
        <div class="flex justify-end mb-4">
            <Button @click="showCreateModal = true">Create Webhook</Button>
        </div>

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>URL</TableHead>
                    <TableHead>Events</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Last Success</TableHead>
                    <TableHead>Actions</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="webhook in webhooks.data" :key="webhook.id">
                    <TableCell>{{ webhook.name }}</TableCell>
                    <TableCell>{{ webhook.url }}</TableCell>
                    <TableCell>
                        <span
                            v-for="event in webhook.events"
                            :key="event"
                            class="mr-1 text-xs bg-gray-200 px-2 py-1 rounded"
                        >
                            {{ event }}
                        </span>
                    </TableCell>
                    <TableCell>
                        <Badge :variant="webhook.is_active ? 'default' : 'secondary'">
                            {{ webhook.is_active ? 'Active' : 'Paused' }}
                        </Badge>
                    </TableCell>
                    <TableCell>{{ webhook.last_success_at }}</TableCell>
                    <TableCell>
                        <Button variant="ghost" size="sm" @click="toggleStatus(webhook)">
                            {{ webhook.is_active ? 'Pause' : 'Resume' }}
                        </Button>
                        <Button variant="ghost" size="sm" class="text-red-600" @click="deleteWebhook(webhook)">
                            Delete
                        </Button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

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
