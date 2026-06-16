<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Plus, Mail, Eye, Edit, Trash2 } from 'lucide-vue-next'

interface Template {
  id: string
  name: string
  subject: string
  body: string
  variables: string[]
  is_active: boolean
  creator_name?: string
  created_at: string
}

const props = defineProps<{ templates: Template[] }>()
const templates = ref(props.templates)
const showCreateDialog = ref(false)
const showPreviewDialog = ref(false)
const previewTemplate = ref<Template | null>(null)
const editingTemplate = ref<Template | null>(null)

const newTemplate = ref({
  name: '',
  subject: '',
  body: '',
  variables: '["contact_name", "contact_email", "company_name"]',
  is_active: true,
})

const openCreate = () => {
  editingTemplate.value = null
  newTemplate.value = {
    name: '',
    subject: '',
    body: '',
    variables: '["contact_name", "contact_email", "company_name"]',
    is_active: true,
  }
  showCreateDialog.value = true
}

const openEdit = (template: Template) => {
  editingTemplate.value = template
  newTemplate.value = {
    name: template.name,
    subject: template.subject,
    body: template.body,
    variables: JSON.stringify(template.variables),
    is_active: template.is_active,
  }
  showCreateDialog.value = true
}

const openPreview = (template: Template) => {
  previewTemplate.value = template
  showPreviewDialog.value = true
}

const submitTemplate = () => {
  const payload = {
    ...newTemplate.value,
    variables: JSON.parse(newTemplate.value.variables as unknown as string),
  }

  if (editingTemplate.value) {
    router.put('/admin/email-templates/' + editingTemplate.value.id, payload, {
      onSuccess: () => {
        showCreateDialog.value = false
      },
    })
  } else {
    router.post('/admin/email-templates', payload, {
      onSuccess: () => {
        showCreateDialog.value = false
        newTemplate.value = {
          name: '',
          subject: '',
          body: '',
          variables: '["contact_name", "contact_email", "company_name"]',
          is_active: true,
        }
      },
    })
  }
}

const deleteTemplate = (id: string) => {
  if (confirm('Are you sure you want to delete this template?')) {
    router.delete('/admin/email-templates/' + id)
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Welcome Email Templates" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Welcome Email Templates</h1>
          <p class="text-gray-500">Manage welcome email templates for onboarding sequences.</p>
        </div>
        <Dialog v-model:open="showCreateDialog">
          <DialogTrigger as-child><Button @click="openCreate"><Plus class="h-4 w-4 mr-2" />New Template</Button></DialogTrigger>
          <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader><DialogTitle>{{ editingTemplate ? 'Edit' : 'Create' }} Welcome Template</DialogTitle></DialogHeader>
            <form @submit.prevent="submitTemplate" class="space-y-4 py-4">
              <div class="space-y-2"><Label>Template Name *</Label><Input v-model="newTemplate.name" placeholder="e.g. Customer Welcome" required /></div>
              <div class="space-y-2"><Label>Subject Line *</Label><Input v-model="newTemplate.subject" placeholder="Welcome to {{company_name}}!" required /></div>
              <div class="space-y-2"><Label>Email Body (HTML supported)</Label><Textarea v-model="newTemplate.body" placeholder="<p>Hi {{contact_name}},</p><p>Welcome aboard!</p>" rows="8" /></div>
              <div class="space-y-2"><Label>Available Variables (JSON array)</Label><Input v-model="newTemplate.variables" placeholder='["contact_name", "company_name"]' /></div>
              <label class="flex items-center gap-2 text-sm"><Checkbox v-model:checked="newTemplate.is_active" /> Active</label>
              <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="showCreateDialog = false">Cancel</Button>
                <Button type="submit">{{ editingTemplate ? 'Update' : 'Create' }} Template</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Preview Dialog -->
      <Dialog v-model:open="showPreviewDialog">
        <DialogContent class="sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Template Preview: {{ previewTemplate?.name }}</DialogTitle>
          </DialogHeader>
          <div class="space-y-4">
            <div><Label>Subject:</Label><p class="text-sm font-medium">{{ previewTemplate?.subject }}</p></div>
            <div><Label>Body:</Label>
              <div class="border rounded p-4 bg-gray-50 max-h-96 overflow-y-auto" v-html="previewTemplate?.body"></div>
            </div>
            <div><Label>Variables:</Label>
              <div class="flex flex-wrap gap-2">
                <Badge v-for="v in previewTemplate?.variables" :key="v">{{ v }}</Badge>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <Card>
        <CardHeader><CardTitle>Templates</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Name</TableHead>
                <TableHead class="p-4">Subject</TableHead>
                <TableHead class="p-4">Status</TableHead>
                <TableHead class="p-4">Created</TableHead>
                <TableHead class="p-4 text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="template in templates" :key="template.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-4 font-medium">{{ template.name }}</TableCell>
                <TableCell class="p-4 text-sm text-gray-600 max-w-xs truncate">{{ template.subject }}</TableCell>
                <TableCell class="p-4"><Badge :variant="template.is_active ? 'default' : 'secondary'">{{ template.is_active ? 'Active' : 'Inactive' }}</Badge></TableCell>
                <TableCell class="p-4 text-sm text-gray-500">{{ new Date(template.created_at).toLocaleDateString() }}</TableCell>
                <TableCell class="p-4">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="ghost" size="sm" @click="openPreview(template)"><Eye class="h-4 w-4" /></Button>
                    <Button variant="ghost" size="sm" @click="openEdit(template)"><Edit class="h-4 w-4" /></Button>
                    <Button variant="ghost" size="sm" @click="deleteTemplate(template.id)"><Trash2 class="h-4 w-4 text-rose-500" /></Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="!templates.length">
                <TableCell colspan="5" class="p-8 text-center text-gray-500 italic">No templates configured. Create your first welcome email template.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
