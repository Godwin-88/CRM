<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface CalendarEvent {
  id: string
  type: string
  title: string
  date: string
  color: string
  url: string
  editable: boolean
}

const props = defineProps<{
  events: CalendarEvent[]
  teams: { id: string; name: string }[]
}>()

const currentView = ref('month')
const currentDate = ref(new Date())
const selectedTeam = ref<string | null>(null)

const dateRange = () => {
  const start = new Date(currentDate.value)
  const end = new Date(currentDate.value)

  if (currentView.value === 'month') {
    start.setDate(1)
    end.setMonth(end.getMonth() + 1)
    end.setDate(0)
  } else if (currentView.value === 'week') {
    const day = start.getDay()
    start.setDate(start.getDate() - day)
    end.setDate(end.getDate() + (6 - day))
  } else {
    end.setDate(end.getDate())
  }

  return {
    start: start.toISOString().split('T')[0],
    end: end.toISOString().split('T')[0]
  }
}

const fetchEvents = () => {
  router.get(route('calendar.index'), {
    view: currentView.value,
    start: dateRange().start,
    end: dateRange().end,
    team_id: selectedTeam.value
  }, { preserveState: true, replace: true })
}

const prevPeriod = () => {
  const date = new Date(currentDate.value)
  if (currentView.value === 'month') {
    date.setMonth(date.getMonth() - 1)
  } else if (currentView.value === 'week') {
    date.setDate(date.getDate() - 7)
  } else {
    date.setDate(date.getDate() - 1)
  }
  currentDate.value = date
}

const nextPeriod = () => {
  const date = new Date(currentDate.value)
  if (currentView.value === 'month') {
    date.setMonth(date.getMonth() + 1)
  } else if (currentView.value === 'week') {
    date.setDate(date.getDate() + 7)
  } else {
    date.setDate(date.getDate() + 1)
  }
  currentDate.value = date
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <Head title="Calendar" />

  <AppLayout>
    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Shared Calendar</h1>

        <div class="flex items-center space-x-4">
          <select v-model="selectedTeam" @change="fetchEvents" class="border rounded px-3 py-1">
            <option :value="null">My Calendar</option>
            <option v-for="team in teams" :key="team.id" :value="team.id">
              {{ team.name }}
            </option>
          </select>

          <div class="flex items-center space-x-2">
            <Button @click="prevPeriod" variant="ghost" size="sm">
              <ChevronLeft class="h-4 w-4" />
            </Button>
            <span class="text-sm font-medium">
              {{ currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}
            </span>
            <Button @click="nextPeriod" variant="ghost" size="sm">
              <ChevronRight class="h-4 w-4" />
            </Button>
          </div>

          <div class="flex space-x-1">
            <Button
              @click="currentView = 'month'"
              :variant="currentView === 'month' ? 'default' : 'ghost'"
              size="sm"
            >Month</Button>
            <Button
              @click="currentView = 'week'"
              :variant="currentView === 'week' ? 'default' : 'ghost'"
              size="sm"
            >Week</Button>
            <Button
              @click="currentView = 'day'"
              :variant="currentView === 'day' ? 'default' : 'ghost'"
              size="sm"
            >Day</Button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-4">
        <div class="space-y-2">
          <div
            v-for="event in events"
            :key="event.id"
            class="flex items-center justify-between p-3 border-l-4 rounded-r"
            :class="`border-${event.color}-500 bg-${event.color}-50`"
          >
            <div>
              <p class="font-medium">{{ event.title }}</p>
              <p class="text-sm text-gray-500">{{ formatDate(event.date) }}</p>
            </div>
            <Badge :variant="event.type === 'activity' ? 'default' : 'secondary'">
              {{ event.type.replace('_', ' ') }}
            </Badge>
          </div>
        </div>

        <div v-if="events.length === 0" class="text-center py-8 text-gray-500">
          No events scheduled for this period.
        </div>
      </div>
    </div>
  </AppLayout>
</template>