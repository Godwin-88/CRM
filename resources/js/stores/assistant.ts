import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface AssistantArticleLink {
  title: string;
  url: string;
}

export interface AssistantNavigationOption {
  type?: string;
  id?: string;
  label: string;
  route: string;
  description?: string;
  allowed?: boolean;
}

export interface AssistantNavigation {
  allowed?: boolean;
  route?: string;
  href?: string;
  label?: string;
  query?: Record<string, string>;
  prefill?: Record<string, any>;
  summary?: string;
  reason?: string;
  message?: string;
  who_can_access?: string[];
  disambiguation?: AssistantNavigationOption[];
}

export interface ChatMessage {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  timestamp: number;
  metadata?: {
    helpType?: 'navigate' | 'explain' | 'execute' | 'clarify';
    intent?: string;
    confidence?: number;
    featureRefs?: string[];
    lowConfidence?: boolean;
    clarifyingOptions?: string[];
    navigation?: AssistantNavigation;
    toolCalls?: any[];
    requiresConfirmation?: boolean;
    confirmationMessage?: string;
    toolsToCall?: any[];
    quickReplies?: string[];
    fallbackArticles?: AssistantArticleLink[];
    executedActions?: any[];
  };
}

export interface ConfirmedAction {
  tool: string;
  arguments: Record<string, any>;
}

const SESSION_TTL_MS = 60 * 60 * 1000;
const DEFAULT_STORAGE_KEY = 'crm_assistant_session';

export const useAssistantStore = defineStore('assistant', () => {
  const messages = ref<ChatMessage[]>([]);
  const sessionId = ref<string>('');
  const isLoading = ref(false);
  const isOpen = ref(false);
  const applicablePermissions = ref<string[]>([]);
  const error = ref<string | null>(null);
  const storageKey = ref<string>(DEFAULT_STORAGE_KEY);
  const lastActiveAt = ref<number>(0);

  function setStorageKey(key: string) {
    storageKey.value = key;
  }

  function touch() {
    lastActiveAt.value = Date.now();
    persist();
  }

  function isSessionExpired() {
    if (!lastActiveAt.value) return false;
    return Date.now() - lastActiveAt.value > SESSION_TTL_MS;
  }

  function persist() {
    if (!storageKey.value) return;

    try {
      localStorage.setItem(storageKey.value, JSON.stringify({
        sessionId: sessionId.value,
        messages: messages.value,
        lastActiveAt: lastActiveAt.value,
      }));
    } catch {
    }
  }

  function clearStorage() {
    try {
      if (storageKey.value) localStorage.removeItem(storageKey.value);
    } catch {
    }
  }

  function restoreFromStorage() {
    if (!storageKey.value) return false;

    try {
      const raw = localStorage.getItem(storageKey.value);
      if (!raw) return false;

      const data = JSON.parse(raw) as {
        sessionId?: string;
        messages?: ChatMessage[];
        lastActiveAt?: number;
      };

      if (data.lastActiveAt && Date.now() - data.lastActiveAt > SESSION_TTL_MS) {
        clearStorage();
        return false;
      }

      hydrate(data.messages || [], data.sessionId || '', data.lastActiveAt || Date.now());
      return true;
    } catch {
      clearStorage();
      return false;
    }
  }

  function open() {
    touch();
    isOpen.value = true;
  }

  function close() {
    touch();
    isOpen.value = false;
  }

  function toggle() {
    if (isOpen.value) {
      close();
      return;
    }

    open();
  }

  function setSessionId(id: string) {
    sessionId.value = id;
    touch();
  }

  function addUserMessage(content: string) {
    const msg: ChatMessage = {
      id: crypto.randomUUID(),
      role: 'user',
      content,
      timestamp: Date.now(),
    };
    messages.value.push(msg);
    touch();
    return msg.id;
  }

  function addAssistantMessage(content: string, metadata?: ChatMessage['metadata']) {
    const msg: ChatMessage = {
      id: crypto.randomUUID(),
      role: 'assistant',
      content,
      timestamp: Date.now(),
      metadata,
    };
    messages.value.push(msg);
    touch();
    return msg.id;
  }

  function appendToLastAssistant(content: string) {
    const last = messages.value.filter(m => m.role === 'assistant').at(-1);
    if (last) {
      last.content += content;
      touch();
    } else {
      addAssistantMessage(content);
    }
  }

  function finalizeLastMessage(metadata?: ChatMessage['metadata']) {
    const last = messages.value.filter(m => m.role === 'assistant').at(-1);
    if (last) {
      last.metadata = {
        ...last.metadata,
        ...metadata,
      };
      touch();
    }
  }

  function startLoading() {
    isLoading.value = true;
    error.value = null;
  }

  function stopLoading() {
    isLoading.value = false;
  }

  function setError(message: string) {
    error.value = message;
    isLoading.value = false;
  }

  function clearError() {
    error.value = null;
  }

  function resetConversation() {
    messages.value = [];
    sessionId.value = '';
    applicablePermissions.value = [];
    error.value = null;
    clearStorage();
  }

  function setPermissions(permissions: string[]) {
    applicablePermissions.value = permissions;
  }

  function hydrate(messagesData: ChatMessage[], sid: string, activeAt = Date.now()) {
    messages.value = messagesData;
    sessionId.value = sid;
    lastActiveAt.value = activeAt;
  }

  return {
    messages,
    sessionId,
    isLoading,
    isOpen,
    applicablePermissions,
    error,
    storageKey,
    lastActiveAt,
    open,
    close,
    toggle,
    setSessionId,
    resetConversation,
    addUserMessage,
    addAssistantMessage,
    appendToLastAssistant,
    appendToLastMessage: appendToLastAssistant,
    finalizeLastMessage,
    startLoading,
    stopLoading,
    setError,
    clearError,
    setPermissions,
    hydrate,
    setStorageKey,
    restoreFromStorage,
    isSessionExpired,
    touch,
  };
});
