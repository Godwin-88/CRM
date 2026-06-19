<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Paperclip, Plus, Link2, ShieldCheck, Clock, CheckCircle2, AlertTriangle } from 'lucide-vue-next';

const props = defineProps<{
  matter: {
    id: string;
    subject: string;
    description: string;
    status: string;
    type: string;
    assigned_to: string | null;
    account: { id: string; name: string } | null;
    contact: { id: string; first_name: string; last_name: string; email?: string } | null;
    assignee: { id: string; name: string } | null;
    creator: { id: string; name: string };
    created_at: string;
    resolved_at: string | null;
    closed_at: string | null;
    resolution_notes: string | null;
    notes: {
      id: string;
      body: string;
      type: string;
      created_at: string;
      creator: { id: string; name: string };
      attachments: string[];
    }[];
    contracts: { id: string; title: string; status: string }[];
  };
}>();

const matter = computed(() => props.matter);
const activeTab = ref('notes');
const noteForm = useForm({
  body: '',
  type: 'note',
});
const attachmentForm = useForm({
  attachment: null as File | null,
});
const attachmentUrls = ref<Record<string, string>>({});
const attachmentLoading = ref<Record<string, boolean>>({});
const attachmentError = ref('');

const noteStatusClass = (status: string) => {
  const map: Record<string, string> = {
    open: 'bg-gray-100 text-gray-700 ring-gray-200',
    in_progress: 'bg-blue-50 text-blue-700 ring-blue-200',
    pending_external: 'bg-amber-50 text-amber-700 ring-amber-200',
    resolved: 'bg-green-50 text-green-700 ring-green-200',
    closed: 'bg-slate-100 text-slate-700 ring-slate-200',
  };

  return map[status] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const typeClass = (type: string) => {
  const map: Record<string, string> = {
    dispute: 'bg-rose-50 text-rose-700 ring-rose-200',
    correspondence: 'bg-blue-50 text-blue-700 ring-blue-200',
    regulatory: 'bg-purple-50 text-purple-700 ring-purple-200',
    advisory: 'bg-green-50 text-green-700 ring-green-200',
    custom: 'bg-gray-100 text-gray-700 ring-gray-200',
  };

  return map[type] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const formatDate = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatDateTime = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleString();
};

const capitalize = (value?: string) => {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const submitNote = () => {
  if (!noteForm.body.trim()) return;

  const formData = new FormData();
  formData.append('body', noteForm.body);
  formData.append('type', noteForm.type);

  fetch(`/legal/${matter.value.id}/notes`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: formData,
  }).then(() => {
    noteForm.reset();
    router.reload({ preserveScroll: true });
  });
};

const selectAttachment = (event: Event) => {
  const input = event.target as HTMLInputElement;
  attachmentForm.attachment = input.files?.[0] || null;
};

const uploadAttachment = () => {
  if (!attachmentForm.attachment) return;

  attachmentError.value = '';
  const formData = new FormData();
  formData.append('attachment', attachmentForm.attachment);

  fetch(`/legal/${matter.value.id}/attachments`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
    },
    body: formData,
  }).then(() => {
    attachmentForm.reset();
    router.reload({ preserveScroll: true });
  });
};

const getSignedUrl = async (path: string) => {
  attachmentError.value = '';
  attachmentLoading.value = { ...attachmentLoading.value, [path]: true };

  try {
    const response = await fetch(`/legal/${matter.value.id}/attachments/signed-url`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
      },
      body: JSON.stringify({ path }),
    });

    if (!response.ok) {
      throw new Error('Unable to load signed attachment URL.');
    }

    const data = await response.json();
    attachmentUrls.value = { ...attachmentUrls.value, [path]: data.url };
  } catch (error) {
    attachmentError.value = error instanceof Error ? error.message : 'Unable to load signed attachment URL.';
  } finally {
    attachmentLoading.value = { ...attachmentLoading.value, [path]: false };
  }
};

const attachmentPaths = computed(() => {
  const paths = new Set<string>();
  matter.value.notes?.forEach((note) => note.attachments?.forEach((attachment) => paths.add(attachment)));
  return Array.from(paths);
});

const noteCount = computed(() => matter.value.notes?.length || 0);
const attachmentCount = computed(() => attachmentPaths.value.length);
const isResolved = computed(() => ['resolved', 'closed'].includes(matter.value.status));
</script>

