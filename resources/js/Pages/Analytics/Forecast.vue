<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';

interface ForecastData {
  total_unweighted: number;
  total_weighted: number;
  by_stage: Record<string, { total_value: number; weighted_value: number }>;
}

interface TimeBucket {
  month: string;
  total_value: number;
  weighted_value: number;
}

interface WinRate {
  stage: string;
  configured_probability: number;
  historical_win_rate: number;
}

const props = defineProps<{
  forecast: ForecastData;
  timeBuckets: TimeBucket[];
  winRates: WinRate[];
  pipelines: { id: string; name: string }[];
}>();

const forecast = ref(props.forecast);
const timeBuckets = ref(props.timeBuckets);
const winRates = ref(props.winRates);
const pipelines = ref(props.pipelines);

const filters = ref({
  pipeline_id: '',
  owner_id: '',
  close_from: '',
  close_to: '',
});

const loadForecast = () => {
  fetch(`/api/v1/analytics/forecast?${new URLSearchParams(filters.value)}`)
    .then(r => r.json())
    .then(data => {
      forecast.value = data.forecast;
      timeBuckets.value = data.time_bucketed;
      winRates.value = data.historical_win_rates;
    });
};
</script>

<template>
  <AppLayout>
    <Head title="Revenue Forecast" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Revenue Forecast</h1>
          <p class="text-gray-500">Weighted pipeline forecast and analytics.</p>
        </div>
        <Select v-model="filters.pipeline_id" @update:model-value="loadForecast">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="All pipelines" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="">All Pipelines</SelectItem>
            <SelectItem v-for="pipeline in pipelines" :key="pipeline.id" :value="pipeline.id">
              {{ pipeline.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Total Value</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">${{ Number(forecast.total_unweighted || 0).toLocaleString() }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Weighted Forecast</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-3xl font-bold">${{ Number(forecast.total_weighted || 0).toLocaleString() }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Win Rate Comparison</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-sm text-gray-500">Historical vs Configured</div>
          </CardContent>
        </Card>
      </div>

      <!-- Stage Breakdown -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>By Stage</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4">
            <div v-for="(stage, name) in forecast.by_stage" :key="name" class="p-3 bg-gray-50 rounded">
              <div class="font-medium">{{ name }}</div>
              <div class="text-sm text-gray-500 mt-1">
                Value: ${{ Number(stage.total_value).toLocaleString() }} • 
                Weighted: ${{ Number(stage.weighted_value).toLocaleString() }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Time Bucketed View -->
      <Card>
        <CardHeader>
          <CardTitle>Monthly Forecast</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-3 gap-4">
            <div v-for="bucket in timeBuckets" :key="bucket.month" class="p-3 border rounded">
              <div class="font-medium">{{ bucket.month }}</div>
              <div class="text-sm mt-1">
                <div>Value: ${{ Number(bucket.total_value).toLocaleString() }}</div>
                <div class="text-blue-600">Weighted: ${{ Number(bucket.weighted_value).toLocaleString() }}</div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>