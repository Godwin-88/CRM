<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const props = defineProps<{
    catalog: Array<{ name: string; category: string; provider: string; description: string; logo: string | null }>;
    connected: string[];
}>();

const search = ref("");
const category = ref("all");
const showConnectDialog = ref(false);
const selectedConnector = ref<any>(null);
const integrationName = ref("");
const apiKey = ref("");
const clientId = ref("");
const clientSecret = ref("");
const submitting = ref(false);

const filteredCatalog = computed(() => {
    return props.catalog.filter((c) => {
        const matchesSearch =
            c.name.toLowerCase().includes(search.value.toLowerCase()) ||
            c.description.toLowerCase().includes(search.value.toLowerCase());
        const matchesCategory = category.value === "all" || c.category === category.value;
        return matchesSearch && matchesCategory;
    });
});

function isConnected(provider: string) {
    return props.connected.includes(provider);
}

function openConnectDialog(connector: any) {
    selectedConnector.value = connector;
    integrationName.value = connector.name;
    apiKey.value = "";
    clientId.value = "";
    clientSecret.value = "";
    showConnectDialog.value = true;
}

function handleConnect() {
    if (!selectedConnector.value) return;
    
    submitting.value = true;
    router.post(
        `/admin/integrations/${selectedConnector.value.provider}/connect`,
        {
            name: integrationName.value,
            api_key: apiKey.value || undefined,
            client_id: clientId.value || undefined,
            client_secret: clientSecret.value || undefined,
        },
        { 
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
                showConnectDialog.value = false;
            }
        }
    );
}
</script>

<template>
    <AppLayout>
        <Head title="Integration Marketplace" />
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Integration Marketplace</h1>
                    <p class="text-gray-500">Discover and connect third-party services to your CRM.</p>
                </div>
            </div>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex gap-4">
                        <Input v-model="search" placeholder="Search connectors..." class="max-w-xs" />
                        <Select v-model="category">
                            <SelectTrigger class="max-w-xs">
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All categories</SelectItem>
                                <SelectItem value="communications">Communications</SelectItem>
                                <SelectItem value="finance">Finance</SelectItem>
                                <SelectItem value="productivity">Productivity</SelectItem>
                                <SelectItem value="identity">Identity</SelectItem>
                                <SelectItem value="e-signature">E-signature</SelectItem>
                                <SelectItem value="payments">Payments</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card
                    v-for="connector in filteredCatalog"
                    :key="connector.provider"
                    class="hover:shadow-md transition-shadow"
                >
                    <CardHeader class="pb-2">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded flex items-center justify-center text-blue-600 font-bold">
                                {{ connector.name.charAt(0) }}
                            </div>
                            <div>
                                <CardTitle class="text-lg">{{ connector.name }}</CardTitle>
                                <span class="text-xs font-medium px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full capitalize">
                                    {{ connector.category }}
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
<p class="text-sm text-gray-600 mb-6 h-10 line-clamp-2">{{ connector.description }}</p>
                         <Dialog v-model:open="showConnectDialog">
                             <DialogTrigger as-child>
                                 <Button
                                     @click="openConnectDialog(connector)"
                                     :disabled="isConnected(connector.provider)"
                                     variant="outline"
                                     class="w-full"
                                 >
                                     {{ isConnected(connector.provider) ? "Connected" : "Connect" }}
                                 </Button>
                             </DialogTrigger>
                             <DialogContent>
                                 <DialogHeader>
                                     <DialogTitle>Connect {{ selectedConnector?.name }}</DialogTitle>
                                 </DialogHeader>
                                 <div class="space-y-4 py-4">
                                     <div class="space-y-2">
                                         <Label>Integration Name</Label>
                                         <Input v-model="integrationName" placeholder="My Mailchimp Integration" />
                                     </div>
                                     <div class="space-y-2">
                                         <Label>API Key (optional)</Label>
                                         <Input v-model="apiKey" type="password" placeholder="Enter API key" />
                                     </div>
                                     <div class="space-y-2">
                                         <Label>Client ID (optional)</Label>
                                         <Input v-model="clientId" placeholder="OAuth Client ID" />
                                     </div>
                                     <div class="space-y-2">
                                         <Label>Client Secret (optional)</Label>
                                         <Input v-model="clientSecret" type="password" placeholder="OAuth Client Secret" />
                                     </div>
                                 </div>
                                 <DialogFooter>
                                     <Button variant="outline" @click="showConnectDialog = false">Cancel</Button>
                                     <Button @click="handleConnect" :disabled="submitting">Connect Integration</Button>
                                 </DialogFooter>
                             </DialogContent>
                         </Dialog>
                     </CardContent>
                 </Card>
             </div>
            
            <div v-if="filteredCatalog.length === 0" class="text-center py-20 bg-white rounded-lg border border-dashed">
                <p class="text-gray-500">No connectors found matching your criteria.</p>
            </div>
        </div>
    </AppLayout>
</template>
