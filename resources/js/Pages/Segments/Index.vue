<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import RuleBuilder from '@/Components/Segments/RuleBuilder.vue';

interface Segment {
  id: string;
  name: string;
  description?: string | null;
  type: string;
  goal?: string | null;
  status: string;
  criteria: { rules: any[]; join_operator?: string };
  join_operator: string;
  contact_count: number;
  contact_count_cached_at?: string | null;
  last_evaluated_at?: string | null;
  campaign_id?: string | null;
  tags?: string[];
  channel_eligibility?: string[];
  campaign?: { id: string; name: string } | null;
  creator?: { id: string; name: string } | null;
  created_at: string;
}

const props = defineProps<{
  segments: Segment[];
  campaigns: { id: string; name: string; status: string }[];
  users: { id: string; name: string }[];
}>();

const segmentTypes = ['demographic', 'psychographic', 'behavioral', 'geographic', 'firmographic', 'technographic'];
const segmentGoals = ['acquisition', 'retention', 'reactivation', 'upsell', 'cross_sell', 'loyalty', 'awareness', 'win_back'];
const segmentStatuses = ['draft', 'active', 'paused', 'archived'];
const channelOptions = ['email', 'sms', 'push', 'in_app', 'whatsapp', 'facebook', 'instagram'];

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isPreviewOpen = ref(false);
const editingSegment = ref<Segment | null>(null);
const previewData = ref<any>(null);
const previewLoading = ref(false);
const error = ref('');
const success = ref('');
const searchQuery = ref('');
const filterType = ref('');
const filterGoal = ref('');
const filterStatus = ref('');

const form = ref<{
  name: string;
  description: string;
  type: string;
  goal: string;
  status: string;
  criteria: { rules: any[]; join_operator: string };
  campaign_id: string;
  tags: string[];
  channel_eligibility: string[];
  tagInput: string;
}>({
  name: '',
  description: '',
  type: 'demographic',
  goal: '',
  status: 'draft',
  criteria: { rules: [], join_operator: 'and' },
  campaign_id: '',
  tags: [],
  channel_eligibility: [],
  tagInput: '',
});

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    type: 'demographic',
    goal: '',
    status: 'draft',
    criteria: { rules: [], join_operator: 'and' },
    campaign_id: '',
    tags: [],
    channel_eligibility: [],
    tagInput: '',
  };
};

const openCreate = () => {
  resetForm();
  isCreateOpen.value = true;
};

const openEdit = (segment: Segment) => {
  editingSegment.value = segment;
  form.value = {
    name: segment.name,
    description: segment.description || '',
    type: segment.type,
    goal: segment.goal || '',
    status: segment.status,
    criteria: { ...segment.criteria, join_operator: segment.criteria.join_operator || segment.join_operator || 'and' },
    campaign_id: segment.campaign_id || '',
    tags: segment.tags || [],
    channel_eligibility: segment.channel_eligibility || [],
    tagInput: '',
  };
  isEditOpen.value = true;
};

const addTag = () => {
  const tag = form.value.tagInput.trim();
  if (tag && !form.value.tags.includes(tag)) {
    form.value.tags.push(tag);
  }
  form.value.tagInput = '';
};

const removeTag = (index: number) => {
  form.value.tags.splice(index, 1);
};

const toggleChannel = (channel: string) => {
  const idx = form.value.channel_eligibility.indexOf(channel);
  if (idx >= 0) {
    form.value.channel_eligibility.splice(idx, 1);
  } else {
    form.value.channel_eligibility.push(channel);
  }
};

const submitCreate = async () => {
  error.value = '';
  success.value = '';
  try {
    await router.post('/segments', {
      name: form.value.name,
      description: form.value.description || null,
      type: form.value.type,
      goal: form.value.goal || null,
      status: form.value.status,
      criteria: form.value.criteria,
      campaign_id: form.value.campaign_id || null,
      tags: form.value.tags,
      channel_eligibility: form.value.channel_eligibility,
    });
    isCreateOpen.value = false;
    resetForm();
    success.value = 'Segment created successfully.';
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Failed to create segment.';
  }
};

const submitUpdate = async () => {
  if (!editingSegment.value) return;
  error.value = '';
  success.value = '';
  try {
    await router.put(`/segments/${editingSegment.value.id}`, {
      name: form.value.name,
      description: form.value.description || null,
      type: form.value.type,
      goal: form.value.goal || null,
      status: form.value.status,
      criteria: form.value.criteria,
      campaign_id: form.value.campaign_id || null,
      tags: form.value.tags,
      channel_eligibility: form.value.channel_eligibility,
    });
    isEditOpen.value = false;
    editingSegment.value = null;
    success.value = 'Segment updated successfully.';
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Failed to update segment.';
  }
};

