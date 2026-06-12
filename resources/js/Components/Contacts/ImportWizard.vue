<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';

const step = ref(1); // 1=upload, 2=field mapping, 3=validation preview
const file = ref<File | null>(null);
const headers = ref<string[]>([]);
const fieldMapping = ref<Record<string, string>>({});
const isProcessing = ref(false);
const importResult = ref<{ created: number; skipped: number; failed: number; errors: string[] } | null>(null);

const contactFields = [
  { value: 'first_name', label: 'First Name *', required: true },
  { value: 'last_name', label: 'Last Name *', required: true },
  { value: 'email', label: 'Email *', required: true },
  { value: 'type', label: 'Type *', required: true },
  { value: 'phone', label: 'Phone' },
  { value: 'status', label: 'Status' },
  { value: 'source', label: 'Source' },
  { value: 'owner_id', label: 'Owner ID' },
  { value: 'loyalty_tier', label: 'Loyalty Tier' },
  { value: 'preferred_channel', label: 'Preferred Channel' },
  { value: 'clv_score', label: 'CLV Score' },
  { value: '', label: '— Skip Column —' },
];

const requiredMapped = computed(() => {
  const required = ['first_name', 'last_name', 'email', 'type'];
  const mapped = Object.values(fieldMapping.value);
  return required.every(f => mapped.includes(f));
});

const handleFile = (e: Event) => {
  const input = e.target as HTMLInputElement;
  if (input.files?.length) {
    file.value = input.files[0];
    // In a real app, parse CSV to extract headers
    // For now simulate with a reader
    const reader = new FileReader();
    reader.onload = (evt) => {
      const text = evt.target?.result as string;
      const lines = text.split('\n');
      if (lines.length > 0) {
        headers.value = lines[0].split(',').map(h => h.trim());
        // Auto-map matching columns
        const mapping: Record<string, string> = {};
        headers.value.forEach(h => {
          const match = contactFields.find(f => f.value && h.toLowerCase().includes(f.value));
          if (match) mapping[h] = match.value;
        });
        fieldMapping.value = mapping;
        step.value = 2;
      }
    };
    reader.readAsText(input.files[0]);
  }
};

const submitImport = async () => {
  if (!file.value || !requiredMapped.value) return;
  isProcessing.value = true;
  
  const formData = new FormData();
  formData.append('file', file.value);
  formData.append('field_mapping', JSON.stringify(fieldMapping.value));

  try {
    const response = await fetch('/api/v1/contacts/import', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content },
      body: formData,
    });
    const data = await response.json();
    importResult.value = data;
    step.value = 3;
  } catch (e: any) {
    importResult.value = { created: 0, skipped: 0, failed: 1, errors: [e.message || 'Import failed'] };
    step.value = 3;
  } finally {
    isProcessing.value = false;
  }
};

const reset = () => {
  step.value = 1;
  file.value = null;
  headers.value = [];
  fieldMapping.value = {};
  importResult.value = null;
};
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Bulk Import Contacts</CardTitle>
    </CardHeader>
    <CardContent>
      <!-- Step 1: Upload -->
      <div v-if="step === 1" class="space-y-4">
        <p class="text-sm text-gray-500">Upload a CSV or Excel file (.csv, .xlsx) up to 10MB.</p>
        <Input type="file" accept=".csv,.xlsx,.xls" @change="handleFile" />
      </div>

      <!-- Step 2: Field Mapping -->
      <div v-if="step === 2" class="space-y-4">
        <div class="flex items-center gap-2 mb-2">
          <Badge variant="secondary">{{ headers.length }} columns detected</Badge>
          <Badge :variant="requiredMapped ? 'default' : 'destructive'">
            {{ requiredMapped ? 'Required fields mapped' : 'Missing required fields' }}
          </Badge>
        </div>
        <div v-for="header in headers" :key="header" class="flex items-center gap-2">
          <Label class="w-32 text-sm font-medium truncate">{{ header }}</Label>
          <Select v-model="fieldMapping[header]">
            <SelectTrigger class="flex-1"><SelectValue :placeholder="'Map to...'" /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="cf in contactFields" :key="cf.value" :value="cf.value">
                {{ cf.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="flex gap-2">
          <Button @click="submitImport" :disabled="!requiredMapped || isProcessing">
            {{ isProcessing ? 'Importing...' : 'Start Import' }}
          </Button>
          <Button variant="outline" @click="reset">Cancel</Button>
        </div>
      </div>

      <!-- Step 3: Results -->
      <div v-if="step === 3" class="space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-green-50 p-3 rounded text-center">
            <p class="text-2xl font-bold text-green-600">{{ importResult?.created || 0 }}</p>
            <p class="text-xs text-green-700">Created</p>
          </div>
          <div class="bg-amber-50 p-3 rounded text-center">
            <p class="text-2xl font-bold text-amber-600">{{ importResult?.skipped || 0 }}</p>
            <p class="text-xs text-amber-700">Skipped</p>
          </div>
          <div class="bg-red-50 p-3 rounded text-center">
            <p class="text-2xl font-bold text-red-600">{{ importResult?.failed || 0 }}</p>
            <p class="text-xs text-red-700">Failed</p>
          </div>
        </div>
        <div v-if="importResult?.errors?.length" class="bg-red-50 p-3 rounded max-h-32 overflow-y-auto">
          <p class="text-sm font-medium text-red-700 mb-1">Errors:</p>
          <p v-for="(err, i) in importResult.errors.slice(0, 10)" :key="i" class="text-xs text-red-600">{{ err }}</p>
        </div>
        <Button variant="outline" @click="reset">Import Another File</Button>
      </div>
    </CardContent>
  </Card>
</template>