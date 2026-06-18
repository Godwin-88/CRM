<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Bell } from 'lucide-vue-next'

interface Notification {
  id: string
  type: string
  data: {
    type: string
    [key: string]: any
  }
  read_at: string | null
  created_at: string
}

defineProps<{
  notifications: {
    data: Notification[]
    links: {
      next: string | null
      prev: string | null
    }
    meta: {
      current_page: number
      last_page: number
      total: number
    }
  }
}>()

const markAsRead = (id: string) => {
  router.post(route('notifications.read', id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      window.location.reload()
    }
  })
}

const markAllRead = () => {
  router.post(route('notifications.readAll'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      window.location.reload()
    }
  })
}

const getIcon = (type: string) => {
  switch (type) {
    case 'comment_mentioned':
      return '💬'
    case 'ticket_assigned':
      return '🎫'
    case 'deal_stage_moved':
      return '📈'
    case 'contract_signed':
      return '📄'
    default:
      return '🔔'
  }
}
</script>

<template>
  <Head title="Notifications" />

  <AppLayout>
    <div class="container mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Notifications</h1>
        <Button v-if="notifications.meta.total > 0" @click="markAllRead" variant="outline">
          Mark all as read
        </Button>
      </div>

      <div v-if="notifications.data.length === 0" class="text-center py-12">
        <Bell class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-semibold">No notifications</h3>
        <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
      </div>

      <div class="space-y-4">
        <Card v-for="notification in notifications.data" :key="notification.id">
          <CardContent class="flex items-start justify-between p-4">
            <div class="flex items-start space-x-3">
              <div class="text-2xl">{{ getIcon(notification.data.type) }}</div>
              <div>
                <p class="text-sm font-medium">{{ notification.data.type.replace('_', ' ') }}</p>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                  {{ notification.data.comment_excerpt || notification.data.title || 'New notification' }}
                </p>
                <p class="text-xs text-gray-400 mt-2">{{ new Date(notification.created_at).toLocaleString() }}</p>
              </div>
            </div>
            <Button
              v-if="!notification.read_at"
              size="sm"
              variant="ghost"
              @click="markAsRead(notification.id)"
            >
              Mark read
            </Button>
            <Badge v-else variant="secondary">Read</Badge>
          </CardContent>
        </Card>
      </div>

      <div v-if="notifications.meta.current_page < notifications.meta.last_page" class="mt-6 text-center">
        <Button @click="notifications.links.next && $inertia.visit(notifications.links.next)" variant="outline">
          Load more
        </Button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
</style>