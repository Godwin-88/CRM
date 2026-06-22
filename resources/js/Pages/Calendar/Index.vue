<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps<{
  events: any[]
  teams: { id: string; name: string }[]
}>()

const currentDate = ref(new Date())
const currentView = ref<'month' | 'week' | 'day'>('month')
const selectedTeam = ref<string | null>(null)
const events = ref<any[]>(props.events)

const periodRange = () => {
  const date = currentDate.value
  const year = date.getFullYear()
  const month = date.getMonth()
  const day = date.getDate()

  if (currentView.value === 'day') {
    const start = new Date(year, month, day)
    const end = new Date(year, month, day, 23, 59, 59, 999)
    return { start, end }
  }

  if (currentView.value === 'week') {
    const start = new Date(year, month, day)
    start.setDate(day - start.getDay())
    start.setHours(0, 0, 0, 0)
    const end = new Date(start)
    end.setDate(start.getDate() + 6)
    end.setHours(23, 59, 59, 999)
    return { start, end }
  }

  const start = new Date(year, month, 1)
  const end = new Date(year, month + 1, 0, 23, 59, 59, 999)
  return { start, end }
}

const fetchEvents = async () => {
  const range = periodRange()
  const params = new URLSearchParams({
    start: range.start.toISOString(),
    end: range.end.toISOString(),
    view: currentView.value,
  })

  if (selectedTeam.value) {
    params.set('team_id', selectedTeam.value)
  }

  const response = await fetch(`/api/v1/calendar?${params.toString()}`)
  const payload = await response.json()
  events.value = payload.data || []
}

const prevPeriod = () => {
  const d = new Date(currentDate.value)
  d.setMonth(d.getMonth() - 1)
  currentDate.value = d
}
const nextPeriod = () => {
  const d = new Date(currentDate.value)
  d.setMonth(d.getMonth() + 1)
  currentDate.value = d
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

watch(currentView, fetchEvents)
watch(currentDate, fetchEvents)
</script>

<template>
  <Head title="Calendar" />
  <AppLayout>
    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Shared Calendar</h1>

        <div class="flex items-center space-x-4">
          <Select v-model="selectedTeam" @update:model-value="fetchEvents">
            <SelectTrigger class="w-48">
              <SelectValue placeholder="My Calendar" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">My Calendar</SelectItem>
              <SelectItem v-for="team in teams" :key="team.id" :value="team.id">
                {{ team.name }}
              </SelectItem>
            </SelectContent>
          </Select>

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

      <Card>
        <CardContent class="p-4">
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
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
