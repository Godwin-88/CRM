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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

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

interface Template {
  id: string;
  name: string;
  subject: string;
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
const isSegmentSelectorOpen = ref(false);

const newCampaign = ref({
  name: '',
  type: 'email',
});

const selectedSegmentId = ref<string | null>(null);
const segmentCountPreview = ref<number | null>(null);
const isCalculatingCount = ref(false);

const createCampaign = async () => {
  const response = await fetch('/api/v1/campaigns', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newCampaign.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  }
};

const previewSegmentCount = async (segmentId: string) => {
  if (!segmentId) {
    segmentCountPreview.value = null;
    return;
  }
  isCalculatingCount.value = true;
  const response = await fetch(`/api/v1/segments/${segmentId}/preview`);
  const data = await response.json();
  segmentCountPreview.value = data.count;
  isCalculatingCount.value = false;
};

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
            <DialogContent class="max-w-2xl">
              <DialogHeader>
                <DialogTitle>Create Campaign</DialogTitle>
              </DialogHeader>
              <div class="space-y-4">
                <div>
                  <label class="text-sm font-medium">Campaign Name</label>
                  <Input v-model="newCampaign.name" placeholder="e.g., Q3 Welcome Series" />
                </div>
                <div>
                  <label class="text-sm font-medium">Channel Type</label>
                  <Select v-model="newCampaign.type">
                    <SelectTrigger>
                      <SelectValue placeholder="Select channel" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="email">Email</SelectItem>
                      <SelectItem value="sms">SMS</SelectItem>
                      <SelectItem value="multi_channel">Multi-Channel</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Button @click="createCampaign">Create Campaign</Button>
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