<script setup lang="ts">
import { Head, useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const form = useForm({
    code: "",
    use_recovery: false,
});
</script>

<template>
    <AppLayout>
        <Head title="Two-Factor Authentication" />

        <div class="max-w-md mx-auto py-8">
            <Card>
                <CardHeader>
                    <CardTitle>Two-Factor Authentication</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        @submit.prevent="form.post($route('mfa.verify'))"
                        class="space-y-4"
                    >
                        <p class="text-sm text-gray-600">
                            Enter the code from your authenticator app.
                        </p>

                        <div>
                            <Label for="code">TOTP Code</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                required
                                autocomplete="one-time-code"
                            />
                            <p
                                v-if="form.errors.code"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.code }}
                            </p>
                        </div>

                        <p class="text-xs text-gray-500">
                            Can't access your app? Use a recovery code instead
                            (format: rc-XXXXXX).
                        </p>

                        <Button type="submit" :disabled="form.processing"
                            >Verify</Button
                        >
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
