<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  employee: any;
}>();
</script>

<template>
  <AppLayout>
    <Head :title="employee.user?.name" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/employees" class="text-blue-600 hover:underline text-sm">← Back to Employees</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ employee.user?.name }}</CardTitle>
              <p class="text-gray-500">{{ employee.job_title }}</p>
            </div>
            <Badge :variant="employee.employment_status === 'active' ? 'default' : 'secondary'">
              {{ employee.employment_status }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-2 gap-4">
            <div>
              <dt class="text-sm text-gray-500">Employee #</dt>
              <dd>{{ employee.employee_number }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Department</dt>
              <dd>{{ employee.department }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Employment Type</dt>
              <dd>{{ employee.employment_type }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Start Date</dt>
              <dd>{{ employee.start_date }}</dd>
            </div>
            <div v-if="employee.reportingManager">
              <dt class="text-sm text-gray-500">Reports To</dt>
              <dd>{{ employee.reportingManager?.name }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>