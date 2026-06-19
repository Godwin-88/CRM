<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps<{
  employees: any;
  departments: any[];
  headcountSummary: Record<string, number>;
  filters: any;
}>();
</script>

<template>
  <AppLayout>
    <Head title="Employees" />
    
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Employees</h1>
        <Link href="/employees/create">
          <Button><PlusIcon class="w-4 h-4 mr-2" />New Employee</Button>
        </Link>
      </div>

      <Card class="mb-6">
        <CardHeader><CardTitle>Headcount Summary</CardTitle></CardHeader>
        <CardContent>
          <div class="grid grid-cols-4 gap-4">
            <div v-for="dept in departments" :key="dept.id" class="text-center">
              <p class="text-sm text-gray-500">{{ dept.name }}</p>
              <p class="text-2xl font-bold">{{ headcountSummary[dept.name] || 0 }} / {{ dept.target_count }}</p>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: Math.min(100, ((headcountSummary[dept.name] || 0) / dept.target_count * 100)) + '%' }"></div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Employee #</TableHead>
                <TableHead>Department</TableHead>
                <TableHead>Job Title</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="employee in employees.data" :key="employee.id">
                <TableCell>{{ employee.user?.name }}</TableCell>
                <TableCell>{{ employee.employee_number }}</TableCell>
                <TableCell>{{ employee.department }}</TableCell>
                <TableCell>{{ employee.job_title }}</TableCell>
                <TableCell>
                  <Badge :variant="employee.employment_status === 'active' ? 'default' : 'secondary'">
                    {{ employee.employment_status }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Link :href="`/employees/${employee.id}`" class="text-blue-600 hover:underline text-sm">View</Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>