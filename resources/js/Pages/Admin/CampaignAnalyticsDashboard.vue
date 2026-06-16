<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, SelectGroup, SelectLabel } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Bar, Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
} from 'chart.js';
import { BarChart3, Crosshair, DollarSign, TrendingUp } from 'lucide-vue-next';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement);

interface Campaign {
  id: string;
  name: string;
  status: string;
}

interface Props {
  campaigns: Campaign[];
}

const props = defineProps<Props>();

const campaigns = ref(props.campaigns);
const selectedCampaignIds = ref<string[]>([]);
const metrics = ref<any>(null);
const revenue = ref<any>(null);
const perLink = ref<any[]>([]);
const showRevenue = ref(false);

const startDate = ref('');
const endDate = ref('');

onMounted(() => {
  if (campaigns.value.length > 0) {
    selectedCampaignIds.value = [campaigns.value[0].id];
  }
});

const updateCampaigns = (ids: string[]) => {
  selectedCampaignIds.value = ids;
};

const fetchCrossCampaign = async () => {
  const params = new URLSearchParams();
  selectedCampaignIds.value.forEach(id => params.append('campaign_ids[]', id));
  if (startDate.value) params.set('start_date', startDate.value);
  if (endDate.value) params.set('end_date', endDate.value);

  const res = await fetch(`/api/v1/analytics/cross-campaign?${params.toString()}`);
  if (res.ok) metrics.value = await res.json();
};

const fetchRevenue = async () => {
  if (selectedCampaignIds.value.length !== 1) return;
  const res = await fetch(`/api/v1/analytics/campaign-revenue/${selectedCampaignIds.value[0]}`);
  if (res.ok) revenue.value = await res.json();
};

const fetchPerLink = async () => {
  if (selectedCampaignIds.value.length !== 1) return;
  const res = await fetch(`/api/v1/analytics/campaign-per-link/${selectedCampaignIds.value[0]}`);
  if (res.ok) perLink.value = await res.json();
};

const refreshAll = () => {
  fetchCrossCampaign();
  fetchRevenue();
  fetchPerLink();
};

const declineMap: Record<string, any> = { default: { borderColor: '#60a5fa', backgroundColor: '#60a5fa' } };

const barData = computed(() => ({
  labels: ['Sent', 'Opened', 'Clicked', 'Unsubscribed'],
  datasets: [{
    label: 'Cross Campaign',
    data: [metrics.value?.total_sent ?? 0, metrics.value?.opened ?? 0, metrics.value?.clicked ?? 0, metrics.value?.unsubscribed ?? 0],
    backgroundColor: ['#60a5fa', '#fbbf24', '#f97316', '#d1d5db'],
  }],
}));

const barOptions = { responsive: true, plugins: { legend: { display: false } } };
</script>

<template>
  <AppLayout>
    <Head title="Cross-Campaign Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Cross-Campaign Analytics</h1>
          <p class="text-muted-foreground">Compare performance across multiple campaigns.</p>
        </div>
        <Button @click="refreshAll">Refresh</Button>
      </div>

      <Card>
        <CardContent class="pt-6">
          <div class="grid grid-cols-4 gap-4">
            <div class="space-y-2"><Label>Campaigns</Label>
              <Select multiple :model-value="selectedCampaignIds" @update:model-value="updateCampaigns">
                <SelectTrigger class="w-full"><SelectValue placeholder="Select campaigns" /></SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectLabel>Campaigns</SelectLabel>
                    <SelectItem v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2"><Label>Start Date</Label><Input type="date" v-model="startDate" /></div>
            <div class="space-y-2"><Label>End Date</Label><Input type="date" v-model="endDate" /></div>
            <div class="space-y-2"><Label>&nbsp;</Label>
              <Button class="w-full" @click="fetchCrossCampaign">Apply</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <template v-if="metrics">
        <div class="grid grid-cols-4 gap-4">
          <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics.total_sent ?? 0 }}</p><p class="text-sm text-muted-foreground">Total Sent</p></CardContent></Card>
          <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics.open_rate ?? 0 }}%</p><p class="text-sm text-muted-foreground">Open Rate</p></CardContent></Card>
          <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics.click_rate ?? 0 }}%</p><p class="text-sm text-muted-foreground">Click Rate</p></CardContent></Card>
          <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics.unsubscribed ?? 0 }}</p><p class="text-sm text-muted-foreground">Unsubscribes</p></CardContent></Card>
        </div>

        <Card>
          <CardHeader><CardTitle>Engagement Overview</CardTitle></CardHeader>
          <CardContent><Bar v-if="metrics" :data="barData" :options="barOptions" /></CardContent>
        </Card>
      </template>

      <div class="grid grid-cols-2 gap-6">
        <Card>
          <CardContent class="pt-6 space-y-3">
            <div class="flex items-center gap-2"><DollarSign class="h-4 w-4 text-green-600" /><p class="font-medium">Revenue Attribution</p></div>
            <p class="text-sm text-muted-foreground">Conversions: {{ revenue?.conversions ?? 0 }}</p>
            <p class="text-sm text-muted-foreground">Revenue: {{ revenue?.revenue ? '$' + revenue.revenue.toLocaleString() : '$0' }}</p>
            <p class="text-sm text-muted-foreground">Window: {{ revenue?.attribution_window_days ?? 30 }} days</p>
            <Button variant="outline" size="sm" class="w-full" @click="showRevenue = !showRevenue">{{ showRevenue ? 'Hide' : 'Show' }} Revenue</Button>
          </CardContent>
        </Card>

          <Card>
            <CardHeader><CardTitle>Per-Link Clicks</CardTitle></CardHeader>
            <CardContent>
              <template v-if="perLink.length">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>URL</TableHead>
                      <TableHead>Clicks</TableHead>
                      <TableHead>Unique</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="link in perLink" :key="link.url">
                      <TableCell class="truncate max-w-[220px]">{{ link.url }}</TableCell>
                      <TableCell>{{ link.clicks }}</TableCell>
                      <TableCell>{{ link.unique_clicks }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </template>
              <p v-else class="text-sm text-muted-foreground text-center py-4">Select a single campaign to view link stats.</p>
            </CardContent>
          </Card>
      </div>
    </div>
  </AppLayout>
</template>
