<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface SocialPost {
  id: string;
  channel: string;
  content: string;
  scheduled_at: string;
  published_at: string;
  status: string;
  likes: number;
  comments: number;
  shares: number;
  impressions: number;
}

const props = defineProps<{
  posts: SocialPost[];
}>();

const posts = ref(props.posts);
const isCreateOpen = ref(false);

const newPost = ref({
  channel: 'x',
  content: '',
  scheduled_at: '',
});

const maxCharacterLimits = {
  linkedin: 3000,
  x: 280,
  facebook: 63206,
};

const characterCount = computed(() => newPost.value.content.length);
const remainingChars = computed(() => {
  const limit = maxCharacterLimits[newPost.value.channel as keyof typeof maxCharacterLimits] || 280;
  return limit - characterCount.value;
});

const createPost = async () => {
  const response = await fetch('/api/v1/social-posts', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(newPost.value),
  });
  
  if (response.ok) {
    isCreateOpen.value = false;
    router.reload();
  } else {
    const error = await response.json();
    alert(error.message || 'Failed to create post');
  }
};

const statusColor = (status: string) => {
  const colors: Record<string, string> = {
    draft: 'outline',
    scheduled: 'secondary',
    published: 'default',
    failed: 'destructive',
  };
  return colors[status] || 'outline';
};
</script>

<template>
  <AppLayout>
    <Head title="Social Posts" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Social Posts</h1>
          <p class="text-gray-500">Schedule and manage social media posts.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ New Post</Button>
          </DialogTrigger>
          <DialogContent class="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create Social Post</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium">Channel</label>
                <Select v-model="newPost.channel">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="linkedin">LinkedIn (3000 chars)</SelectItem>
                    <SelectItem value="x">X/Twitter (280 chars)</SelectItem>
                    <SelectItem value="facebook">Facebook (63206 chars)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <label class="text-sm font-medium">Content</label>
                <Textarea v-model="newPost.content" rows="4" />
                <p class="text-xs text-gray-500 mt-1">{{ characterCount }} / {{ maxCharacterLimits[newPost.channel as keyof typeof maxCharacterLimits] }} characters</p>
              </div>
              <div>
                <label class="text-sm font-medium">Scheduled For</label>
                <Input v-model="newPost.scheduled_at" type="datetime-local" />
              </div>
              <Button @click="createPost">Schedule Post</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Content</TableHead>
                <TableHead class="p-4">Channel</TableHead>
                <TableHead class="p-4">Status</TableHead>
                <TableHead class="p-4">Scheduled</TableHead>
                <TableHead class="p-4">Published</TableHead>
                <TableHead class="p-4">Engagement</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="post in posts" :key="post.id" class="border-b">
                <TableCell class="p-4 max-w-xs truncate">{{ post.content }}</TableCell>
                <TableCell class="p-4">
                  <Badge variant="outline">{{ post.channel }}</Badge>
                </TableCell>
                <TableCell class="p-4">
                  <Badge :variant="statusColor(post.status)">{{ post.status }}</Badge>
                </TableCell>
                <TableCell class="p-4">{{ post.scheduled_at ? new Date(post.scheduled_at).toLocaleString() : '-' }}</TableCell>
                <TableCell class="p-4">{{ post.published_at ? new Date(post.published_at).toLocaleString() : '-' }}</TableCell>
                <TableCell class="p-4 text-sm">
                  <span class="mr-3">❤️ {{ post.likes }}</span>
                  <span class="mr-3">💬 {{ post.comments }}</span>
                  <span>🔁 {{ post.shares }}</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
