<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
  events: any[];
  contactId?: string;
}>();

const typeFilter = ref('all');
const currentPage = ref(1);

const filteredEvents = ref(props.events || []);

const typeIcons: Record<string, string> = {
  interaction: '🔊',
  activity: '📋',
  deal: '💼',
  ticket: '🎫',
  contract: '📄',
};

watch(typeFilter, () => {
  loadMore();
});

const loadMore = () => {
  if (!props.contactId) return;
  
  const params: any = { page: currentPage.value };
  if (typeFilter.value !== 'all') {
    params.types = typeFilter.value;
  }
  
  router.get(`/api/v1/contacts/${props.contactId}/timeline`, params, {
    preserveState: true,
    preserveScroll: true,
    only: ['events'],
    onSuccess: (page: any) => {
      filteredEvents.value = page.props.events?.data || [];
    },
  });
};
</script>

<template>
  <Card>
    <CardHeader class="flex flex-row items-center justify-between">
      <CardTitle>Timeline</CardTitle>
      <div class="flex items-center gap-2">
        <Select v-model="typeFilter">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Filter by type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Events</SelectItem>
            <SelectItem value="interaction">Interactions</SelectItem>
            <SelectItem value="activity">Activities</SelectItem>
            <SelectItem value="deal">Deals</SelectItem>
            <SelectItem value="ticket">Tickets</SelectItem>
            <SelectItem value="contract">Contracts</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </CardHeader>
    <CardContent>
      <ScrollArea class="h-[500px]">
        <div v-if="filteredEvents.length" class="space-y-4">
          <div
            v-for="event in filteredEvents"
            :key="event.id"
            class="border-l-2 pl-4 pb-4 relative"
            :class="{
              'border-blue-400': event.type === 'interaction',
              'border-green-400': event.type === 'activity',
              'border-purple-400': event.type === 'deal',
              'border-amber-400': event.type === 'ticket',
              'border-red-400': event.type === 'contract',
            }"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span>{{ typeIcons[event.type] || '📌' }}</span>
                <h3 class="font-semibold text-sm">{{ event.summary }}</h3>
              </div>
              <Badge variant="outline" class="text-xs">{{ event.type_label }}</Badge>
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ event.date ? new Date(event.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '' }}
            </p>
            <p v-if="event.detail" class="text-sm mt-1">{{ event.detail }}</p>
            <p v-if="event.agent" class="text-xs text-gray-400 mt-1">by {{ event.agent }}</p>
          </div>
        </div>
        <p v-else class="text-gray-400 text-sm text-center py-8">No timeline events found.</p>
      </ScrollArea>
    </CardContent>
  </Card>
</template>