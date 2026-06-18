<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Plus, ArrowUpRight, ArrowDownRight, Search, Filter } from 'lucide-vue-next'

interface LedgerEntry {
  id: string
  contact_id: string
  contact_name: string
  program_name: string
  type: string
  points_amount: number
  running_balance: number
  description: string
  triggered_by_event: string
  transaction_date: string
  creator_name?: string
  reason_note?: string
}

interface Program {
  id: string
  name: string
  currency_symbol: string
}

const props = defineProps<{
  ledger: LedgerEntry[]
  programs: Program[]
  stats: {
    total_credits: number
    total_debits: number
    net_change: number
    transaction_count: number
  }
}>()

const ledger = ref(props.ledger)
const programs = ref(props.programs)
const stats = ref(props.stats)
const searchQuery = ref('')
const selectedProgram = ref('')
const selectedType = ref('')
const showAdjustDialog = ref(false)
const selectedContactId = ref('')

const adjustForm = ref({
  contact_id: '',
  program_id: programs.value[0]?.id ?? '',
  type: 'credit',
  points_amount: '',
  description: '',
  reason_note: '',
})

const filteredLedger = ref(ledger.value)

const applyFilters = () => {
  filteredLedger.value = ledger.value.filter(entry => {
    const matchesSearch = !searchQuery.value ||
      entry.contact_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      entry.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesProgram = selectedProgram.value === 'all' || !selectedProgram.value || entry.program_name === selectedProgram.value
    const matchesType = selectedType.value === 'all' || !selectedType.value || entry.type === selectedType.value
    return matchesSearch && matchesProgram && matchesType
  })
}

const submitAdjust = () => {
  router.post('/api/v1/contacts/' + adjustForm.value.contact_id + '/loyalty/adjust', adjustForm.value, {
    onSuccess: () => {
      showAdjustDialog.value = false
      adjustForm.value = {
        contact_id: '',
        program_id: programs.value[0]?.id ?? '',
        type: 'credit',
        points_amount: '',
        description: '',
        reason_note: '',
      }
    },
  })
}

const typeBadgeVariant = (type: string) => {
  return type === 'credit' ? 'default' : 'destructive'
}

const typeIcon = (type: string) => {
  return type === 'credit'
    ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-500"><path d="m18 15-6-6-6 6"/></svg>'
    : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-rose-500"><path d="m6 9 6 6 6-6"/></svg>'
}
</script>

<template>
  <AppLayout>
    <Head title="Points Ledger" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Points Ledger</h1>
          <p class="text-gray-500">Track all points transactions across loyalty programs.</p>
        </div>
        <Dialog v-model:open="showAdjustDialog">
          <DialogTrigger as-child><Button><Plus class="h-4 w-4 mr-2" />Manual Adjustment</Button></DialogTrigger>
          <DialogContent class="sm:max-w-lg">
            <DialogHeader><DialogTitle>Manual Points Adjustment</DialogTitle></DialogHeader>
            <form @submit.prevent="submitAdjust" class="space-y-4 py-4">
              <div class="space-y-2"><Label>Contact ID</Label><Input v-model="adjustForm.contact_id" placeholder="Enter contact ID" required /></div>
              <div class="space-y-2">
                <Label>Program</Label>
                <Select v-model="adjustForm.program_id">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.name }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Type</Label>
                  <Select v-model="adjustForm.type">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="credit">Credit</SelectItem>
                      <SelectItem value="debit">Debit</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2"><Label>Points Amount</Label><Input type="number" v-model="adjustForm.points_amount" min="1" required /></div>
              </div>
              <div class="space-y-2"><Label>Description</Label><Input v-model="adjustForm.description" placeholder="Reason for adjustment" /></div>
              <div class="space-y-2"><Label>Reason Note (required)</Label><Textarea v-model="adjustForm.reason_note" placeholder="Detailed reason for audit trail" required /></div>
              <Button type="submit" class="w-full">Submit Adjustment</Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Total Credits</p>
                <p class="text-2xl font-bold text-emerald-600">+{{ Number(stats.total_credits || 0).toLocaleString() }}</p>
              </div>
              <div class="p-2 bg-emerald-100 rounded-full"><ArrowUpRight class="h-5 w-5 text-emerald-600" /></div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Total Debits</p>
                <p class="text-2xl font-bold text-rose-600">-{{ Number(stats.total_debits || 0).toLocaleString() }}</p>
              </div>
              <div class="p-2 bg-rose-100 rounded-full"><ArrowDownRight class="h-5 w-5 text-rose-600" /></div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Net Change</p>
                <p class="text-2xl font-bold" :class="stats.net_change >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ stats.net_change >= 0 ? '+' : '' }}{{ Number(stats.net_change || 0).toLocaleString() }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="pt-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Transactions</p>
                <p class="text-2xl font-bold">{{ stats.transaction_count || 0 }}</p>
              </div>
              <div class="p-2 bg-blue-100 rounded-full"><Filter class="h-5 w-5 text-blue-600" /></div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input v-model="searchQuery" placeholder="Search by contact or description..." class="pl-10" @input="applyFilters" />
              </div>
            </div>
            <div class="w-48">
              <Select v-model="selectedProgram" @update:model-value="applyFilters">
                <SelectTrigger><SelectValue placeholder="All Programs" /></SelectTrigger>
