<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RuleBuilder from '@/Components/Segments/RuleBuilder.vue';

defineProps<{ segments: any[] }>();

const form = useForm({
  name: '',
  type: 'demographic',
  criteria: []
});

const submit = () => form.post('/segments');
</script>

<template>
  <AppLayout>
    <Head title="Segments" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Dynamic Segmentation</h1>
        <Dialog>
            <DialogTrigger as-child>
                <Button>+ Create Segment</Button>
            </DialogTrigger>
            <DialogContent class="max-w-3xl">
                <DialogHeader><DialogTitle>Create New Segment</DialogTitle></DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label>Segment Name</Label>
                        <Input v-model="form.name" required />
                    </div>
                    <RuleBuilder :rules="form.criteria" @update="form.criteria = $event" />
                    <Button type="submit">Save Segment</Button>
                </form>
            </DialogContent>
        </Dialog>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card v-for="segment in segments" :key="segment.id">
          <CardContent class="pt-6">
            <h3 class="font-bold text-lg">{{ segment.name }}</h3>
            <p class="text-gray-600">Contacts: {{ segment.contact_count }}</p>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
