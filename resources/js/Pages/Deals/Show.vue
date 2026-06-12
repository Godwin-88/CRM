<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

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

const activitySummary = computed(() => {
  const total = deal.value.activities?.length || 0;
  const completed = deal.value.activities?.filter((a: any) => a.completed_at).length || 0;
  const overdue = deal.value.activities?.filter((a: any) => !a.completed_at && a.due_at && new Date(a.due_at) < new Date()).length || 0;
  return { total, completed, overdue };
});

const addNewActivity = async () => {
  // Implementation for adding activity
};

const addComment = async () => {
  // Implementation for adding comment
};

const scheduleDemo = async () => {
  // Implementation for scheduling demo
};

const generateQuote = async () => {
  // Implementation for generating quote
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
                <div class="text-2xl font-bold">${{ Number(deal.value || 0).toLocaleString() }}</div>
              </div>
            </CardHeader>
            <CardContent>
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
                  :class="activity.completed_at ? 'border-l-green-500 bg-green-50' : (new Date(activity.due_at) < new Date() ? 'border-l-red-500 bg-red-50' : 'border-l-blue-500 bg-gray-50')">
                  <div class="flex justify-between">
                    <span class="font-medium text-sm">{{ activity.subject }}</span>
                    <span class="text-xs text-gray-500">{{ activity.type }}</span>
                  </div>
                  <div v-if="activity.due_at" class="text-xs text-gray-500 mt-1">
                    Due: {{ new Date(activity.due_at).toLocaleString() }}
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm">No activities logged.</p>
            </CardContent>
          </Card>

          <!-- Comments Section -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Comments</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3 max-h-64 overflow-y-auto mb-4">
                <div v-for="comment in deal.comments" :key="comment.id" class="p-3 bg-gray-50 rounded">
                  <div class="flex justify-between">
                    <span class="font-medium text-sm">{{ comment.user?.name }}</span>
                    <span class="text-xs text-gray-500">{{ new Date(comment.created_at).toLocaleDateString() }}</span>
                  </div>
                  <p class="text-sm mt-1">{{ comment.body }}</p>
                </div>
              </div>
              <form @submit.prevent="addComment" class="flex gap-2">
                <Textarea placeholder="Add a comment..." class="flex-1" rows="2" />
                <Button type="submit">Send</Button>
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
              <div v-if="deal.quotes?.length" class="space-y-2">
                <div v-for="quote in deal.quotes" :key="quote.id" class="p-2 bg-gray-50 rounded flex justify-between items-center">
                  <span class="text-sm font-medium">Quote #{{ quote.id.substring(0, 8) }}</span>
                  <Badge :variant="quote.status === 'accepted' ? 'default' : 'secondary'">{{ quote.status }}</Badge>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm mb-2">No quotes generated.</p>
              <Button size="sm" class="w-full" @click="generateQuote">Generate Quote</Button>
            </CardContent>
          </Card>

          <!-- Demo/Trial Section -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Demos & Trials</CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="deal.demoTrials?.length" class="space-y-2">
                <div v-for="demo in deal.demoTrials" :key="demo.id" class="p-2 bg-gray-50 rounded">
                  <div class="text-sm font-medium">{{ demo.type }} - {{ demo.scheduled_date }}</div>
                  <Badge size="sm">{{ demo.status }}</Badge>
                </div>
              </div>
              <p v-else class="text-gray-400 text-sm mb-2">No demos/trials scheduled.</p>
              <Button size="sm" variant="outline" class="w-full" @click="scheduleDemo">Schedule Demo</Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>