<SelectContent>
                   <SelectItem value="all">All Programs</SelectItem>
                   <SelectItem v-for="prog in programs" :key="prog.id" :value="prog.name">{{ prog.name }}</SelectItem>
                 </SelectContent>
               </Select>
             </div>
             <div class="w-40">
               <Select v-model="selectedType" @update:model-value="applyFilters">
                 <SelectTrigger><SelectValue placeholder="All Types" /></SelectTrigger>
                 <SelectContent>
                   <SelectItem value="all">All Types</SelectItem>
                  <SelectItem value="credit">Credit</SelectItem>
                  <SelectItem value="debit">Debit</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Ledger Table -->
      <Card>
        <CardHeader><CardTitle>Transaction History</CardTitle></CardHeader>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="p-4">Date</TableHead>
                <TableHead class="p-4">Contact</TableHead>
                <TableHead class="p-4">Program</TableHead>
                <TableHead class="p-4">Type</TableHead>
                <TableHead class="p-4">Amount</TableHead>
                <TableHead class="p-4">Balance</TableHead>
                <TableHead class="p-4">Description</TableHead>
                <TableHead class="p-4">Trigger</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="entry in filteredLedger" :key="entry.id" class="border-b hover:bg-gray-50">
                <TableCell class="p-4 text-sm">{{ new Date(entry.transaction_date).toLocaleDateString() }}</TableCell>
                <TableCell class="p-4 font-medium">{{ entry.contact_name }}</TableCell>
                <TableCell class="p-4 text-sm text-gray-600">{{ entry.program_name }}</TableCell>
                <TableCell class="p-4">
                  <Badge :variant="typeBadgeVariant(entry.type)" class="flex items-center gap-1 w-fit">
                    <span v-html="typeIcon(entry.type)"></span>
                    {{ entry.type }}
                  </Badge>
                </TableCell>
                <TableCell class="p-4 font-mono font-bold" :class="entry.type === 'credit' ? 'text-emerald-600' : 'text-rose-600'">
                  {{ entry.type === 'credit' ? '+' : '-' }}{{ Number(entry.points_amount).toLocaleString() }}
                </TableCell>
                <TableCell class="p-4 font-mono">{{ Number(entry.running_balance).toLocaleString() }}</TableCell>
                <TableCell class="p-4 text-sm text-gray-600 max-w-[200px] truncate">{{ entry.description || '—' }}</TableCell>
                <TableCell class="p-4 text-sm text-gray-500">{{ entry.triggered_by_event.replace(/_/g, ' ') }}</TableCell>
              </TableRow>
              <TableRow v-if="!filteredLedger.length">
                <TableCell colspan="8" class="p-8 text-center text-gray-500 italic">No transactions found.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