<template>
  <AppLayout>
    <Head :title="matter.subject" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="mb-4">
        <Link href="/legal" class="text-blue-600 hover:underline text-sm">← Back to Legal Matters</Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
              <div class="mb-3 flex flex-wrap gap-2">
                <Badge class="capitalize ring-1" :class="noteStatusClass(matter.status)">{{ capitalize(matter.status) }}</Badge>
                <Badge class="capitalize ring-1" :class="typeClass(matter.type)">{{ capitalize(matter.type) }}</Badge>
              </div>
              <CardTitle class="text-2xl">{{ matter.subject }}</CardTitle>
              <p class="mt-2 text-sm text-gray-500">
                {{ matter.account?.name || 'No account' }} · {{ matter.contact ? `${matter.contact.first_name} ${matter.contact.last_name}` : 'No contact' }}
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Link :href="`/legal/${matter.id}/edit`">
                <Button size="sm" variant="outline">Edit Matter</Button>
              </Link>
              <Button size="sm" variant="secondary" @click="activeTab = 'notes'">
                <Plus class="mr-2 h-4 w-4" />
                Add Note
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-gray-700 whitespace-pre-line">{{ matter.description || 'No description provided.' }}</p>
          <div v-if="matter.resolution_notes" class="mt-4 rounded-lg border bg-gray-50 p-3 text-sm">
            <div class="font-medium">Resolution notes</div>
            <p>{{ matter.resolution_notes }}</p>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-3 md:grid-cols-4">
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Notes</div>
            <div class="mt-1 text-2xl font-semibold">{{ noteCount }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Attachments</div>
            <div class="mt-1 text-2xl font-semibold">{{ attachmentCount }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Linked contracts</div>
            <div class="mt-1 text-2xl font-semibold">{{ matter.contracts?.length || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Assignee</div>
            <div class="mt-1 text-lg font-semibold">{{ matter.assignee?.name || 'Unassigned' }}</div>
          </CardContent>
        </Card>
      </div>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList class="grid w-full grid-cols-2 md:grid-cols-4">
          <TabsTrigger value="notes">Notes</TabsTrigger>
          <TabsTrigger value="attachments">Attachments</TabsTrigger>
          <TabsTrigger value="contracts">Linked Contracts</TabsTrigger>
          <TabsTrigger value="timeline">Timeline</TabsTrigger>
        </TabsList>

        <TabsContent value="notes" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
          <div class="space-y-4">
            <Card>
              <CardHeader><CardTitle class="text-lg">Append-only case notes</CardTitle></CardHeader>
              <CardContent>
                <div v-if="matter.notes?.length" class="space-y-3">
                  <div v-for="note in matter.notes" :key="note.id" class="rounded-xl border p-4">
                    <div class="mb-2 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                      <div>
                        <div class="font-medium">{{ note.creator?.name || 'Unknown user' }}</div>
                        <div class="text-xs text-gray-500">{{ formatDateTime(note.created_at) }}</div>
                      </div>
                      <Badge variant="outline" class="w-fit capitalize">{{ note.type || 'note' }}</Badge>
                    </div>
                    <p class="whitespace-pre-line text-sm text-gray-700">{{ note.body }}</p>
                    <div v-if="note.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                      <a
                        v-for="attachment in note.attachments"
                        :key="attachment"
                        :href="attachmentUrls[attachment] || '#'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-md border bg-gray-50 px-2 py-1 text-xs text-blue-700 hover:bg-gray-100"
                        @click.prevent="getSignedUrl(attachment)"
                      >
                        <Paperclip class="mr-1 h-3 w-3" />
                        {{ attachment.split('/').pop() }}
                      </a>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500">No notes yet. Add the first immutable case note below.</p>

                <form @submit.prevent="submitNote" class="mt-4 space-y-3">
                  <Label for="note-body">New note</Label>
                  <Textarea id="note-body" v-model="noteForm.body" placeholder="Record the legal correspondence, analysis, or next action..." rows="5" />
                  <div class="flex items-center justify-between gap-2">
                    <Select v-model="noteForm.type">
                      <SelectTrigger class="w-[180px]">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="note">Note</SelectItem>
                        <SelectItem value="correspondence">Correspondence</SelectItem>
                        <SelectItem value="decision">Decision</SelectItem>
                        <SelectItem value="escalation">Escalation</SelectItem>
                      </SelectContent>
                    </Select>
                    <Button type="submit" :disabled="noteForm.processing || !noteForm.body.trim()">
                      Add immutable note
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
          </div>

          <aside class="space-y-4">
            <Card>
              <CardHeader><CardTitle class="text-lg">Matter controls</CardTitle></CardHeader>
              <CardContent class="space-y-3">
                <Alert v-if="isResolved">
                  <CheckCircle2 class="h-4 w-4" />
                  <AlertDescription>This matter is resolved or closed. Notes remain append-only.</AlertDescription>
                </Alert>
                <Alert v-else>
                  <ShieldCheck class="h-4 w-4" />
                  <AlertDescription>Use notes for immutable chronology. Edit matter metadata only when facts change.</AlertDescription>
                </Alert>
              </CardContent>
            </Card>
          </aside>
        </TabsContent>

        <TabsContent value="attachments">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="text-lg">R2 attachments</CardTitle>
                  <p class="text-sm text-gray-500">Files are stored privately and opened through 15-minute signed URLs.</p>
                </div>
              </div>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="rounded-lg border border-dashed p-4">
                <div class="space-y-3">
                  <Label for="attachment">Upload attachment</Label>
                  <Input id="attachment" type="file" @change="selectAttachment" />
                  <Button :disabled="!attachmentForm.attachment || attachmentForm.processing" @click="uploadAttachment">
                    <Paperclip class="mr-2 h-4 w-4" />
                    Upload to R2
                  </Button>
                  <p v-if="attachmentError" class="text-sm text-red-600">{{ attachmentError }}</p>
                </div>
              </div>

              <div v-if="attachmentPaths.length" class="space-y-2">
                <div v-for="path in attachmentPaths" :key="path" class="flex items-center justify-between gap-3 rounded-lg border p-3">
                  <div class="min-w-0">
                    <div class="truncate text-sm font-medium">{{ path.split('/').pop() }}</div>
                    <div class="truncate text-xs text-gray-500">{{ path }}</div>
                  </div>
                  <Button size="sm" variant="outline" :disabled="attachmentLoading[path]" @click="getSignedUrl(path)">
                    <Clock v-if="attachmentLoading[path]" class="mr-2 h-4 w-4 animate-spin" />
                    <Paperclip v-else class="mr-2 h-4 w-4" />
                    Open
                  </Button>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No attachments have been uploaded for this matter.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="contracts">
          <Card>
            <CardHeader><CardTitle class="text-lg">Linked contracts</CardTitle></CardHeader>
            <CardContent>
              <div v-if="matter.contracts?.length" class="space-y-3">
                <Link v-for="linkedContract in matter.contracts" :key="linkedContract.id" :href="`/contracts/${linkedContract.id}`" class="block rounded-lg border p-3 hover:bg-gray-50">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <div class="font-medium">{{ linkedContract.title }}</div>
                      <div class="text-xs text-gray-500">Open contract detail</div>
                    </div>
                    <Badge class="capitalize">{{ linkedContract.status }}</Badge>
                  </div>
                </Link>
              </div>
              <div v-else class="rounded-lg border border-dashed p-8 text-center text-sm text-gray-500">
                <Link2 class="mx-auto mb-2 h-6 w-6 text-gray-400" />
                No contracts are linked to this matter yet.
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="timeline">
          <Card>
            <CardHeader><CardTitle class="text-lg">Case timeline</CardTitle></CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex gap-3">
                  <div class="mt-1 h-2 w-2 rounded-full bg-blue-500" />
                  <div>
                    <div class="font-medium">Matter created</div>
                    <div class="text-sm text-gray-500">{{ formatDateTime(matter.created_at) }} by {{ matter.creator?.name }}</div>
                  </div>
                </div>
                <div v-if="matter.resolved_at" class="flex gap-3">
                  <div class="mt-1 h-2 w-2 rounded-full bg-green-500" />
                  <div>
                    <div class="font-medium">Resolved</div>
                    <div class="text-sm text-gray-500">{{ formatDateTime(matter.resolved_at) }}</div>
                  </div>
                </div>
                <div v-if="matter.closed_at" class="flex gap-3">
                  <div class="mt-1 h-2 w-2 rounded-full bg-slate-500" />
                  <div>
                    <div class="font-medium">Closed</div>
                    <div class="text-sm text-gray-500">{{ formatDateTime(matter.closed_at) }}</div>
                  </div>
                </div>
                <div v-if="!isResolved" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                  <AlertTriangle class="mr-2 inline h-4 w-4" />
                  Matter remains open. Keep notes chronological and append-only.
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
