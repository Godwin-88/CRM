<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="store.isOpen"
      class="fixed inset-0 z-40 bg-black/20"
      @click="close"
    />
  </Transition>

  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-x-full opacity-0"
    enter-to-class="translate-x-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-x-0 opacity-100"
    leave-to-class="translate-x-full opacity-0"
  >
    <div
      v-if="store.isOpen"
      class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-2xl dark:bg-gray-900 sm:w-[420px] border-l border-gray-200 dark:border-gray-700"
      role="dialog"
      aria-modal="true"
      aria-label="AI CRM Assistant"
    >
      <header class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="truncate text-base font-semibold text-gray-900 dark:text-gray-100">AI Assistant</h2>
            <Badge variant="secondary" class="text-xs">Beta</Badge>
          </div>
          <p class="truncate text-xs text-gray-500 dark:text-gray-400">
            Viewing: {{ currentScreenLabel }}
          </p>
        </div>
        <button
          type="button"
          @click="close"
          class="rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800"
        >
          <span class="sr-only">Close assistant</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="lucide lucide-x">
            <path d="M18 6 6 18M6 6l12 12"/>
          </svg>
        </button>
      </header>

      <div class="space-y-4 overflow-y-auto px-4 py-4">
        <div v-if="store.messages.length > 0" class="mb-2 rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
          <div class="flex flex-wrap items-center gap-2">
            <Badge v-if="lastAssistantMessage?.metadata?.helpType" variant="secondary" class="text-[10px]">
              {{ helpTypeLabel(lastAssistantMessage.metadata.helpType) }}
            </Badge>
            <span v-if="lastAssistantMessage?.metadata?.confidence !== undefined">
              Confidence: {{ Math.round(lastAssistantMessage.metadata.confidence * 100) }}%
            </span>
            <span v-if="lastAssistantMessage?.metadata?.lowConfidence" class="text-amber-600 dark:text-amber-400">
              Low documentation confidence
            </span>
          </div>
          <p v-if="lastAssistantMessage?.metadata?.featureRefs?.length" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
            Mapped features: {{ lastAssistantMessage.metadata.featureRefs.join(', ') }}
          </p>
        </div>
        <div v-if="isEmpty" class="flex h-full min-h-[280px] flex-col items-center justify-center text-center text-sm text-gray-600 dark:text-gray-400">
          <p>Hello! I can help you navigate, explain, or execute CRM tasks.</p>
          <p class="mt-2 max-w-xs text-xs text-gray-500">
            I can see you are on {{ currentScreenLabel }}.
          </p>
        </div>

        <div
          v-for="message in store.messages"
          :key="message.id"
          :class="['flex gap-2', message.role === 'user' ? 'justify-end' : 'justify-start']"
        >
          <div
            :class="[
              'max-w-[88%] rounded-2xl px-3 py-2 text-sm',
              message.role === 'user'
                ? 'rounded-br-sm bg-blue-600 text-white'
                : 'rounded-bl-sm bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100',
            ]"
          >
            <div
              class="whitespace-pre-wrap"
              v-html="renderMessageContent(message.content)"
              @click="handleContentLinkClick"
            />

            <div v-if="message.metadata?.navigation" class="mt-2">
              <div v-if="message.metadata.navigation.allowed === false && message.metadata.navigation.disambiguation?.length" class="space-y-2">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-200">
                  {{ message.metadata.navigation.message || 'Choose a record to open' }}
                </p>
                <div class="space-y-1">
                  <button
                    v-for="option in message.metadata.navigation.disambiguation"
                    :key="`${option.type || 'record'}-${option.id || option.label}`"
                    type="button"
                    class="block w-full rounded-md border border-gray-200 bg-white px-2 py-2 text-left text-xs text-gray-800 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800"
                    @click="navigateTo({ route: option.route, label: option.label })"
                  >
                    <span class="font-medium">{{ option.label }}</span>
                    <span v-if="option.description" class="block text-gray-500 dark:text-gray-400">
                      {{ option.description }}
                    </span>
                  </button>
                </div>
              </div>

              <div v-else-if="message.metadata.navigation.allowed === false" class="rounded-md border border-amber-200 bg-amber-50 px-2 py-2 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                {{ message.metadata.navigation.message || 'This navigation is not available for your role.' }}
              </div>

              <button
                v-else
                type="button"
                @click="navigateTo(message.metadata.navigation)"
                class="inline-flex items-center gap-1 text-xs font-medium underline underline-offset-2"
                :class="message.role === 'user' ? 'text-white/90' : 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>
                </svg>
                {{ message.metadata.navigation.label || 'Open screen' }}
              </button>

              <p v-if="message.metadata.navigation.summary" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                {{ message.metadata.navigation.summary }}
              </p>
            </div>

            <div v-if="message.metadata?.fallbackArticles?.length" class="mt-3 space-y-2 rounded-lg border border-gray-200 bg-white p-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
              <p class="font-medium">Relevant documentation</p>
              <a
                v-for="article in message.metadata.fallbackArticles"
                :key="article.url"
                :href="article.url"
                data-assistant-link
                class="block rounded-md px-2 py-1.5 text-blue-600 underline underline-offset-2 hover:bg-gray-50 dark:text-blue-400 dark:hover:bg-gray-800"
                @click.prevent="handleContentLinkClick"
              >
                {{ article.title }}
              </a>
            </div>

            <div v-if="message.metadata?.toolCalls?.length" class="mt-2 space-y-2 rounded-lg border border-gray-200 bg-white p-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
              <p class="font-medium">Assistant activity</p>
              <div v-for="(call, idx) in message.metadata.toolCalls" :key="idx" class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                  <Badge :variant="call.status === 'failed' ? 'destructive' : call.status === 'executed' || call.status === 'success' ? 'success' : 'default'" class="text-[10px]">
                    {{ call.tool || call.name || 'Tool' }}
                  </Badge>
                  <span v-if="call.status === 'failed'" class="text-red-500">
                    {{ call.error?.message || call.error || 'Failed' }}
                  </span>
                  <span v-else-if="call.result?.record_url" class="text-blue-600 dark:text-blue-400">
                    <a href="#" data-assistant-link @click.prevent="handleContentLinkClick">View record</a>
                  </span>
                  <span v-else-if="call.status === 'executed' || call.status === 'success'" class="text-green-600 dark:text-green-400">
                    Completed
                  </span>
                </div>
                <p v-if="call.result?.message" class="text-gray-600 dark:text-gray-400">
                  {{ call.result.message }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="store.isLoading" class="flex justify-start gap-2">
          <div class="rounded-2xl rounded-bl-sm bg-gray-100 px-3 py-2 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
            <span class="inline-flex gap-1">
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
              <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
            </span>
          </div>
        </div>
      </div>

      <ConfirmationCard
        v-if="pendingConfirmation"
        :message="pendingConfirmation.message"
        :tool="pendingConfirmation.tool"
        :arguments="pendingConfirmation.arguments"
        @confirm="handleConfirm"
        @cancel="handleCancel"
      />

      <footer class="space-y-2 border-t border-gray-200 px-3 py-3 dark:border-gray-700">
        <QuickReplies
          v-if="suggestedReplies.length > 0 && !store.isLoading && !pendingConfirmation"
          :suggestions="suggestedReplies"
          @select="handleQuickReply"
        />
        <form @submit.prevent="handleSend" class="flex items-end gap-2">
          <Textarea
            v-model="input"
            rows="1"
            placeholder="Type a message..."
            class="flex-1 resize-none"
            @keydown.enter.exact.prevent="handleSend"
          />
          <Button type="submit" size="icon" :disabled="store.isLoading || !input.trim()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m22 2-7 20-4-9-9-4Z"/>
              <path d="M22 2 11 13"/>
            </svg>
          </Button>
        </form>
      </footer>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useAssistant } from '@/composables/useAssistant';
