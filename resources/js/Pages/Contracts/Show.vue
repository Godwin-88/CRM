<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

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

const downloadPdf = async () => {
  try {
    const response = await fetch(`/contracts/${contract.value.id}/download`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
      },
    });
    if (response.ok) {
      const data = await response.json();
      window.open(data.url, '_blank');
    }
  } catch (error) {
    console.error(error);
  }
};

const regeneratePdf = async () => {
  if (!confirm('Regenerate contract PDF? This will create a new version.')) return;
  await fetch(`/contracts/${contract.value.id}/regenerate`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
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
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
      'Accept': 'application/json',
    },
  });
  router.visit('/contracts');
};
</script>

<template>
  <AppLayout>
    <Head :title="contract.title" />
    <div class="max-w-7xl mx-auto">
      <div class="mb-4">
        <Link href="/contracts" class="text-blue-600 hover:underline text-sm">← Back to Contracts</Link>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="col-span-2 space-y-6">
          <!-- Contract Header -->
          <Card>
            <CardHeader>
              <div class="flex justify-between items-start">
                <div>
                  <CardTitle class="text-2xl">{{ contract.title }}</CardTitle>
                  <div class="flex gap-2 mt-2">
                    <Badge :variant="getStatusVariant(contract.status)">{{ contract.status }}</Badge>
                    <Badge variant="outline" class="capitalize">{{ contract.type }}</Badge>
                    <Badge v-if="contract.e_signature_status" variant="secondary">
                      e-Sig: {{ contract.e_signature_status }}
                    </Badge>
                  </div>
                </div>
                <div class="text-2xl font-bold">
                  {{ contract.value ? `${contract.currency || 'USD'} ${Number(contract.value).toLocaleString()}` : '—' }}
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div class="flex flex-wrap gap-2">
                <Button size="sm" @click="downloadPdf" :disabled="!contract.document_path">Download PDF</Button>
                <Button size="sm" variant="outline" @click="regeneratePdf">Regenerate</Button>
                <Button size="sm" variant="outline" @click="duplicateContract">Duplicate</Button>
                <Link href="/contracts">
                  <Button size="sm" variant="ghost">Close</Button>
                </Link>
              </div>
            </CardContent>
          </Card>

          <!-- Details -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Details</CardTitle>
            </CardHeader>
            <CardContent>
              <dl class="grid grid-cols-2 gap-4">
                <div>
                  <dt class="text-sm text-gray-500">Account</dt>
                  <dd class="font-medium">
                    <Link v-if="contract.account" :href="`/accounts/${contract.account.id}`" class="text-blue-600 hover:underline">
                      {{ contract.account.name }}
                    </Link>
                    <span v-else>—</span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Contact</dt>
                  <dd class="font-medium">
                    <span v-if="contract.contact">
                      {{ contract.contact.first_name }} {{ contract.contact.last_name }}
                    </span>
                    <span v-else>—</span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Template</dt>
                  <dd class="font-medium">{{ contract.template?.name || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Owner</dt>
                  <dd class="font-medium">{{ contract.accountManager?.name || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Start Date</dt>
                  <dd class="font-medium">{{ contract.start_date ? new Date(contract.start_date).toLocaleDateString() : '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">End Date</dt>
                  <dd class="font-medium">{{ contract.end_date ? new Date(contract.end_date).toLocaleDateString() : '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Activated At</dt>
                  <dd class="font-medium">{{ contract.activated_at ? new Date(contract.activated_at).toLocaleString() : '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Terminated At</dt>
                  <dd class="font-medium">{{ contract.terminated_at ? new Date(contract.terminated_at).toLocaleString() : '—' }}</dd>
                </div>
                <div v-if="contract.termination_reason" class="col-span-2">
                  <dt class="text-sm text-gray-500">Termination Reason</dt>
                  <dd class="font-medium">{{ contract.termination_reason }}</dd>
                </div>
                <div v-if="contract.signed_at" class="col-span-2">
                  <dt class="text-sm text-gray-500">Signed At</dt>
                  <dd class="font-medium">{{ new Date(contract.signed_at).toLocaleString() }}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <!-- Versions -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Versions</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="versions?.data?.length" class="space-y-2">
                <div v-for="version in versions.data" :key="version.id" class="p-3 border rounded flex justify-between items-center">
                  <div>
                    <div class="font-medium">v{{ version.version_number }}</div>
                    <div class="text-xs text-gray-500">{{ new Date(version.created_at).toLocaleString() }} • {{ version.createdBy?.name }}</div>
                  </div>
                  <div class="flex gap-2 items-center">
                    <Badge :variant="version.status === 'active' ? 'default' : 'secondary'">{{ version.status }}</Badge>
                    <span class="text-xs text-gray-500">{{ version.page_count }} pages</span>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No versions.</p>
            </CardContent>
          </Card>

          <!-- Signatories -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Signatories</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="signatories?.length" class="space-y-2">
                <div v-for="signer in signatories" :key="signer.id" class="p-3 border rounded flex justify-between items-center">
                  <div>
                    <div class="font-medium">{{ signer.name }}</div>
                    <div class="text-xs text-gray-500">{{ signer.email }} • {{ signer.role }}</div>
                  </div>
                  <div class="flex gap-2 items-center">
                    <Badge :variant="signer.status === 'signed' ? 'default' : 'secondary'">{{ signer.status }}</Badge>
                    <span class="text-xs text-gray-500">Order {{ signer.signing_order }}</span>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No signatories.</p>
            </CardContent>
          </Card>

          <!-- Milestones -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Milestones</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="milestones?.length" class="space-y-2">
                <div v-for="milestone in milestones" :key="milestone.id" class="p-3 border rounded flex justify-between items-center">
                  <div>
                    <div class="font-medium">{{ milestone.name }}</div>
                    <div class="text-xs text-gray-500">Due: {{ new Date(milestone.due_date).toLocaleDateString() }}</div>
                  </div>
                  <Badge :variant="milestone.status === 'completed' ? 'default' : 'secondary'">
                    {{ milestone.status }}
                  </Badge>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No milestones.</p>
            </CardContent>
          </Card>

          <!-- KPIs -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Performance</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="kpiValues?.length" class="space-y-3">
                <div v-for="kpi in kpiValues" :key="kpi.id" class="flex justify-between">
                  <span class="text-sm font-medium">{{ kpi.kpiField?.name }}</span>
                  <span class="text-sm text-gray-600">{{ kpi.value }}</span>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No KPI values.</p>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Tags</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="contract.tags?.length" class="flex flex-wrap gap-2">
                <Badge v-for="tag in contract.tags" :key="tag.id" :style="{ backgroundColor: tag.color, color: '#fff' }">
                  {{ tag.name }}
                </Badge>
              </div>
              <p v-else class="text-gray-400 text-sm">No tags.</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Quick Actions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
              <Link href="/contracts">
                <Button variant="outline" class="w-full">All Contracts</Button>
              </Link>
              <Link href="/admin/contract-templates">
                <Button variant="outline" class="w-full">Templates</Button>
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
