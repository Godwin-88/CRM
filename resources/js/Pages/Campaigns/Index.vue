<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2, Users, Tag, BarChart3 } from 'lucide-vue-next';
import SegmentBuilder from '@/Components/Segments/SegmentBuilder.vue';

interface Campaign {
  id: string;
  name: string;
  description: string;
  type: string;
  status: string;
  scheduled_at: string;
  segment?: { id: string; name: string; contact_count: number };
  created_by?: { name: string };
}

interface Segment {
  id: string;
  name: string;
  contact_count: number;
}

interface ChannelBreakdown {
  email: number;
  sms: number;
  push: number;
  in_app: number;
  whatsapp: number;
  facebook: number;
  instagram: number;
  tiktok: number;
  linkedin: number;
}

interface Template {
  id: string;
  name: string;
  subject: string;
  type: string;
}

interface StepDraft {
  channel: string;
  template_id: string;
  delay_type: string;
  delay_value: number;
}

const props = defineProps<{
  campaigns: Campaign[];
  segments: Segment[];
  templates: Template[];
}>();

const campaigns = ref(props.campaigns);
const segments = ref(props.segments);
const templates = ref(props.templates);
const isCreateOpen = ref(false);
const isSegmentCreateOpen = ref(false);

const newCampaign = ref({
  name: '',
  description: '',
  type: 'email',
  tags: [] as string[],
});

const selectedSegmentId = ref<string | null>(null);
const segmentCountPreview = ref<number | null>(null);
const segmentChannelBreakdown = ref<ChannelBreakdown | null>(null);
const segmentSample = ref<any[] | null>(null);
const isCalculatingCount = ref(false);
const tagInput = ref('');
const steps = ref<StepDraft[]>([]);

const availableChannels = computed(() => {
  const base = ['email', 'sms', 'push', 'in_app'];
  const social = ['whatsapp', 'facebook', 'instagram', 'tiktok', 'linkedin'];
  if (newCampaign.value.type === 'multi_channel' || newCampaign.value.type === 'social') {
    return [...base, ...social];
  }
  if (newCampaign.value.type === 'email') return ['email'];
  if (newCampaign.value.type === 'sms') return ['sms'];
  if (newCampaign.value.type === 'social') return social;
  return base;
});

const addStep = () => {
  steps.value.push({
    channel: availableChannels.value[0] || 'email',
    template_id: '',
    delay_type: steps.value.length === 0 ? 'immediately' : 'n_hours',
    delay_value: 24,
  });
};

const removeStep = (index: number) => {
  steps.value.splice(index, 1);
};

const addTag = () => {
  const val = tagInput.value.trim();
  if (val && !newCampaign.value.tags.includes(val)) {
    newCampaign.value.tags.push(val);
    tagInput.value = '';
  }
};

const removeTag = (tag: string) => {
  newCampaign.value.tags = newCampaign.value.tags.filter(t => t !== tag);
};

const previewSegmentCount = async (segmentId: string) => {
  if (!segmentId) {
    segmentCountPreview.value = null;
    segmentChannelBreakdown.value = null;
    segmentSample.value = null;
    return;
  }
  isCalculatingCount.value = true;
  try {
    const response = await fetch(`/api/v1/segments/${segmentId}/count`);
    const data = await response.json();
    segmentCountPreview.value = data.total_count;
    segmentChannelBreakdown.value = {
      email: data.email_eligible ?? 0,
      sms: data.sms_eligible ?? 0,
      push: data.push_eligible ?? 0,
      in_app: data.in_app_eligible ?? 0,
      whatsapp: data.whatsapp_eligible ?? 0,
      facebook: data.facebook_eligible ?? 0,
      instagram: data.instagram_eligible ?? 0,
      tiktok: data.tiktok_eligible ?? 0,
      linkedin: data.linkedin_eligible ?? 0,
    };
    segmentSample.value = data.sample_contacts ?? [];
  } catch {
    segmentCountPreview.value = null;
    segmentChannelBreakdown.value = null;
  } finally {
    isCalculatingCount.value = false;
  }
};

