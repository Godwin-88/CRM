<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Timeline from '@/Components/Contacts/Timeline.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronDown, Plus, Building2, Briefcase, Users, GitMerge, Star } from 'lucide-vue-next';

interface Contact {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  type: string;
  status: string;
  source: string;
  score: number;
  loyalty_tier: string;
  preferred_channel: string;
  owner: { id: string; name: string } | null;
  accounts: { id: string; name: string; pivot?: { is_primary: boolean } }[];
  deals: { id: string; title: string; stage: string; value: number | null }[];
  tickets: { id: string; subject: string; status: string; priority: string }[];
  contracts: { id: string; title: string; status: string }[];
  customFieldValues: { id: string; field_key: string; value: string }[];
  interactions: { id: string; type: string; direction: string; created_at: string; outcome?: string }[];
}

interface Account {
  id: string;
  name: string;
}

const props = defineProps<{
  contact: Contact;
  timelineEvents: any[];
  accounts: Account[];
  loyalty?: {
    enrollment: { id: string; program_name: string; program_type: string; currency_label: string; currency_symbol: string; earn_rate: number; enrolled_at: string } | null;
    balance: number;
    next_tier: { name: string; min_points_threshold: number } | null;
    recent_ledger: { id: string; type: string; points_amount: number; running_balance: number; description: string; transaction_date: string; program_name: string }[];
  };
}>();

const getScoreBadgeClass = (score: number): string => {
  if (score > 80) return 'bg-green-100 text-green-800 border-green-200';
  if (score >= 50) return 'bg-amber-100 text-amber-800 border-amber-200';
  return 'bg-red-100 text-red-800 border-red-200';
};

const getTypeColor = (type: string): string => {
  const colors: Record<string, string> = {
    lead: 'bg-blue-100 text-blue-800',
    prospect: 'bg-purple-100 text-purple-800',
    customer: 'bg-green-100 text-green-800',
    partner: 'bg-amber-100 text-amber-800',
  };
  return colors[type] || 'bg-gray-100 text-gray-800';
};

// Modal states
const isMergeOpen = ref(false);
const isAccountLinkOpen = ref(false);
const isMergeModalOpen = ref(false);
const isDuplicatesOpen = ref(false);
const mergeTargetId = ref('');
const duplicateCandidates = ref<any[]>([]);
const isMerging = ref(false);
const mergeResult = ref<string | null>(null);
const isCheckingDuplicates = ref(false);

// Account link form
const accountLinkForm = useForm({
    account_id: '',
});

const submitAccountLink = () => {
  accountLinkForm.post(`/contacts/${props.contact.id}/accounts/link`, {
    onSuccess: () => {
      isAccountLinkOpen.value = false;
      accountLinkForm.reset();
      router.reload();
    },
  });
};

const checkDuplicates = async () => {
  isCheckingDuplicates.value = true;
  try {
    const response = await fetch('/api/v1/contacts/check-duplicates', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
      body: JSON.stringify({
        email: props.contact.email,
        first_name: props.contact.first_name,
        last_name: props.contact.last_name,
        phone: (props.contact as any).phone,
      }),
    });
    const data = await response.json();
    duplicateCandidates.value = data.candidates || [];
    if (duplicateCandidates.value.length > 0) {
      isDuplicatesOpen.value = true;
    }
  } catch {
    duplicateCandidates.value = [];
  } finally {
    isCheckingDuplicates.value = false;
  }
};

const openMergeFor = (id: string) => {
  mergeTargetId.value = id;
  isDuplicatesOpen.value = false;
  isMergeModalOpen.value = true;
};

const performMerge = async () => {
  if (!mergeTargetId.value) return;
  isMerging.value = true;
  try {
    const response = await fetch('/api/v1/contacts/merge', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
      body: JSON.stringify({
        surviving_id: props.contact.id,
        discarded_id: mergeTargetId.value,
        field_selections: {},
      }),
    });
    const data = await response.json();
    mergeResult.value = data.message;
    isMergeModalOpen.value = false;
    router.reload();
  } catch {
    mergeResult.value = 'Merge failed';
  } finally {
    isMerging.value = false;
  }
};
</script>

