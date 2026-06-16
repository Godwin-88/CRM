<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';

interface CampaignTemplate {
  id: string;
  name: string;
  subject: string;
  status: string;
  version: number;
  is_active: boolean;
  created_at: string;
  blocks?: EmailBlock[];
  html_content?: string;
  raw_html?: string;
}

interface EmailBlock {
  id: string;
  type: 'header' | 'text' | 'image' | 'button' | 'divider' | 'spacer' | 'social_links';
  content?: string;
  settings?: Record<string, any>;
}

type VariablePlaceholder = {
  key: string;
  label: string;
};

const availableVariables: VariablePlaceholder[] = [
  { key: '{{contact.first_name}}', label: 'Contact First Name' },
  { key: '{{contact.last_name}}', label: 'Contact Last Name' },
  { key: '{{account.name}}', label: 'Account Name' },
  { key: '{{agent.name}}', label: 'Agent Name' },
  { key: '{{unsubscribe_link}}', label: 'Unsubscribe Link' },
];

const props = defineProps<{
  templates: CampaignTemplate[];
}>();

const templates = ref(props.templates);
const isCreateOpen = ref(false);
const isEditorOpen = ref(false);
const editingTemplate = ref<CampaignTemplate | null>(null);
const isMobilePreview = ref(false);
const editorMode = ref<'visual' | 'html'>('visual');

const newTemplate = ref({
  name: '',
  subject: '',
});

const blocks = ref<EmailBlock[]>([]);
const rawHtml = ref('');

const selectedBlock = ref<EmailBlock | null>(null);

const emailEditorWidth = computed(() => isMobilePreview.value ? '375px' : '600px');

const addBlock = (type: EmailBlock['type']) => {
  const id = Date.now().toString();
  const newBlock: EmailBlock = {
    id,
    type,
    settings: getDefaultSettings(type),
  };
  if (type === 'header') newBlock.content = 'Header Text';
  if (type === 'text') newBlock.content = 'Enter your text here...';
  if (type === 'button') { newBlock.content = 'Button Text'; (newBlock.settings as any).url = '#'; }
  blocks.value.push(newBlock);
  selectedBlock.value = newBlock;
};

const getDefaultSettings = (type: EmailBlock['type']) => {
  switch (type) {
    case 'header': return { level: 'h1', align: 'left' };
    case 'text': return { fontSize: '14px', bold: false, italic: false, link: '', isList: false };
    case 'image': return { alt: 'Image', url: '' };
    case 'button': return { url: '#', backgroundColor: '#007bff', textColor: '#ffffff' };
    case 'social_links': return { platforms: ['facebook', 'twitter', 'linkedin'] };
    default: return {};
  }
};

const duplicateBlock = (block: EmailBlock) => {
  const newBlock = { ...block, id: Date.now().toString() };
  const index = blocks.value.findIndex(b => b.id === block.id);
  blocks.value.splice(index + 1, 0, newBlock);
};

const deleteBlock = (block: EmailBlock) => {
  const index = blocks.value.findIndex(b => b.id === block.id);
  blocks.value.splice(index, 1);
  if (selectedBlock.value?.id === block.id) selectedBlock.value = null;
};

const reorderBlocks = (fromIndex: number, toIndex: number) => {
  const [item] = blocks.value.splice(fromIndex, 1);
  blocks.value.splice(toIndex, 0, item);
};

const generateHtml = (): string => {
  let html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
  blocks.value.forEach(block => {
    html += renderBlockToHtml(block);
  });
  html += '</div>';
  return html;
};

const renderBlockToHtml = (block: EmailBlock): string => {
  switch (block.type) {
    case 'header':
      const level = (block.settings?.level as string) || 'h1';
      const align = (block.settings?.align as string) || 'left';
      return `<${level} style="text-align: ${align};">${block.content || ''}</${level}>`;
    case 'text':
      let style = `font-size: ${(block.settings?.fontSize || '14px')};`;
      if (block.settings?.bold) style += ' font-weight: bold;';
      if (block.settings?.italic) style += ' font-style: italic;';
      return `<p style="${style}">${block.content || ''}</p>`;
    case 'image':
      return `<img src="${block.settings?.url || ''}" alt="${block.settings?.alt || ''}" style="max-width: 100%;" />`;
    case 'button':
      return `<div style="text-align: center; margin: 20px 0;"><a href="${block.settings?.url || '#'}" style="background-color: ${block.settings?.backgroundColor || '#007bff'}; color: ${block.settings?.textColor || '#ffffff'}; padding: 10px 20px; text-decoration: none; border-radius: 4px;">${block.content || ''}</a></div>`;
    case 'divider':
      return `<hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;" />`;
    case 'spacer':
      return `<div style="height: ${(block.settings?.height || 20)}px;"></div>`;
    case 'social_links':
      return `<div style="text-align: center; margin: 20px 0;"><p>Follow us: Facebook | Twitter | LinkedIn</p></div>`;
    default:
      return '';
  }
};

