<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AccountForm from '@/Components/Accounts/AccountForm.vue';
import RuleBuilder from '@/Components/Segments/RuleBuilder.vue';

const props = defineProps<{
  accounts: {
    data: Account[];
  };
  filters: {
    name: string;
    type: string;
    industry: string;
  };
}>();

interface Account {
  id: string;
  name: string;
  type: string;
  industry: string;
  status: string;
  website: string;
  phone: string;
}

const filters = ref({
    name: props.filters?.name || '',
    type: props.filters?.type || '',
    industry: props.filters?.industry || '',
});

watch(filters, (newFilters) => {
    router.get('/accounts', newFilters, { preserveState: true, replace: true });
}, { deep: true });

const isCreateSegmentModalOpen = ref(false);
const isCreateAccountModalOpen = ref(false);

const segmentForm = useForm({
  name: '',
  type: 'demographic',
  criteria: []
});

const submitSegment = () => segmentForm.post('/segments', { onSuccess: () => isCreateSegmentModalOpen.value = false });
</script>

<template>
  <AppLayout>
    <Head title="Accounts" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Accounts</h1>
          <p class="text-gray-500 mt-1">Manage your enterprise organizations.</p>
        </div>
        <div class="flex gap-2">
            <Dialog v-model:open="isCreateSegmentModalOpen">
                <DialogTrigger as-child>
                    <Button variant="outline">Create Segment</Button>
                </DialogTrigger>
                <DialogContent class="max-w-3xl">
                    <DialogHeader><DialogTitle>Create Account Segment</DialogTitle></DialogHeader>
                    <form @submit.prevent="submitSegment" class="space-y-4">
                        <div class="space-y-2">
                            <Label>Segment Name</Label>
                            <Input v-model="segmentForm.name" required />
                        </div>
                        <RuleBuilder :rules="segmentForm.criteria" @update="segmentForm.criteria = $event" />
                        <Button type="submit">Save Segment</Button>
                    </form>
                </DialogContent>
            </Dialog>
            <Dialog v-model:open="isCreateAccountModalOpen">
                <DialogTrigger as-child>
                    <Button>+ Create Account</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create New Account</DialogTitle>
                    </DialogHeader>
                    <AccountForm @close="isCreateAccountModalOpen = false" />
                </DialogContent>
            </Dialog>
        </div>
      </div>

      <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <div class="p-4 border-b flex gap-4">
            <Input v-model="filters.name" placeholder="Filter by Name..." class="max-w-xs" />
            <Input v-model="filters.type" placeholder="Filter by Type..." class="max-w-xs" />
            <Input v-model="filters.industry" placeholder="Filter by Industry..." class="max-w-xs" />
        </div>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Industry</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Website</TableHead>
              <TableHead>Phone</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="account in accounts.data" :key="account.id">
              <TableCell class="font-medium text-gray-900">{{ account.name }}</TableCell>
              <TableCell>{{ account.type }}</TableCell>
              <TableCell>{{ account.industry }}</TableCell>
              <TableCell>{{ account.status }}</TableCell>
              <TableCell>{{ account.website }}</TableCell>
              <TableCell>{{ account.phone }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
