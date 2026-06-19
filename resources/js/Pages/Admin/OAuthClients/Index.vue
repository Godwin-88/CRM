<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Shield, Globe, Trash2, AlertTriangle } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    clients: Array<{
        id: string;
        name: string;
        redirect_uris: string[];
        grant_types: string[];
        client_id: string;
        client_secret?: string;
        is_suspended: boolean;
        suspension_reason?: string;
    }>;
}>();

const isCreateOpen = ref(false);
const form = useForm({
    name: '',
    redirect_uris: [''],
    grant_types: ['authorization_code'],
});

const submit = () => {
    form.post('/admin/oauth-clients', {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="OAuth2 Applications" />
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">OAuth2 Applications</h1>
                    <p class="text-gray-500">Manage third-party integrations and API client credentials.</p>
                </div>
                <Dialog v-model:open="isCreateOpen">
                    <DialogTrigger as-child>
                        <Button><Plus class="h-4 w-4 mr-2" /> Register Client</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Register New OAuth2 Client</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="space-y-4 py-4">
                            <div class="space-y-2">
                                <Label for="name">Client Name</Label>
                                <Input v-model="form.name" id="name" placeholder="e.g. My Website" required />
                            </div>
                            <div class="space-y-2">
                                <Label for="redirect">Redirect URI</Label>
                                <Input v-model="form.redirect_uris[0]" id="redirect" placeholder="https://example.com/callback" required />
                            </div>
                            <div class="space-y-2">
                                <Label>Permitted Grant Types</Label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" v-model="form.grant_types" value="authorization_code" />
                                        Auth Code
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" v-model="form.grant_types" value="client_credentials" />
                                        Client Credentials
                                    </label>
                                </div>
                            </div>
                            <Button type="submit" class="w-full" :disabled="form.processing">Create Client</Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="grid gap-4">
                <Card v-for="client in clients" :key="client.id" :class="{'opacity-60': client.is_suspended}">
                    <CardContent class="py-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <div class="p-2 bg-gray-100 rounded">
                                    <Shield class="h-6 w-6 text-gray-600" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-lg">{{ client.name }}</h3>
                                        <Badge v-if="client.is_suspended" variant="destructive">Suspended</Badge>
                                        <Badge v-else variant="success">Active</Badge>
                                    </div>
                                    <p class="text-sm text-gray-500 font-mono mt-1">ID: {{ client.client_id }}</p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <Badge v-for="grant in client.grant_types" :key="grant" variant="outline" class="capitalize">
                                            {{ grant.replace('_', ' ') }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-600 hover:bg-red-50">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        
                        <div v-if="client.suspension_reason" class="mt-4 p-3 bg-red-50 border border-red-100 rounded-md flex items-start gap-3">
                            <AlertTriangle class="h-4 w-4 text-red-600 shrink-0 mt-0.5" />
                            <p class="text-sm text-red-700"><strong>Suspended:</strong> {{ client.suspension_reason }}</p>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm border-t pt-4">
                            <div>
                                <p class="text-gray-500 mb-1">Redirect URIs</p>
                                <div class="flex items-center gap-2 text-gray-700" v-for="uri in client.redirect_uris" :key="uri">
                                    <Globe class="h-3 w-3" />
                                    {{ uri }}
                                </div>
                            </div>
                            <div class="text-right">
                                <Button variant="link" class="text-blue-600 p-0 h-auto">Edit Configuration</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="clients.length === 0">
                    <CardContent class="py-12 text-center text-gray-500 italic">
                        No OAuth2 clients registered yet.
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
