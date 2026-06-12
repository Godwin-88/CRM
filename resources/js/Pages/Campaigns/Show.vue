<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface Campaign {
  id: string;
  name: string;
  description: string;
  type: string;
  status: string;
  scheduled_at: string;
  throttle_emails_per_hour: number;
  throttle_sms_per_hour: number;
  optimize_send_time: boolean;
  utm_source: string;
  utm_medium: string;
  utm_campaign: string;
  utm_term: string;
  utm_content: string;
  tags: string[];
  segment?: { id: string; name: string; contact_count: number };
  created_by?: { name: string };
  steps?: CampaignStep[];
  abTest?: ABTest;
}

interface CampaignStep {
  id: string;
  position: number;
  channel: string;
  delay_type: string;
  delay_value: number;
  status: string;
  email_template?: { id: string; name: string };
}

interface ABTest {
  id: string;
  test_type: string;
  winner_criterion: string;
  test_percentage: number;
  duration_hours: number;
  status: string;
  winning_variant: string;
}

const props = defineProps<{
  campaign: Campaign;
  templates: { id: string; name: string }[];
}>();

const campaign = ref(props.campaign);
const templates = ref(props.templates);
const isAddStepOpen = ref(false);
const isABTestOpen = ref(false);
const isScheduleOpen = ref(false);
const isUtmOpen = ref(false);

const newStep = ref({
  channel: 'email',
  delay_type: 'immediately',
  delay_value: 0,
  email_template_id: '',
});

const abTestData = ref({
  test_type: 'subject_line',
  winner_criterion: 'open_rate',
  test_percentage: 20,
  duration_hours: 24,
  subject_line_a: '',
  subject_line_b: '',
});

const scheduleData = ref({
  scheduled_at: '',
  throttle_emails_per_hour: 5000,
  throttle_sms_per_hour: 1000,
  optimize_send_time: false,
});

const utmData = ref({
  source: '',
  medium: '',
  campaign: '',
  term: '',
  content: '',
});

const addStep = async () => {
  const position = campaign.value.steps?.length || 0;
  const response = await fetch(`/api/v1/campaigns/${campaign.value.id}/steps`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify({ ...newStep.value, position }),
  });
  if (response.ok) {
    isAddStepOpen.value = false;
    router.reload();
  }
};

const scheduleCampaign = async () => {
  const response = await fetch(`/api/v1/campaigns/${campaign.value.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify({ ...scheduleData.value, status: 'scheduled' }),
  });
  if (response.ok) {
    isScheduleOpen.value = false;
    router.reload();
  }
};

const updateUtm = async () => {
  const response = await fetch(`/api/v1/campaigns/${campaign.value.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify({
      utm_source: utmData.value.source,
      utm_medium: utmData.value.medium,
      utm_campaign: utmData.value.campaign,
      utm_term: utmData.value.term,
      utm_content: utmData.value.content,
    }),
  });
  if (response.ok) {
    isUtmOpen.value = false;
    router.reload();
  }
};

const pauseCampaign = async () => {
  await fetch(`/api/v1/campaigns/${campaign.value.id}/pause`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  router.reload();
};

const resumeCampaign = async () => {
  await fetch(`/api/v1/campaigns/${campaign.value.id}/resume`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
  });
  router.reload();
};

const countdown = computed(() => {
  if (!campaign.value.scheduled_at) return null;
  const scheduled = new Date(campaign.value.scheduled_at);
  const now = new Date();
  const diff = scheduled.getTime() - now.getTime();
  if (diff <= 0) return 'Sending now';
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  return `${hours}h ${minutes}m`;
});
</script>

