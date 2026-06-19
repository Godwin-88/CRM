<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Activity, AlertTriangle, CalendarClock, CheckCircle2, Clock, Copy, Download, FileText, Link2, RefreshCw, Send, ShieldCheck, UserCheck } from 'lucide-vue-next';

interface Contract {
  id: string;
  title: string;
  type: string;
  status: string;
  value: number;
  currency: string;
  start_date: string;
  end_date: string;
  document_path: string;
  e_signature_status: string | null;
  template: { id: string; name: string } | null;
  account: { id: string; name: string } | null;
  contact: { id: string; first_name: string; last_name: string; email: string } | null;
  created_by: string;
  createdBy: { id: string; name: string };
  account_manager_id: string;
  accountManager: { id: string; name: string };
  current_version: number;
  activated_at: string | null;
  terminated_at: string | null;
  termination_reason: string | null;
  custom_variables: Record<string, any>;
  signed_at: string | null;
  tags: { id: string; name: string; color: string }[];
  legal_matter_id: string | null;
  suppress_reminders: boolean;
  days_remaining: number | null;
}

interface ContractVersion {
  id: string;
  version_number: number;
  status: string;
  document_path: string;
  page_count: number;
  file_size: number;
  created_at: string;
  createdBy: { id: string; name: string };
}

interface ContractSignatory {
  id: string;
  name: string;
  email: string;
  role: string;
  status: string;
  signed_at: string | null;
  signing_order: number;
}

interface ContractMilestone {
  id: string;
  name: string;
  description: string;
  due_date: string;
  status: string;
  completed_at: string | null;
  completion_note: string | null;
}

interface ContractKpiValue {
  id: string;
  value: any;
  period_start: string;
  period_end: string;
  kpiField: { id: string; name: string; type: string };
}

const props = defineProps<{
  contract: Contract;
  activeVersion: ContractVersion | null;
  versions: { data: ContractVersion[] };
  signatories: ContractSignatory[];
  milestones: ContractMilestone[];
  kpiValues: ContractKpiValue[];
}>();

const contract = computed(() => props.contract);
const activeTab = ref('overview');
const previewUrl = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const signatureDialogOpen = ref(false);

const signatureForm = useForm({
  provider: 'internal',
  mode: 'parallel',
  signatories: props.contract.contact
    ? [{ name: `${props.contract.contact.first_name} ${props.contract.contact.last_name}`, email: props.contract.contact.email, role: 'counterparty', order: 1, is_sequential: false }]
    : [{ name: '', email: '', role: 'counterparty', order: 1, is_sequential: false }],
});

const getStatusVariant = (status: string) => {
  const map: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline',
    sent: 'secondary',
    signed: 'default',
    active: 'default',
    expiring: 'outline',
    expired: 'destructive',
    declined: 'destructive',
    terminated: 'destructive',
  };
  return map[status] || 'secondary';
};

const statusClass = (status: string) => {
  const map: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 ring-gray-200',
    sent: 'bg-blue-50 text-blue-700 ring-blue-200',
    signed: 'bg-teal-50 text-teal-700 ring-teal-200',
    active: 'bg-green-50 text-green-700 ring-green-200',
    expiring: 'bg-amber-50 text-amber-700 ring-amber-200',
    expired: 'bg-red-50 text-red-700 ring-red-200',
    declined: 'bg-rose-50 text-rose-700 ring-rose-200',
    terminated: 'bg-slate-100 text-slate-700 ring-slate-200',
  };

  return map[status] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const signatoryStatusClass = (status: string) => {
  const map: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-700 ring-gray-200',
    viewed: 'bg-blue-50 text-blue-700 ring-blue-200',
    signed: 'bg-green-50 text-green-700 ring-green-200',
    declined: 'bg-rose-50 text-rose-700 ring-rose-200',
  };

  return map[status] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const milestoneStatusClass = (status: string) => {
  const map: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-700 ring-gray-200',
    completed: 'bg-green-50 text-green-700 ring-green-200',
    missed: 'bg-red-50 text-red-700 ring-red-200',
  };

  return map[status] || 'bg-gray-100 text-gray-700 ring-gray-200';
};

