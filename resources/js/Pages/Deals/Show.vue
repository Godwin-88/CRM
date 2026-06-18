<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { AlertCircle } from 'lucide-vue-next';

interface Deal {
  id: string;
  title: string;
  value: number;
  currency: string;
  stage: string;
  probability: number;
  expected_close_date: string | null;
  owner: { id: string; name: string } | null;
  account: { id: string; name: string };
  contact: { id: string; first_name: string; last_name: string };
  win_loss_reason?: { id: string; label: string; type: string };
  win_loss_note?: string;
  quotes: any[];
  activities: any[];
  demoTrials: any[];
  comments: any[];
  unread_comments_count: number;
}

interface Pipeline {
  id: string;
  name: string;
  stages: { id: string; name: string; probability: number }[];
}

const props = defineProps<{
  deal: Deal;
  pipelines: Pipeline[];
}>();

const deal = ref(props.deal);
const pipelines = ref(props.pipelines);

const isCloseModalOpen = ref(false);
const currentUserId = (window as any).userId;
const closeLoading = ref(false);
const closeType = ref<'won' | 'lost'>('won');
const closeReasonId = ref('');
const closeNote = ref('');

const reasons = ref<{ won: any[]; lost: any[] }>({ won: [], lost: [] });

const newComment = ref({ body: '', mentions: [] as string[] });
const postCommentLoading = ref(false);

const newActivity = ref({ subject: '', type: 'task', due_at: '', priority: 'medium', notes: '' });
const postActivityLoading = ref(false);

const scheduleDemoTrialForm = ref({ type: 'demo', scheduled_date: '', start_date: '', end_date: '', scope_notes: '', assigned_to: '' });
const submitDemoLoading = ref(false);
const demoSuccess = ref<string | null>(null);

const quoteForm = ref({ template_id: '', product_id: '', quantity: 1, discount_pct: 0 });
const createQuoteLoading = ref(false);
const quoteSuccess = ref<string | null>(null);
const updateStatusDraft = ref<Record<string, string>>({});

const activitySummary = computed(() => {
  const total = deal.value.activities?.length || 0;
  const completed = deal.value.activities?.filter((a: any) => a.completed_at).length || 0;
  const overdue = deal.value.activities?.filter((a: any) => !a.completed_at && a.due_at && new Date(a.due_at) < new Date()).length || 0;
  return { total, completed, overdue };
});

const sortedComments = computed(() => {
  return [...(deal.value.comments || [])].reverse();
});

const isClosed = computed(() => {
  const s = typeof deal.value.stage === 'string' ? deal.value.stage.toLowerCase() : '';
  return s.includes('closed_won') || s.includes('closed_lost');
});

const canWrite = computed(() => {
  if (!props.deal.owner) return false;
  return String(props.deal.owner?.id) === String(currentUserId);
});

const csrf = () => (document.querySelector('meta[name="csrf-token"]') as any)?.content;

const fetchReasons = async () => {
  const res = await fetch('/api/v1/win-loss-reasons', { headers: { 'X-CSRF-TOKEN': csrf() } });
  reasons.value = await res.json();
};

const openCloseModal = (type: 'won' | 'lost') => {
  closeType.value = type;
  closeReasonId.value = '';
  closeNote.value = '';
  isCloseModalOpen.value = true;
  fetchReasons();
};

const submitClose = async () => {
  if (!closeReasonId.value) return;
  closeLoading.value = true;
  try {
    const res = await fetch(`/api/v1/deals/${deal.value.id}/close`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ status: closeType.value, win_loss_reason_id: closeReasonId.value, note: closeNote.value }),
    });
    if (!res.ok) throw new Error('Failed');
    const data = await res.json();
    deal.value = { ...deal.value, ...data };
    isCloseModalOpen.value = false;
  } finally {
    closeLoading.value = false;
  }
};

const postComment = async () => {
  if (!newComment.value.body.trim()) return;
  postCommentLoading.value = true;
  try {
    const res = await fetch(`/api/v1/deals/${deal.value.id}/comments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(newComment.value),
    });
    if (!res.ok) throw new Error('Failed');
    const comment = await res.json();
    deal.value.comments = [...(deal.value.comments || []), comment];
    newComment.value = { body: '', mentions: [] };
  } finally {
    postCommentLoading.value = false;
  }
};

const deleteComment = async (commentId: string, authorId: string) => {
  if (!confirm('Delete comment?')) return;
  await fetch(`/api/v1/deal-comments/${commentId}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrf() },
  });
  deal.value.comments = deal.value.comments.filter((c: any) => c.id !== commentId);
};

