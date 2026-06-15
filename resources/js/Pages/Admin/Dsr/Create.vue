<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

const form = useForm({
    contact_id: "",
    type: "access",
});

const submit = () => {
    form.post(route("admin.dsr.store"));
};
</script>

<template>
    <AppLayout>
        <Head title="Create Data Subject Request" />

        <div class="max-w-2xl mx-auto py-6">
            <Card>
                <CardHeader>
                    <CardTitle>Create Data Subject Request</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Label for="contact_id">Contact ID or Email</Label>
                            <Input
                                id="contact_id"
                                v-model="form.contact_id"
                                placeholder="Enter contact ULID or email address"
                                required
                            />
                            <p
                                v-if="form.errors.contact_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.contact_id }}
                            </p>
                        </div>

                        <div>
                            <Label for="type">Request Type</Label>
                            <Select v-model="form.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="access"
                                        >Data Access (Export)</SelectItem
                                    >
                                    <SelectItem value="erasure"
                                        >Right to Erasure</SelectItem
                                    >
                                    <SelectItem value="rectification"
                                        >Rectification</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>

                        <Button type="submit" :disabled="form.processing"
                            >Create Request</Button
                        >
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