import ConfirmationCard from '@/Components/CRM/ConfirmationCard.vue';
import QuickReplies from './QuickReplies.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import type { AssistantNavigation, ChatMessage } from '@/stores/assistant';

interface AssistantPageProps {
  [key: string]: any;
  user?: {
    id: string | number;
  };
}

const page = usePage<AssistantPageProps>();
const { store, sendMessage, sendConfirmedAction, close: closeStore } = useAssistant();

const input = ref('');
let expiryTimer: number | undefined;

const currentScreenLabel = computed(() => {
  const component = page.component || 'current screen';
  return component.replace(/\//g, ' > ');
});

const isEmpty = computed(() => store.messages.length === 0 && !store.isLoading);

const lastAssistantMessage = computed(() => {
  return store.messages.filter(m => m.role === 'assistant').at(-1) || null;
});

const pendingConfirmation = computed(() => {
  const last = store.messages.filter(m => m.role === 'assistant').at(-1);
  if (last?.metadata?.requiresConfirmation && last?.metadata?.toolsToCall?.[0]) {
    return {
      message: last.metadata.confirmationMessage || 'Confirm this action?',
      tool: last.metadata.toolsToCall[0].tool,
      arguments: last.metadata.toolsToCall[0].arguments || {},
    };
  }
  return null;
});

const suggestedReplies = computed(() => {
  const last = store.messages.filter(m => m.role === 'assistant').at(-1);
  const quickReplies = last?.metadata?.quickReplies;

  if (quickReplies?.length) return quickReplies;

  if (last?.metadata?.intent === 'clarify') {
    return ['Contacts', 'Deals', 'Tickets', 'Reports'];
  }

  if (last?.metadata?.intent === 'navigate') {
    return last.metadata.navigation ? ['Open it', 'Tell me more'] : ['Tell me more'];
  }

  if (store.messages.length === 0) {
    return ['What can you do?', 'Show my tickets', 'Recent deals overview'];
  }

  return [];
});

onMounted(() => {
  store.setStorageKey(assistantStorageKey());
  store.restoreFromStorage();
  store.touch();
  expiryTimer = window.setInterval(() => {
    if (store.isOpen && store.isSessionExpired()) {
      store.resetConversation();
    }
  }, 60 * 1000);
});

onUnmounted(() => {
  if (expiryTimer) window.clearInterval(expiryTimer);
});

watch(
  () => page.props.user?.id,
  (userId) => {
    store.setStorageKey(`crm_assistant_session:user:${userId || 'anonymous'}`);
    store.restoreFromStorage();
    store.touch();
  }
);

function assistantStorageKey() {
  const userId = page.props.user?.id;
  return `crm_assistant_session:user:${userId || 'anonymous'}`;
}

function helpTypeLabel(value: string) {
  return {
    navigate: 'Navigate',
    explain: 'Explain',
    execute: 'Execute',
    clarify: 'Clarify',
  }[value] || value;
}

function buildRouteContext() {
  return {
    context: {
      route: page.component,
      path: page.url,
      screen: page.component,
      title: document.title || page.component,
      url: page.url,
    },
    current_route: page.url,
    current_screen: page.component,
  };
}

function close() {
  closeStore();
}

function navigateTo(nav: AssistantNavigation) {
  if (nav.allowed === false) return;

  if (nav.prefill && (nav.route || nav.href)) {
    localStorage.setItem(`assistant_navigation_prefill:${nav.route || nav.href}`, JSON.stringify(nav.prefill));
  }

  router.visit(resolveHref(nav.href || nav.route || '/', nav.query));
}

function resolveHref(route: string, query?: Record<string, string>) {
  const base = route.startsWith('/') || /^https?:\/\//i.test(route) ? route : `/${route}`;
  const url = new URL(base, window.location.origin);

  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value !== undefined && value !== null) url.searchParams.set(key, String(value));
    });
  }

  if (/^https?:\/\//i.test(base)) return url.toString();
  return `${url.pathname}${url.search}${url.hash}`;
}