const postActivity = async () => {
  postActivityLoading.value = true;
  try {
    const res = await fetch(`/api/v1/deals/${deal.value.id}/activities`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(newActivity.value),
    });
    if (!res.ok) throw new Error('Failed');
    const activity = await res.json();
    deal.value.activities = [...(deal.value.activities || []), activity];
    newActivity.value = { subject: '', type: 'task', due_at: '', priority: 'medium', notes: '' };
  } finally {
    postActivityLoading.value = false;
  }
};

const markActivityComplete = async (activityId: string) => {
  await fetch(`/api/v1/deal-activities/${activityId}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: JSON.stringify({ outcome: 'completed', completed_at: new Date().toISOString() }),
  });
  const act = deal.value.activities.find((a: any) => a.id === activityId);
  if (act) act.completed_at = new Date().toISOString();
};

const submitDemoTrial = async () => {
  submitDemoLoading.value = true;
  demoSuccess.value = null;
  try {
    const res = await fetch(`/api/v1/deals/${deal.value.id}/demo-trial`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(scheduleDemoTrialForm.value),
    });
    if (!res.ok) {
      const err = await res.json();
      throw new Error(err.message || 'Failed');
    }
    const data = await res.json();
    deal.value.demoTrials = [...(deal.value.demoTrials || []), data];
    scheduleDemoTrialForm.value = { type: 'demo', scheduled_date: '', start_date: '', end_date: '', scope_notes: '', assigned_to: '' };
    demoSuccess.value = 'Scheduled successfully.';
  } catch (err: any) {
    demoSuccess.value = err.message;
  } finally {
    submitDemoLoading.value = false;
  }
};

const updateDemoStatus = async (demoId: string, status: string) => {
  await fetch(`/api/v1/deal-demo-trials/${demoId}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: JSON.stringify({ status }),
  });
  const demo = deal.value.demoTrials.find((d: any) => d.id === demoId);
  if (demo) demo.status = status;
};

