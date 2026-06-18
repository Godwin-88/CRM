<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ExternalLink, RefreshCw, AlertCircle } from 'lucide-vue-next';

defineProps<{
    integrations: Array<{
        id: string;
        name: string;
        provider: string;
        connection_status: string;
        is_active: boolean;
        last_active_at: string | null;
        settings: Record<string, any>;
    }>;
}>();

const statusVariant = (status: string) => {
    switch (status) {
        case 'connected': return 'success';
        case 'error': return 'destructive';
        case 'not_connected': return 'secondary';
        default: return 'outline';
    }
};

const disconnect = (integration: any) => {
    if (confirm('Disconnect this integration?')) {
        router.post(`/admin/integrations/${integration.provider || integration.id}/disconnect`);
    }
};

</script>

<template>
    <AppLayout>
        <Head title="Service Registry" />
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Service Registry</h1>
                    <p class="text-gray-500">Audit and manage all external system connections.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/integrations/marketplace">
                        <Button variant="outline">Browse Marketplace</Button>
                    </Link>
                    <Button variant="outline" size="icon">
                        <RefreshCw class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Connected Integrations</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Integration</TableHead>
                                <TableHead>Provider</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Last Active</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="integration in integrations" :key="integration.id">
                                <TableCell class="font-medium">{{ integration.name }}</TableCell>
                                <TableCell class="capitalize">{{ integration.provider }}</TableCell>
                                <TableCell>
                                    <Badge :variant="statusVariant(integration.connection_status)">
                                        {{ integration.connection_status.replace('_', ' ') }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-gray-500 text-sm">
                                    {{ integration.last_active_at ? new Date(integration.last_active_at).toLocaleString() : 'Never' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button variant="ghost" size="sm">Configure</Button>
                                    <Button v-if="integration.connection_status === 'connected'" variant="ghost" size="sm" @click="disconnect(integration)">Disconnect</Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="integrations.length === 0">
                                <TableCell colspan="5" class="text-center py-12 text-gray-500 italic">
                                    <div class="flex flex-col items-center gap-2">
                                        <AlertCircle class="h-8 w-8 text-gray-400" />
                                        <p>No active integrations found.</p>
                                        <Link href="/admin/integrations/marketplace" class="text-blue-600 hover:underline">Visit the marketplace to get started.</Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <Card class="bg-blue-50 border-blue-200">
                    <CardHeader>
                        <CardTitle class="text-blue-900 flex items-center gap-2">
                            <ExternalLink class="h-5 w-5" />
                            Developer Portal
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-blue-700 text-sm mb-4">
                            Access our full API documentation, technical guides, and SDKs.
                        </p>
                        <Link href="/docs">
                            <Button variant="outline" class="bg-white">View Docs</Button>
                        </Link>
                    </CardContent>
                </Card>
                
                <Card class="bg-gray-50 border-gray-200">
                    <CardHeader>
                        <CardTitle class="text-gray-900 flex items-center gap-2">
                            <RefreshCw class="h-5 w-5" />
                            Webhooks
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-gray-600 text-sm mb-4">
                            Configure outbound webhooks to notify your systems of CRM events in real-time.
                        </p>
                        <Link href="/admin/integrations/webhooks">
                            <Button variant="outline" class="bg-white">Manage Webhooks</Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
