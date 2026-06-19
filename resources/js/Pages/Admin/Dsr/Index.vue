<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
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
import { Badge } from "@/components/ui/badge";

const props = defineProps<{
    requests: {
        data: Array<{
            id: string;
            type: string;
            contact: {
                id: string;
                first_name: string;
                last_name: string;
                email: string;
            };
            status: string;
            requested_by: string;
            blocking_reason?: string;
            completed_at?: string;
            created_at: string;
        }>;
        links: Array<any>;
    };
}>();
</script>

<template>
    <AppLayout>
        <Head title="Data Subject Requests" />

        <div class="max-w-7xl mx-auto py-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Data Subject Requests</h1>
                <Link :href="$route('admin.dsr.create')">
                    <Button>Create Request</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>DSR Management</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Type</TableHead>
                                <TableHead>Contact</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Requested By</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="request in requests.data"
                                :key="request.id"
                            >
                                <TableCell>{{ request.type }}</TableCell>
                                <TableCell>
                                    <Link
                                        :href="
                                            $route(
                                                'contacts.show',
                                                request.contact.id,
                                            )
                                        "
                                        class="text-blue-600"
                                    >
                                        {{ request.contact.first_name }}
                                        {{ request.contact.last_name }}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            request.status === 'completed'
                                                ? 'default'
                                                : request.blocking_reason
                                                  ? 'destructive'
                                                  : 'secondary'
                                        "
                                    >
                                        {{ request.status }}
                                    </Badge>
                                    <p
                                        v-if="request.blocking_reason"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ request.blocking_reason }}
                                    </p>
                                </TableCell>
                                <TableCell>{{
                                    request.requested_by
                                }}</TableCell>
                                <TableCell>{{
                                    new Date(
                                        request.created_at,
                                    ).toLocaleDateString()
                                }}</TableCell>
                                <TableCell>
                                    <Link
                                        :href="
                                            $route('admin.dsr.show', request.id)
                                        "
                                    >
                                        <Button variant="outline" size="sm"
                                            >View</Button
                                        >
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
