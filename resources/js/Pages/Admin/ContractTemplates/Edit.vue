<script setup lang="ts">
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowDown, ArrowUp, History, Plus, Trash2 } from 'lucide-vue-next';

interface ClauseOption {
  id: string;
  name: string;
  category: string;
  body?: string;
}

interface TemplateClause {
  id: string;
  name?: string;
  category?: string;
  body?: string;
  is_mandatory?: boolean;
  is_optional?: boolean;
  sort_order?: number;
  pivot?: {
    sort_order?: number;
    is_mandatory?: boolean;
    is_optional?: boolean;
    is_included_by_default?: boolean;
  };
}

const props = defineProps<{
  template: {
    id: string;
    name: string;
    description: string;
    type: string;
    is_active: boolean;
    clauses?: TemplateClause[];
    versions?: { id: string; version_number: number; created_at: string; change_summary?: string[] }[];
  };
  clauses: ClauseOption[];
  contractTypes: string[];
}>();

const clauseMap = computed(() => {
  const map = new Map<string, ClauseOption>();
  props.clauses.forEach((clause) => map.set(clause.id, clause));
  return map;
});

const form = useForm({
  name: props.template?.name || '',
  description: props.template?.description || '',
  type: props.template?.type || '',
  is_active: props.template?.is_active ?? true,
  clauses: (props.template?.clauses || []).map((clause, index) => {
    const source = clauseMap.value.get(clause.id);
    return {
      id: clause.id,
      name: clause.name || source?.name || 'Clause',
      category: clause.category || source?.category || 'General',
      body: clause.body || source?.body || '',
      is_mandatory: clause.is_mandatory ?? clause.pivot?.is_mandatory ?? true,
      is_optional: clause.is_optional ?? clause.pivot?.is_optional ?? !(clause.pivot?.is_included_by_default ?? true),
      sort_order: clause.sort_order ?? clause.pivot?.sort_order ?? index,
    };
  }).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0)).map((clause, index) => ({ ...clause, sort_order: index })),
});

const selectedClauseIds = computed(() => new Set(form.clauses.map((clause) => clause.id)));
const availableClauses = computed(() => props.clauses.filter((clause) => !selectedClauseIds.value.has(clause.id)));

const submit = () => {
  form.put(`/admin/contract-templates/${props.template.id}`, {
    onSuccess: () => {
      router.visit('/admin/contract-templates');
    },
  });
};

const addClause = (clause: ClauseOption) => {
  form.clauses.push({
    id: clause.id,
    name: clause.name,
    category: clause.category,
    body: clause.body,
    is_mandatory: true,
    is_optional: false,
    sort_order: form.clauses.length,
  });
};

const removeClause = (index: number) => {
  form.clauses.splice(index, 1);
  form.clauses.forEach((clause, clauseIndex) => {
    clause.sort_order = clauseIndex;
  });
};

const moveClause = (index: number, direction: -1 | 1) => {
  const target = index + direction;
  if (target < 0 || target >= form.clauses.length) return;

  const [item] = form.clauses.splice(index, 1);
  form.clauses.splice(target, 0, item);
  form.clauses.forEach((clause, clauseIndex) => {
    clause.sort_order = clauseIndex;
  });
};

const toggleMandatory = (index: number) => {
  const clause = form.clauses[index];
  clause.is_mandatory = !clause.is_mandatory;
  clause.is_optional = !clause.is_mandatory;
};
</script>