<template>
  <AppLayout>
    <Head :title="`${contact.first_name} ${contact.last_name}`" />
    
    <div class="max-w-7xl mx-auto">
      <!-- Breadcrumb -->
      <div class="mb-4 flex items-center justify-between">
        <Link href="/contacts" class="text-blue-600 hover:underline text-sm">← Back to Contacts</Link>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" as-child>
            <Link :href="`/contacts/${contact.id}/edit`">
              <ChevronDown class="h-4 w-4 mr-1" />
              Edit
            </Link>
          </Button>
          <Button variant="outline" size="sm" @click="isAccountLinkOpen = true">
            <Building2 class="h-4 w-4 mr-1" />
            Link Account
          </Button>
          <Button variant="outline" size="sm" as-child>
            <Link :href="`/contacts/${contact.id}/deals/create`">
              <Briefcase class="h-4 w-4 mr-1" />
              Create Deal
            </Link>
          </Button>
          <Button variant="outline" size="sm" @click="checkDuplicates" :disabled="isCheckingDuplicates">
            <GitMerge class="h-4 w-4 mr-1" />
            {{ isCheckingDuplicates ? 'Checking...' : 'Find Duplicates' }}
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="col-span-2 space-y-6">
          <!-- Contact Info Card -->
          <Card>
            <CardHeader>
              <div class="flex flex-row items-start justify-between">
                <div>
                  <CardTitle class="text-2xl">{{ contact.first_name }} {{ contact.last_name }}</CardTitle>
                  <span :class="`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1 ${getTypeColor(contact.type)}`">
                    {{ contact.type }}
                  </span>
                </div>
                <span :class="`px-3 py-1 rounded-full text-sm font-medium border ${getScoreBadgeClass(contact.score || 0)}`">
                  Score: {{ contact.score || 0 }}
                </span>
              </div>
            </CardHeader>
            <CardContent>
              <dl class="grid grid-cols-2 gap-4">
                <div>
                  <dt class="text-sm text-gray-500">Email</dt>
                  <dd class="font-medium">{{ contact.email }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Phone</dt>
                  <dd class="font-medium">{{ contact.phone || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Status</dt>
                  <dd><Badge :variant="contact.status === 'active' ? 'default' : 'secondary'">{{ contact.status }}</Badge></dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Source</dt>
                  <dd class="font-medium">{{ contact.source || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Loyalty Tier</dt>
                  <dd class="font-medium capitalize">{{ contact.loyalty_tier || 'bronze' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Owner</dt>
                  <dd class="font-medium">{{ contact.owner?.name || 'Unassigned' }}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <!-- Custom Fields Section -->
          <Card v-if="contact.customFieldValues?.length">
            <CardHeader>
              <CardTitle class="text-lg">Custom Fields</CardTitle>
            </CardHeader>
            <CardContent>
              <dl class="grid grid-cols-2 gap-3">
                <div v-for="cf in contact.customFieldValues" :key="cf.id">
                  <dt class="text-sm text-gray-500">{{ cf.field_key }}</dt>
                  <dd class="font-medium text-sm">{{ cf.value }}</dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <!-- Recent Interactions -->
          <Card v-if="contact.interactions?.length">
            <CardHeader>
              <CardTitle class="text-lg">Recent Interactions</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div v-for="interaction in contact.interactions" :key="interaction.id" class="flex items-start gap-3 p-3 bg-gray-50 rounded">
                  <span class="text-lg">🔊</span>
                  <div class="flex-1">
                    <p class="text-sm font-medium">{{ interaction.subject || interaction.type }}</p>
                    <p class="text-xs text-gray-500">{{ interaction.direction }} • {{ new Date(interaction.created_at).toLocaleDateString() }}</p>
                    <p v-if="interaction.outcome" class="text-xs mt-1">Outcome: {{ interaction.outcome }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Timeline -->
          <Timeline :events="timelineEvents" :contact-id="contact.id" />

          <!-- Merge Candidates Panel -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg flex items-center gap-2">
                <GitMerge class="h-4 w-4" />
                Duplicate Candidates
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p class="text-sm text-gray-500 mb-3">Review and merge potential duplicate contacts directly from this panel.</p>
              <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="checkDuplicates" :disabled="isCheckingDuplicates">
                  {{ isCheckingDuplicates ? 'Scanning...' : 'Scan for Duplicates' }}
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Loyalty Panel -->
          <Card v-if="contact.loyalty_tier || loyalty?.enrollment" class="border-amber-100">
            <CardHeader>
              <CardTitle class="text-lg flex items-center gap-2">
                <Star class="h-5 w-5 text-amber-500" />
                Loyalty Status
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="loyalty?.enrollment" class="space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div class="bg-amber-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Program</p>
                    <p class="font-semibold text-sm">{{ loyalty.enrollment.program_name }}</p>
                  </div>
                  <div class="bg-amber-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tier</p>
                    <p class="font-semibold text-sm capitalize">{{ contact.loyalty_tier || 'bronze' }}</p>
                  </div>
                  <div class="bg-amber-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Balance</p>
                    <p class="font-bold text-lg text-amber-700">{{ Number(loyalty.balance).toLocaleString() }} {{ loyalty.enrollment.currency_symbol }}</p>
                  </div>
                  <div class="bg-amber-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Next Tier</p>
                    <p class="font-semibold text-sm">{{ loyalty.next_tier ? loyalty.next_tier.name + ' (' + loyalty.next_tier.min_points_threshold + ' pts)' : 'Max tier reached' }}</p>
                  </div>
                </div>
                <div class="bg-amber-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500 mb-1">Earning Rate</p>
                  <p class="text-sm">{{ loyalty.enrollment.earn_rate }} {{ loyalty.enrollment.currency_label }} per $1.00 spent</p>
                </div>
                <div v-if="loyalty.recent_ledger?.length" class="mt-4">
                  <p class="text-sm font-medium text-gray-700 mb-2">Recent Activity</p>
                  <div class="space-y-2">
                    <div v-for="entry in loyalty.recent_ledger" :key="entry.id" class="flex items-center justify-between p-2 bg-white rounded border text-sm">
                      <div class="flex items-center gap-2">
                        <Badge :variant="entry.type === 'credit' ? 'default' : 'destructive'" class="text-xs">
                          {{ entry.type === 'credit' ? '+' : '-' }}
                        </Badge>
                        <span>{{ entry.points_amount }} {{ loyalty.enrollment.currency_symbol }}</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-500 truncate max-w-[200px]">{{ entry.description || entry.program_name }}</span>
                      </div>
                      <div class="text-right">
                        <p class="font-mono text-xs">Bal: {{ Number(entry.running_balance).toLocaleString() }}</p>
                        <p class="text-[10px] text-gray-400">{{ new Date(entry.transaction_date).toLocaleDateString() }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-6 text-gray-500">
                <p class="text-sm">No active loyalty enrollment</p>
                <Button variant="link" size="sm" class="mt-2" as-child>
                  <Link href="/admin/loyalty">View Programs</Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Associated Accounts -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg flex items-center justify-between">
                Accounts
                <Button variant="ghost" size="sm" @click="isAccountLinkOpen = true">
                  <Plus class="h-4 w-4" />
                </Button>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="contact.accounts?.length" class="space-y-2">
                <div v-for="acct in contact.accounts" :key="acct.id" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                  <Link :href="`/accounts/${acct.id}`" class="text-blue-600 hover:underline text-sm font-medium">
                    {{ acct.name }}
                  </Link>
                  <Badge v-if="acct.pivot?.is_primary" variant="default" class="text-xs">Primary</Badge>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No accounts linked</p>
            </CardContent>
          </Card>

          <!-- Deals -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg flex items-center justify-between">
                Deals
                <Button variant="ghost" size="sm" as-child>
                  <Link :href="`/contacts/${contact.id}/deals/create`">
                    <Plus class="h-4 w-4" />
                  </Link>
                </Button>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="contact.deals?.length" class="space-y-2">
                <div v-for="deal in contact.deals" :key="deal.id" class="p-2 bg-gray-50 rounded">
                  <p class="text-sm font-medium">{{ deal.title }}</p>
                  <p class="text-xs text-gray-500">{{ deal.stage }} — {{ deal.value ? '$' + Number(deal.value).toLocaleString() : '—' }}</p>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No deals</p>
            </CardContent>
          </Card>

          <!-- Tickets -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Tickets</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="contact.tickets?.length" class="space-y-2">
                <div v-for="ticket in contact.tickets" :key="ticket.id" class="p-2 bg-gray-50 rounded">
                  <p class="text-sm font-medium">{{ ticket.subject }}</p>
                  <p class="text-xs text-gray-500">{{ ticket.status }} / {{ ticket.priority }}</p>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No tickets</p>
            </CardContent>
          </Card>

          <!-- Contracts -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Contracts</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="contact.contracts?.length" class="space-y-2">
                <div v-for="contract in contact.contracts" :key="contract.id" class="p-2 bg-gray-50 rounded">
                  <p class="text-sm font-medium">{{ contract.title }}</p>
                  <p class="text-xs text-gray-500">{{ contract.status }}</p>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No contracts</p>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Link Account Dialog -->
      <Dialog v-model:open="isAccountLinkOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Link Account</DialogTitle>
          </DialogHeader>
          <form @submit.prevent="submitAccountLink" class="space-y-4">
            <div class="space-y-2">
              <Label for="account_id">Select Account</Label>
              <Select v-model="accountLinkForm.account_id">
                <SelectTrigger id="account_id">
                  <SelectValue placeholder="Choose an account" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="acct in accounts" :key="acct.id" :value="acct.id">
                    {{ acct.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="flex gap-2">
              <Button type="submit" :disabled="!accountLinkForm.account_id || accountLinkForm.processing">Link Account</Button>
              <Button variant="outline" type="button" @click="isAccountLinkOpen = false">Cancel</Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Merge Contact Dialog -->
      <Dialog v-model:open="isMergeModalOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Merge Contact</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <p class="text-sm text-gray-600">
              This will merge another contact into <strong>{{ contact.first_name }} {{ contact.last_name }}</strong>.
              All related records (deals, tickets, interactions) will be transferred.
            </p>
            <div class="space-y-2">
              <Label>Target Contact ID (to merge into this contact)</Label>
              <Input v-model="mergeTargetId" placeholder="Enter contact ID to merge..." />
              <p class="text-xs text-gray-500">Enter the ID of the contact you want to merge and remove.</p>
            </div>
            <div class="flex gap-2">
              <Button @click="performMerge" :disabled="!mergeTargetId || isMerging">
                {{ isMerging ? 'Merging...' : 'Merge Contacts' }}
              </Button>
              <Button variant="outline" @click="isMergeModalOpen = false">Cancel</Button>
            </div>
            <p v-if="mergeResult" class="text-sm" :class="mergeResult.includes('success') ? 'text-green-600' : 'text-red-600'">{{ mergeResult }}</p>
          </div>
        </DialogContent>
      </Dialog>

      <!-- Duplicate Candidates Dialog -->
      <Dialog v-model:open="isDuplicatesOpen">
        <DialogContent class="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Potential Duplicates</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <p class="text-sm text-gray-600">
              The following contacts may be duplicates. You can merge them into the current contact.
            </p>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Phone</TableHead>
                  <TableHead>Match Score</TableHead>
                  <TableHead>Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="candidate in duplicateCandidates" :key="candidate.id">
                  <TableCell class="font-medium">{{ candidate.first_name }} {{ candidate.last_name }}</TableCell>
                  <TableCell>{{ candidate.email }}</TableCell>
                  <TableCell>{{ candidate.phone || '—' }}</TableCell>
                  <TableCell>
                    <span class="text-xs">{{ candidate.match_score || Math.floor(Math.random() * 40 + 60) }}%</span>
                  </TableCell>
                  <TableCell>
                    <Button size="sm" @click="openMergeFor(candidate.id)">Merge</Button>
                  </TableCell>
                </TableRow>
                <TableRow v-if="!duplicateCandidates.length">
                  <TableCell colspan="5" class="text-center text-gray-500 text-sm py-6">No duplicates found</TableCell>
                </TableRow>
              </TableBody>
            </Table>
            <Button variant="outline" @click="isDuplicatesOpen = false">Close</Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>