const formatDate = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatDateTime = (value?: string | null) => {
  if (!value) return '—';
  return new Date(value).toLocaleString();
};

const formatCurrency = (value?: number, currency?: string) => {
  if (!value) return '—';
  return `${currency || 'USD'} ${Number(value).toLocaleString()}`;
};

const capitalize = (value?: string) => {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const daysText = computed(() => {
  if (contract.value.days_remaining === null || contract.value.days_remaining === undefined) return 'No end date';
  if (contract.value.days_remaining < 0) return `${Math.abs(contract.value.days_remaining)} days overdue`;
  if (contract.value.days_remaining === 0) return 'Due today';
  return `${contract.value.days_remaining} days remaining`;
});

const performancePct = computed(() => {
  if (!props.milestones.length) return 0;
  const completed = props.milestones.filter((milestone) => milestone.status === 'completed').length;
  return Math.round((completed / props.milestones.length) * 100);
});

const signedCount = computed(() => props.signatories.filter((signatory) => signatory.status === 'signed').length);
const pendingCount = computed(() => props.signatories.filter((signatory) => ['pending', 'viewed'].includes(signatory.status)).length);

const downloadPdf = async () => {
  previewError.value = '';
  previewLoading.value = true;

  try {
    const response = await fetch(`/contracts/${contract.value.id}/download`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
      },
    });

    if (!response.ok) {
      throw new Error('Unable to load signed PDF URL.');
    }

    const data = await response.json();
    previewUrl.value = data.url;
    activeTab.value = 'pdf';
  } catch (error) {
    previewError.value = error instanceof Error ? error.message : 'Unable to load signed PDF URL.';
  } finally {
    previewLoading.value = false;
  }
};

const regeneratePdf = async () => {
  if (!confirm('Regenerate contract PDF? This will create a new version.')) return;

  await fetch(`/contracts/${contract.value.id}/regenerate`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
      'Accept': 'application/json',
    },
  });

  router.reload();
};

const duplicateContract = async () => {
  if (!confirm('Duplicate this contract? A new draft will be created.')) return;

  await fetch(`/contracts/${contract.value.id}/duplicate`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
      'Accept': 'application/json',
    },
  });

  router.visit('/contracts');
};

const submitSignature = () => {
  signatureForm.post(`/contracts/${contract.value.id}/send-signature`, {
    preserveScroll: true,
    onSuccess: () => {
      signatureDialogOpen.value = false;
      router.reload();
    },
  });
};

const addSignatory = () => {
  signatureForm.signatories.push({ name: '', email: '', role: 'counterparty', order: signatureForm.signatories.length + 1, is_sequential: signatureForm.mode === 'sequential' });
};

const removeSignatory = (index: number) => {
  if (signatureForm.signatories.length === 1) {
    signatureForm.signatories[0] = { name: '', email: '', role: 'counterparty', order: 1, is_sequential: false };
    return;
  }

  signatureForm.signatories.splice(index, 1);
};

const refreshOrders = () => {
  signatureForm.signatories.forEach((signatory, index) => {
    signatory.order = index + 1;
    signatory.is_sequential = signatureForm.mode === 'sequential';
  });
};

const variableEntries = computed(() => Object.entries(contract.value.custom_variables || {}));
const nextMilestone = computed(() => props.milestones.find((milestone) => milestone.status === 'pending') || props.milestones[0] || null);
const healthClass = computed(() => {
  if (['expired', 'declined', 'terminated'].includes(contract.value.status)) return 'text-red-600';
  if (contract.value.status === 'expiring' || (contract.value.days_remaining !== null && contract.value.days_remaining <= 30)) return 'text-amber-600';
  if (contract.value.status === 'active' || contract.value.status === 'signed') return 'text-green-600';
  return 'text-gray-600';
});
</script>