<template>
  <AppLayout>
    <Head title="Edit Contract Template" />
    <div class="max-w-6xl mx-auto">
      <div class="mb-4 flex items-center justify-between">
        <Link href="/admin/contract-templates" class="text-blue-600 hover:underline text-sm">← Back to Templates</Link>
        <Button type="submit" form="template-form" :disabled="form.processing">Save Version</Button>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <form id="template-form" @submit.prevent="submit" class="space-y-6">
          <Card>
            <CardHeader><CardTitle>Template details</CardTitle></CardHeader>
            <CardContent class="space-y-4">
              <div>
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" />
                <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
              </div>

              <div>
                <Label for="description">Description</Label>
                <Textarea id="description" v-model="form.description" rows="3" />
                <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">{{ form.errors.description }}</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <Label for="type">Type</Label>
                  <Select v-model="form.type">
                    <SelectTrigger>
                      <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="type in contractTypes" :key="type" :value="type">{{ type }}</SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.type" class="text-xs text-red-600 mt-1">{{ form.errors.type }}</p>
                </div>

                <div class="flex items-end">
                  <label class="flex items-center gap-2 rounded-md border p-3 text-sm">
                    <Checkbox v-model:checked="form.is_active" />
                    Active in generation flow
                  </label>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Clause order</CardTitle>
              <p class="text-sm text-gray-500">Reorder clauses, mark mandatory clauses, and expose optional clauses at generation time.</p>
            </CardHeader>
            <CardContent>
              <div v-if="form.clauses.length" class="space-y-3">
                <div v-for="(clause, index) in form.clauses" :key="clause.id" class="rounded-lg border p-3">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="font-medium">{{ clause.name }}</div>
                      <div class="text-xs text-gray-500">Position {{ index + 1 }} · {{ clause.category }}</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                      <Button type="button" size="sm" variant="ghost" :disabled="index === 0" @click="moveClause(index, -1)">
                        <ArrowUp class="h-4 w-4" />
                      </Button>
                      <Button type="button" size="sm" variant="ghost" :disabled="index === form.clauses.length - 1" @click="moveClause(index, 1)">
                        <ArrowDown class="h-4 w-4" />
                      </Button>
                      <Button type="button" size="sm" variant="ghost" @click="removeClause(index)">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <p v-if="clause.body" class="mt-3 line-clamp-3 text-sm text-gray-600">{{ clause.body }}</p>
                  <div class="mt-3 flex flex-wrap gap-3">
                    <label class="flex items-center gap-2 text-sm">
                      <Checkbox :checked="clause.is_mandatory" @update:checked="(checked: boolean) => { clause.is_mandatory = checked; clause.is_optional = !checked; }" />
                      Mandatory
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                      <Checkbox :checked="clause.is_optional" @update:checked="(checked: boolean) => { clause.is_optional = checked; clause.is_mandatory = !checked; }" />
                      Optional
                    </label>
                  </div>
                </div>
              </div>
              <p v-else class="rounded-lg border border-dashed p-6 text-center text-sm text-gray-500">No clauses selected yet.</p>

              <p v-if="form.errors.clauses" class="mt-3 text-xs text-red-600">{{ form.errors.clauses }}</p>
            </CardContent>
          </Card>
        </form>

        <aside class="space-y-4">
          <Card>
            <CardHeader><CardTitle>Clause library</CardTitle></CardHeader>
            <CardContent>
              <div v-if="availableClauses.length" class="space-y-3">
                <div v-for="clause in availableClauses" :key="clause.id" class="rounded-lg border p-3">
                  <div class="mb-2 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="font-medium">{{ clause.name }}</div>
                      <div class="text-xs text-gray-500">{{ clause.category }}</div>
                    </div>
                    <Button type="button" size="sm" variant="ghost" @click="addClause(clause)">
                      <Plus class="h-4 w-4" />
                    </Button>
                  </div>
                  <p class="line-clamp-3 text-sm text-gray-600">{{ clause.body }}</p>
                </div>
              </div>
              <p v-else class="rounded-lg border border-dashed p-6 text-center text-sm text-gray-500">All available clauses are already in the template.</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle class="flex items-center gap-2"><History class="h-4 w-4" /> Version history</CardTitle></CardHeader>
            <CardContent>
              <div v-if="template.versions?.length" class="space-y-3">
                <div v-for="version in template.versions" :key="version.id" class="rounded-lg border p-3">
                  <div class="font-medium">v{{ version.version_number }}</div>
                  <div class="text-xs text-gray-500">{{ new Date(version.created_at).toLocaleString() }}</div>
                  <div v-if="version.change_summary?.length" class="mt-2 text-sm text-gray-600">{{ version.change_summary.join(', ') }}</div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No versions recorded.</p>
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>
