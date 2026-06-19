<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Settings, MessageSquare, AlertCircle, PanelBottom } from 'lucide-vue-next'

const activeTab = ref('interactions')
</script>

<template>
  <AppLayout>
    <Head title="OmniChannel Settings" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
        <p class="text-gray-500">Configure channels, manage integrations, and review unmatched items.</p>
      </div>

      <Tabs v-model:model-value="activeTab" default-value="interactions">
        <TabsList class="grid w-full grid-cols-4">
          <TabsTrigger value="interactions" class="flex items-center gap-2">
            <MessageSquare class="h-4 w-4" />
            Interactions
          </TabsTrigger>
          <TabsTrigger value="channels" class="flex items-center gap-2">
            <Settings class="h-4 w-4" />
            Channels
          </TabsTrigger>
          <TabsTrigger value="unmatched" class="flex items-center gap-2">
            <AlertCircle class="h-4 w-4" />
            Unmatched
          </TabsTrigger>
          <TabsTrigger value="kiosk" class="flex items-center gap-2">
            <PanelBottom class="h-4 w-4" />
            Kiosk
          </TabsTrigger>
        </TabsList>

        <TabsContent value="interactions" class="mt-4">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Channel</TableHead>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Subject</TableHead>
                <TableHead class="p-4">Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="4" class="p-8 text-center text-gray-500">No interactions.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TabsContent>

        <TabsContent value="channels" class="mt-4">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Name</TableHead>
                <TableHead class="p-4">Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="2" class="p-8 text-center text-gray-500">No channels configured.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TabsContent>

        <TabsContent value="unmatched" class="mt-4">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Source</TableHead>
                <TableHead class="p-4">Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="2" class="p-8 text-center text-gray-500">No unmatched items.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TabsContent>

        <TabsContent value="kiosk" class="mt-4">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Name</TableHead>
                <TableHead class="p-4">Location</TableHead>
                <TableHead class="p-4">Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow>
                <TableCell colspan="3" class="p-8 text-center text-gray-500">No kiosk integrations.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>