async function handleSend() {
  const text = input.value.trim();
  if (!text) return;

  input.value = '';
  await sendMessage(text, buildRouteContext());
  await nextTick();
}

function handleQuickReply(text: string) {
  input.value = text;
  handleSend();
}

async function handleConfirm(tool: string, arguments_: Record<string, any>) {
  await sendConfirmedAction(tool, arguments_);
}

function handleCancel() {
  store.addAssistantMessage('Action cancelled.');
}

async function fetchProactive() {
  try {
    const response = await fetch('/api/v1/assistant/proactive', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (response.ok) {
      const data = await response.json();
      if (data.proactive_items && data.proactive_items.length > 0) {
        const item = data.proactive_items[0];
        const metadata = {
          quickReplies: item.quick_replies || ['Show me', 'Dismiss'],
          navigation: item.navigation || undefined,
        };
        store.addAssistantMessage(item.message || 'I found something that may need your attention.', metadata);
      }
    }
  } catch {
  }
}

watch(
  () => store.isOpen,
  async (isOpen) => {
    if (!isOpen) return;

    if (store.isSessionExpired()) {
      store.resetConversation();
    }

    if (!store.sessionId) {
      await fetchProactive();
      await sendMessage('', buildRouteContext());
    } else {
      await fetchProactive();
    }
  }
);

function renderMessageContent(content: string) {
  const pattern = /(\[[^\]]+]?\([^)]*\)|https?:\/\/[^\s]+|\/(?:support|accounts|contacts|deals|tickets|contracts|invoices|assets|vendors|purchase-orders|banking|employees|finance|analytics|admin|docs|legal|portal)(?:\/[^\s]+)?)/gi;
  let html = '';
  let lastIndex = 0;
  let match: RegExpExecArray | null;

  while ((match = pattern.exec(content)) !== null) {
    html += escapeHtml(content.slice(lastIndex, match.index));

    const raw = match[0];
    const parsed = parseMarkdownLink(raw);
    const href = cleanHref(parsed.href);
    const label = parsed.label || href;

    html += `<a href="${escapeAttribute(href)}" data-assistant-link class="font-medium text-blue-600 underline underline-offset-2 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">${escapeHtml(label)}</a>`;
    lastIndex = match.index + raw.length;
  }

  html += escapeHtml(content.slice(lastIndex));
  return html;
}

function parseMarkdownLink(value: string) {
  const markdown = value.match(/^\[([^\]]+)]\(([^)]+)\)$/);
  if (!markdown) return { label: '', href: value };
  return { label: markdown[1], href: markdown[2] };
}

function cleanHref(href: string) {
  return href.replace(/&amp;/g, '&').replace(/[),.;]+$/g, '');
}

function escapeHtml(value: string) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escapeAttribute(value: string) {
  return escapeHtml(value).replace(/`/g, '&#096;');
}

function handleContentLinkClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  const link = target.closest('a[data-assistant-link]') as HTMLAnchorElement | null;
  if (!link) return;

  const href = link.getAttribute('href');
  if (!href) return;

  if (href.startsWith('#')) return;

  if (isInternalHref(href)) {
    event.preventDefault();
    router.visit(href);
    return;
  }

  link.target = '_blank';
  link.rel = 'noopener noreferrer';
}

function isInternalHref(href: string) {
  if (href.startsWith('/')) return true;

  try {
    const url = new URL(href, window.location.origin);
    return url.origin === window.location.origin;
  } catch {
    return false;
  }
}
</script>
