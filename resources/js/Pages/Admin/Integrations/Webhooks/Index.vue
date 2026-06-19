<template>
    <AppLayout>
        <Head title="Webhooks" />
        <div class="max-w-7xl mx-auto py-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Webhooks</h1>
                <Dialog v-model:open="showCreateModal">
                    <DialogTrigger as-child>
                        <Button>Create Webhook</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Webhook</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="createWebhook" class="space-y-4 py-4">
                            <div class="space-y-2">
                                <Label>Name</Label>
                                <Input v-model="form.name" placeholder="Webhook name" required />
                            </div>
                            <div class="space-y-2">
                                <Label>URL</Label>
                                <Input v-model="form.url" type="url" placeholder="https://example.com/webhook" required />
                            </div>
                            <div class="space-y-2">
                                <Label>Events</Label>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="event in availableEvents" :key="event" class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" v-model="form.events" :value="event" />
                                        {{ event }}
                                    </label>
                                </div>
                            </div>
                            <Button type="submit" :disabled="form.processing">Create</Button>
                        </form>
                    </DialogContent>
                </Dialog>
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
                    <TableRow v-for="webhook in webhooks" :key="webhook.id">
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
            
            <div v-if="webhooks.length === 0" class="text-center py-12 text-gray-500">
                No webhooks configured yet.
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";

defineProps({
    webhooks: Array,
});

const showCreateModal = ref(false);
const availableEvents = ['contact.created', 'deal.created', 'contract.signed', 'ticket.created', 'invoice.paid'];

const form = useForm({
    name: '',
    url: '',
    events: [],
});

const createWebhook = () => {
    form.post('/admin/integrations/webhooks', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
        preserveScroll: true,
    });
};

function toggleStatus(webhook) {
    router.post(`/api/v1/webhooks/${webhook.id}/${webhook.is_active ? 'pause' : 'resume'}`, {}, { preserveScroll: true });
}

function deleteWebhook(webhook) {
    if (confirm("Are you sure?")) {
        router.delete(
            `/api/v1/webhooks/${webhook.id}`,
            { preserveScroll: true },
        );
    }
}
</script>