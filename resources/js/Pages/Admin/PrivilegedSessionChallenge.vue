<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const routeFn = route

const form = useForm({
    password: "",
    mfa_code: "",
});
</script>

<template>
    <AppLayout>
        <Head title="Privileged Session Required" />

        <div class="max-w-md mx-auto py-6">
            <Card>
                <CardHeader>
                    <CardTitle>Privileged Session Required</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-gray-600 mb-4">
                        This action requires privileged session access. Please
                        re-authenticate to continue.
                    </p>

                    <form
                        @submit.prevent="
                            () => form.post(routeFn('admin.privileged.enter'))
                        "
                        class="space-y-4"
                    >
                        <div>
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="current-password"
                            />
                            <p
                                v-if="form.errors.password"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div>
                            <Label for="mfa_code">MFA Code (if enabled)</Label>
                            <Input
                                id="mfa_code"
                                v-model="form.mfa_code"
                                type="text"
                                placeholder="6-digit code"
                            />
                            <p
                                v-if="form.errors.mfa_code"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.mfa_code }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="form.processing"
                            >Enter Privileged Mode</Button
                        >
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
