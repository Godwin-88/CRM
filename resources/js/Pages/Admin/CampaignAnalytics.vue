<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement);

interface Campaign {
  id: string;
  name: string;
  status: string;
}

const props = defineProps<{
  campaigns: Campaign[];
}>();

const campaigns = ref(props.campaigns);
const selectedCampaignId = ref<string>('');
const metrics = ref<any>(null);
const timeSeries = ref<any[]>([]);
const perContact = ref<any[]>([]);
const perLink = ref<any[]>([]);

const fetchMetrics = async (campaignId: string) => {
  const response = await fetch(`/api/v1/analytics/campaign-performance?campaign_id=${campaignId}`);
  metrics.value = await response.json();
};

const fetchTimeSeries = async (campaignId: string) => {
  const response = await fetch(`/api/v1/analytics/campaign-time-series/${campaignId}`);
  timeSeries.value = await response.json();
};

const fetchPerContact = async (campaignId: string) => {
  const response = await fetch(`/api/v1/analytics/campaign-per-contact/${campaignId}`);
  perContact.value = await response.json();
};

onMounted(() => {
  if (campaigns.value.length > 0) {
    selectedCampaignId.value = campaigns.value[0].id;
    fetchMetrics(selectedCampaignId.value);
    fetchTimeSeries(selectedCampaignId.value);
    fetchPerContact(selectedCampaignId.value);
  }
});

const barChartData = computed(() => ({
  labels: ['Sent', 'Delivered', 'Opened', 'Clicked', 'Bounced', 'Unsubscribed'],
  datasets: [{
    label: 'Count',
    data: [
      metrics.value?.total_sent ?? 0,
      metrics.value?.delivered ?? 0,
      metrics.value?.opened ?? 0,
      metrics.value?.clicked ?? 0,
      metrics.value?.bounced ?? 0,
      metrics.value?.unsubscribed ?? 0,
    ],
    backgroundColor: ['#60a5fa', '#34d399', '#fbbf24', '#f97316', '#f87171', '#d1d5db'],
  }],
}));

const lineChartData = computed(() => ({
  labels: timeSeries.value.map((d: any) => d.bucket),
  datasets: [
    {
      label: 'Opens',
      data: timeSeries.value.map((d: any) => d.opens),
      borderColor: '#3b82f6',
      fill: false,
    },
    {
      label: 'Clicks',
      data: timeSeries.value.map((d: any) => d.clicks),
      borderColor: '#f97316',
      fill: false,
    },
  ],
}));

const barChartOptions = { responsive: true, plugins: { legend: { display: false } } };
const lineChartOptions = { responsive: true };
</script>

<template>
  <AppLayout>
    <Head title="Campaign Analytics" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold">Campaign Analytics</h1>
          <p class="text-gray-500">Track performance and engagement across campaigns.</p>
        </div>
        <select v-model="selectedCampaignId" @change="fetchMetrics(selectedCampaignId); fetchTimeSeries(selectedCampaignId); fetchPerContact(selectedCampaignId)" class="p-2 border rounded">
          <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <!-- Metrics Cards -->
      <div class="grid grid-cols-4 gap-4">
        <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics?.total_sent ?? 0 }}</p><p class="text-sm text-gray-500">Total Sent</p></CardContent></Card>
        <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics?.open_rate ?? 0 }}%</p><p class="text-sm text-gray-500">Open Rate</p></CardContent></Card>
        <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics?.click_rate ?? 0 }}%</p><p class="text-sm text-gray-500">Click Rate</p></CardContent></Card>
        <Card><CardContent class="pt-6"><p class="text-2xl font-bold">{{ metrics?.delivered ?? 0 }}</p><p class="text-sm text-gray-500">Delivered</p></CardContent></Card>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>Engagement Summary</CardTitle></CardHeader>
          <CardContent>
            <Bar v-if="metrics" :data="barChartData" :options="barChartOptions" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Time Series</CardTitle></CardHeader>
          <CardContent>
            <Line v-if="timeSeries.length" :data="lineChartData" :options="lineChartOptions" />
          </CardContent>
        </Card>
      </div>

      <!-- Per Contact Table -->
      <Card>
        <CardHeader><CardTitle>Per-Contact Status</CardTitle></CardHeader>
        <CardContent class="p-0">
          <table class="w-full">
            <thead class="border-b">
              <tr class="text-left">
                <th class="p-4">Contact</th>
                <th class="p-4">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in perContact" :key="item.contact_id" class="border-b">
                <td class="p-4">{{ item.contact?.first_name }} {{ item.contact?.last_name }}</td>
                <td class="p-4"><Badge>{{ item.status }}</Badge></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>