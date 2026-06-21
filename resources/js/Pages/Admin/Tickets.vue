<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

onMounted(() => {
  router.visit('/support/tickets')
})
</script>

<template>
  <AppLayout>
    <Head title="Tickets" />
    <div class="max-w-7xl mx-auto space-y-6">
      <Card>
        <CardContent class="pt-6">
          <p class="text-center text-gray-500">Redirecting to ticket management...</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>