<template>
  <AppLayout>
    <Head :title="campaign.name" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">{{ campaign.name }}</h1>
          <p class="text-gray-500">{{ campaign.description }}</p>
        </div>
        <div class="flex gap-2">
          <Badge :variant="campaign.status === 'sending' || campaign.status === 'scheduled' ? 'default' : 'outline'">
            {{ campaign.status }}
          </Badge>
          <Button variant="outline" @click="isUtmOpen = true">UTM Settings</Button>
          <Button variant="outline" @click="isScheduleOpen = true">Schedule</Button>
          <Button variant="outline" @click="isABTestOpen = true">A/B Test</Button>
          <Button @click="isAddStepOpen = true">+ Add Step</Button>
        </div>
      </div>

      <!-- Countdown Display -->
      <div v-if="campaign.status === 'scheduled'" class="bg-blue-50 p-4 rounded-lg">
        <p class="text-sm font-medium">Scheduled to send in: <span class="text-blue-600">{{ countdown }}</span></p>
      </div>

      <!-- A/B Test Status -->
      <Card v-if="campaign.abTest">
        <CardHeader>
          <CardTitle>A/B Test: {{ campaign.abTest.test_type.replace('_', ' ') }}</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-500">Winner Criterion</p>
              <p>{{ campaign.abTest.winner_criterion }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Status</p>
              <Badge>{{ campaign.abTest.status }}</Badge>
            </div>
            <div v-if="campaign.abTest.winning_variant">
              <p class="text-xs text-gray-500">Winning Variant</p>
              <Badge variant="default">{{ campaign.abTest.winning_variant }}</Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Campaign Steps -->
      <Card>
        <CardHeader>
          <CardTitle>Campaign Steps</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>#</TableHead>
                <TableHead>Channel</TableHead>
                <TableHead>Template/SMS</TableHead>
                <TableHead>Delay</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="step in campaign.steps" :key="step.id">
                <TableCell>{{ step.position }}</TableCell>
                <TableCell>
                  <Badge variant="outline">{{ step.channel }}</Badge>
                </TableCell>
                <TableCell>
                  <span v-if="step.channel === 'email'">{{ step.email_template?.name || 'No template' }}</span>
                  <span v-else-if="step.channel === 'sms'">SMS content</span>
                </TableCell>
                <TableCell>
                  {{ step.delay_type === 'immediately' ? 'Immediately' : `${step.delay_value} ${step.delay_type === 'n_hours' ? 'hours' : 'days'}` }}
                </TableCell>
                <TableCell>
                  <Badge :variant="step.status === 'sent' ? 'default' : 'outline'">{{ step.status }}</Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Segment Target -->
      <Card>
        <CardHeader>
          <CardTitle>Target Audience</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="campaign.segment">
            <p class="font-medium">{{ campaign.segment.name }}</p>
            <p class="text-sm text-gray-500">{{ campaign.segment.contact_count }} contacts in segment</p>
          </div>
          <div v-else>
            <p class="text-gray-500">No segment selected</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Add Step Dialog -->
    <Dialog v-model:open="isAddStepOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Campaign Step</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium">Channel</label>
            <Select v-model="newStep.channel">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="email">Email</SelectItem>
                <SelectItem value="sms">SMS</SelectItem>
                <SelectItem value="push">Push</SelectItem>
                <SelectItem value="in_app">In-App</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <label class="text-sm font-medium">Email Template</label>
            <Select v-model="newStep.email_template_id">
              <SelectTrigger><SelectValue placeholder="Select template" /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <label class="text-sm font-medium">Delay</label>
            <Select v-model="newStep.delay_type">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="immediately">Immediately</SelectItem>
                <SelectItem value="n_hours">After N Hours</SelectItem>
                <SelectItem value="n_days">After N Days</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div v-if="newStep.delay_type !== 'immediately'">
            <Input v-model="newStep.delay_value" type="number" min="1" placeholder="Delay value" />
          </div>
          <Button @click="addStep">Add Step</Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Schedule Dialog -->
    <Dialog v-model:open="isScheduleOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Schedule Campaign</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium">Scheduled At</label>
            <Input v-model="scheduleData.scheduled_at" type="datetime-local" />
          </div>
          <div>
            <label class="text-sm font-medium">Email Throttle (per hour)</label>
            <Input v-model="scheduleData.throttle_emails_per_hour" type="number" />
          </div>
          <div>
            <label class="text-sm font-medium">SMS Throttle (per hour)</label>
            <Input v-model="scheduleData.throttle_sms_per_hour" type="number" />
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="scheduleData.optimize_send_time" id="optimize" />
            <label for="optimize" class="text-sm">Optimize send time per contact</label>
          </div>
          <Button @click="scheduleCampaign">Schedule Campaign</Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- UTM Dialog -->
    <Dialog v-model:open="isUtmOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>UTM Tracking</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div><label class="text-sm font-medium">Source</label><Input v-model="utmData.source" /></div>
          <div><label class="text-sm font-medium">Medium</label><Input v-model="utmData.medium" /></div>
          <div><label class="text-sm font-medium">Campaign</label><Input v-model="utmData.campaign" /></div>
          <div><label class="text-sm font-medium">Term</label><Input v-model="utmData.term" /></div>
          <div><label class="text-sm font-medium">Content</label><Input v-model="utmData.content" /></div>
          <Button @click="updateUtm">Save UTM</Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- A/B Test Dialog -->
    <Dialog v-model:open="isABTestOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>A/B Test Configuration</DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium">Test Type</label>
            <Select v-model="abTestData.test_type">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="subject_line">Subject Line</SelectItem>
                <SelectItem value="content_variant">Content Variant</SelectItem>
                <SelectItem value="send_time">Send Time</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <label class="text-sm font-medium">Winner Criterion</label>
            <Select v-model="abTestData.winner_criterion">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="open_rate">Open Rate</SelectItem>
                <SelectItem value="click_rate">Click Rate</SelectItem>
                <SelectItem value="conversion">Conversion</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <label class="text-sm font-medium">Test Percentage</label>
            <Input v-model="abTestData.test_percentage" type="number" min="1" max="100" />
          </div>
          <div>
            <label class="text-sm font-medium">Duration (hours)</label>
            <Input v-model="abTestData.duration_hours" type="number" min="1" max="72" />
          </div>
          <Button>Apply A/B Test</Button>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
