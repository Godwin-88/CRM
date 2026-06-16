<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Plus, Play, Square, BarChart3, ArrowLeft } from 'lucide-vue-next';

interface Props {
  campaign: {
    id: string;
    name: string;
    description: string;
    status: string;
    abTest?: {
      id: string;
      test_type: string;
      winner_criterion: string;
      test_percentage: number;
      duration_hours: number;
      status: string;
      winner_variant?: string;
      subject_line_a?: string;
      subject_line_b?: string;
      variantATemplate?: { id: string; name: string };
      variantBTemplate?: { id: string; name: string };
      results?: {
        variant_a: { sent: number; opened: number; clicked: number; open_rate: number; click_rate: number };
        variant_b: { sent: number; opened: number; clicked: number; open_rate: number; click_rate: number };
      };
    };
  };
}

const props = defineProps<Props>();
const campaign = ref(props.campaign);
const abTest = ref(props.campaign.abTest || null);
const isCreateOpen = ref(false);
const isResultsOpen = ref(false);

const newTest = ref({
  test_type: 'subject_line',
  winner_criterion: 'open_rate',
  test_percentage: 20,
  duration_hours: 24,
  variant_a_template_id: '',
  variant_b_template_id: '',
  subject_line_a: '',
  subject_line_b: '',
});

const testTypeLabels: Record<string, string> = {
  subject_line: 'Subject Line',
  content_variant: 'Content Variant',
  send_time: 'Send Time',
};

const statusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'secondary',
    running: 'default',
    concluded: 'outline',
    inconclusive: 'destructive',
  };
  return colors[status] || 'outline';
};

const createTest = async () => {
  const payload = {
    ...newTest.value,
    variant_a_template_id: newTest.value.variant_a_template_id || undefined,
    variant_b_template_id: newTest.value.variant_b_template_id || undefined,
  };

  await router.post(`/admin/campaigns/${campaign.value.id}/ab-test`, payload, {
    onSuccess: () => {
      isCreateOpen.value = false;
      router.reload({ only: ['campaign'] });
    },
  });
};

const startTest = async () => {
  await router.post(`/admin/campaigns/${campaign.value.id}/ab-test/start`, {}, {
    onSuccess: () => router.reload({ only: ['campaign'] }),
  });
};

const concludeTest = async () => {
  await router.post(`/admin/campaigns/${campaign.value.id}/ab-test/conclude`, {}, {
    onSuccess: () => {
      isResultsOpen.value = true;
      router.reload({ only: ['campaign'] });
    },
  });
};

const goBack = () => {
  window.history.back();
};
</script>

