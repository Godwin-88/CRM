<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  employee: any;
  managers: { id: string; name: string }[];
}>();

const form = useForm({
  department: props.employee.department,
  job_title: props.employee.job_title,
  employment_type: props.employee.employment_type,
  reporting_manager_id: props.employee.reporting_manager_id,
});

const submit = () => {
  form.put(`/employees/${props.employee.id}`);
};
</script>

<template>
  <AppLayout>
    <Head title="Edit Employee" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/employees" class="text-blue-600 hover:underline text-sm">← Back to Employees</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Edit Employee</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="department">Department</Label>
              <Input id="department" v-model="form.department" />
            </div>

            <div>
              <Label for="job_title">Job Title</Label>
              <Input id="job_title" v-model="form.job_title" />
            </div>

            <div>
              <Label for="employment_type">Employment Type</Label>
              <Select v-model="form.employment_type">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="full_time">Full Time</SelectItem>
                  <SelectItem value="part_time">Part Time</SelectItem>
                  <SelectItem value="contract">Contract</SelectItem>
                  <SelectItem value="intern">Intern</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="reporting_manager_id">Reporting Manager</Label>
              <Select v-model="form.reporting_manager_id">
                <SelectTrigger><SelectValue placeholder="Select manager (optional)" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="manager in managers" :key="manager.id" :value="manager.id">
                    {{ manager.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button type="submit" :disabled="form.processing">Update Employee</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>