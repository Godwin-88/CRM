<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  matter: any;
}>();

const matter = computed(() => props.matter);

const noteForm = useForm({
  body: '',
  type: 'note',
  attachments: [] as any[],
});

const submitNote = () => {
  const formData = new FormData();
  formData.append('body', noteForm.body);
  formData.append('type', noteForm.type);
  noteForm.attachments.forEach((file: any, index: number) => {
    formData.append(`attachments[${index}]`, file);
  });

  fetch(`/legal/${matter.value.id}/notes`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: formData,
  }).then(() => {
    noteForm.reset();
    router.reload();
  });
};

const getStatusBadge = (status: string) => {
  const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    open: 'outline',
    in_progress: 'default',
    pending_external: 'secondary',
    resolved: 'default',
    closed: 'secondary',
  };
  return map[status] || 'secondary';
};

const getTypeBadge = (type: string) => {
  const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    dispute: 'destructive',
    correspondence: 'outline',
    regulatory: 'default',
    advisory: 'secondary',
    custom: 'outline',
  };
  return map[type] || 'secondary';
};
</script>

<template>
  <AppLayout>
    <Head :title="matter.subject" />
    <div class="max-w-7xl mx-auto">
      <div class="mb-4">
        <Link href="/legal" class="text-blue-600 hover:underline text-sm">← Back to Legal Matters</Link>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="col-span-2 space-y-6">
          <!-- Matter Header -->
          <Card>
            <CardHeader>
              <div class="flex justify-between items-start">
                <div>
                  <CardTitle class="text-2xl">{{ matter.subject }}</CardTitle>
                  <div class="flex gap-2 mt-2">
                    <Badge :variant="getStatusBadge(matter.status)">{{ matter.status }}</Badge>
                    <Badge :variant="getTypeBadge(matter.type)" class="capitalize">{{ matter.type }}</Badge>
                  </div>
                </div>
                <Link :href="`/legal/${matter.id}/edit`">
                  <Button size="sm" variant="outline">Edit</Button>
                </Link>
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-gray-700">{{ matter.description }}</p>
              <div class="mt-4 flex gap-2">
<Link href="/legal">
                  <Button size="sm" variant="outline">Close</Button>
                </Link>
              </div>
            </CardContent>
          </Card>

          <!-- Notes -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Notes</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="matter.notes?.length" class="space-y-3 mb-4">
                <div v-for="note in matter.notes" :key="note.id" class="p-3 bg-gray-50 rounded">
                  <div class="flex justify-between">
                    <span class="font-medium text-sm">{{ note.creator?.name }}</span>
                    <span class="text-xs text-gray-500">{{ new Date(note.created_at).toLocaleDateString() }}</span>
                  </div>
                  <p class="text-sm mt-1">{{ note.body }}</p>
                  <div v-if="note.attachments?.length" class="mt-2">
                    <span v-for="attachment in note.attachments" :key="attachment" class="text-xs text-blue-600 underline">
                      {{ attachment }}
                    </span>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm mb-2">No notes.</p>
              <form @submit.prevent="submitNote" class="space-y-2">
                <Textarea v-model="noteForm.body" placeholder="Add a note..." rows="3" />
                <Button type="submit" size="sm" :disabled="noteForm.processing">Add Note</Button>
              </form>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Details</CardTitle>
            </CardHeader>
            <CardContent>
              <dl class="space-y-2">
                <div>
                  <dt class="text-sm text-gray-500">Account</dt>
                  <dd class="font-medium">
                    <Link v-if="matter.account" :href="`/accounts/${matter.account.id}`" class="text-blue-600 hover:underline">
                      {{ matter.account.name }}
                    </Link>
                    <span v-else>—</span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Contact</dt>
                  <dd class="font-medium">
                    <span v-if="matter.contact">
                      {{ matter.contact.first_name }} {{ matter.contact.last_name }}
                    </span>
                    <span v-else>—</span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Assignee</dt>
                  <dd class="font-medium">{{ matter.assignee?.name || 'Unassigned' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Created By</dt>
                  <dd class="font-medium">{{ matter.creator?.name }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Created At</dt>
                  <dd class="font-medium">{{ new Date(matter.created_at).toLocaleString() }}</dd>
                </div>
                <div v-if="matter.resolved_at">
                  <dt class="text-sm text-gray-500">Resolved At</dt>
                  <dd class="font-medium">{{ new Date(matter.resolved_at).toLocaleString() }}</dd>
                </div>
                <div v-if="matter.closed_at">
                  <dt class="text-sm text-gray-500">Closed At</dt>
                  <dd class="font-medium">{{ new Date(matter.closed_at).toLocaleString() }}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
