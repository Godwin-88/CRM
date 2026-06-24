<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, Save, Send, Bold, Italic, Underline, Heading1, Heading2, Heading3, List, ListOrdered, Link, Image } from 'lucide-vue-next';

const props = defineProps<{
  categories: any[];
  article: {
    id: string;
    title: string;
    slug: string;
    body: string;
    category_id: string;
    status: string;
    audience: string;
    feature_refs: string[];
  } | null;
}>();

const isEditing = computed(() => !!props.article);

const title = ref(props.article?.title || '');
const slug = ref(props.article?.slug || '');
const categoryId = ref(props.article?.category_id || '');
const status = ref(props.article?.status || 'draft');
const audience = ref(props.article?.audience || 'all');
const featureRefs = ref((props.article?.feature_refs || []).join(', '));
const saving = ref(false);
const error = ref('');

const editorRef = ref<HTMLElement | null>(null);

onMounted(() => {
  if (editorRef.value && props.article?.body) {
    editorRef.value.innerHTML = props.article.body;
  }
});

watch(title, (newTitle) => {
  if (!props.article) {
    slug.value = slugify(newTitle);
  }
});

const slugify = (text: string) => {
  return text
    .toString()
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-')
    .replace(/^-+/, '')
    .replace(/-+$/, '');
};

const exec = (command: string, value: string | null = null) => {
  document.execCommand(command, false, value);
  editorRef.value?.focus();
};

const insertLink = () => {
  const url = prompt('Enter URL:');
  if (url) {
    exec('createLink', url);
  }
};

const insertImage = () => {
  const url = prompt('Enter image URL:');
  if (url) {
    exec('insertImage', url);
  }
};

const getBody = (): string => {
  return editorRef.value?.innerHTML || '';
};