const createCampaign = async () => {
  const payload: any = {
    name: newCampaign.value.name,
    description: newCampaign.value.description,
    type: newCampaign.value.type,
    tags: newCampaign.value.tags,
    segment_id: selectedSegmentId.value,
    steps: steps.value.map((s, idx) => ({
      channel: s.channel,
      position: idx,
      delay_type: s.delay_type,
      delay_value: s.delay_value,
      ...(s.channel === 'email' ? { email_template_id: s.template_id || null } : {}),
      ...(s.channel === 'sms' ? { sms_content: '' } : {}),
      ...(s.channel === 'push' ? { push_title: '', push_content: '' } : {}),
      ...(s.channel === 'in_app' ? { in_app_title: '', in_app_content: '' } : {}),
      ...(s.channel === 'whatsapp' ? { whatsapp_content: '' } : {}),
      ...(s.channel === 'facebook' ? { facebook_content: '' } : {}),
      ...(s.channel === 'instagram' ? { instagram_content: '' } : {}),
      ...(s.channel === 'tiktok' ? { tiktok_content: '' } : {}),
      ...(s.channel === 'linkedin' ? { linkedin_content: '' } : {}),
    })),
  };

  const response = await fetch('/api/v1/campaigns', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(payload),
  });

  if (response.ok) {
    const data = await response.json();
    isCreateOpen.value = false;
    router.visit(`/admin/campaigns/${data.id}`);
  }
};

const canSubmit = computed(() => {
  if (!newCampaign.value.name.trim().length) return false;
  if (steps.value.length > 0 && !selectedSegmentId.value) return false;
  if (steps.value.length > 0 && segmentCountPreview.value === 0) return false;
  return true;
});

const statusColor = (status: string): "default" | "outline" | "secondary" | "destructive" | "success" | null | undefined => {
  const colors: Record<string, "default" | "outline" | "secondary" | "destructive" | "success"> = {
    draft: 'outline',
    scheduled: 'secondary',
    sending: 'default',
    sent: 'default',
    paused: 'secondary',
    cancelled: 'outline',
    failed: 'destructive',
  };
  return colors[status] || 'outline';
};

const handleSegmentCreated = async (segmentData: any) => {
  try {
    const response = await fetch('/api/v1/segments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
      },
      body: JSON.stringify(segmentData),
    });
    if (response.ok) {
      const newSegment = await response.json();
      segments.value.push(newSegment);
      selectedSegmentId.value = newSegment.id;
      isSegmentCreateOpen.value = false;
      previewSegmentCount(newSegment.id);
    }
  } catch {
    // ignore
  }
};
</script>

