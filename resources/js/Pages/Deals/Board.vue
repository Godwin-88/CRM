<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface Deal {
  id: string;
  title: string;
  value: number;
  expected_close_date: string | null;
  stage: string;
  owner: { id: string; name: string; avatar?: string } | null;
  account_name: string;
}

interface Column {
  id: string;
  name: string;
  probability: number;
  deal_count: number;
  weighted_value: number;
  total_value: number;
  deals: Deal[];
}

const props = defineProps<{
  pipelines: { id: string; name: string; is_default: boolean; is_archived: boolean; stages?: any[] }[];
  boardData: { pipeline: any; columns: Column[] };
}>();

const selectedPipeline = ref(props.boardData?.pipeline?.id || props.pipelines.find(p => p.is_default)?.id);
const columns = ref<Column[]>(props.boardData?.columns || []);
const draggedDeal = ref<Deal | null>(null);
const draggedFromColumn = ref<string | null>(null);

const filters = ref({
  owner: '',
  close_from: '',
  close_to: '',
  value_min: '',
  value_max: '',
});

const loadBoard = () => {
  if (!selectedPipeline.value) return;
  router.get(`/pipelines/${selectedPipeline.value}/board`, {}, { 
    preserveState: true,
    onSuccess: (data: any) => {
      columns.value = data.props.boardData?.columns || [];
    },
  });
};

const onDragStart = (deal: Deal, columnId: string) => {
  draggedDeal.value = deal;
  draggedFromColumn.value = columnId;
};

const onDragOver = (e: DragEvent) => {
  e.preventDefault();
};

const onDrop = async (targetColumn: Column) => {
  if (!draggedDeal.value || !targetColumn) return;
  
  const dealId = draggedDeal.value.id;
  const newStage = targetColumn.name;
  
  draggedDeal.value = null;
  draggedFromColumn.value = null;

  try {
    router.post(`/deals/${dealId}/move-stage`, { stage: newStage }, {
      onSuccess: () => {
        const col = columns.value.find(c => c.id === targetColumn.id);
        if (col && col.deals) {
          const idx = col.deals.findIndex(d => d.id === dealId);
          if (idx > -1) col.deals.splice(idx, 1);
        }
      },
    });
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  // Listen for real-time updates via Echo
  if ((window as any).Echo) {
    (window as any).Echo.private(`pipeline.${selectedPipeline.value}`)
      .listen('DealStageMoved', (e: any) => {
        router.reload();
      });
  }
});

onBeforeUnmount(() => {
  if ((window as any).Echo) {
    (window as any).Echo.leave(`pipeline.${selectedPipeline.value}`);
  }
});
</script>

<template>
  <AppLayout>
    <Head title="Deal Pipeline" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Deal Pipeline</h1>
          <p class="text-gray-500">Manage deals visually with drag-and-drop Kanban board.</p>
        </div>
        <div class="flex items-center gap-3">
          <Select v-model="selectedPipeline" @update:model-value="loadBoard">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="Select pipeline" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="pipeline in pipelines" :key="pipeline.id" :value="pipeline.id">
                {{ pipeline.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Link href="/deals">
            <Button variant="outline">List View</Button>
          </Link>
        </div>
      </div>

      <div class="flex gap-4 mb-4 overflow-x-auto pb-2">
        <div v-for="column in columns" :key="column.id" 
          class="flex-1 min-w-[280px] max-w-[320px] bg-gray-50 rounded-lg"
          @dragover="onDragOver"
          @drop="() => onDrop(column)">
          <div class="p-3 border-b bg-white rounded-t-lg">
            <h3 class="font-semibold">{{ column.name }}</h3>
            <div class="text-xs text-gray-500 mt-1">
              {{ column.deal_count }} deals • {{ column.weighted_value?.toFixed(0) }} weighted
            </div>
          </div>
          <div class="p-2 space-y-2 max-h-[600px] overflow-y-auto">
            <div v-for="deal in column.deals" :key="deal.id" 
              class="bg-white p-3 rounded shadow-sm cursor-move hover:shadow-md transition-shadow"
              draggable="true"
              @dragstart="onDragStart(deal, column.id)">
              <div class="font-medium text-sm">{{ deal.title }}</div>
              <div class="text-xs text-gray-500 mt-1">{{ deal.account_name }}</div>
              <div class="flex justify-between items-center mt-2">
                <span class="text-sm font-semibold">${{ Number(deal.value || 0).toLocaleString() }}</span>
                <span class="text-xs text-gray-500">{{ deal.owner?.name || '—' }}</span>
              </div>
              <div v-if="deal.expected_close_date" class="text-xs text-gray-400 mt-1">
                Closes: {{ new Date(deal.expected_close_date).toLocaleDateString() }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4 mb-6">
        <Input v-model="filters.owner" placeholder="Filter by owner..." class="max-w-xs" />
        <Input v-model="filters.close_from" type="date" placeholder="Close from..." />
        <Input v-model="filters.close_to" type="date" placeholder="Close to..." />
      </div>
    </div>
  </AppLayout>
</template>