<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Search, Tag as TagIcon, Trash2 } from 'lucide-vue-next';

interface Tag {
  id: string;
  name: string;
  type: string;
  usage_count: number;
}

const props = defineProps<{ tags: Tag[] }>();

const tags = ref<Tag[]>([]);
const filteredTags = ref<Tag[]>([]);
const searchQuery = ref('');
const isCreateOpen = ref(false);
const isBulkOpen = ref(false);

const newTag = ref({ name: '', type: 'campaign' });

const bulkData = ref({ campaign_ids: [] as string[], tags: [] as string[], operation: 'add' as 'add' | 'remove' });
const bulkCampaignSearch = ref('');
const availableCampaigns = ref<{ id: string; name: string }[]>([]);

onMounted(async () => {
  tags.value = props.tags;
  filteredTags.value = tags.value;
  await fetchAvailableCampaigns();
});

const filterTags = () => {
  if (!searchQuery.value) {
    filteredTags.value = tags.value;
  } else {
    filteredTags.value = tags.value.filter(t => t.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
  }
};

const fetchAvailableCampaigns = async () => {
  const res = await fetch('/api/v1/campaigns?per_page=100');
  if (res.ok) {
    const data = await res.json();
    availableCampaigns.value = (data.data || []).map((c: any) => ({ id: c.id, name: c.name }));
  }
};

const createTag = async () => {
  const res = await fetch('/api/v1/tags', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify(newTag.value),
  });
  if (res.ok) {
    const tag = await res.json();
    tags.value.push(tag);
    newTag.value = { name: '', type: 'campaign' };
    isCreateOpen.value = false;
  }
};

const deleteTag = async (tagId: string) => {
  if (!confirm('Delete this tag?')) return;
  const res = await fetch(`/api/v1/tags/${tagId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content } });
  if (res.ok) {
    tags.value = tags.value.filter(t => t.id !== tagId);
  }
};

const bulkApply = async () => {
  const res = await fetch('/api/v1/tags/bulk-apply', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
    body: JSON.stringify(bulkData.value),
  });
  if (res.ok) {
    isBulkOpen.value = false;
    router?.reload?.();
  }
};

const toggleCampaign = (id: string) => {
  const idx = bulkData.value.campaign_ids.indexOf(id);
  if (idx === -1) bulkData.value.campaign_ids.push(id);
  else bulkData.value.campaign_ids.splice(idx, 1);
};

const addNewTagToBulk = () => {
  const name = prompt('Tag name');
  if (name && !bulkData.value.tags.includes(name)) bulkData.value.tags.push(name);
};

const removeBulkTag = (name: string) => {
  bulkData.value.tags = bulkData.value.tags.filter(t => t !== name);
};
</script>

<template>
  <AppLayout>
    <Head title="Tag Management" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Tag Management</h1>
          <p class="text-muted-foreground">Create, organize, and apply campaign tags.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />New Tag</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Tag</DialogTitle></DialogHeader>
            <div class="space-y-4 py-4">
              <div class="space-y-2"><Label>Tag Name</Label><Input v-model="newTag.name" placeholder="e.g. q3-launch" /></div>
              <div class="space-y-2"><Label>Type</Label><Input v-model="newTag.type" placeholder="campaign" /></div>
              <Button @click="createTag" class="w-full">Create Tag</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <div class="flex items-center gap-2">
            <Search class="h-4 w-4 text-gray-400" />
            <Input v-model="searchQuery" placeholder="Search tags..." class="max-w-sm" @input="filterTags" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="tag in filteredTags" :key="tag.id" class="flex items-center justify-between border rounded-lg p-3 hover:bg-gray-50">
              <div class="flex items-center gap-2">
                <TagIcon class="h-4 w-4 text-blue-500" />
                <div>
                  <div class="font-medium text-sm">{{ tag.name }}</div>
                  <div class="text-xs text-gray-500">{{ tag.type }} · {{ tag.usage_count }} uses</div>
                </div>
              </div>
              <Button variant="ghost" size="sm" @click="deleteTag(tag.id)"><Trash2 class="h-4 w-4 text-red-500" /></Button>
            </div>
            <div v-if="!filteredTags.length" class="col-span-full text-center py-8 text-gray-500">No tags found.</div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Bulk Apply Tags</CardTitle></CardHeader>
        <CardContent>
          <Button variant="outline" @click="isBulkOpen = true">Open Bulk Tag Manager</Button>
          <Dialog v-model:open="isBulkOpen">
            <DialogContent class="sm:max-w-2xl">
              <DialogHeader><DialogTitle>Bulk Tag Manager</DialogTitle></DialogHeader>
              <div class="space-y-4 py-4">
                <div class="space-y-2">
                  <Label>Campaigns</Label>
                  <Input v-model="bulkCampaignSearch" placeholder="Search campaigns..." />
                  <div class="max-h-40 overflow-auto border rounded p-2">
                    <label v-for="c in availableCampaigns" :key="c.id" class="flex items-center gap-2 py-1">
                      <input type="checkbox" :checked="bulkData.campaign_ids.includes(c.id)" @change="toggleCampaign(c.id)" />
                      <span class="text-sm">{{ c.name }}</span>
                    </label>
                  </div>
                </div>
                <div class="space-y-2"><Label>Tags</Label>
                  <div class="flex flex-wrap gap-2">
                    <Badge v-for="t in bulkData.tags" :key="t" class="cursor-pointer" @click="removeBulkTag(t)">{{ t }} ×</Badge>
                    <Button variant="outline" size="sm" @click="addNewTagToBulk"><Plus class="h-3 w-3 mr-1" />Add</Button>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Label>Operation</Label>
                  <Select v-model="bulkData.operation">
                    <SelectTrigger class="w-32">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="add">Add</SelectItem>
                      <SelectItem value="remove">Remove</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Button @click="bulkApply" class="w-full">Apply to Selected Campaigns</Button>
              </div>
            </DialogContent>
          </Dialog>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