const exportHtml = () => {
  const html = generateHtml();
  const blob = new Blob([html], { type: 'text/html' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'email-template.html';
  a.click();
  URL.revokeObjectURL(url);
};

const saveTemplate = async () => {
  const payload = {
    name: editingTemplate.value?.name || '',
    subject: editingTemplate.value?.subject || '',
    blocks: blocks.value,
    html_content: generateHtml(),
    raw_html: rawHtml.value,
  };

  const url = editingTemplate.value
    ? `/api/v1/campaign-templates/${editingTemplate.value.id}`
    : '/api/v1/campaign-templates';

  const response = await fetch(url, {
    method: editingTemplate.value ? 'PUT' : 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content,
    },
    body: JSON.stringify(payload),
  });

  if (response.ok) {
    isEditorOpen.value = false;
    router.reload();
  }
};

const openEditor = (template?: CampaignTemplate) => {
  editingTemplate.value = template || null;
  blocks.value = template?.blocks || [];
  rawHtml.value = template?.raw_html || template?.html_content || '';
  selectedBlock.value = null;
  editorMode.value = 'visual';
  isEditorOpen.value = true;
};

const switchToHtmlMode = () => {
  rawHtml.value = generateHtml();
  editorMode.value = 'html';
};

const switchToVisualMode = () => {
  // Check if HTML can be represented in visual mode
  const complexHtml = /<[a-z]+[^>]*class="|"[^>]*style=/.test(rawHtml.value);
  if (complexHtml && !confirm('The HTML contains structures that may not be fully editable in visual mode. Continue?')) {
    return;
  }
  editorMode.value = 'visual';
};

const dragOverIndex = ref<number | null>(null);
const draggingBlock = ref<EmailBlock | null>(null);

const selectedVariable = ref('');

const insertVariable = (value: string) => {
  if (value && selectedBlock.value) {
    selectedBlock.value.content = (selectedBlock.value.content || '') + ' ' + value;
    selectedVariable.value = '';
  }
};

const handleDragOver = (index: number) => {
  dragOverIndex.value = index;
};

const handleDragEnd = () => {
  if (dragOverIndex.value !== null && draggingBlock.value) {
    const fromIndex = blocks.value.findIndex(b => b.id === draggingBlock.value!.id);
    if (fromIndex !== -1 && fromIndex !== dragOverIndex.value) {
      reorderBlocks(fromIndex, dragOverIndex.value);
    }
  }
  draggingBlock.value = null;
  dragOverIndex.value = null;
};

</script>

<template>
  <AppLayout>
    <Head title="Campaign Templates" />
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">Campaign Templates</h1>
          <p class="text-gray-500">Manage email templates for campaigns.</p>
        </div>
        <Dialog v-model:open="isCreateOpen">
          <DialogTrigger as-child>
            <Button>+ New Template</Button>
          </DialogTrigger>
          <DialogContent class="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create Template</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
              <div>
                <Label class="text-sm font-medium">Name</Label>
                <Input v-model="newTemplate.name" placeholder="e.g., Welcome Email" />
              </div>
              <div>
                <Label class="text-sm font-medium">Subject Line</Label>
                <Input v-model="newTemplate.subject" placeholder="Enter email subject" />
              </div>
              <Button @click="openEditor({ ...newTemplate, id: '', status: 'draft', blocks: [] }); isCreateOpen = false">Create & Edit</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div class="grid gap-4">
        <Card v-for="template in templates" :key="template.id">
          <CardHeader>
            <div class="flex justify-between items-start">
              <CardTitle>{{ template.name }}</CardTitle>
              <div class="flex gap-2">
                <Badge :variant="template.status === 'approved' ? 'default' : 'outline'">
                  {{ template.status.replace('_', ' ') }}
                </Badge>
                <Badge :variant="template.is_active ? 'secondary' : 'outline'" v-if="template.is_active">
                  Active
                </Badge>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-gray-600">{{ template.subject || 'No subject' }}</p>
            <div class="flex gap-2 mt-4">
              <Button variant="outline" size="sm" @click="openEditor(template)">Edit</Button>
              <Button variant="outline" size="sm" v-if="template.status === 'draft'">Submit for Review</Button>
              <Button variant="ghost" size="sm" @click="exportHtml">Export HTML</Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Full Template Editor Modal -->
    <Dialog v-model:open="isEditorOpen">
      <DialogContent class="max-w-[1400px] w-full h-[90vh] flex flex-col">
        <DialogHeader class="flex-shrink-0">
          <div class="flex justify-between items-center">
            <DialogTitle>{{ editingTemplate?.name || 'New Template' }}</DialogTitle>
            <div class="flex gap-2">
              <Button size="sm" variant="ghost" @click="isMobilePreview = !isMobilePreview">
                {{ isMobilePreview ? 'Desktop Preview' : 'Mobile Preview' }}
              </Button>
              <Button size="sm" variant="ghost" @click="editorMode === 'visual' ? switchToHtmlMode() : switchToVisualMode()">
                {{ editorMode === 'visual' ? 'HTML Mode' : 'Visual Mode' }}
              </Button>
              <Button size="sm" @click="saveTemplate">Save Template</Button>
            </div>
          </div>
        </DialogHeader>

        <div class="flex flex-1 gap-4 overflow-hidden">
          <!-- Left Panel: Block Palette -->
          <div class="w-64 bg-gray-50 rounded-lg p-4 overflow-y-auto">
            <h3 class="font-semibold mb-3">Content Blocks</h3>
            <div class="space-y-2">
              <Button block @click="addBlock('header')" variant="outline" class="w-full justify-start">Header</Button>
              <Button block @click="addBlock('text')" variant="outline" class="w-full justify-start">Text</Button>
              <Button block @click="addBlock('image')" variant="outline" class="w-full justify-start">Image</Button>
              <Button block @click="addBlock('button')" variant="outline" class="w-full justify-start">Button</Button>
              <Button block @click="addBlock('divider')" variant="outline" class="w-full justify-start">Divider</Button>
              <Button block @click="addBlock('spacer')" variant="outline" class="w-full justify-start">Spacer</Button>
              <Button block @click="addBlock('social_links')" variant="outline" class="w-full justify-start">Social Links</Button>
            </div>
          </div>

          <!-- Center: Canvas/Preview -->
          <div class="flex-1 bg-gray-100 rounded-lg p-4 overflow-y-auto flex justify-center">
            <div
              class="bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300"
              :style="{ width: emailEditorWidth, minHeight: '600px' }"
            >
              <div v-if="editorMode === 'visual'" class="p-6">
                <div
                  v-for="(block, index) in blocks"
                  :key="block.id"
                  class="relative group border-2 border-transparent hover:border-blue-300 rounded cursor-move"
                  :class="{ 'border-blue-500': selectedBlock?.id === block.id }"
                  @click="selectedBlock = block"
                  @dragstart="handleDragStart(block, index)"
                  @dragover.prevent="handleDragOver(index)"
                  @dragend="handleDragEnd"
                >
                  <!-- Block Controls -->
                  <div class="absolute top-2 right-2 hidden group-hover:flex gap-1">
                    <Button size="sm" variant="ghost" @click.stop="duplicateBlock(block)">⧉</Button>
                    <Button size="sm" variant="ghost" @click.stop="deleteBlock(block)">✕</Button>
                  </div>

                  <!-- Block Preview -->
                  <div class="p-4">
                    <div v-if="block.type === 'header'">
                      <h1 v-if="block.settings?.level === 'h1'" class="text-xl font-bold" :style="{ textAlign: block.settings?.align }">{{ block.content }}</h1>
                      <h2 v-else-if="block.settings?.level === 'h2'" class="text-lg font-bold" :style="{ textAlign: block.settings?.align }">{{ block.content }}</h2>
                      <h3 v-else class="text-base font-bold" :style="{ textAlign: block.settings?.align }">{{ block.content }}</h3>
                    </div>
                    <p v-else-if="block.type === 'text'" :style="{ fontSize: block.settings?.fontSize }">{{ block.content }}</p>
                    <img v-else-if="block.type === 'image'" :src="block.settings?.url" :alt="block.settings?.alt" class="max-w-full" />
                    <div v-else-if="block.type === 'button'" class="text-center">
                      <button class="px-4 py-2 rounded" :style="{ backgroundColor: block.settings?.backgroundColor, color: block.settings?.textColor }">{{ block.content }}</button>
                    </div>
                    <hr v-else-if="block.type === 'divider'" class="border-dashed" />
                    <div v-else-if="block.type === 'spacer'" :style="{ height: (block.settings?.height || 20) + 'px' }"></div>
                    <div v-else-if="block.type === 'social_links'" class="text-center text-sm">Social Links</div>
                  </div>
                </div>

                <div v-if="blocks.length === 0" class="p-8 text-center text-gray-500">
                  Select blocks from the left panel to add content
                </div>
              </div>

              <div v-else class="p-4">
                <Textarea v-model="rawHtml" :rows="20" class="w-full font-mono" />
                <p class="text-xs text-gray-500 mt-2">Raw HTML mode - switching back to visual mode may lose unsupported structures</p>
              </div>
            </div>
          </div>

          <!-- Right Panel: Settings -->
          <div class="w-80 bg-gray-50 rounded-lg p-4 overflow-y-auto">
            <h3 class="font-semibold mb-3">Block Settings</h3>
            <div v-if="selectedBlock">
              <!-- Header Settings -->
              <div v-if="selectedBlock.type === 'header'" class="space-y-3">
                <div>
                  <Label class="text-xs">Header Level</Label>
                  <Select v-model="selectedBlock.settings.level">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="h1">H1</SelectItem>
                      <SelectItem value="h2">H2</SelectItem>
                      <SelectItem value="h3">H3</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label class="text-xs">Alignment</Label>
                  <Select v-model="selectedBlock.settings.align">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="left">Left</SelectItem>
                      <SelectItem value="center">Center</SelectItem>
                      <SelectItem value="right">Right</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label class="text-xs">Content</Label>
                  <Textarea v-model="selectedBlock.content" rows="3" />
                </div>
              </div>

              <!-- Text Settings -->
              <div v-if="selectedBlock.type === 'text'" class="space-y-3">
                <div>
                  <Label class="text-xs">Font Size</Label>
                  <Input v-model="selectedBlock.settings.fontSize" placeholder="14px" />
                </div>
                <div class="flex gap-2">
                  <label class="flex items-center gap-1 text-xs">
                    <Checkbox v-model:checked="selectedBlock.settings.bold" />
                    <span>Bold</span>
                  </label>
                  <label class="flex items-center gap-1 text-xs">
                    <Checkbox v-model:checked="selectedBlock.settings.italic" />
                    <span>Italic</span>
                  </label>
                </div>
                <div>
                  <Label class="text-xs">Content</Label>
                  <Textarea v-model="selectedBlock.content" rows="4" />
                </div>
              </div>

              <!-- Image Settings -->
              <div v-if="selectedBlock.type === 'image'" class="space-y-3">
                <div>
                  <Label class="text-xs">Image URL</Label>
                  <Input v-model="selectedBlock.settings.url" placeholder="https://..." />
                </div>
                <div>
                  <Label class="text-xs">Alt Text</Label>
                  <Input v-model="selectedBlock.settings.alt" placeholder="Image description" />
                </div>
              </div>

              <!-- Button Settings -->
              <div v-if="selectedBlock.type === 'button'" class="space-y-3">
                <div>
                  <Label class="text-xs">Button Text</Label>
                  <Input v-model="selectedBlock.content" />
                </div>
                <div>
                  <Label class="text-xs">URL</Label>
                  <Input v-model="selectedBlock.settings.url" placeholder="https://" />
                </div>
              </div>
            </div>
            <div v-else class="text-gray-500 text-sm">
              Select a block to edit its settings
            </div>

            <!-- Variable Placeholder Selector -->
            <div class="mt-6 pt-4 border-t" v-if="selectedBlock && (selectedBlock.type === 'text' || selectedBlock.type === 'header')">
              <h4 class="font-medium mb-2">Insert Variable</h4>
              <Select v-model="selectedVariable" @update:model-value="(v) => { if(v) insertVariable(v); }">
                <SelectTrigger>
                  <SelectValue placeholder="Select a variable..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Select a variable...</SelectItem>
                  <SelectItem v-for="v in availableVariables" :key="v.key" :value="v.key">{{ v.label }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
