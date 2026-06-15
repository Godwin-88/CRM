<template>
    <Head title="API Tokens" />
    <div class="max-w-4xl mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">API Tokens</h1>

        <div
            class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-4"
            v-if="!tokens.length"
        >
            <p>No API tokens found. Create one below.</p>
        </div>

        <table v-else class="min-w-full bg-white border mb-6">
            <thead>
                <tr class="border-b">
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Token</th>
                    <th class="px-4 py-2 text-left">Expires</th>
                    <th class="px-4 py-2 text-left">Last Used</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="token in tokens" :key="token.id" class="border-b">
                    <td class="px-4 py-2">{{ token.name }}</td>
                    <td class="px-4 py-2 font-mono">
                        {{ token.masked_token }}
                    </td>
                    <td class="px-4 py-2">
                        {{ token.expires_at || "No expiry" }}
                    </td>
                    <td class="px-4 py-2">{{ token.last_used_at }}</td>
                    <td class="px-4 py-2">
                        <button
                            @click="revokeToken(token.id)"
                            class="text-sm text-red-600"
                        >
                            Revoke
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="border rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-2">Create Token</h2>

            <form @submit.prevent="createToken">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="border rounded px-3 py-2 w-full"
                    />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Abilities (optional)</label>
                    <select
                        v-model="form.abilities"
                        multiple
                        class="border rounded px-3 py-2 w-full h-32"
                    >
                        <option value="contacts:read">contacts:read</option>
                        <option value="contacts:write">contacts:write</option>
                        <option value="deals:read">deals:read</option>
                        <option value="deals:write">deals:write</option>
                        <option value="tickets:read">tickets:read</option>
                        <option value="tickets:write">tickets:write</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Expires At (optional)</label>
                    <input
                        v-model="form.expires_at"
                        type="date"
                        class="border rounded px-3 py-2 w-full"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating...' : 'Create Token' }}
                </button>
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
                <button
                    @click="clearToken"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    Done
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";

const page = usePage();

defineProps<{
    tokens: Array<{ id: string; name: string; masked_token: string; expires_at: string; last_used_at: string }>,
}>();

const newToken = computed(() => page.props.flash?.newToken as string | undefined);

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
