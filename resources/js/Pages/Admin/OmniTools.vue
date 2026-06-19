<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Mail, MessageSquare, Phone, MapPin, Plus, Send } from 'lucide-vue-next'

const activeTab = ref('email')

// Email state
const showEmailCompose = ref(false)
const emailContactId = ref('')
const emailBody = ref('')

// SMS state
const showSmsCompose = ref(false)
const smsContactId = ref('')
const smsMessage = ref('')

// Call state
const showCallLog = ref(false)
const callContactId = ref('')
const callSubject = ref('')
const callNotes = ref('')
</script>

<template>
  <AppLayout>
    <Head title="OmniChannel Agent Tools" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Agent Tools</h1>
        <p class="text-gray-500">Compose and log customer interactions.</p>
      </div>

      <Tabs v-model:model-value="activeTab" default-value="email">
        <TabsList class="grid w-full grid-cols-4">
          <TabsTrigger value="email" class="flex items-center gap-2">
            <Mail class="h-4 w-4" />
            Email
          </TabsTrigger>
          <TabsTrigger value="sms" class="flex items-center gap-2">
            <MessageSquare class="h-4 w-4" />
            SMS
          </TabsTrigger>
          <TabsTrigger value="call" class="flex items-center gap-2">
            <Phone class="h-4 w-4" />
            Call
          </TabsTrigger>
          <TabsTrigger value="field" class="flex items-center gap-2">
            <MapPin class="h-4 w-4" />
            Field
          </TabsTrigger>
        </TabsList>
        
        <TabsContent value="email" class="mt-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Email</h2>
            <Dialog v-model:open="showEmailCompose">
              <DialogTrigger as-child><Button size="sm"><Send class="h-4 w-4 mr-2" />Compose</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Compose Email</DialogTitle></DialogHeader>
                <div class="space-y-4">
                  <div class="space-y-2">
                    <Label>Contact</Label>
                    <Select v-model="emailContactId">
                      <SelectTrigger><SelectValue placeholder="Select contact" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="demo">Demo Contact</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label>Message Body</Label>
                    <Textarea v-model="emailBody" placeholder="Compose your message..." rows="4" />
                  </div>
                  <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showEmailCompose = false">Cancel</Button>
                    <Button>Send</Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </div>
          <Card>
            <CardHeader><CardTitle>Email Interactions</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Subject</TableHead>
                    <TableHead class="p-4">Contact</TableHead>
                    <TableHead class="p-4">Direction</TableHead>
                    <TableHead class="p-4">Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell colspan="4" class="p-8 text-center text-gray-500">No email interactions.</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="sms" class="mt-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">SMS</h2>
            <Dialog v-model:open="showSmsCompose">
              <DialogTrigger as-child><Button size="sm"><Send class="h-4 w-4 mr-2" />New SMS</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Compose SMS</DialogTitle></DialogHeader>
                <div class="space-y-4">
                  <div class="space-y-2">
                    <Label>Contact</Label>
                    <Select v-model="smsContactId">
                      <SelectTrigger><SelectValue placeholder="Select contact" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="demo">Demo Contact</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label>Message</Label>
                    <Textarea v-model="smsMessage" placeholder="Type your message..." rows="3" />
                  </div>
                  <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showSmsCompose = false">Cancel</Button>
                    <Button>Send</Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </div>
          <Card>
            <CardHeader><CardTitle>SMS Interactions</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Contact</TableHead>
                    <TableHead class="p-4">Message</TableHead>
                    <TableHead class="p-4">Direction</TableHead>
                    <TableHead class="p-4">Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell colspan="4" class="p-8 text-center text-gray-500">No SMS interactions.</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="call" class="mt-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Call Logger</h2>
            <Dialog v-model:open="showCallLog">
              <DialogTrigger as-child><Button size="sm"><Plus class="h-4 w-4 mr-2" />Log Call</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Log Call</DialogTitle></DialogHeader>
                <div class="space-y-4">
                  <div class="space-y-2">
                    <Label>Contact</Label>
                    <Select v-model="callContactId">
                      <SelectTrigger><SelectValue placeholder="Select contact" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="demo">Demo Contact</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label>Subject</Label>
                    <input v-model="callSubject" class="w-full p-2 border rounded" placeholder="Call subject" />
                  </div>
                  <div class="space-y-2">
                    <Label>Notes</Label>
                    <Textarea v-model="callNotes" placeholder="Call notes..." rows="3" />
                  </div>
                  <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showCallLog = false">Cancel</Button>
                    <Button>Save</Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </div>
          <Card>
            <CardHeader><CardTitle>Call Logs</CardTitle></CardHeader>
            <CardContent class="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="p-4">Contact</TableHead>
                    <TableHead class="p-4">Subject</TableHead>
                    <TableHead class="p-4">Direction</TableHead>
                    <TableHead class="p-4">Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell colspan="4" class="p-8 text-center text-gray-500">No call logs.</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="field" class="mt-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Field Visit Sync</h2>
          </div>
          <Card>
            <CardHeader><CardTitle>Offline Sync Status</CardTitle></CardHeader>
            <CardContent>
              <p class="text-sm text-gray-500 mb-4">Last sync: {{ new Date().toLocaleString() }}</p>
              <p class="text-sm text-gray-500">Pending records: 0</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>