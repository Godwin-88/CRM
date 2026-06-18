<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { ref, watch } from 'vue'

const props = defineProps<{
  contact?: {
    id: string
    first_name: string
    last_name: string
    email: string
  }
  categories: Array<{
    id: string
    name: string
    default_priority?: string
    is_agent_only?: boolean
  }>
}>()

const form = useForm({
  subject: '',
  description: '',
  category_id: '',
  form_response: {} as Record<string, any>,
})

const selectedCategory = ref<typeof props.categories[0] | null>(null)
const customFields = ref<Array<{
  id: string
  name: string
  type: string
  required: boolean
  options?: string[]
}>>([])

watch(() => form.category_id, (newCategoryId) => {
  const category = props.categories.find(c => c.id === newCategoryId)
  selectedCategory.value = category || null
  form.form_response = {}
  
  fetch(`/support/categories/${newCategoryId}/form`)
    .then(res => res.json())
    .then(data => {
      if (data.form) {
        customFields.value = data.form.fields || []
      } else {
        customFields.value = []
      }
    })
    .catch(() => {
      customFields.value = []
    })
})

const submit = () => {
  form.post(route('self-service.tickets.store'), {
    onSuccess: () => {
      form.reset()
    }
  })
}

const renderField = (field: typeof customFields.value[0]) => {
  const fieldProps = {
    label: field.name,
    required: field.required,
  }

  switch (field.type) {
    case 'text':
      return { ...fieldProps, type: 'text' }
    case 'textarea':
      return { ...fieldProps, type: 'textarea' }
    case 'number':
      return { ...fieldProps, type: 'number' }
    case 'date':
      return { ...fieldProps, type: 'date' }
    case 'dropdown':
      return { ...fieldProps, type: 'select', options: field.options }
    case 'checkbox':
      return { ...fieldProps, type: 'checkbox' }
    default:
      return { ...fieldProps, type: 'text' }
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Create Support Ticket" />
    
    <div class="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Create Support Ticket</h1>
        <p class="text-gray-500">Submit a new support request.</p>
      </div>

      <Card>
        <CardContent class="pt-6">
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Subject *</label>
              <Input v-model="form.subject" required placeholder="Brief description of the issue" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Category *</label>
              <select v-model="form.category_id" required class="w-full p-2 border rounded">
                <option value="">Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Description *</label>
              <Textarea v-model="form.description" required placeholder="Detailed description..." rows="5" />
            </div>

            <div v-if="customFields.length > 0" class="border-t pt-4">
              <h3 class="text-lg font-medium mb-3">Additional Information</h3>
              <div v-for="field in customFields" :key="field.id" class="mb-4">
                <label class="block text-sm font-medium mb-1">
                  {{ field.name }}
                  <span v-if="field.required" class="text-red-500">*</span>
                </label>
                
                <Input
                  v-if="field.type === 'text' || field.type === 'number'"
                  v-model="form.form_response[field.id]"
                  :type="field.type"
                  :required="field.required"
                />
                
                <Textarea
                  v-else-if="field.type === 'textarea'"
                  v-model="form.form_response[field.id]"
                  :required="field.required"
                  rows="3"
                />
                
                <Input
                  v-else-if="field.type === 'date'"
                  v-model="form.form_response[field.id]"
                  type="date"
                  :required="field.required"
                />
                
                <select
                  v-else-if="field.type === 'dropdown'"
                  v-model="form.form_response[field.id]"
                  :required="field.required"
                  class="w-full p-2 border rounded"
                >
                  <option value="">Select...</option>
                  <option v-for="opt in field.options" :key="opt" :value="opt">
                    {{ opt }}
                  </option>
                </select>
              </div>
            </div>

            <Button type="submit" :disabled="form.processing">
              Submit Ticket
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>