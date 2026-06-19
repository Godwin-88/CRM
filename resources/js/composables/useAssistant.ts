import { ref, computed } from 'vue';
import axios from 'axios';
import { useAssistantStore } from '@/stores/assistant';

const API_BASE = '/api/v1/assistant';

export function useAssistant() {
  const store = useAssistantStore();
  const error = ref<string | null>(null);

  const isConfigured = computed(() => {
    return true;
  });

  async function sendMessage(message: string, extra: Record<string, any> = {}) {
    if (!message && !extra.confirmed_actions?.length) return;

    store.startLoading();
    store.clearError();
    error.value = null;

    if (message.trim()) {
      store.addUserMessage(message);
    }

    try {
      const payload: any = {
        message,
        session_id: store.sessionId || undefined,
        ...extra,
      };

      const response = await axios.post(`${API_BASE}/chat`, payload);

      if (response.data?.session_id && !store.sessionId) {
        store.setSessionId(response.data.session_id);
      }

      const assistantContent = response.data?.response !== undefined ? response.data.response : 'I processed your request.';
      const metadata: any = {};

      if (response.data?.intent) metadata.intent = response.data.intent;
      if (response.data?.help_type) metadata.helpType = response.data.help_type;
      if (response.data?.confidence) metadata.confidence = response.data.confidence;
      if (response.data?.feature_refs) metadata.featureRefs = response.data.feature_refs;
      if (response.data?.low_confidence) metadata.lowConfidence = true;
      if (response.data?.clarifying_options) metadata.clarifyingOptions = response.data.clarifying_options;
      if (response.data?.tool_calls && response.data.tool_calls.length > 0) {
        metadata.toolCalls = response.data.tool_calls;
        metadata.toolsToCall = response.data.tool_calls;
      }
      if (response.data?.requires_confirmation) {
        metadata.requiresConfirmation = true;
        metadata.confirmationMessage = response.data?.tool_calls?.[0]?.confirmation_message || 'This action requires your confirmation.';
        metadata.toolsToCall = response.data.tool_calls;
      }
      if (response.data?.navigation) {
        metadata.navigation = response.data.navigation;
      }
      if (response.data?.quick_replies) {
        metadata.quickReplies = response.data.quick_replies;
      }
      if (response.data?.articles) {
        metadata.fallbackArticles = response.data.articles;
      }
      if (response.data?.applicable_permissions) {
        store.setPermissions(response.data.applicable_permissions);
      }
      if (response.data?.executed_actions && response.data.executed_actions.length > 0) {
        metadata.toolCalls = response.data.executed_actions;
        metadata.executedActions = response.data.executed_actions;
      }

      if (assistantContent) {
        store.addAssistantMessage(assistantContent, metadata);
      }
      return response.data;
    } catch (e: any) {
      const message = e?.response?.data?.message || e?.message || 'Something went wrong. Please try again.';
      store.setError(message);
      store.addAssistantMessage(`Error: ${message}`);
      return null;
    } finally {
      store.stopLoading();
    }
  }

  async function streamChat(message: string, extra: Record<string, any> = {}): Promise<void> {
    if (!message && Object.keys(extra).length === 0) return;

    store.startLoading();
    error.value = null;

    if (message.trim()) {
      store.addUserMessage(message);
    }

    const payload: any = {
      message,
      session_id: store.sessionId || undefined,
      stream: true,
      ...extra,
    };

    try {
      const response = await fetch(`${API_BASE}/chat/stream`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const reader = response.body?.getReader();
      const decoder = new TextDecoder();
      let sessionIdSet = false;

      while (reader) {
        const { done, value } = await reader.read();
        if (done) break;

        const chunk = decoder.decode(value, { stream: true });
        const lines = chunk.split('\n');

        for (const line of lines) {
          if (line.startsWith('data: ')) {
            try {
              const data = JSON.parse(line.slice(6));

              if (data.session_id && !sessionIdSet) {
                store.setSessionId(data.session_id);
                sessionIdSet = true;
              }

              if (data.chunk) {
                store.appendToLastMessage?.(data.chunk);
              }

              if (data.done) {
                const metadata: any = {};
                if (data.intent) metadata.intent = data.intent;
                if (data.help_type) metadata.helpType = data.help_type;
                if (data.confidence) metadata.confidence = data.confidence;
                if (data.feature_refs) metadata.featureRefs = data.feature_refs;
                if (data.low_confidence) metadata.lowConfidence = true;
                if (data.clarifying_options) metadata.clarifyingOptions = data.clarifying_options;
                if (data.tool_calls) {
                  metadata.toolCalls = data.tool_calls;
                  metadata.toolsToCall = data.tool_calls;
                }
                if (data.requires_confirmation) {
                  metadata.requiresConfirmation = true;
                  metadata.toolsToCall = data.tool_calls;
                }
                if (data.navigation) metadata.navigation = data.navigation;
                if (data.quick_replies) metadata.quickReplies = data.quick_replies;
                if (data.articles) metadata.fallbackArticles = data.articles;

                store.finalizeLastMessage?.(metadata);
              }
            } catch {
            }
          }
        }
      }
    } catch (e: any) {
      const msg = e?.message || 'Streaming failed. Please try again.';
      store.setError(msg);
      store.addAssistantMessage(`Error: ${msg}`);
    } finally {
      store.stopLoading();
    }
  }

  async function sendConfirmedAction(tool: string, args: Record<string, any>) {
    return sendMessage('', { confirmed_actions: [{ tool, arguments: args }] });
  }

  function open() {
    store.open();
  }

  function close() {
    store.close();
  }

  function toggle() {
    store.toggle();
  }

  function resetConversation() {
    store.resetConversation();
  }

  return {
    store,
    error,
    isConfigured,
    sendMessage,
    streamChat,
    sendConfirmedAction,
    open,
    close,
    toggle,
    resetConversation,
  };
}
