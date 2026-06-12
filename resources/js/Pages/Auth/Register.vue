<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem, SelectLabel } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'agent',
});

const roleOptions = [
  { value: 'agent', label: 'Agent', description: 'Can create and edit contacts/accounts, view segments' },
  { value: 'read-only', label: 'Read-Only', description: 'View-only access to contacts, accounts, and segments' },
];

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Sign Up - Enterprise CRM" />
  
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
      <Card>
        <CardHeader class="text-center">
          <div class="flex items-center justify-center mb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold">CRM</span>
            </div>
          </div>
          <CardTitle class="text-2xl">Create your account</CardTitle>
          <CardDescription>Get started with Enterprise CRM</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
              <Label for="name">Full name</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="John Doe"
                required
                autofocus
                :class="{ 'border-red-500': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            
            <div class="space-y-2">
              <Label for="email">Email address</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="you@company.com"
                required
                autocomplete="email"
                :class="{ 'border-red-500': form.errors.email }"
              />
              <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
            </div>
            
            <div class="space-y-2">
              <Label for="password">Password</Label>
              <Input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
                :class="{ 'border-red-500': form.errors.password }"
              />
              <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
            </div>
            
            <div class="space-y-2">
              <Label for="password_confirmation">Confirm password</Label>
              <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
                :class="{ 'border-red-500': form.errors.password_confirmation }"
              />
            </div>
            
            <div class="space-y-2">
              <Label for="role">Account type</Label>
              <Select v-model="form.role">
                <SelectTrigger id="role" :class="{ 'border-red-500': form.errors.role }">
                  <SelectValue placeholder="Select account type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectLabel>Choose your role</SelectLabel>
                  <SelectItem v-for="option in roleOptions" :key="option.value" :value="option.value">
                    <div>
                      <div class="font-medium">{{ option.label }}</div>
                      <div class="text-sm text-gray-500">{{ option.description }}</div>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.role" class="text-sm text-red-600">{{ form.errors.role }}</p>
            </div>
            
            <Alert v-if="form.errors.general" variant="destructive" class="mt-4">
              <AlertCircle class="h-4 w-4" />
              <AlertDescription>{{ form.errors.general }}</AlertDescription>
            </Alert>
            
            <Button type="submit" class="w-full" :disabled="form.processing">
              Create Account
            </Button>
          </form>
        </CardContent>
        <CardFooter class="text-center">
          <p class="text-sm text-gray-600">
            Already have an account?
            <a href="/login" class="text-blue-600 hover:text-blue-500 font-medium">Sign in</a>
          </p>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>