<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Plus, ShieldCheck, FileText } from 'lucide-vue-next'

interface SlaDefinition {
  id: string
  name: string
  description: string
  support_category?: string
  priority: string
  first_response_time_business_hours: number
  resolution_time_business_hours: number
  is_default: boolean
  businessHours?: any[]
}

interface SlaInstance {
  id: string
  ticket_id: string
  ticket?: { subject: string; priority: string }
  sla_definition_id: string
  sla_definition?: { name: string }
  first_response_deadline: string
  resolution_deadline: string
  first_response_breached: boolean
  resolution_breached: boolean
}

const props = defineProps<{
  slaDefinitions: SlaDefinition[]
  businessHours: any[]
  instances: SlaInstance[]
}>()
</script>

<template>
  <AppLayout>
    <Head title="Loyalty & CX – Service" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Loyalty & CX – Service</h1>
        <p class="text-gray-500 mt-1">Service level agreements and self-service portal configuration.</p>
      </div>

      <Tabs default-value="sla-definitions" class="space-y-6">
        <TabsList class="w-full justify-start">
          <TabsTrigger value="sla-definitions">SLA Definitions</TabsTrigger>
          <TabsTrigger value="sla-instances">SLA Instances</TabsTrigger>
        </TabsList>

        <TabsContent value="sla-definitions" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold flex items-center gap-2"><ShieldCheck class="h-5 w-5 text-blue-500" /> SLA Policy Definitions</h2>
              <p class="text-sm text-gray-500">Configure response and resolution targets per support category and loyalty tier.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card>
              <CardContent class="pt-6">
                <p class="text-sm text-gray-500">Active SLAs</p>
                <p class="text-3xl font-bold">{{ slaDefinitions.length }}</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <p class="text-sm text-gray-500">Avg First Response</p>
                <p class="text-3xl font-bold">
                  {{ slaDefinitions.length ? Math.round(slaDefinitions.reduce((a: any, b: any) => a + b.first_response_time_business_hours, 0) / slaDefinitions.length) : 0 }} hrs
                </p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <p class="text-sm text-gray-500">Avg Resolution</p>
                <p class="text-3xl font-bold">
                  {{ slaDefinitions.length ? Math.round(slaDefinitions.reduce((a: any, b: any) => a + b.resolution_time_business_hours, 0) / slaDefinitions.length) : 0 }} hrs
                </p>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader><CardTitle>SLA Policies</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHead>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Priority</TableHead>
                    <TableHead>First Response</TableHead>
                    <TableHead>Resolution</TableHead>
                    <TableHead>Default</TableHead>
                  </TableRow>
                </TableHead>
                <TableBody>
                  <TableRow v-for="sla in slaDefinitions" :key="sla.id">
                    <TableCell class="font-medium">{{ sla.name }}</TableCell>
                    <TableCell>{{ sla.support_category || '-' }}</TableCell>
                    <TableCell><Badge :variant="sla.priority === 'critical' ? 'destructive' : 'secondary'">{{ sla.priority }}</Badge></TableCell>
                    <TableCell>{{ sla.first_response_time_business_hours }} hrs</TableCell>
                    <TableCell>{{ sla.resolution_time_business_hours }} hrs</TableCell>
                    <TableCell><Badge :variant="sla.is_default ? 'default' : 'outline'">{{ sla.is_default ? 'Yes' : 'No' }}</Badge></TableCell>
                  </TableRow>
                  <TableRow v-if="!slaDefinitions.length"><TableCell colspan="6" class="p-8 text-center text-gray-500 italic">No SLA policies configured.</TableCell></TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="sla-instances" class="space-y-6">
          <div>
            <h2 class="text-xl font-semibold flex items-center gap-2"><FileText class="h-5 w-5 text-purple-500" /> SLA Monitoring</h2>
            <p class="text-sm text-gray-500">Track active and breached service level commitments on individual tickets.</p>
          </div>

          <Card>
            <CardHeader><CardTitle>SLA Instances</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHead>
                  <TableRow>
                    <TableHead>Ticket</TableHead>
                    <TableHead>SLA Policy</TableHead>
                    <TableHead>Response Deadline</TableHead>
                    <TableHead>Resolution Deadline</TableHead>
                    <TableHead>Breach Status</TableHead>
                  </TableRow>
                </TableHead>
                <TableBody>
                  <TableRow v-for="instance in instances" :key="instance.id">
                    <TableCell>
                      <a :href="`/support/tickets/${instance.ticket_id}`" class="text-blue-600 hover:underline">
                        {{ instance.ticket?.subject || instance.ticket_id }}
                      </a>
                    </TableCell>
                    <TableCell>{{ instance.sla_definition?.name }}</TableCell>
                    <TableCell>{{ instance.first_response_deadline }}</TableCell>
                    <TableCell>{{ instance.resolution_deadline }}</TableCell>
                    <TableCell>
                      <div class="flex flex-wrap gap-1">
                        <Badge v-if="instance.first_response_breached" variant="destructive">Response Breached</Badge>
                        <Badge v-if="instance.resolution_breached" variant="destructive">Resolution Breached</Badge>
                        <Badge v-if="!instance.first_response_breached && !instance.resolution_breached" variant="default">On Track</Badge>
                      </div>
                    </TableCell>
                  </TableRow>
                  <TableRow v-if="!instances.length"><TableCell colspan="5" class="p-8 text-center text-gray-500 italic">No SLA instances recorded.</TableCell></TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
