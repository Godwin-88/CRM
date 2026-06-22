<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Search, Plus, Trash2, FileText, BookOpen, PenLine, Archive } from 'lucide-vue-next'

const props = defineProps<{
  articles: { data: any[]; links?: any }
  categories: any[]
  isAdmin?: boolean
}>()

const isAdmin = computed(() => props.isAdmin === true)
const search = ref('')
const statusFilter = ref('all')

const visibleArticles = computed(() => {
  const list = Array.isArray(props.articles) ? props.articles : props.articles?.data || []
  return list.filter(Boolean)
})

const visibleCategories = computed(() => {
  const list = Array.isArray(props.categories) ? props.categories : props.categories?.data || []
  return list.filter(Boolean)
})

const filteredAdminArticles = computed(() => {
  const term = search.value.toLowerCase()
  return visibleArticles.value.filter((article) => {
    const matchesSearch = !term || article.title.toLowerCase().includes(term) || (article.body || '').toLowerCase().includes(term)
    const matchesStatus = statusFilter.value === 'all' || article.status === statusFilter.value
    return matchesSearch && matchesStatus
  })
})

const stats = computed(() => ({
  total: visibleArticles.value.length,
  published: visibleArticles.value.filter((a) => a.status === 'published').length,
  drafts: visibleArticles.value.filter((a) => a.status === 'draft').length,
  archived: visibleArticles.value.filter((a) => a.status === 'archived').length,
}))

const deleteArticle = async (article: any) => {
  if (!confirm(`Delete "${article.title}"? This can be undone via version history.`)) return

  const response = await fetch(`/api/v1/knowledge-base/${article.id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content,
      'Accept': 'application/json',
    },
  })

  if (response.ok) {
    router.reload()
  } else {
    const payload = await response.json().catch(() => ({}))
    alert(payload.message || 'Unable to delete article.')
  }
}

const statusBadgeVariant = (status: string) => {
  switch (status) {
    case 'published':
      return 'success'
    case 'draft':
      return 'outline'
    case 'in_review':
      return 'secondary'
    case 'approved':
      return 'default'
    case 'archived':
      return 'destructive'
    default:
      return 'outline'
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Knowledge Base" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div v-if="isAdmin">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Knowledge Base Management</h1>
            <p class="text-gray-500">Create and manage articles. Published articles appear in the Documentation Center.</p>
          </div>
          <Button @click="router.visit('/support/knowledge-base/create')">
            <Plus class="h-4 w-4 mr-2" />
            New Article
          </Button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent class="pt-6">
              <div class="flex items-center gap-3">
                <FileText class="h-5 w-5 text-gray-500" />
                <div>
                  <p class="text-xs text-gray-500">Total Articles</p>
                  <p class="text-2xl font-bold">{{ stats.total }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <div class="flex items-center gap-3">
                <BookOpen class="h-5 w-5 text-green-500" />
                <div>
                  <p class="text-xs text-gray-500">Published</p>
                  <p class="text-2xl font-bold">{{ stats.published }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <div class="flex items-center gap-3">
                <PenLine class="h-5 w-5 text-yellow-500" />
                <div>
                  <p class="text-xs text-gray-500">Drafts</p>
                  <p class="text-2xl font-bold">{{ stats.drafts }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <div class="flex items-center gap-3">
                <Archive class="h-5 w-5 text-red-500" />
                <div>
                  <p class="text-xs text-gray-500">Archived</p>
                  <p class="text-2xl font-bold">{{ stats.archived }}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Articles</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
              <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <Input v-model="search" placeholder="Search articles..." class="pl-9" />
              </div>
              <div class="flex gap-2">
                <Select v-model="statusFilter">
                  <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="All statuses" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="status in ['all', 'draft', 'in_review', 'approved', 'published', 'archived']" :key="status" :value="status">
                      {{ status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ') }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Audience</TableHead>
                    <TableHead>Author</TableHead>
                    <TableHead class="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="article in filteredAdminArticles" :key="article.id">
                    <TableCell class="font-medium">{{ article.title }}</TableCell>
                    <TableCell>{{ article.category?.name || '-' }}</TableCell>
                    <TableCell>
                      <Badge :variant="statusBadgeVariant(article.status)">{{ article.status }}</Badge>
                    </TableCell>
                    <TableCell class="capitalize">{{ article.audience }}</TableCell>
                    <TableCell>{{ article.author?.name || '-' }}</TableCell>
                    <TableCell class="text-right">
                      <div class="flex justify-end gap-2">
                        <Button size="sm" variant="outline" @click="router.visit(`/support/knowledge-base/${article.id}/edit`)">Edit</Button>
                        <Button size="sm" variant="ghost" @click="deleteArticle(article)">
                          <Trash2 class="h-4 w-4 text-red-500" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                  <TableRow v-if="filteredAdminArticles.length === 0">
                    <TableCell colspan="6" class="text-center py-8 text-sm text-gray-500">
                      No articles found.
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

      <div v-else>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Knowledge Base</h1>
          <p class="text-gray-500">Find answers to common questions.</p>
        </div>

        <form @submit.prevent="" class="flex gap-2 max-w-md">
          <Input v-model="search" placeholder="Search articles..." class="flex-1" />
          <Button type="button">Search</Button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="md:col-span-1">
            <Card>
              <CardHeader>
                <CardTitle>Categories</CardTitle>
              </CardHeader>
              <CardContent>
                <ul class="space-y-2">
                  <li v-for="category in visibleCategories" :key="category.id">
                    <a href="#" class="text-blue-600 hover:underline">{{ category.name }}</a>
                  </li>
                </ul>
              </CardContent>
            </Card>
          </div>

          <div class="md:col-span-3">
            <div class="space-y-4">
              <Card v-for="article in visibleArticles" :key="article.id">
                <CardHeader>
                  <CardTitle>
                    <a :href="`/support/knowledge-base/${article.id}`" class="hover:underline">
                      {{ article.title }}
                    </a>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p class="text-sm text-gray-600 line-clamp-3" v-html="article.body?.substring(0, 200) + '...'"></p>
                  <p class="text-xs text-gray-500 mt-2">
                    {{ article.view_count }} views | Helpfulness: {{ article.helpful_votes }}/{{ article.helpful_votes + article.not_helpful_votes }}
                  </p>
                </CardContent>
              </Card>
            </div>

            <div v-if="visibleArticles.length === 0" class="text-center py-8 text-gray-500">
              No articles found.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
