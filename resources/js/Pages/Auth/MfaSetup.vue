<script setup lang="ts">
import { Head, useForm, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const props = defineProps<{
    qrCode: string | null;
    secret: string | null;
    requiresMfa: boolean;
    recoveryCodes: string[] | null;
    success: boolean;
}>();

const form = useForm({
    code: "",
});

const generateSecret = () => {
    router.post(route("mfa.setup.generate"));
};

const submit = () => {
    form.post(route("mfa.enable"));
};
</script>

<template>
    <AppLayout>
        <Head title="Set Up Two-Factor Authentication" />

        <div class="max-w-2xl mx-auto py-8">
            <Card>
                <CardHeader>
                    <CardTitle>Two-Factor Authentication Setup</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!success && !qrCode" class="space-y-4">
                        <p class="text-sm text-gray-600">
                            Click below to generate your TOTP secret and QR
                            code. Scan the QR code with your authenticator app.
                        </p>
                        <Button @click="generateSecret">Generate Secret</Button>
                    </div>

                    <div v-else-if="!success && qrCode" class="space-y-4">
                        <p class="text-sm text-gray-600">
                            Scan this QR code with Google Authenticator, Authy,
                            or similar:
                        </p>
                        <div class="flex justify-center py-4">
                            <img :src="qrCode" alt="MFA QR Code" />
                        </div>
                        <p class="text-xs text-center font-mono">
                            {{ secret }}
                        </p>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <Label for="code"
                                    >Enter verification code from your
                                    app</Label
                                >
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
                            <Button type="submit" :disabled="form.processing"
                                >Verify and Enable</Button
                            >
                        </form>
                    </div>

                    <div v-else class="space-y-4">
                        <h3 class="text-lg font-semibold text-green-600">
                            MFA Enabled Successfully!
                        </h3>
                        <p class="text-sm text-gray-600">
                            Save these recovery codes securely. Each can only be
                            used once.
                        </p>
                        <div class="bg-gray-100 p-4 rounded font-mono">
                            <div
                                v-for="code in recoveryCodes"
                                :key="code"
                                class="text-sm"
                            >
                                {{ code }}
                            </div>
                        </div>
                        <Button @click="() => (window.location.href = '/deals')"
                            >Continue to Dashboard</Button
                        >
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