<template>
  <AppLayout>
    <Head title="A/B Test" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="sm" @click="goBack">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Back
        </Button>
        <div class="flex-1">
          <h1 class="text-3xl font-bold tracking-tight">A/B Test</h1>
          <p class="text-muted-foreground">{{ campaign.name }}</p>
        </div>
      </div>

      <template v-if="!abTest">
        <Card>
          <CardContent class="py-12 text-center">
            <BarChart3 class="h-12 w-12 mx-auto text-gray-400 mb-4" />
            <h2 class="text-xl font-semibold mb-2">No A/B Test Configured</h2>
            <p class="text-muted-foreground mb-6">Create an A/B test to compare variants and optimize performance.</p>
            <Dialog v-model:open="isCreateOpen">
              <DialogTrigger as-child>
                <Button><Plus class="h-4 w-4 mr-2" />Create A/B Test</Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-2xl">
                <DialogHeader><DialogTitle>Configure A/B Test</DialogTitle></DialogHeader>
                <div class="space-y-4 py-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                      <Label>Test Type</Label>
                      <Select v-model="newTest.test_type">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="subject_line">Subject Line</SelectItem>
                          <SelectItem value="content_variant">Content Variant</SelectItem>
                          <SelectItem value="send_time">Send Time</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div class="space-y-2">
                      <Label>Winner Criterion</Label>
                      <Select v-model="newTest.winner_criterion">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="open_rate">Open Rate</SelectItem>
                          <SelectItem value="click_rate">Click Rate</SelectItem>
                          <SelectItem value="conversion">Conversion</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                      <Label>Test Percentage ({{ newTest.test_percentage }}%)</Label>
                      <Input type="range" min="1" max="50" v-model.number="newTest.test_percentage" />
                    </div>
                    <div class="space-y-2">
                      <Label>Duration (hours)</Label>
                      <Input type="number" v-model.number="newTest.duration_hours" min="1" max="72" />
                    </div>
                  </div>
                  <div class="border-t pt-4">
                    <h3 class="font-medium mb-3">Subject Lines</h3>
                    <div class="grid grid-cols-2 gap-4">
                      <div class="space-y-2"><Label>Variant A Subject</Label><Input v-model="newTest.subject_line_a" placeholder="Subject line A" /></div>
                      <div class="space-y-2"><Label>Variant B Subject</Label><Input v-model="newTest.subject_line_b" placeholder="Subject line B" /></div>
                    </div>
                  </div>
                  <Button @click="createTest" class="w-full">Create Test</Button>
                </div>
              </DialogContent>
            </Dialog>
          </CardContent>
        </Card>
      </template>

      <template v-else>
        <div class="grid grid-cols-4 gap-4">
          <Card>
            <CardContent class="pt-6">
              <p class="text-sm text-muted-foreground">Status</p>
              <Badge :variant="statusColor(abTest.status)">{{ abTest.status }}</Badge>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <p class="text-sm text-muted-foreground">Test Type</p>
              <p class="font-medium capitalize">{{ testTypeLabels[abTest.test_type] || abTest.test_type }}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <p class="text-sm text-muted-foreground">Winner Criterion</p>
              <p class="font-medium capitalize">{{ abTest.winner_criterion }}</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent class="pt-6">
              <p class="text-sm text-muted-foreground">Sample Size</p>
              <p class="font-medium">{{ abTest.test_percentage }}% of list</p>
            </CardContent>
          </Card>
        </div>

        <Tabs default-value="variants" class="space-y-4">
          <TabsList>
            <TabsTrigger value="variants">Variants</TabsTrigger>
            <TabsTrigger value="results" :disabled="abTest.status !== 'concluded' && abTest.status !== 'inconclusive'">Results</TabsTrigger>
          </TabsList>

          <TabsContent value="variants" class="space-y-4">
            <div class="grid grid-cols-2 gap-6">
              <Card>
                <CardHeader><CardTitle>Variant A</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                  <div>
                    <p class="text-sm font-medium">Template</p>
                    <p class="text-sm text-muted-foreground">{{ abTest.variantATemplate?.name || 'Not set' }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Subject Line</p>
                    <p class="text-sm text-muted-foreground">{{ abTest.subject_line_a || '—' }}</p>
                  </div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader><CardTitle>Variant B</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                  <div>
                    <p class="text-sm font-medium">Template</p>
                    <p class="text-sm text-muted-foreground">{{ abTest.variantBTemplate?.name || 'Not set' }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Subject Line</p>
                    <p class="text-sm text-muted-foreground">{{ abTest.subject_line_b || '—' }}</p>
                  </div>
                </CardContent>
              </Card>
            </div>

            <div class="flex gap-3">
              <Button v-if="abTest.status === 'pending'" @click="startTest" class="flex-1">
                <Play class="h-4 w-4 mr-2" />Start Test
              </Button>
              <Button v-if="abTest.status === 'running'" @click="concludeTest" variant="outline" class="flex-1">
                <Square class="h-4 w-4 mr-2" />Conclude Test
              </Button>
            </div>
          </TabsContent>

          <TabsContent value="results" class="space-y-4">
            <Card>
              <CardHeader><CardTitle>Results Summary</CardTitle></CardHeader>
              <CardContent>
                <div v-if="abTest.winner_variant" class="mb-4">
                  <Badge>Winner: Variant {{ abTest.winner_variant }}</Badge>
                </div>
                <div class="grid grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <h3 class="font-medium">Variant A</h3>
                    <div class="text-sm"><span class="text-muted-foreground">Open Rate:</span> {{ Math.round(abTest.results?.variant_a?.open_rate || 0) }}%</div>
                    <div class="text-sm"><span class="text-muted-foreground">Click Rate:</span> {{ Math.round(abTest.results?.variant_a?.click_rate || 0) }}%</div>
                    <div class="text-sm"><span class="text-muted-foreground">Sent:</span> {{ abTest.results?.variant_a?.sent || 0 }}</div>
                    <div class="text-sm"><span class="text-muted-foreground">Opened:</span> {{ abTest.results?.variant_a?.opened || 0 }}</div>
                    <div class="text-sm"><span class="text-muted-foreground">Clicked:</span> {{ abTest.results?.variant_a?.clicked || 0 }}</div>
                  </div>
                  <div class="space-y-2">
                    <h3 class="font-medium">Variant B</h3>
                    <div class="text-sm"><span class="text-muted-foreground">Open Rate:</span> {{ Math.round(abTest.results?.variant_b?.open_rate || 0) }}%</div>
                    <div class="text-sm"><span class="text-muted-foreground">Click Rate:</span> {{ Math.round(abTest.results?.variant_b?.click_rate || 0) }}%</div>
                    <div class="text-sm"><span class="text-muted-foreground">Sent:</span> {{ abTest.results?.variant_b?.sent || 0 }}</div>
                    <div class="text-sm"><span class="text-muted-foreground">Opened:</span> {{ abTest.results?.variant_b?.opened || 0 }}</div>
                    <div class="text-sm"><span class="text-muted-foreground">Clicked:</span> {{ abTest.results?.variant_b?.clicked || 0 }}</div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </template>
    </div>
  </AppLayout>
</template>