<template>
  <AppLayout>
    <Head title="Campaigns" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Campaigns</h1>
          <p class="text-gray-500">Manage marketing campaigns across all channels.</p>
        </div>
        <div class="flex gap-2">
          <Button variant="outline" @click="router.visit('/admin/drip-sequences')">Drip Sequences</Button>
          <Dialog v-model:open="isCreateOpen">
            <DialogTrigger as-child>
              <Button>+ New Campaign</Button>
            </DialogTrigger>
            <DialogContent class="max-w-3xl max-h-[85vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>Create Campaign</DialogTitle>
              </DialogHeader>
              <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="text-sm font-medium">Campaign Name</label>
                    <Input v-model="newCampaign.name" placeholder="e.g., Q3 Welcome Series" />
                  </div>
                  <div>
                    <label class="text-sm font-medium">Channel Type</label>
                    <Select v-model="newCampaign.type">
                      <SelectTrigger>
                        <SelectValue placeholder="Select channel type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="email">Email</SelectItem>
                        <SelectItem value="sms">SMS</SelectItem>
                        <SelectItem value="multi_channel">Multi-Channel</SelectItem>
                        <SelectItem value="social">Social Media</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div>
                  <label class="text-sm font-medium">Description</label>
                  <Textarea v-model="newCampaign.description" placeholder="Describe the campaign goal and target audience..." rows="2" />
                </div>

                <div>
                  <label class="text-sm font-medium flex items-center gap-2">
                    <Users class="h-4 w-4" /> Target Segment
                  </label>
                  <Select v-model="selectedSegmentId" @update:model-value="(val: any) => {
                    if (val === '__create_new__') {
                      selectedSegmentId = null;
                      isSegmentCreateOpen = true;
                    } else if (val) {
                      previewSegmentCount(val);
                    }
                  }">
                    <SelectTrigger>
                      <SelectValue placeholder="Select a segment" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="seg in segments" :key="seg.id" :value="seg.id">
                        {{ seg.name }} ({{ seg.contact_count }} contacts)
                      </SelectItem>
                      <SelectItem value="__create_new__">+ Create new segment</SelectItem>
                    </SelectContent>
                  </Select>
                  <div v-if="isCalculatingCount" class="text-xs text-gray-500 mt-1">Calculating reach...</div>
                  <div v-else-if="segmentCountPreview !== null" class="mt-2 p-3 bg-blue-50 rounded-md text-sm">
                    <div class="flex items-center gap-2 font-medium text-blue-900">
                      <BarChart3 class="h-4 w-4" />
                      {{ segmentCountPreview.toLocaleString() }} contacts reachable
                    </div>
                    <div v-if="segmentChannelBreakdown" class="mt-1 text-xs text-blue-700 space-y-0.5">
                      <div>{{ segmentChannelBreakdown.email.toLocaleString() }} email-eligible</div>
                      <div>{{ segmentChannelBreakdown.sms.toLocaleString() }} SMS-eligible</div>
                      <div>{{ segmentChannelBreakdown.push.toLocaleString() }} push-eligible</div>
                      <div>{{ segmentChannelBreakdown.in_app.toLocaleString() }} in-app-eligible</div>
                      <div>{{ segmentChannelBreakdown.whatsapp.toLocaleString() }} WhatsApp-eligible</div>
                      <div>{{ segmentChannelBreakdown.facebook.toLocaleString() }} Facebook-eligible</div>
                      <div>{{ segmentChannelBreakdown.instagram.toLocaleString() }} Instagram-eligible</div>
                      <div>{{ segmentChannelBreakdown.tiktok.toLocaleString() }} TikTok-eligible</div>
                      <div>{{ segmentChannelBreakdown.linkedin.toLocaleString() }} LinkedIn-eligible</div>
                    </div>
                    <div v-if="segmentSample && segmentSample.length" class="mt-2 text-xs text-gray-600">
                      Sample: {{ segmentSample.slice(0, 5).map((c: any) => `${c.first_name} ${c.last_name}`).join(', ') }}
                    </div>
                    <div v-if="segmentCountPreview === 0 && steps.length > 0" class="mt-2 text-xs text-red-600 font-medium">
                      Cannot proceed: segment has zero matching contacts.
                    </div>
                  </div>
                </div>

                <div>
                  <label class="text-sm font-medium flex items-center gap-2">
                    <Tag class="h-4 w-4" /> Tags
                  </label>
                  <div class="flex gap-2 mt-1">
                    <Input v-model="tagInput" @keyup.enter="addTag" placeholder="Add tag and press Enter" />
                    <Button type="button" size="sm" variant="outline" @click="addTag">Add</Button>
                  </div>
                  <div v-if="newCampaign.tags.length" class="flex flex-wrap gap-2 mt-2">
                    <Badge v-for="tag in newCampaign.tags" :key="tag" variant="secondary" class="cursor-pointer" @click="removeTag(tag)">
                      {{ tag }} ×
                    </Badge>
                  </div>
                </div>

                <div v-if="newCampaign.type === 'multi_channel' || newCampaign.type === 'social'" class="border-t pt-4">
                  <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium">Campaign Steps</label>
                    <Button type="button" size="sm" variant="outline" @click="addStep">
                      <Plus class="h-4 w-4 mr-1" /> Add Step
                    </Button>
                  </div>
                  <div v-if="!steps.length" class="text-sm text-gray-500 p-4 border rounded-md text-center">
                    No steps defined. Add steps to build your multi-channel sequence.
                  </div>
                  <div v-for="(step, idx) in steps" :key="idx" class="grid grid-cols-12 gap-2 mb-2 items-end">
                    <div class="col-span-2">
                      <label class="text-xs text-gray-500">Channel</label>
                      <Select v-model="step.channel">
                        <SelectTrigger class="h-9">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="ch in availableChannels" :key="ch" :value="ch">{{ ch }}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div class="col-span-4">
                      <label class="text-xs text-gray-500">Template / Content</label>
                      <Select v-model="step.template_id" v-if="step.channel === 'email'">
                        <SelectTrigger class="h-9">
                          <SelectValue placeholder="Select template" />
                        </SelectTrigger>
                       <SelectContent>
                         <SelectItem v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</SelectItem>
                       </SelectContent>
                      </Select>
                      <Input v-else v-model="step.template_id" placeholder="Content placeholder" class="h-9" />
                    </div>
                    <div class="col-span-2">
                      <label class="text-xs text-gray-500">Delay</label>
                      <Select v-model="step.delay_type">
                        <SelectTrigger class="h-9">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="immediately">Now</SelectItem>
                          <SelectItem value="n_hours">+Hours</SelectItem>
                          <SelectItem value="n_days">+Days</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div class="col-span-2" v-if="step.delay_type !== 'immediately'">
                      <label class="text-xs text-gray-500">Value</label>
                      <Input v-model.number="step.delay_value" type="number" min="1" class="h-9" />
                    </div>
                    <div class="col-span-1">
                      <Button type="button" size="icon" variant="ghost" class="h-9 w-9 text-red-500" @click="removeStep(idx)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>

                <Dialog v-model:open="isSegmentCreateOpen">
                  <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                      <DialogTitle>Create Segment</DialogTitle>
                    </DialogHeader>
                    <SegmentBuilder @update="handleSegmentCreated" />
                  </DialogContent>
                </Dialog>

                <Button @click="createCampaign" :disabled="!canSubmit" class="w-full">
                  Create Campaign
                </Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Name</TableHead>
                <TableHead class="p-4">Type</TableHead>
                <TableHead class="p-4">Status</TableHead>
                <TableHead class="p-4">Segment</TableHead>
                <TableHead class="p-4">Scheduled For</TableHead>
                <TableHead class="p-4">Created By</TableHead>
                <TableHead class="p-4"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="campaign in campaigns" :key="campaign.id" class="border-b">
                <TableCell class="p-4 font-medium">{{ campaign.name }}</TableCell>
                <TableCell class="p-4">
                  <Badge variant="outline">{{ campaign.type.replace('_', ' ') }}</Badge>
                </TableCell>
                <TableCell class="p-4">
                  <Badge :variant="statusColor(campaign.status)">{{ campaign.status }}</Badge>
                </TableCell>
                <TableCell class="p-4">{{ campaign.segment?.name || 'Not set' }}</TableCell>
                <TableCell class="p-4">{{ campaign.scheduled_at ? new Date(campaign.scheduled_at).toLocaleString() : '-' }}</TableCell>
                <TableCell class="p-4">{{ campaign.created_by?.name || '-' }}</TableCell>
                <TableCell class="p-4">
                  <Button variant="ghost" size="sm" @click="router.visit(`/admin/campaigns/${campaign.id}`)">
                    {{ campaign.status === 'draft' ? 'Build' : 'View' }}
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
