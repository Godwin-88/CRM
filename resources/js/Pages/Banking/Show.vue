<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';

const { relationship, canViewFinancials, canViewDocuments } = defineProps<{
  relationship: any;
  canViewFinancials: boolean;
  canViewDocuments: boolean;
}>();

const noteForm = useForm({
  content: '',
});

const submitNote = () => {
  noteForm.post(`/banking/${relationship.id}/notes`, {
    onSuccess: () => noteForm.reset(),
  });
};
</script>

<template>
  <AppLayout>
    <Head :title="relationship.institution_name" />
    
    <div class="max-w-4xl mx-auto">
      <div class="mb-4">
        <Link href="/banking" class="text-blue-600 hover:underline text-sm">← Back to Banking</Link>
      </div>

      <Card class="mb-6">
        <CardHeader>
          <div class="flex justify-between items-start">
            <div>
              <CardTitle class="text-2xl">{{ relationship.institution_name }}</CardTitle>
              <p class="text-gray-500 capitalize">{{ relationship.relationship_type?.replace('_', ' ') }}</p>
            </div>
            <Badge :variant="relationship.facilities_expiring_soon ? 'destructive' : 'default'">
              {{ relationship.facilities_expiring_soon ? 'Expires Soon' : 'Active' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid grid-cols-2 gap-4">
            <div>
              <dt class="text-sm text-gray-500">Manager</dt>
              <dd>{{ relationship.relationship_manager_name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Email</dt>
              <dd>{{ relationship.relationship_manager_email }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Phone</dt>
              <dd>{{ relationship.relationship_manager_phone }}</dd>
            </div>
            <div v-if="canViewFinancials">
              <dt class="text-sm text-gray-500">Account #</dt>
              <dd>{{ relationship.account_number || relationship.masked_account_number || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Credit Limit</dt>
              <dd>{{ relationship.credit_limit ? '$' + relationship.credit_limit : '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Interest Rate</dt>
              <dd>{{ relationship.interest_rate || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Expires</dt>
              <dd>{{ relationship.facility_expiry_date || '—' }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card v-if="canViewDocuments" class="mb-6">
        <CardHeader><CardTitle>Documents</CardTitle></CardHeader>
        <CardContent>
          <p class="text-gray-500">Documents stored on R2 and accessible via 15-minute signed URLs.</p>
          <div v-if="relationship.media?.length">
            <ul class="mt-2 space-y-1">
              <li v-for="doc in relationship.media" :key="doc.id">
                <a :href="doc.url" target="_blank" class="text-blue-600 hover:underline">{{ doc.name }}</a>
              </li>
            </ul>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Notes</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="submitNote" class="mb-4">
            <div class="space-y-2">
              <Label>Add Note</Label>
              <Textarea v-model="noteForm.content" placeholder="Add a note..." rows="3" />
              <Button type="submit" size="sm">Add Note</Button>
            </div>
          </form>

          <div class="space-y-4">
            <div v-for="note in relationship.notes" :key="note.id" class="border-b pb-2">
              <p class="font-medium">{{ note.user?.name }} <span class="text-xs text-gray-500">{{ note.created_at }}</span></p>
              <p class="mt-1">{{ note.content }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>