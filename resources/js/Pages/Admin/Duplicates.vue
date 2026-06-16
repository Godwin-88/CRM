<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, GitMerge } from 'lucide-vue-next';

interface Contact {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  owner: { id: string; name: string } | null;
}

const props = defineProps<{
  contacts: { data: Contact[] };
}>();

const search = ref({ email: '', first_name: '', last_name: '', phone: '' });
const candidates = ref<any[]>([]);
const isScanning = ref(false);

onMounted(() => {});

const rescan = async () => {
  isScanning.value = true;
  try {
    const response = await fetch('/api/v1/contacts/check-duplicates', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
      body: JSON.stringify(search.value),
    });
    const data = await response.json();
    candidates.value = data.candidates || [];
  } catch {
    candidates.value = [];
  } finally {
    isScanning.value = false;
  }
};

const mergeCandidate = (discardedId: string) => {
  const targetId = prompt('Target surviving contact ID:');
  if (!targetId) return;
  router.post('/api/v1/contacts/merge', {
    surviving_id: targetId,
    discarded_id: discardedId,
    field_selections: {},
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Duplicate Contacts" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold">Duplicate Contacts</h1>
        <p class="text-gray-500">Scan and resolve duplicate contact records.</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Scan Parameters</CardTitle>
        </CardHeader>
        <CardContent class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium">Email</label>
            <Input v-model="search.email" placeholder="Filter by email" />
          </div>
          <div>
            <label class="text-sm font-medium">First Name</label>
            <Input v-model="search.first_name" placeholder="Filter by first name" />
          </div>
          <div>
            <label class="text-sm font-medium">Last Name</label>
            <Input v-model="search.last_name" placeholder="Filter by last name" />
          </div>
          <div>
            <label class="text-sm font-medium">Phone</label>
            <Input v-model="search.phone" placeholder="Filter by phone" />
          </div>
        </CardContent>
        <CardContent>
          <Button @click="rescan" :disabled="isScanning">
            <Search class="h-4 w-4 mr-2" />
            {{ isScanning ? 'Scanning...' : 'Scan for Duplicates' }}
          </Button>
        </CardContent>
      </Card>

      <Card v-if="candidates.length">
        <CardHeader>
          <CardTitle>Potential Duplicates</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Owner</TableHead>
                <TableHead class="text-right">Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="candidate in candidates" :key="candidate.id">
                <TableCell class="font-medium">{{ candidate.first_name }} {{ candidate.last_name }}</TableCell>
                <TableCell>{{ candidate.email }}</TableCell>
                <TableCell>{{ candidate.phone || '—' }}</TableCell>
                <TableCell>{{ candidate.owner?.name || '—' }}</TableCell>
                <TableCell class="text-right">
                  <Button variant="outline" size="sm" @click="mergeCandidate(candidate.id)">
                    <GitMerge class="h-4 w-4 mr-2" /> Merge
                  </Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