const deleteSegment = async (segment: Segment) => {
  if (!confirm(`Delete segment "${segment.name}"? This cannot be undone.`)) return;
  try {
    await router.delete(`/segments/${segment.id}`);
    success.value = 'Segment deleted.';
  } catch {
    error.value = 'Failed to delete segment.';
  }
};

const previewSegment = async (criteria: any) => {
  previewLoading.value = true;
  error.value = '';
  try {
    const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content;
    const res = await fetch('/api/v1/segments/preview', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
      } as Record<string, string>,
      body: JSON.stringify({ criteria }),
    });
    if (!res.ok) throw new Error('Preview failed');
    previewData.value = await res.json();
    isPreviewOpen.value = true;
  } catch (e) {
    error.value = 'Failed to preview segment.';
  } finally {
    previewLoading.value = false;
  }
};

const activateSegment = async (segment: Segment) => {
  try {
    await router.put(`/segments/${segment.id}`, { status: 'active' });
    success.value = 'Segment activated.';
  } catch {
    error.value = 'Failed to activate segment.';
  }
};

const pauseSegment = async (segment: Segment) => {
  try {
    await router.put(`/segments/${segment.id}`, { status: 'paused' });
    success.value = 'Segment paused.';
  } catch {
    error.value = 'Failed to pause segment.';
  }
};

const typeBadgeVariant = (type: string) => {
  const map: Record<string, string> = { demographic: 'default', psychographic: 'secondary', behavioral: 'outline', geographic: 'success', firmographic: 'warning', technographic: 'destructive' };
  return (map as any)[type] || 'default';
};

const goalBadgeColor = (goal: string) => {
  const map: Record<string, string> = { acquisition: 'Acquisition', retention: 'Retention', reactivation: 'Reactivation', upsell: 'Upsell', cross_sell: 'Cross-sell', loyalty: 'Loyalty', awareness: 'Awareness', win_back: 'Win Back' };
  return (map as any)[goal] || goal;
};

const statusBadgeVariant = (status: string) => {
  const map: Record<string, string> = { draft: 'secondary', active: 'success', paused: 'warning', archived: 'destructive' };
  return (map as any)[status] || 'secondary';
};

const userById = (id: string) => props.users.find((u) => u.id === id);

const filteredSegments = computed(() => {
  return props.segments.filter((s) => {
    if (searchQuery.value && !s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) && !s.description?.toLowerCase().includes(searchQuery.value.toLowerCase())) return false;
    if (filterType.value && s.type !== filterType.value) return false;
    if (filterGoal.value && s.goal !== filterGoal.value) return false;
    if (filterStatus.value && s.status !== filterStatus.value) return false;
    return true;
  });
});

</script>

