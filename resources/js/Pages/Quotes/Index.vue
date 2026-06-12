<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileText } from 'lucide-vue-next';
</script>

<template>
  <AppLayout>
    <Head title="Quotes" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Quotes & Proposals</h1>
          <p class="text-gray-500">Manage and share quotes with your prospects.</p>
        </div>
        <Link href="/admin/quote-templates">
          <Button variant="outline">Manage Templates</Button>
        </Link>
      </div>

      <Card>
        <CardContent class="p-8 text-center">
          <FileText class="h-12 w-12 mx-auto mb-4 text-gray-400" />
          <p class="text-gray-500">Quotes functionality coming soon. Access quote templates via Admin menu.</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>