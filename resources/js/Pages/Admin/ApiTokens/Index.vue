<template>
    <AppLayout>
        <Head title="API Tokens" />
        <div class="max-w-4xl mx-auto py-6">
            <h1 class="text-2xl font-bold mb-4">API Tokens</h1>
            
            <div v-if="!tokens.length" class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4">
                <p>No API tokens found. Create one below.</p>
            </div>
            
            <template v-else>
                <Table class="mb-6">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Token</TableHead>
                            <TableHead>Expires</TableHead>
                            <TableHead>Last Used</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="token in tokens" :key="token.id">
                            <TableCell>{{ token.name }}</TableCell>
                            <TableCell class="font-mono">{{ token.masked_token }}</TableCell>
                            <TableCell>{{ token.expires_at || "No expiry" }}</TableCell>
                            <TableCell>{{ token.last_used_at }}</TableCell>
                            <TableCell>
                                <Button variant="ghost" size="sm" class="text-red-600" @click="revokeToken(token.id)">
                                    Revoke
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </template>
            
            <div class="border rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-2">Create Token</h2>
                
                <form @submit.prevent="createToken" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Name</Label>
                        <Input v-model="form.name" type="text" required />
                    </div>
                    
                    <div class="space-y-2">
                        <Label>Abilities (optional)</Label>
                        <Select v-model="form.abilities" multiple>
                            <SelectTrigger>
                                <SelectValue placeholder="Select abilities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="contacts:read">contacts:read</SelectItem>
                                <SelectItem value="contacts:write">contacts:write</SelectItem>
                                <SelectItem value="deals:read">deals:read</SelectItem>
                                <SelectItem value="deals:write">deals:write</SelectItem>
                                <SelectItem value="tickets:read">tickets:read</SelectItem>
                                <SelectItem value="tickets:write">tickets:write</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="space-y-2">
                        <Label>Expires At (optional)</Label>
                        <Input v-model="form.expires_at" type="date" />
                    </div>
                    
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Token' }}
                    </Button>
                </form>
            </div>
            
            <div
                v-if="newToken"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            >
                <div class="bg-white p-6 rounded-lg max-w-lg w-full">
                    <h3 class="text-lg font-semibold mb-2">Token Created</h3>
                    <p class="mb-2">Save this token now - it will not be shown again!</p>
                    <code class="block bg-gray-100 p-3 rounded mb-4 break-all">{{ newToken }}</code>
                    <Button @click="clearToken">Done</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const page = usePage();

defineProps<{
    tokens: Array<{ id: string; name: string; masked_token: string; expires_at: string; last_used_at: string }>,
}>();

const newToken = computed(() => page.props.flash && (page.props.flash as any).newToken as string | undefined);

const form = useForm({
    name: "",
    abilities: [] as string[],
    expires_at: "",
});

const createToken = () => {
    form.post(route("admin.api-tokens.store"), {
        onSuccess: () => {
            form.reset();
        },
        preserveScroll: true,
    });
};

const revokeToken = (tokenId: string) => {
    if (confirm("Are you sure?")) {
        router.delete(route("admin.api-tokens.destroy", tokenId), {
            preserveScroll: true,
        });
    }
};

const clearToken = () => {
    router.visit(route("admin.api-tokens.index"), { replace: true });
};
</script>