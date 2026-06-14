<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

const props = defineProps<{
    request: {
        id: string;
        type: string;
        status: string;
        contact: {
            id: string;
            first_name: string;
            last_name: string;
            email: string;
        };
        blocking_reason?: string;
        completed_at?: string;
        justification?: string;
        created_at: string;
    };
}>();

const overrideForm = useForm({
    justification: "",
});

const executeRequest = () => {
    router.post(route("admin.dsr.execute", props.request.id));
};

const overrideBlock = () => {
    overrideForm.post(route("admin.dsr.override", props.request.id));
};
</script>

<template>
    <AppLayout>
        <Head title="Data Subject Request" />

        <div class="max-w-4xl mx-auto py-6">
            <Card>
                <CardHeader>
                    <CardTitle>DSR Request Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium">Type</p>
                            <p>{{ request.type }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Status</p>
                            <p>{{ request.status }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Contact</p>
                            <p>
                                {{ request.contact.first_name }}
                                {{ request.contact.last_name }} ({{
                                    request.contact.email
                                }})
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Requested</p>
                            <p>
                                {{
                                    new Date(
                                        request.created_at,
                                    ).toLocaleString()
                                }}
                            </p>
                        </div>
                    </div>

                    <div v-if="request.blocking_reason" class="border-t pt-4">
                        <p class="text-sm font-medium text-red-600">
                            Blocking Reason
                        </p>
                        <p>{{ request.blocking_reason }}</p>

                        <form
                            v-if="request.status === 'blocked'"
                            @submit.prevent="overrideBlock"
                            class="mt-4 space-y-4"
                        >
                            <label class="block">
                                <span class="text-sm font-medium"
                                    >Override Justification</span
                                >
                                <textarea
                                    v-model="overrideForm.justification"
                                    class="mt-1 block w-full rounded border"
                                    rows="3"
                                    required
                                />
                            </label>
                            <Button
                                type="submit"
                                :disabled="overrideForm.processing"
                                >Override and Continue</Button
                            >
                        </form>
                    </div>

                    <div
                        v-if="
                            request.status === 'pending' &&
                            !request.blocking_reason
                        "
                        class="border-t pt-4"
                    >
                        <Button @click="executeRequest">Execute Request</Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