<template>
  <AppLayout>
    <Head title="Segments" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-start gap-4">
        <div>
          <h1 class="text-3xl font-bold">Audience Segments</h1>
          <p class="text-gray-500">Define targeted audience groups for marketing campaigns, sales outreach, and customer engagement.</p>
        </div>
        <Button @click="openCreate">+ Create Segment</Button>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="text-sm text-green-600">{{ success }}</p>

      <!-- Filters -->
      <Card>
        <CardContent class="pt-4">
          <div class="grid gap-4 sm:grid-cols-4">
            <div>
              <Label>Search</Label>
              <Input v-model="searchQuery" placeholder="Search by name or description..." />
            </div>
            <div>
              <Label>Type</Label>
              <select v-model="filterType" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                <option value="">All Types</option>
                <option v-for="t in segmentTypes" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div>
              <Label>Goal</Label>
              <select v-model="filterGoal" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                <option value="">All Goals</option>
                <option v-for="g in segmentGoals" :key="g" :value="g">{{ g }}</option>
              </select>
            </div>
            <div>
              <Label>Status</Label>
              <select v-model="filterStatus" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option v-for="s in segmentStatuses" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Segment List -->
      <div v-if="filteredSegments.length === 0" class="text-center text-gray-500 py-12">
        <p class="text-lg">No segments found.</p>
        <p class="text-sm">Create your first audience segment to get started with targeted marketing and sales.</p>
      </div>

      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card v-for="segment in filteredSegments" :key="segment.id" class="hover:shadow-md transition-shadow">
          <CardHeader class="pb-2">
            <div class="flex justify-between items-start gap-2">
              <div>
                <CardTitle class="text-lg">{{ segment.name }}</CardTitle>
                <p v-if="segment.description" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ segment.description }}</p>
              </div>
              <Badge :variant="statusBadgeVariant(segment.status)">{{ segment.status }}</Badge>
            </div>
          </CardHeader>
          <CardContent>
            <div class="flex flex-wrap gap-1.5 mb-3">
              <Badge variant="outline" class="text-xs">{{ segment.type }}</Badge>
              <Badge v-if="segment.goal" variant="secondary" class="text-xs">{{ goalBadgeColor(segment.goal) }}</Badge>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
              <div>
                <span class="font-semibold">{{ segment.contact_count }}</span>
                <span class="text-gray-500 ml-1">contacts</span>
              </div>
              <div class="text-right">
                <span v-if="segment.campaign" class="text-xs text-gray-500">Campaign: {{ segment.campaign.name }}</span>
                <span v-else class="text-xs text-gray-400">No linked campaign</span>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
              <div class="text-xs text-gray-500">
                Created: {{ new Date(segment.created_at).toLocaleDateString() }}
              </div>
              <div class="text-right text-xs text-gray-500">
                By {{ segment.creator?.name || 'Unknown' }}
              </div>
            </div>

            <div v-if="segment.tags && segment.tags.length" class="flex flex-wrap gap-1 mb-3">
              <span v-for="tag in segment.tags" :key="tag" class="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ tag }}</span>
            </div>

            <div v-if="segment.channel_eligibility && segment.channel_eligibility.length" class="flex flex-wrap gap-1 mb-3">
              <span v-for="ch in segment.channel_eligibility" :key="ch" class="inline-block bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded-full">{{ ch }}</span>
            </div>

            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t">
              <Button size="sm" variant="outline" @click="openEdit(segment)">Edit</Button>
              <Button size="sm" variant="outline" @click="previewSegment(segment.criteria)" :disabled="previewLoading">Preview</Button>
              <Button v-if="segment.status === 'draft' || segment.status === 'paused'" size="sm" variant="outline" class="text-green-600 border-green-300" @click="activateSegment(segment)">Activate</Button>
              <Button v-if="segment.status === 'active'" size="sm" variant="outline" class="text-amber-600 border-amber-300" @click="pauseSegment(segment)">Pause</Button>
              <Button size="sm" variant="destructive" @click="deleteSegment(segment)">Delete</Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Create Dialog -->
      <Dialog v-model:open="isCreateOpen">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Create Audience Segment</DialogTitle>
            <DialogDescription>Define targeting criteria, business context, and channel configuration for this audience group.</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitCreate" class="space-y-6">
            <!-- Basic Information -->
            <Card>
              <CardHeader><CardTitle class="text-base">Basic Information</CardTitle></CardHeader>
              <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <Label>Segment Name <span class="text-red-500">*</span></Label>
                    <Input v-model="form.name" required placeholder="e.g., High-value enterprise leads" />
                  </div>
                  <div>
                    <Label>Type <span class="text-red-500">*</span></Label>
                    <select v-model="form.type" class="w-full rounded-md border bg-white px-3 py-2 text-sm" required>
                      <option v-for="t in segmentTypes" :key="t" :value="t">{{ t }}</option>
                    </select>
                  </div>
                </div>
                <div>
                  <Label>Description</Label>
                  <p class="text-xs text-gray-500 mb-1">Help your team understand the purpose and scope of this segment.</p>
                  <Textarea v-model="form.description" placeholder="Describe the purpose of this segment for your marketing/sales team..." />
                </div>
              </CardContent>
            </Card>

            <!-- Business Context -->
            <Card>
              <CardHeader><CardTitle class="text-base">Business Context</CardTitle></CardHeader>
              <CardContent>
                <div class="grid gap-4 sm:grid-cols-3">
                  <div>
                    <Label>Marketing / Sales Goal</Label>
                    <p class="text-xs text-gray-500 mb-1">Align this segment with a business objective.</p>
                    <select v-model="form.goal" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                      <option value="">No specific goal</option>
                      <option v-for="g in segmentGoals" :key="g" :value="g">{{ g }}</option>
                    </select>
                  </div>
                  <div>
                    <Label>Status</Label>
                    <p class="text-xs text-gray-500 mb-1">Control whether this segment is actively used.</p>
                    <select v-model="form.status" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                      <option v-for="s in segmentStatuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                  </div>
                  <div>
                    <Label>Linked Campaign</Label>
                    <p class="text-xs text-gray-500 mb-1">Attach this segment to an existing campaign.</p>
                    <select v-model="form.campaign_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                      <option value="">No campaign</option>
                      <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }} ({{ c.status }})</option>
                    </select>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Audience Criteria -->
            <Card>
              <CardHeader><CardTitle class="text-base">Audience Criteria</CardTitle></CardHeader>
              <CardContent class="space-y-4">
                <div>
                  <Label>Filter Rules <span class="text-red-500">*</span></Label>
                  <p class="text-xs text-gray-500 mb-2">Define criteria to match contacts. Add one or more rules with AND/OR logic.</p>
                  <RuleBuilder :rules="form.criteria.rules" @update="form.criteria.rules = $event" />
                </div>
                <div>
                  <Label>Join Logic</Label>
                  <p class="text-xs text-gray-500 mb-1">Determine how multiple rules are combined.</p>
                  <select v-model="form.criteria.join_operator" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                    <option value="and">ALL rules must match (AND)</option>
                    <option value="or">ANY rule can match (OR)</option>
                  </select>
                </div>
              </CardContent>
            </Card>

            <!-- Channel Eligibility -->
            <Card>
              <CardHeader><CardTitle class="text-base">Channel Eligibility</CardTitle></CardHeader>
              <CardContent>
                <p class="text-xs text-gray-500 mb-3">Select which outreach channels this segment is eligible for.</p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                  <label v-for="ch in channelOptions" :key="ch" class="flex items-center gap-2 text-sm cursor-pointer rounded-md border p-2 hover:bg-gray-50">
                    <Checkbox
                      :checked="form.channel_eligibility.includes(ch)"
                      @update:checked="(val: boolean) => {
                        if (val) form.channel_eligibility.push(ch);
                        else {
                          const idx = form.channel_eligibility.indexOf(ch);
                          if (idx >= 0) form.channel_eligibility.splice(idx, 1);
                        }
                      }"
                    />
                    <span class="capitalize">{{ ch.replace('_', ' ') }}</span>
                  </label>
                </div>
              </CardContent>
            </Card>

            <!-- Tags -->
            <Card>
              <CardHeader><CardTitle class="text-base">Tags</CardTitle></CardHeader>
              <CardContent>
                <p class="text-xs text-gray-500 mb-2">Add tags to help organize and find this segment later.</p>
                <div class="flex gap-2">
                  <Input v-model="form.tagInput" placeholder="Add a tag and press Add" @keydown.enter.prevent="addTag" />
                  <Button type="button" variant="outline" size="sm" @click="addTag">Add</Button>
                </div>
                <div v-if="form.tags.length" class="flex flex-wrap gap-1 mt-2">
                  <span v-for="(tag, i) in form.tags" :key="i" class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                    {{ tag }}
                    <button type="button" class="text-blue-400 hover:text-blue-700" @click="removeTag(i)">✕</button>
                  </span>
                </div>
              </CardContent>
            </Card>

            <DialogFooter>
              <Button type="button" variant="outline" @click="isCreateOpen = false">Cancel</Button>
              <Button type="submit">Save Segment</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Edit Dialog -->
      <Dialog v-model:open="isEditOpen">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Edit Segment</DialogTitle>
            <DialogDescription>Update targeting criteria, business context, and channel configuration for this audience group.</DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitUpdate" class="space-y-6">
            <!-- Basic Information -->
            <Card>
              <CardHeader><CardTitle class="text-base">Basic Information</CardTitle></CardHeader>
              <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <Label>Segment Name <span class="text-red-500">*</span></Label>
                    <Input v-model="form.name" required />
                  </div>
                  <div>
                    <Label>Type <span class="text-red-500">*</span></Label>
                    <select v-model="form.type" class="w-full rounded-md border bg-white px-3 py-2 text-sm" required>
                      <option v-for="t in segmentTypes" :key="t" :value="t">{{ t }}</option>
                    </select>
                  </div>
                </div>
                <div>
                  <Label>Description</Label>
                  <Textarea v-model="form.description" />
                </div>
              </CardContent>
            </Card>

            <!-- Business Context -->
            <Card>
              <CardHeader><CardTitle class="text-base">Business Context</CardTitle></CardHeader>
              <CardContent>
                <div class="grid gap-4 sm:grid-cols-3">
                  <div>
                    <Label>Marketing / Sales Goal</Label>
                    <select v-model="form.goal" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                      <option value="">No specific goal</option>
                      <option v-for="g in segmentGoals" :key="g" :value="g">{{ g }}</option>
                    </select>
                  </div>
                  <div>
                    <Label>Status</Label>
                    <select v-model="form.status" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                      <option v-for="s in segmentStatuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                  </div>
                  <div>
                    <Label>Linked Campaign</Label>
                    <select v-model="form.campaign_id" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                      <option value="">No campaign</option>
                      <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }} ({{ c.status }})</option>
                    </select>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Audience Criteria -->
            <Card>
              <CardHeader><CardTitle class="text-base">Audience Criteria</CardTitle></CardHeader>
              <CardContent class="space-y-4">
                <div>
                  <Label>Filter Rules</Label>
                  <RuleBuilder :rules="form.criteria.rules" @update="form.criteria.rules = $event" />
                </div>
                <div>
                  <Label>Join Logic</Label>
                  <select v-model="form.criteria.join_operator" class="w-full rounded-md border bg-white px-3 py-2 text-sm">
                    <option value="and">ALL rules must match (AND)</option>
                    <option value="or">ANY rule can match (OR)</option>
                  </select>
                </div>
              </CardContent>
            </Card>

            <!-- Channel Eligibility -->
            <Card>
              <CardHeader><CardTitle class="text-base">Channel Eligibility</CardTitle></CardHeader>
              <CardContent>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                  <label v-for="ch in channelOptions" :key="ch" class="flex items-center gap-2 text-sm cursor-pointer rounded-md border p-2 hover:bg-gray-50">
                    <Checkbox
                      :checked="form.channel_eligibility.includes(ch)"
                      @update:checked="(val: boolean) => {
                        if (val) form.channel_eligibility.push(ch);
                        else {
                          const idx = form.channel_eligibility.indexOf(ch);
                          if (idx >= 0) form.channel_eligibility.splice(idx, 1);
                        }
                      }"
                    />
                    <span class="capitalize">{{ ch.replace('_', ' ') }}</span>
                  </label>
                </div>
              </CardContent>
            </Card>

            <!-- Tags -->
            <Card>
              <CardHeader><CardTitle class="text-base">Tags</CardTitle></CardHeader>
              <CardContent>
                <div class="flex gap-2">
                  <Input v-model="form.tagInput" placeholder="Add a tag" @keydown.enter.prevent="addTag" />
                  <Button type="button" variant="outline" size="sm" @click="addTag">Add</Button>
                </div>
                <div v-if="form.tags.length" class="flex flex-wrap gap-1 mt-2">
                  <span v-for="(tag, i) in form.tags" :key="i" class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                    {{ tag }}
                    <button type="button" class="text-blue-400 hover:text-blue-700" @click="removeTag(i)">✕</button>
                  </span>
                </div>
              </CardContent>
            </Card>

            <DialogFooter>
              <Button type="button" variant="outline" @click="isEditOpen = false">Cancel</Button>
              <Button type="submit">Update Segment</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Preview Dialog -->
      <Dialog v-model:open="isPreviewOpen">
        <DialogContent class="max-w-2xl">
          <DialogHeader><DialogTitle>Segment Preview</DialogTitle></DialogHeader>
          <div v-if="previewData" class="space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <Card><CardContent class="pt-4 text-center"><p class="text-2xl font-bold">{{ previewData.total_count }}</p><p class="text-xs text-gray-500">Total Matches</p></CardContent></Card>
              <Card><CardContent class="pt-4 text-center"><p class="text-2xl font-bold">{{ previewData.email_eligible }}</p><p class="text-xs text-gray-500">Email Eligible</p></CardContent></Card>
              <Card><CardContent class="pt-4 text-center"><p class="text-2xl font-bold">{{ previewData.sms_eligible }}</p><p class="text-xs text-gray-500">SMS Eligible</p></CardContent></Card>
              <Card><CardContent class="pt-4 text-center"><p class="text-2xl font-bold">{{ previewData.in_app_eligible }}</p><p class="text-xs text-gray-500">In-App Eligible</p></CardContent></Card>
            </div>
            <div v-if="previewData.sample_contacts?.length" class="border rounded-lg p-3">
              <p class="text-sm font-medium mb-2">Sample Matching Contacts</p>
              <div v-for="c in previewData.sample_contacts" :key="c.id" class="text-sm py-1 border-b last:border-0">
                {{ c.first_name }} {{ c.last_name }} — {{ c.email || 'No email' }} ({{ c.type || 'N/A' }})
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
