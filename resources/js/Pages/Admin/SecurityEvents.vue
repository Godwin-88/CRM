<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

const props = defineProps<{
    events: {
        data: Array<{
            id: number;
            event_type: string;
            email: string;
            ip_address: string;
            user_agent: string;
            outcome: string;
            created_at: string;
        }>;
        links: Array<any>;
    };
    eventTypes: string[];
    filters: {
        event_type?: string;
        user_id?: string;
        ip_address?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

const columns = [
    { key: "created_at", label: "Date/Time" },
    { key: "event_type", label: "Event Type" },
    { key: "email", label: "Email" },
    { key: "ip_address", label: "IP Address" },
    { key: "outcome", label: "Outcome" },
];
</script>

<template>
    <AppLayout>
        <Head title="Security Events" />

        <div class="max-w-7xl mx-auto py-6">
            <div class="mb-4">
                <h1 class="text-2xl font-bold">Security Events</h1>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Event Log</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date/Time</TableHead>
                                <TableHead>Event Type</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>IP Address</TableHead>
                                <TableHead>Outcome</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="event in events.data"
                                :key="event.id"
                            >
                                <TableCell>{{
                                    new Date(event.created_at).toLocaleString()
                                }}</TableCell>
                                <TableCell>{{ event.event_type }}</TableCell>
                                <TableCell>{{ event.email || "-" }}</TableCell>
                                <TableCell>{{
                                    event.ip_address || "-"
                                }}</TableCell>
                                <TableCell>
                                    <span
                                        :class="
                                            event.outcome === 'success'
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{ event.outcome }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div
                        class="mt-4 flex justify-between items-center"
                        v-if="events.links?.length"
                    >
                        <Link
                            v-for="link in events.links"
                            :key="link.url"
                            :href="link.url"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
