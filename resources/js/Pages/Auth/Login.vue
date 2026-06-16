<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem, SelectLabel } from '@/components/ui/select';
import { supportedLocales, setI18nLocale } from '@/lib/i18n';
import { Globe } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const locale = ref(supportedLocales[0].code);

const submit = () => {
  setI18nLocale(locale.value);
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Sign In - Enterprise CRM" />
  
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
      <Card>
        <CardHeader class="text-center">
          <div class="flex items-center justify-center gap-2 mb-2">
            <Globe class="h-5 w-5 text-gray-500" />
            <Select v-model="locale" class="w-28">
              <SelectTrigger>
                <SelectValue placeholder="Lang" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="l in supportedLocales" :key="l.code" :value="l.code">{{ l.flag }} {{ l.label }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex items-center justify-center mb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold">CRM</span>
            </div>
          </div>
          <CardTitle class="text-2xl">Welcome back</CardTitle>
          <CardDescription>Sign in to your Enterprise CRM account</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
              <Label for="email">Email address</Label>
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="you@company.com"
                required
                autofocus
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
                autocomplete="current-password"
                :class="{ 'border-red-500': form.errors.password }"
              />
              <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
            </div>
            
            <div class="flex items-center justify-between">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  id="remember"
                  v-model="form.remember"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium">Remember me</span>
              </label>
              <a href="/forgot-password" class="text-sm text-blue-600 hover:text-blue-500">Forgot password?</a>
            </div>
            
            <Button type="submit" class="w-full" :disabled="form.processing">
              Sign In
            </Button>
          </form>
        </CardContent>
        <CardFooter class="text-center">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <a href="/register" class="text-blue-600 hover:text-blue-500 font-medium">Sign up</a>
          </p>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>