const submit = async (publish: boolean) => {
  saving.value = true;
  error.value = '';

  const payload: any = {
    title: title.value,
    slug: slug.value,
    body: getBody(),
    category_id: categoryId.value,
    audience: audience.value,
    feature_refs: featureRefs.value
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean),
  };

  if (publish) {
    payload.status = 'published';
  } else {
    payload.status = status.value;
  }

  const url = isEditing.value ? `/api/v1/knowledge-base/${props.article!.id}` : '/api/v1/knowledge-base';
  const method = isEditing.value ? 'PUT' : 'POST';

  try {
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (response.ok) {
      router.visit('/support/knowledge-base');
      return;
    }

    if (response.status === 422) {
      const data = await response.json().catch(() => ({}));
      error.value = data.message || 'Validation failed. Please check the form.';
      return;
    }

    const data = await response.json().catch(() => ({}));
    error.value = data.message || 'Unable to save article.';
  } catch {
    error.value = 'Network error. Please try again.';
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AppLayout>
    <Head :title="isEditing ? 'Edit Article' : 'New Article'" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="icon" @click="router.visit('/support/knowledge-base')">
          <ArrowLeft class="h-4 w-4" />
        </Button>
        <div>
          <h1 class="text-3xl font-bold tracking-tight">{{ isEditing ? 'Edit Article' : 'New Article' }}</h1>
          <p class="text-gray-500">{{ isEditing ? 'Update the article content and settings.' : 'Write and publish a new knowledge base article.' }}</p>
        </div>
      </div>

      <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
          </div>
          <p class="text-sm font-medium text-red-700">{{ error }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-3 space-y-4">
          <div class="rounded-lg border bg-white shadow-sm">
            <div class="border-b p-2 flex flex-wrap gap-1">
              <Button type="button" variant="ghost" size="sm" @click="exec('bold')" title="Bold">
                <Bold class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="exec('italic')" title="Italic">
                <Italic class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="exec('underline')" title="Underline">
                <Underline class="h-4 w-4" />
              </Button>
              <span class="w-px bg-gray-200 mx-1" />
              <Button type="button" variant="ghost" size="sm" @click="exec('formatBlock', 'H1')" title="Heading 1">
                <Heading1 class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="exec('formatBlock', 'H2')" title="Heading 2">
                <Heading2 class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="exec('formatBlock', 'H3')" title="Heading 3">
                <Heading3 class="h-4 w-4" />
              </Button>
              <span class="w-px bg-gray-200 mx-1" />
              <Button type="button" variant="ghost" size="sm" @click="exec('insertUnorderedList')" title="Bullet List">
                <List class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="exec('insertOrderedList')" title="Numbered List">
                <ListOrdered class="h-4 w-4" />
              </Button>
              <span class="w-px bg-gray-200 mx-1" />
              <Button type="button" variant="ghost" size="sm" @click="insertLink" title="Insert Link">
                <Link class="h-4 w-4" />
              </Button>
              <Button type="button" variant="ghost" size="sm" @click="insertImage" title="Insert Image">
                <Image class="h-4 w-4" />
              </Button>
            </div>

            <div
              ref="editorRef"
              contenteditable="true"
              class="article-body min-h-[400px] max-h-[600px] overflow-y-auto p-4 focus:outline-none"
              style="font-family: inherit; line-height: 1.6;"
            ></div>
          </div>
        </div>

        <div class="space-y-4">
          <div class="rounded-lg border bg-white shadow-sm p-4 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">Article Settings</h3>
            <div class="space-y-3">
              <div>
                <Label>Title</Label>
                <Input v-model="title" placeholder="Article title" />
              </div>
              <div>
                <Label>Slug</Label>
                <Input v-model="slug" placeholder="article-slug" />
              </div>
              <div>
                <Label>Category</Label>
                <Select v-model="categoryId">
                  <SelectTrigger>
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Status</Label>
                <Select v-model="status" :disabled="!isEditing">
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="draft">Draft</SelectItem>
                    <SelectItem value="in_review">In Review</SelectItem>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="published">Published</SelectItem>
                    <SelectItem value="archived">Archived</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Audience</Label>
                <Select v-model="audience">
                  <SelectTrigger>
                    <SelectValue placeholder="Select audience" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    <SelectItem value="agent">Agent</SelectItem>
                    <SelectItem value="manager">Manager</SelectItem>
                    <SelectItem value="admin">Admin</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Feature Refs (comma-separated)</Label>
                <Input v-model="featureRefs" placeholder="e.g. tickets, sla" />
                <p class="mt-1 text-xs text-gray-500">Optional contextual help tags.</p>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <Button @click="submit(false)" :disabled="saving" class="w-full">
              <Save class="h-4 w-4 mr-2" />
              {{ saving ? 'Saving...' : 'Save Draft' }}
            </Button>
            <Button @click="submit(true)" :disabled="saving" variant="default" class="w-full">
              <Send class="h-4 w-4 mr-2" />
              {{ saving ? 'Publishing...' : 'Publish' }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.article-body :deep(h2) {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
  padding-bottom: 0.25rem;
  border-bottom: 2px solid #e5e7eb;
}
.article-body :deep(h3) {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin-top: 1.25rem;
  margin-bottom: 0.4rem;
}
.article-body :deep(p) {
  color: #374151;
  line-height: 1.7;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}
.article-body :deep(ul),
.article-body :deep(ol) {
  margin: 0.5rem 0 1rem 1.5rem;
  padding-left: 1rem;
}
.article-body :deep(ul) {
  list-style-type: disc;
}
.article-body :deep(ol) {
  list-style-type: decimal;
}
.article-body :deep(li) {
  color: #374151;
  line-height: 1.65;
  margin-bottom: 0.35rem;
  font-size: 0.95rem;
}
.article-body :deep(li::marker) {
  color: #4b5563;
  font-weight: 600;
}
.article-body :deep(strong) {
  font-weight: 600;
  color: #111827;
}
.article-body :deep(code) {
  background: #f3f4f6;
  padding: 0.15rem 0.35rem;
  border-radius: 0.25rem;
  font-size: 0.9em;
  color: #dc2626;
}
.article-body :deep(a) {
  color: #2563eb;
  text-decoration: underline;
}
.article-body :deep(hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 1.5rem 0;
}
</style>
