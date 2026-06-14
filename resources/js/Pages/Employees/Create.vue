<script setup lang="ts">
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  users: { id: string; name: string; email: string }[];
  managers: { id: string; name: string }[];
}>();

const form = useForm({
  user_id: '',
  department: '',
  job_title: '',
  employment_type: 'full_time',
  start_date: '',
  reporting_manager_id: null as string | null,
});

const submit = () => {
  form.post('/employees');
};
</script>

<template>
  <AppLayout>
    <Head title="Create Employee" />
    
    <div class="max-w-2xl mx-auto">
      <div class="mb-4">
        <Link href="/employees" class="text-blue-600 hover:underline text-sm">← Back to Employees</Link>
      </div>

      <Card>
        <CardHeader><CardTitle>Create Employee</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <Label for="user_id">User</Label>
              <Select v-model="form.user_id">
                <SelectTrigger><SelectValue placeholder="Select user" /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.email }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="department">Department</Label>
              <Input id="department" v-model="form.department" placeholder="e.g., Engineering, Sales" />
            </div>

            <div>
              <Label for="job_title">Job Title</Label>
              <Input id="job_title" v-model="form.job_title" placeholder="e.g., Developer" />
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
              <Label for="start_date">Start Date</Label>
              <Input id="start_date" type="date" v-model="form.start_date" />
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

            <Button type="submit" :disabled="form.processing">Create Employee</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>