const createQuote = async () => {
  createQuoteLoading.value = true;
  quoteSuccess.value = null;
  try {
    const res = await fetch(`/api/v1/deals/${deal.value.id}/quotes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(quoteForm.value),
    });
    if (!res.ok) throw new Error('Quote creation failed');
    const quote = await res.json();
    deal.value.quotes = [...(deal.value.quotes || []), quote];
    quoteForm.value = { template_id: '', product_id: '', quantity: 1, discount_pct: 0 };
    quoteSuccess.value = 'Quote generated successfully.';
  } finally {
    createQuoteLoading.value = false;
  }
};
</script>

<template>
  <AppLayout>
    <Head :title="deal.title" />
    <div class="max-w-7xl mx-auto">
      <div class="mb-4">
        <Link href="/deals" class="text-blue-600 hover:underline text-sm">← Back to Deals</Link>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="col-span-2 space-y-6">
          <!-- Deal Header -->
          <Card>
            <CardHeader>
              <div class="flex justify-between items-start">
                <div>
                  <CardTitle class="text-2xl">{{ deal.title }}</CardTitle>
                  <div class="flex gap-2 mt-2">
                    <Badge>{{ deal.stage }}</Badge>
                    <Badge variant="outline">{{ deal.probability }}% probability</Badge>
                    <Badge v-if="deal.unread_comments_count" variant="destructive">
                      {{ deal.unread_comments_count }} new comments
                    </Badge>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2">
                  <Link :href="`/contracts/create?account_id=${deal.account?.id || ''}&contact_id=${deal.contact?.id || ''}`">
                    <Button size="sm" variant="secondary">Generate Contract</Button>
                  </Link>
                  <div class="text-2xl font-bold">${{ Number(deal.value || 0).toLocaleString() }}</div>
                </div>
              </div>
              <div v-if="!isClosed" class="flex gap-2 mt-4">
                <Button variant="outline" @click="openCloseModal('won')">Close Won</Button>
                <Button variant="destructive" @click="openCloseModal('lost')">Close Lost</Button>
              </div>
            </CardHeader>
            <CardContent>

              <Dialog v-model:open="isCloseModalOpen">
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>{{ closeType === 'won' ? 'Close as Won' : 'Close as Lost' }}</DialogTitle>
                  </DialogHeader>
                  <div class="space-y-4">
                    <div>
                      <Label>{{ closeType === 'won' ? 'Won Reason' : 'Lost Reason' }}</Label>
                      <Select v-model="closeReasonId">
                        <SelectTrigger>
                          <SelectValue :placeholder="closeType === 'won' ? 'Select won reason' : 'Select lost reason'" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="reason in reasons[closeType === 'won' ? 'won' : 'lost']" :key="reason.id" :value="reason.id">
                            {{ reason.label }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div>
                      <Label>Note</Label>
                      <Textarea v-model="closeNote" placeholder="Optional context..." />
                    </div>
                    <Button :disabled="closeLoading || !closeReasonId" @click="submitClose" class="w-full">
                      {{ closeLoading ? 'Closing...' : 'Confirm Close' }}
                    </Button>
                  </div>
                </DialogContent>
              </Dialog>

              <dl class="grid grid-cols-2 gap-4">
                <div>
                  <dt class="text-sm text-gray-500">Account</dt>
                  <dd class="font-medium">{{ deal.account?.name }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Contact</dt>
                  <dd class="font-medium">{{ deal.contact?.first_name }} {{ deal.contact?.last_name }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Owner</dt>
                  <dd class="font-medium">{{ deal.owner?.name || 'Unassigned' }}</dd>
                </div>
                <div>
                  <dt class="text-sm text-gray-500">Expected Close</dt>
                  <dd class="font-medium">{{ deal.expected_close_date ? new Date(deal.expected_close_date).toLocaleDateString() : '—' }}</dd>
                </div>
              </dl>

              <div v-if="deal.win_loss_reason" class="mt-4 p-3 bg-gray-50 rounded">
                <dt class="text-sm font-medium text-gray-700">Win/Loss Reason: {{ deal.win_loss_reason.label }}</dt>
                <dd v-if="deal.win_loss_note" class="text-sm text-gray-600 mt-1">{{ deal.win_loss_note }}</dd>
              </div>
            </CardContent>
          </Card>

          <!-- Activities Section -->
          <Card>
            <CardHeader>
              <div class="flex justify-between items-center">
                <CardTitle class="text-lg">Activities</CardTitle>
                <div class="text-xs text-gray-500">
                  {{ activitySummary.completed }}/{{ activitySummary.total }} completed
                  <span v-if="activitySummary.overdue" class="text-red-600">• {{ activitySummary.overdue }} overdue</span>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="deal.activities?.length" class="space-y-2 max-h-64 overflow-y-auto">
                <div v-for="activity in deal.activities" :key="activity.id"
                  class="p-3 rounded border-l-4"
                  :class="[
                    activity.completed_at
                      ? 'border-l-green-500 bg-green-50'
                      : (!activity.due_at || new Date(activity.due_at) >= new Date())
                        ? 'border-l-blue-500 bg-gray-50'
                        : 'border-l-red-500 bg-red-50',
                  ]">
                  <div class="flex justify-between">
                    <span class="font-medium text-sm">{{ activity.subject }}</span>
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-gray-500">{{ activity.type }}</span>
                      <span v-if="!activity.completed_at" class="text-xs text-gray-400">Due {{ new Date(activity.due_at).toLocaleString() }}</span>
                      <span v-if="activity.outcome" class="text-xs text-gray-500">{{ activity.outcome }}</span>
                    </div>
                  </div>
                  <p v-if="activity.notes" class="text-sm text-gray-600 mt-1">{{ activity.notes }}</p>
                  <div class="flex gap-2 mt-2" v-if="!activity.completed_at">
                    <Button variant="outline" size="sm" @click="markActivityComplete(activity.id)">Mark Complete</Button>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No activities logged.</p>
              <form @submit.prevent="postActivity" class="mt-4 grid grid-cols-2 gap-2">
                <Input v-model="newActivity.subject" placeholder="Subject" required />
                <Select v-model="newActivity.type">
                  <SelectTrigger>
                    <SelectValue placeholder="Type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="task">Task</SelectItem>
                    <SelectItem value="call">Call</SelectItem>
                    <SelectItem value="email">Email</SelectItem>
                    <SelectItem value="meeting">Meeting</SelectItem>
                  </SelectContent>
                </Select>
                <Input v-model="newActivity.due_at" type="datetime-local" />
                <Input v-model="newActivity.priority" placeholder="Priority (low/medium/high)" />
                <Textarea v-model="newActivity.notes" placeholder="Notes" class="col-span-2" rows="2" />
                <Button type="submit" size="sm" :disabled="postActivityLoading" class="col-span-2">
                  {{ postActivityLoading ? 'Adding...' : 'Log Activity' }}
                </Button>
              </form>
            </CardContent>
          </Card>

          <!-- Comments Section -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Comments</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3 max-h-64 overflow-y-auto mb-4">
                <div v-for="comment in sortedComments" :key="comment.id || comment.placeholder" class="p-3 bg-gray-50 rounded">
                  <div v-if="comment.is_deleted" class="text-sm text-gray-400 italic">
                    Comment deleted {{ new Date(comment.deleted_at).toLocaleString() }}
                  </div>
                  <template v-else>
                    <div class="flex justify-between">
                      <span class="font-medium text-sm">{{ comment.user?.name }}</span>
                      <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">{{ new Date(comment.created_at).toLocaleString() }}</span>
                        <span v-if="comment.edited_at" class="text-xs text-gray-400">(edited)</span>
                        <Button v-if="String(comment.user_id) === String(currentUserId)" variant="ghost" size="sm" @click="deleteComment(comment.id, comment.user_id)">
                          Delete
                        </Button>
                      </div>
                    </div>
                    <p class="text-sm mt-1">{{ comment.body }}</p>
                  </template>
                </div>
                <p v-if="!sortedComments.length" class="text-gray-400 text-sm">No comments yet.</p>
              </div>
              <form @submit.prevent="postComment" class="space-y-2">
                <Input v-model="newComment.body" placeholder="Write a comment..." class="w-full" />
                <div class="flex justify-between">
                  <span class="text-xs text-gray-400">Mention teammates with @name</span>
                  <Button type="submit" size="sm" :disabled="postCommentLoading || !newComment.body.trim()">
                    {{ postCommentLoading ? 'Posting...' : 'Post' }}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Quotes Section -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Quotes</CardTitle>
            </CardHeader>
            <CardContent>
              <Alert v-if="quoteSuccess" class="mb-3">
                <AlertDescription>{{ quoteSuccess }}</AlertDescription>
              </Alert>
              <div v-if="deal.quotes?.length" class="space-y-2 mb-3">
                <div v-for="quote in deal.quotes" :key="quote.id" class="p-2 bg-gray-50 rounded flex justify-between items-center">
                  <div>
                    <span class="text-sm font-medium">Quote #{{ quote.id.substring(0, 8) }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ new Date(quote.created_at).toLocaleDateString() }}</span>
                  </div>
                  <Badge :variant="quote.status === 'accepted' ? 'default' : 'secondary'">{{ quote.status }}</Badge>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm mb-2">No quotes generated.</p>
              <div class="space-y-2">
                <Select v-model="quoteForm.template_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select template" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="t in (props as any).quoteTemplates || []" :key="t.id" :value="t.id">{{ t.name }}</SelectItem>
                  </SelectContent>
                </Select>
                <Input v-model="quoteForm.product_id" placeholder="Product ID" />
                <Input v-model="quoteForm.quantity" type="number" placeholder="Qty" />
                <div class="flex gap-2">
                  <Input v-model="quoteForm.discount_pct" type="number" placeholder="Discount %" />
                  <Button size="sm" class="w-full" :disabled="createQuoteLoading || !quoteForm.template_id" @click="createQuote">
                    {{ createQuoteLoading ? 'Generating...' : 'Generate Quote' }}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Demo/Trial Section -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Demos & Trials</CardTitle>
            </CardHeader>
            <CardContent>
              <Alert v-if="demoSuccess" class="mb-3">
                <AlertDescription>{{ demoSuccess }}</AlertDescription>
              </Alert>
              <div v-if="deal.demoTrials?.length" class="space-y-2 mb-3 max-h-48 overflow-y-auto">
                <div v-for="demo in deal.demoTrials" :key="demo.id" class="p-2 bg-gray-50 rounded flex justify-between items-center">
                  <div>
                    <div class="text-sm font-medium">{{ demo.type }} - {{ demo.scheduled_date }}</div>
                    <div class="text-xs text-gray-500">{{ demo.scope_notes }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Badge>{{ demo.status }}</Badge>
                    <Select v-if="demo.status === 'scheduled'" v-model="updateStatusDraft[demo.id]" @update:model-value="(v: any) => updateDemoStatus(demo.id, v)">
                      <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="Update" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="no_show">No Show</SelectItem>
                        <SelectItem value="converted">Converted</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm mb-2">No demos/trials scheduled.</p>
              <form @submit.prevent="submitDemoTrial" class="space-y-2">
                <Select v-model="scheduleDemoTrialForm.type">
                  <SelectTrigger>
                    <SelectValue placeholder="Type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="demo">Demo</SelectItem>
                    <SelectItem value="trial">Trial</SelectItem>
                  </SelectContent>
                </Select>
                <Input v-model="scheduleDemoTrialForm.scheduled_date" type="date" />
                <Input v-if="scheduleDemoTrialForm.type === 'trial'" v-model="scheduleDemoTrialForm.start_date" type="date" />
                <Input v-if="scheduleDemoTrialForm.type === 'trial'" v-model="scheduleDemoTrialForm.end_date" type="date" />
                <Textarea v-model="scheduleDemoTrialForm.scope_notes" placeholder="Scope notes" class="w-full" rows="2" />
                <Select v-model="scheduleDemoTrialForm.assigned_to">
                  <SelectTrigger>
                    <SelectValue placeholder="Assigned to" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="u in (props as any).users || []" :key="u.id" :value="u.id">{{ u.name }}</SelectItem>
                  </SelectContent>
                </Select>
                <Button size="sm" variant="outline" class="w-full" :disabled="submitDemoLoading" type="submit">
                  {{ submitDemoLoading ? 'Saving...' : 'Schedule Demo/Trial' }}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>