<template>
  <AppLayout>
    <Head :title="contract.title" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="mb-4">
        <Link href="/contracts" class="text-blue-600 hover:underline text-sm">← Back to Contracts</Link>
      </div>

      <Card>
        <CardHeader>
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <Badge class="capitalize ring-1" :class="statusClass(contract.status)">{{ capitalize(contract.status) }}</Badge>
                <Badge variant="outline" class="capitalize">{{ capitalize(contract.type) }}</Badge>
                <Badge v-if="contract.e_signature_status" variant="secondary">e-Sig: {{ contract.e_signature_status }}</Badge>
                <Badge v-for="tag in contract.tags" :key="tag.id" class="text-white" :style="{ backgroundColor: tag.color }">{{ tag.name }}</Badge>
              </div>
              <CardTitle class="text-2xl">{{ contract.title }}</CardTitle>
              <p class="mt-2 text-sm text-gray-500">
                {{ contract.account?.name || 'No account' }} · {{ contract.contact ? `${contract.contact.first_name} ${contract.contact.last_name}` : 'No contact' }}
              </p>
            </div>
            <div class="text-right">
              <div class="text-3xl font-semibold" :class="healthClass">{{ formatCurrency(contract.value, contract.currency) }}</div>
              <div class="text-sm text-gray-500">{{ daysText }}</div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Button size="sm" :disabled="!contract.document_path || previewLoading" @click="downloadPdf">
              <Download class="mr-2 h-4 w-4" />
              Download PDF
            </Button>
            <Button size="sm" variant="outline" :disabled="!contract.document_path" @click="downloadPdf">
              <FileText class="mr-2 h-4 w-4" />
              Preview PDF
            </Button>
            <Button size="sm" variant="outline" @click="regeneratePdf">
              <RefreshCw class="mr-2 h-4 w-4" />
              Regenerate
            </Button>
            <Button size="sm" variant="outline" @click="duplicateContract">
              <Copy class="mr-2 h-4 w-4" />
              Duplicate
            </Button>
            <Link :href="`/contracts/${contract.id}/edit`">
              <Button size="sm" variant="ghost">Edit</Button>
            </Link>
            <Dialog v-model:open="signatureDialogOpen">
              <Button size="sm" variant="secondary" as-child :disabled="!['draft', 'sent'].includes(contract.status)">
                <span>
                  <Send class="mr-2 h-4 w-4" />
                  Send for Signature
                </span>
              </Button>
              <DialogContent class="max-w-3xl">
                <DialogHeader>
                  <DialogTitle>Send contract for e-signature</DialogTitle>
                  <DialogDescription>Choose the signing flow and confirm signatories for {{ contract.title }}.</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitSignature" class="space-y-4">
                  <div class="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label>Provider</Label>
                      <Select v-model="signatureForm.provider">
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="internal">Built-in OTP</SelectItem>
                          <SelectItem value="docusign">DocuSign</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div>
                      <Label>Signing order</Label>
                      <Select v-model="signatureForm.mode" @update:model-value="refreshOrders">
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="parallel">Parallel</SelectItem>
                          <SelectItem value="sequential">Sequential</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  <div class="space-y-3">
                    <div v-for="(signatory, index) in signatureForm.signatories" :key="index" class="rounded-lg border p-3">
                      <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-medium">Signatory {{ index + 1 }}</div>
                        <Button type="button" size="sm" variant="ghost" @click="removeSignatory(index)">Remove</Button>
                      </div>
                      <div class="grid gap-3 md:grid-cols-4">
                        <Input v-model="signatory.name" placeholder="Name" />
                        <Input v-model="signatory.email" placeholder="Email" />
                        <Select v-model="signatory.role">
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="counterparty">Counterparty</SelectItem>
                            <SelectItem value="witness">Witness</SelectItem>
                            <SelectItem value="approver">Approver</SelectItem>
                          </SelectContent>
                        </Select>
                        <Input v-model="signatory.order" type="number" min="1" @change="refreshOrders" />
                      </div>
                    </div>
                  </div>

                  <Button type="button" variant="outline" class="w-full" @click="addSignatory">Add signatory</Button>

                  <DialogFooter>
                    <Button type="button" variant="outline" @click="signatureDialogOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="signatureForm.processing">
                      <Send class="mr-2 h-4 w-4" />
                      Queue Signature Request
                    </Button>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-3 md:grid-cols-4">
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Version</div>
            <div class="mt-1 text-2xl font-semibold">v{{ contract.current_version || 1 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Signatures</div>
            <div class="mt-1 text-2xl font-semibold">{{ signedCount }}/{{ signatories.length || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Pending signatories</div>
            <div class="mt-1 text-2xl font-semibold">{{ pendingCount }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="text-sm text-gray-500">Milestone completion</div>
            <div class="mt-1 text-2xl font-semibold">{{ performancePct }}%</div>
          </CardContent>
        </Card>
      </div>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList class="grid w-full grid-cols-2 md:grid-cols-6">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="pdf">PDF</TabsTrigger>
          <TabsTrigger value="signatures">Signatures</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="renewal">Renewal</TabsTrigger>
          <TabsTrigger value="versions">Versions</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
          <div class="space-y-6">
            <Card>
              <CardHeader><CardTitle class="text-lg">Contract details</CardTitle></CardHeader>
              <CardContent>
                <dl class="grid gap-4 md:grid-cols-2">
                  <div><dt class="text-sm text-gray-500">Account</dt><dd class="font-medium"><Link v-if="contract.account" :href="`/accounts/${contract.account.id}`" class="text-blue-600 hover:underline">{{ contract.account.name }}</Link><span v-else>—</span></dd></div>
                  <div><dt class="text-sm text-gray-500">Contact</dt><dd class="font-medium">{{ contract.contact ? `${contract.contact.first_name} ${contract.contact.last_name}` : '—' }}</dd></div>
                  <div><dt class="text-sm text-gray-500">Template</dt><dd class="font-medium">{{ contract.template?.name || 'Manual contract' }}</dd></div>
                  <div><dt class="text-sm text-gray-500">Account manager</dt><dd class="font-medium">{{ contract.accountManager?.name || 'Unassigned' }}</dd></div>
                  <div><dt class="text-sm text-gray-500">Start Date</dt><dd class="font-medium">{{ formatDate(contract.start_date) }}</dd></div>
                  <div><dt class="text-sm text-gray-500">End Date</dt><dd class="font-medium">{{ formatDate(contract.end_date) }}</dd></div>
                  <div><dt class="text-sm text-gray-500">Activated At</dt><dd class="font-medium">{{ formatDateTime(contract.activated_at) }}</dd></div>
                  <div><dt class="text-sm text-gray-500">Terminated At</dt><dd class="font-medium">{{ formatDateTime(contract.terminated_at) }}</dd></div>
                  <div v-if="contract.termination_reason" class="md:col-span-2"><dt class="text-sm text-gray-500">Termination Reason</dt><dd class="font-medium">{{ contract.termination_reason }}</dd></div>
                  <div v-if="contract.signed_at" class="md:col-span-2"><dt class="text-sm text-gray-500">Signed At</dt><dd class="font-medium">{{ formatDateTime(contract.signed_at) }}</dd></div>
                </dl>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle class="text-lg flex items-center gap-2"><ShieldCheck class="h-5 w-5" /> Compliance monitor</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <Alert v-if="contract.status === 'expiring' || (contract.days_remaining !== null && contract.days_remaining <= 30)" variant="destructive">
                  <AlertTriangle class="h-4 w-4" />
                  <AlertDescription>Renewal action is due. This contract has {{ contract.days_remaining }} days remaining.</AlertDescription>
                </Alert>
                <Alert v-else-if="pendingCount">
                  <Clock class="h-4 w-4" />
                  <AlertDescription>{{ pendingCount }} signator{{ pendingCount === 1 ? 'y' : 'ies' }} still need to sign.</AlertDescription>
                </Alert>
                <Alert v-else>
                  <CheckCircle2 class="h-4 w-4" />
                  <AlertDescription>No active compliance alerts for this contract.</AlertDescription>
                </Alert>
              </CardContent>
            </Card>

            <Card v-if="variableEntries.length">
              <CardHeader><CardTitle class="text-lg">Resolved variables</CardTitle></CardHeader>
              <CardContent>
                <div class="grid gap-2 md:grid-cols-2">
                  <div v-for="[key, value] in variableEntries" :key="key" class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">{{ key }}</div>
                    <div class="text-sm font-medium">{{ value }}</div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <aside class="space-y-4">
            <Card>
              <CardHeader><CardTitle class="text-lg">Next milestone</CardTitle></CardHeader>
              <CardContent v-if="nextMilestone" class="space-y-2">
                <div class="font-medium">{{ nextMilestone.name }}</div>
                <p class="text-sm text-gray-500">{{ nextMilestone.description || 'No description provided.' }}</p>
                <div class="text-sm">Due: {{ formatDate(nextMilestone.due_date) }}</div>
                <Badge class="capitalize ring-1" :class="milestoneStatusClass(nextMilestone.status)">{{ capitalize(nextMilestone.status) }}</Badge>
              </CardContent>
              <CardContent v-else><p class="text-sm text-gray-500">No milestones configured.</p></CardContent>
            </Card>

            <Card>
              <CardHeader><CardTitle class="text-lg">Legal link</CardTitle></CardHeader>
              <CardContent v-if="contract.legal_matter_id">
                <Link :href="`/legal/${contract.legal_matter_id}`" class="inline-flex items-center text-blue-600 hover:underline">
                  <Link2 class="mr-2 h-4 w-4" />
                  Open linked legal matter
                </Link>
              </CardContent>
              <CardContent v-else><p class="text-sm text-gray-500">No legal matter linked.</p></CardContent>
            </Card>
          </aside>
        </TabsContent>

        <TabsContent value="pdf">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="text-lg">Active PDF</CardTitle>
                  <p class="text-sm text-gray-500">Loaded from a fresh signed R2 URL.</p>
                </div>
                <Button variant="outline" :disabled="previewLoading || !contract.document_path" @click="downloadPdf">
                  <RefreshCw :class="{ 'animate-spin': previewLoading }" class="mr-2 h-4 w-4" />
                  Refresh URL
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <p v-if="previewError" class="mb-3 text-sm text-red-600">{{ previewError }}</p>
              <iframe v-if="previewUrl" :src="previewUrl" class="h-[850px] w-full rounded-lg border bg-gray-50" title="Active contract PDF" />
              <div v-else class="rounded-lg border border-dashed p-10 text-center text-sm text-gray-500">Preview is not loaded yet. Use Download PDF or Refresh URL.</div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="signatures">
          <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <Card>
              <CardHeader><CardTitle class="text-lg">Signatory status</CardTitle></CardHeader>
              <CardContent>
                <div v-if="signatories.length" class="space-y-3">
                  <div v-for="signer in signatories" :key="signer.id" class="rounded-lg border p-3">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                      <div>
                        <div class="font-medium">{{ signer.name }}</div>
                        <div class="text-xs text-gray-500">{{ signer.email }} · {{ capitalize(signer.role) }} · Order {{ signer.signing_order }}</div>
                      </div>
                      <Badge class="capitalize ring-1" :class="signatoryStatusClass(signer.status)">{{ capitalize(signer.status) }}</Badge>
                    </div>
                    <div v-if="signer.signed_at" class="mt-2 text-xs text-gray-500">Signed {{ formatDateTime(signer.signed_at) }}</div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500">No signatories have been added yet.</p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader><CardTitle class="text-lg">Signature controls</CardTitle></CardHeader>
              <CardContent class="space-y-3">
                <Button class="w-full" variant="secondary" :disabled="!['draft', 'sent'].includes(contract.status)" @click="signatureDialogOpen = true">
                  <Send class="mr-2 h-4 w-4" />
                  Send for signature
                </Button>
                <p class="text-sm text-gray-500">Built-in OTP and DocuSign provider are available from this panel. Status updates are stored per signatory.</p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="performance">
          <div class="grid gap-6 lg:grid-cols-2">
            <Card>
              <CardHeader><CardTitle class="text-lg">Milestone checklist</CardTitle></CardHeader>
              <CardContent>
                <div v-if="milestones.length" class="space-y-3">
                  <div v-for="milestone in milestones" :key="milestone.id" class="rounded-lg border p-3">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <div class="font-medium">{{ milestone.name }}</div>
                        <div class="text-sm text-gray-500">Due {{ formatDate(milestone.due_date) }}</div>
                        <p v-if="milestone.completion_note" class="mt-2 text-sm text-gray-600">{{ milestone.completion_note }}</p>
                      </div>
                      <Badge class="capitalize ring-1" :class="milestoneStatusClass(milestone.status)">{{ capitalize(milestone.status) }}</Badge>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500">No milestones configured.</p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader><CardTitle class="text-lg">KPI values</CardTitle></CardHeader>
              <CardContent>
                <div v-if="kpiValues.length" class="space-y-3">
                  <div v-for="kpi in kpiValues" :key="kpi.id" class="rounded-lg border p-3">
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="font-medium">{{ kpi.kpiField?.name || 'KPI' }}</div>
                        <div class="text-xs text-gray-500">{{ formatDate(kpi.period_start) }} → {{ formatDate(kpi.period_end) }}</div>
                      </div>
                      <div class="text-sm font-medium">{{ Array.isArray(kpi.value) ? kpi.value.join(', ') : kpi.value }}</div>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-500">No KPI values recorded.</p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="renewal">
          <Card>
            <CardHeader><CardTitle class="text-lg flex items-center gap-2"><CalendarClock class="h-5 w-5" /> Renewal timeline</CardTitle></CardHeader>
            <CardContent class="space-y-4">
              <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-lg border p-4">
                  <div class="text-sm text-gray-500">End date</div>
                  <div class="mt-1 font-semibold">{{ formatDate(contract.end_date) }}</div>
                </div>
                <div class="rounded-lg border p-4">
                  <div class="text-sm text-gray-500">Days remaining</div>
                  <div class="mt-1 font-semibold" :class="healthClass">{{ daysText }}</div>
                </div>
                <div class="rounded-lg border p-4">
                  <div class="text-sm text-gray-500">Reminders</div>
                  <div class="mt-1 font-semibold">{{ contract.suppress_reminders ? 'Suppressed' : 'Active' }}</div>
                </div>
              </div>
              <Alert v-if="contract.status === 'active' || contract.status === 'signed'">
                <Activity class="h-4 w-4" />
                <AlertDescription>Renewal reminders are evaluated nightly at 90, 60, and 30 days before the contract end date.</AlertDescription>
              </Alert>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="versions">
          <Card>
            <CardHeader><CardTitle class="text-lg">Version history</CardTitle></CardHeader>
            <CardContent>
              <div v-if="versions?.data?.length" class="space-y-3">
                <div v-for="version in versions.data" :key="version.id" class="rounded-lg border p-3">
                  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                      <div class="font-medium">v{{ version.version_number }}</div>
                      <div class="text-xs text-gray-500">{{ formatDateTime(version.created_at) }} · {{ version.createdBy?.name }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <Badge class="capitalize ring-1" :class="version.status === 'active' ? statusClass('active') : statusClass(version.status)">{{ capitalize(version.status) }}</Badge>
                      <span class="text-xs text-gray-500">{{ version.page_count }} pages</span>
                      <span class="text-xs text-gray-500">{{ Math.round((version.file_size || 0) / 1024) }} KB</span>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No PDF versions have been generated.</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
