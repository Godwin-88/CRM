<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  PanelBottom,
  Twitter,
  Linkedin,
  Facebook,
  Instagram,
  MessageCircle,
  Music2,
  Mail,
  MessageSquareText,
  MessageCircleMore,
  PhoneCall,
  Smartphone,
} from 'lucide-vue-next'

const activeTab = ref('kiosk')

const props = defineProps<{
  socialIntegrations: Array<{
    id: string
    provider: string
    name: string
    connection_status: string
    is_active: boolean
    config: Record<string, any> | null
    last_active_at: string | null
  }>
  emailIntegrations: Array<{
    id: string
    provider: string
    name: string
    connection_status: string
    is_active: boolean
    config: Record<string, any> | null
    last_active_at: string | null
  }>
  smsIntegrations: Array<{
    id: string
    provider: string
    name: string
    connection_status: string
    is_active: boolean
    config: Record<string, any> | null
    last_active_at: string | null
  }>
  chatIntegration: {
    id: string
    provider: string
    name: string
    connection_status: string
    is_active: boolean
    config: Record<string, any> | null
    last_active_at: string | null
  } | null
  ivrIntegration: {
    id: string
    provider: string
    name: string
    connection_status: string
    is_active: boolean
    config: Record<string, any> | null
    last_active_at: string | null
  } | null
  fieldIntegration: {
    id: string
    provider: string
    name: string
    connection_status: string
    is_active: boolean
    config: Record<string, any> | null
    last_active_at: string | null
  } | null
}>()

type ChannelField = { key: string; label: string; type: 'text' | 'password' | 'number'; required: boolean }

const socialChannels: Array<{ provider: string; label: string; icon: any; description: string; fields: ChannelField[] }> = [
  {
    provider: 'x',
    label: 'X (Twitter)',
    icon: Twitter,
    description: 'Post and monitor X/Twitter',
    fields: [
      { key: 'api_key', label: 'API Key', type: 'text', required: true },
      { key: 'api_secret', label: 'API Secret', type: 'password', required: true },
      { key: 'access_token', label: 'Access Token', type: 'password', required: true },
      { key: 'access_token_secret', label: 'Access Token Secret', type: 'password', required: true },
    ],
  },
  {
    provider: 'linkedin',
    label: 'LinkedIn',
    icon: Linkedin,
    description: 'Share updates and job posts',
    fields: [
      { key: 'client_id', label: 'Client ID', type: 'text', required: true },
      { key: 'client_secret', label: 'Client Secret', type: 'password', required: true },
      { key: 'access_token', label: 'Access Token', type: 'password', required: true },
    ],
  },
  {
    provider: 'facebook',
    label: 'Facebook',
    icon: Facebook,
    description: 'Manage page posts and ads',
    fields: [
      { key: 'app_id', label: 'App ID', type: 'text', required: true },
      { key: 'app_secret', label: 'App Secret', type: 'password', required: true },
      { key: 'page_access_token', label: 'Page Access Token', type: 'password', required: true },
      { key: 'page_id', label: 'Page ID', type: 'text', required: true },
    ],
  },
  {
    provider: 'instagram',
    label: 'Instagram',
    icon: Instagram,
    description: 'Schedule posts and stories',
    fields: [
      { key: 'access_token', label: 'Access Token', type: 'password', required: true },
      { key: 'instagram_business_account_id', label: 'Business Account ID', type: 'text', required: true },
    ],
  },
  {
    provider: 'whatsapp',
    label: 'WhatsApp Business',
    icon: MessageCircle,
    description: 'Send messages and manage conversations',
    fields: [
      { key: 'phone_number_id', label: 'Phone Number ID', type: 'text', required: true },
      { key: 'access_token', label: 'Access Token', type: 'password', required: true },
      { key: 'business_account_id', label: 'Business Account ID', type: 'text', required: true },
      { key: 'webhook_verify_token', label: 'Webhook Verify Token', type: 'text', required: false },
    ],
  },
  {
    provider: 'tiktok',
    label: 'TikTok',
    icon: Music2,
    description: 'Post videos and track trends',
    fields: [
      { key: 'client_key', label: 'Client Key', type: 'text', required: true },
      { key: 'client_secret', label: 'Client Secret', type: 'password', required: true },
      { key: 'access_token', label: 'Access Token', type: 'password', required: true },
    ],
  },
]

const emailProviders: Array<{ provider: string; label: string; icon: any; description: string; fields: ChannelField[] }> = [
  {
    provider: 'mailgun',
    label: 'Mailgun',
    icon: Mail,
    description: 'Transactional email delivery',
    fields: [
      { key: 'domain', label: 'Sending Domain', type: 'text', required: true },
      { key: 'webhook_signing_key', label: 'Webhook Signing Key', type: 'password', required: false },
      { key: 'region', label: 'Region (us/eu)', type: 'text', required: false },
    ],
  },
  {
    provider: 'postmark',
    label: 'Postmark',
    icon: Mail,
    description: 'Fast transactional email',
    fields: [
      { key: 'webhook_token', label: 'Webhook Token', type: 'password', required: false },
    ],
  },
  {
    provider: 'imap',
    label: 'IMAP Inbound',
    icon: Mail,
    description: 'Fetch inbound email via IMAP',
    fields: [
      { key: 'host', label: 'IMAP Host', type: 'text', required: true },
      { key: 'port', label: 'Port (993)', type: 'text', required: false },
      { key: 'username', label: 'Email Address', type: 'text', required: true },
      { key: 'password', label: 'Password / App Password', type: 'password', required: true },
      { key: 'encryption', label: 'Encryption (ssl/tls)', type: 'text', required: false },
    ],
  },
  {
    provider: 'email_webhook',
    label: 'Inbound Webhook',
    icon: Mail,
    description: 'Receive inbound email via webhook',
    fields: [
      { key: 'webhook_url', label: 'Webhook URL', type: 'text', required: true },
      { key: 'webhook_signing_secret', label: 'Signing Secret', type: 'password', required: false },
    ],
  },
]

const smsProviders: Array<{ provider: string; label: string; icon: any; description: string; fields: ChannelField[] }> = [
  {
    provider: 'twilio',
    label: 'Twilio',
    icon: MessageSquareText,
    description: 'SMS & Voice (Twilio)',
    fields: [
      { key: 'account_sid', label: 'Account SID', type: 'text', required: true },
      { key: 'auth_token', label: 'Auth Token', type: 'password', required: true },
      { key: 'phone_number', label: 'Phone Number', type: 'text', required: true },
      { key: 'webhook_url', label: 'Webhook URL', type: 'text', required: false },
    ],
  },
  {
    provider: 'africastalking',
    label: "Africa's Talking",
    icon: MessageSquareText,
    description: 'SMS & Voice (Africa)',
    fields: [
      { key: 'username', label: 'Username', type: 'text', required: true },
      { key: 'api_key', label: 'API Key', type: 'password', required: true },
      { key: 'sender_id', label: 'Sender ID', type: 'text', required: false },
      { key: 'environment', label: 'Environment (sandbox/production)', type: 'text', required: false },
    ],
  },
]

const chatFields: ChannelField[] = [
  { key: 'reverb_host', label: 'Reverb Host', type: 'text', required: false },
  { key: 'reverb_port', label: 'Reverb Port', type: 'text', required: false },
  { key: 'reverb_app_key', label: 'App Key', type: 'text', required: false },
  { key: 'reverb_app_secret', label: 'App Secret', type: 'password', required: false },
  { key: 'accept_timeout_seconds', label: 'Accept Timeout (seconds)', type: 'number', required: false },
  { key: 'auto_queue_after_seconds', label: 'Auto-Queue After (seconds)', type: 'number', required: false },
  { key: 'max_concurrent_per_agent', label: 'Max Concurrent Per Agent', type: 'number', required: false },
]

const ivrFields: ChannelField[] = [
  { key: 'ingest_url', label: 'Ingest Endpoint URL', type: 'text', required: false },
  { key: 'hmac_secret', label: 'HMAC Shared Secret', type: 'password', required: false },
  { key: 'rate_limit_per_minute', label: 'Rate Limit (per minute)', type: 'number', required: false },
  { key: 'consecutive_failure_alert_threshold', label: 'Failure Alert Threshold', type: 'number', required: false },
]

const fieldFields: ChannelField[] = [
  { key: 'sync_interval_minutes', label: 'Sync Interval (minutes)', type: 'number', required: false },
  { key: 'token_expiry_days', label: 'Token Expiry (days)', type: 'number', required: false },
  { key: 'excluded_fields', label: 'Excluded Fields (comma-separated)', type: 'text', required: false },
  { key: 'api_rate_limit', label: 'API Rate Limit', type: 'number', required: false },
]

const showConnectDialog = ref(false)
const dialogTab = ref<'social' | 'email' | 'sms' | 'chat' | 'ivr' | 'field'>('social')
const selectedChannel = ref<any>(null)
const channelName = ref('')
const submitting = ref(false)
const formFields = ref<Record<string, string>>({})

function getIntegration(provider: string, list: Array<any> | null) {
  if (!list) return null
  return list.find((i) => i.provider === provider)
}

function isConnected(provider: string, list: Array<any> | null) {
  const integration = getIntegration(provider, list)
  return integration?.connection_status === 'connected' && integration?.is_active
}

function getChatConfig() {
  return props.chatIntegration?.config || {}
}

function getIvrConfig() {
  return props.ivrIntegration?.config || {}
}

function getFieldConfig() {
  return props.fieldIntegration?.config || {}
}

function openConnectDialog(channel: any, tab: 'social' | 'email' | 'sms' | 'chat' | 'ivr' | 'field') {
  selectedChannel.value = channel
  dialogTab.value = tab
  channelName.value = channel.label
  formFields.value = {}

  if (tab === 'chat') {
    const config = getChatConfig()
    chatFields.forEach((field) => {
      formFields.value[field.key] = String(config[field.key] || '')
    })
  } else if (tab === 'ivr') {
    const config = getIvrConfig()
    ivrFields.forEach((field) => {
      formFields.value[field.key] = String(config[field.key] || '')
    })
  } else if (tab === 'field') {
    const config = getFieldConfig()
    fieldFields.forEach((field) => {
      formFields.value[field.key] = String(config[field.key] || '')
    })
  } else {
    const list = tab === 'social' ? props.socialIntegrations : tab === 'email' ? props.emailIntegrations : props.smsIntegrations
    const integration = getIntegration(channel.provider, list)
    channelName.value = integration?.name || channel.label
    channel.fields.forEach((field: ChannelField) => {
      const currentValue = integration?.config?.[field.key] || ''
      if (field.type === 'password') {
        formFields.value[field.key] = ''
      } else {
        formFields.value[field.key] = currentValue
      }
    })
  }

  showConnectDialog.value = true
}

function resetForm() {
  formFields.value = {}
  channelName.value = ''
  selectedChannel.value = null
}

function handleSave() {
  if (!selectedChannel.value) return
  submitting.value = true
  const routeMap: Record<string, string> = {
    social: `/admin/omni/settings/social-channels/${selectedChannel.value.provider}`,
    email: `/admin/omni/settings/email/${selectedChannel.value.provider}`,
    sms: `/admin/omni/settings/sms/${selectedChannel.value.provider}`,
    chat: '/admin/omni/settings/chat',
    ivr: '/admin/omni/settings/ivr',
    field: '/admin/omni/settings/field',
  }
  router.post(
    routeMap[dialogTab.value],
    {
      name: channelName.value,
      config: dialogTab.value === 'chat' || dialogTab.value === 'ivr' || dialogTab.value === 'field'
        ? formFields.value
        : { credentials: formFields.value },
      is_active: true,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showConnectDialog.value = false
        resetForm()
      },
      onFinish: () => {
        submitting.value = false
      },
    },
  )
}

function handleDisconnect(provider: string, tab: 'social' | 'email' | 'sms' | 'chat' | 'ivr' | 'field') {
  if (confirm('Disconnect this integration? This will stop all syncing.')) {
    const routeMap: Record<string, string> = {
      social: `/admin/omni/settings/social-channels/${provider}/disconnect`,
      email: `/admin/omni/settings/email/${provider}/disconnect`,
      sms: `/admin/omni/settings/sms/${provider}/disconnect`,
      chat: '/admin/omni/settings/channels/chat/disconnect',
      ivr: '/admin/omni/settings/channels/ivr/disconnect',
      field: '/admin/omni/settings/channels/field/disconnect',
    }
    router.post(routeMap[tab])
  }
}

function openChatDialog() {
  selectedChannel.value = { provider: 'chat', label: 'Live Chat', icon: MessageCircleMore, fields: chatFields }
  dialogTab.value = 'chat'
  channelName.value = 'Live Chat'
  const config = getChatConfig()
  formFields.value = {}
  chatFields.forEach((field) => {
    formFields.value[field.key] = String(config[field.key] || '')
  })
  showConnectDialog.value = true
}

function openIvrDialog() {
  selectedChannel.value = { provider: 'ivr', label: 'IVR', icon: PhoneCall, fields: ivrFields }
  dialogTab.value = 'ivr'
  channelName.value = 'IVR'
  const config = getIvrConfig()
  formFields.value = {}
  ivrFields.forEach((field) => {
    formFields.value[field.key] = String(config[field.key] || '')
  })
  showConnectDialog.value = true
}

function openFieldDialog() {
  selectedChannel.value = { provider: 'field', label: 'Field Channel', icon: Smartphone, fields: fieldFields }
  dialogTab.value = 'field'
  channelName.value = 'Field Channel'
  const config = getFieldConfig()
  formFields.value = {}
  fieldFields.forEach((field) => {
    formFields.value[field.key] = String(config[field.key] || '')
  })
  showConnectDialog.value = true
}
</script>

<template>
  <AppLayout>
    <Head title="OmniChannel Settings" />
    <div class="max-w-7xl mx-auto space-y-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
        <p class="text-gray-500">Configure channels, manage integrations, and review unmatched items.</p>
      </div>

      <Tabs v-model:model-value="activeTab" default-value="kiosk">
        <TabsList class="grid w-full grid-cols-2 md:grid-cols-4 lg:grid-cols-7">
          <TabsTrigger value="kiosk" class="flex items-center gap-2">
            <PanelBottom class="h-4 w-4" />
            Kiosk
          </TabsTrigger>
          <TabsTrigger value="social" class="flex items-center gap-2">
            <Twitter class="h-4 w-4" />
            Social
          </TabsTrigger>
          <TabsTrigger value="email" class="flex items-center gap-2">
            <Mail class="h-4 w-4" />
            Email
          </TabsTrigger>
          <TabsTrigger value="sms" class="flex items-center gap-2">
            <MessageSquareText class="h-4 w-4" />
            SMS
          </TabsTrigger>
          <TabsTrigger value="chat" class="flex items-center gap-2">
            <MessageCircleMore class="h-4 w-4" />
            Chat
          </TabsTrigger>
          <TabsTrigger value="ivr" class="flex items-center gap-2">
            <PhoneCall class="h-4 w-4" />
            IVR
          </TabsTrigger>
          <TabsTrigger value="field" class="flex items-center gap-2">
            <Smartphone class="h-4 w-4" />
            Field
          </TabsTrigger>
        </TabsList>

        <TabsContent value="kiosk" class="mt-4">
          <Card>
            <CardContent class="p-8 text-center text-gray-500">
              <PanelBottom class="h-12 w-12 mx-auto mb-4 text-gray-400" />
              <p class="text-lg font-medium">Kiosk Integrations</p>
              <p class="text-sm mt-2">Kiosk management is handled via the dedicated Kiosk page.</p>
              <Button variant="outline" class="mt-4" as-child>
                <a href="/admin/omni/kiosk">Open Kiosk Manager</a>
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="social" class="mt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card
              v-for="channel in socialChannels"
              :key="channel.provider"
              class="hover:shadow-md transition-shadow"
            >
              <CardHeader class="pb-2">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center text-blue-600">
                    <component :is="channel.icon" class="h-5 w-5" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">{{ channel.label }}</CardTitle>
                    <span class="text-xs text-gray-500">{{ channel.description }}</span>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div class="flex items-center justify-between">
                  <Badge :variant="isConnected(channel.provider, props.socialIntegrations) ? 'default' : 'secondary'">
                    {{ isConnected(channel.provider, props.socialIntegrations) ? 'Connected' : 'Not Connected' }}
                  </Badge>
                  <div class="flex gap-2">
                    <Button
                      v-if="isConnected(channel.provider, props.socialIntegrations)"
                      variant="outline"
                      size="sm"
                      @click="openConnectDialog(channel, 'social')"
                    >
                      Configure
                    </Button>
                    <Button
                      v-if="isConnected(channel.provider, props.socialIntegrations)"
                      variant="destructive"
                      size="sm"
                      @click="handleDisconnect(channel.provider, 'social')"
                    >
                      Disconnect
                    </Button>
                    <Button v-else variant="default" size="sm" @click="openConnectDialog(channel, 'social')">
                      Connect
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="email" class="mt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card
              v-for="provider in emailProviders"
              :key="provider.provider"
              class="hover:shadow-md transition-shadow"
            >
              <CardHeader class="pb-2">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-green-100 rounded flex items-center justify-center text-green-600">
                    <component :is="provider.icon" class="h-5 w-5" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">{{ provider.label }}</CardTitle>
                    <span class="text-xs text-gray-500">{{ provider.description }}</span>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div class="flex items-center justify-between">
                  <Badge :variant="isConnected(provider.provider, props.emailIntegrations) ? 'default' : 'secondary'">
                    {{ isConnected(provider.provider, props.emailIntegrations) ? 'Connected' : 'Not Connected' }}
                  </Badge>
                  <div class="flex gap-2">
                    <Button
                      v-if="isConnected(provider.provider, props.emailIntegrations)"
                      variant="outline"
                      size="sm"
                      @click="openConnectDialog(provider, 'email')"
                    >
                      Configure
                    </Button>
                    <Button
                      v-if="isConnected(provider.provider, props.emailIntegrations)"
                      variant="destructive"
                      size="sm"
                      @click="handleDisconnect(provider.provider, 'email')"
                    >
                      Disconnect
                    </Button>
                    <Button v-else variant="default" size="sm" @click="openConnectDialog(provider, 'email')">
                      Connect
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="sms" class="mt-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card
              v-for="provider in smsProviders"
              :key="provider.provider"
              class="hover:shadow-md transition-shadow"
            >
              <CardHeader class="pb-2">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-purple-100 rounded flex items-center justify-center text-purple-600">
                    <component :is="provider.icon" class="h-5 w-5" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">{{ provider.label }}</CardTitle>
                    <span class="text-xs text-gray-500">{{ provider.description }}</span>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div class="flex items-center justify-between">
                  <Badge :variant="isConnected(provider.provider, props.smsIntegrations) ? 'default' : 'secondary'">
                    {{ isConnected(provider.provider, props.smsIntegrations) ? 'Connected' : 'Not Connected' }}
                  </Badge>
                  <div class="flex gap-2">
                    <Button
                      v-if="isConnected(provider.provider, props.smsIntegrations)"
                      variant="outline"
                      size="sm"
                      @click="openConnectDialog(provider, 'sms')"
                    >
                      Configure
                    </Button>
                    <Button
                      v-if="isConnected(provider.provider, props.smsIntegrations)"
                      variant="destructive"
                      size="sm"
                      @click="handleDisconnect(provider.provider, 'sms')"
                    >
                      Disconnect
                    </Button>
                    <Button v-else variant="default" size="sm" @click="openConnectDialog(provider, 'sms')">
                      Connect
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="chat" class="mt-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-orange-100 rounded flex items-center justify-center text-orange-600">
                    <MessageCircleMore class="h-5 w-5" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">Live Chat</CardTitle>
                    <span class="text-xs text-gray-500">Reverb WebSocket, chat widget, queue rules</span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <Button
                    v-if="isConnected('chat', [props.chatIntegration].filter(Boolean))"
                    variant="outline"
                    size="sm"
                    @click="openChatDialog"
                  >
                    Configure
                  </Button>
                  <Button
                    v-if="isConnected('chat', [props.chatIntegration].filter(Boolean))"
                    variant="destructive"
                    size="sm"
                    @click="handleDisconnect('chat', 'chat')"
                  >
                    Disconnect
                  </Button>
                  <Button v-else variant="default" size="sm" @click="openChatDialog">
                    Connect
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent v-if="props.chatIntegration" class="p-0">
              <Table>
                <TableBody>
                  <TableRow>
                    <TableCell class="p-4 font-medium">Status</TableCell>
                    <TableCell class="p-4">
                      <Badge :variant="props.chatIntegration.connection_status === 'connected' ? 'default' : 'secondary'">
                        {{ props.chatIntegration.connection_status.replace('_', ' ') }}
                      </Badge>
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="field in chatFields" :key="field.key">
                    <TableCell class="p-4 font-medium">{{ field.label }}</TableCell>
                    <TableCell class="p-4 text-gray-600">
                      {{ props.chatIntegration.config?.[field.key] || '—' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
            <CardContent v-else class="p-8 text-center text-gray-500">
              <p>No Live Chat configuration. Click Connect to set up.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="ivr" class="mt-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-teal-100 rounded flex items-center justify-center text-teal-600">
                    <PhoneCall class="h-5 w-5" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">IVR</CardTitle>
                    <span class="text-xs text-gray-500">Transcript ingestion, HMAC, rate limits</span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <Button
                    v-if="isConnected('ivr', [props.ivrIntegration].filter(Boolean))"
                    variant="outline"
                    size="sm"
                    @click="openIvrDialog"
                  >
                    Configure
                  </Button>
                  <Button
                    v-if="isConnected('ivr', [props.ivrIntegration].filter(Boolean))"
                    variant="destructive"
                    size="sm"
                    @click="handleDisconnect('ivr', 'ivr')"
                  >
                    Disconnect
                  </Button>
                  <Button v-else variant="default" size="sm" @click="openIvrDialog">
                    Connect
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent v-if="props.ivrIntegration" class="p-0">
              <Table>
                <TableBody>
                  <TableRow>
                    <TableCell class="p-4 font-medium">Status</TableCell>
                    <TableCell class="p-4">
                      <Badge :variant="props.ivrIntegration.connection_status === 'connected' ? 'default' : 'secondary'">
                        {{ props.ivrIntegration.connection_status.replace('_', ' ') }}
                      </Badge>
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="field in ivrFields" :key="field.key">
                    <TableCell class="p-4 font-medium">{{ field.label }}</TableCell>
                    <TableCell class="p-4 text-gray-600">
                      {{ props.ivrIntegration.config?.[field.key] || '—' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
            <CardContent v-else class="p-8 text-center text-gray-500">
              <p>No IVR configuration. Click Connect to set up.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="field" class="mt-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-indigo-100 rounded flex items-center justify-center text-indigo-600">
                    <Smartphone class="h-5 w-5" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">Field Channel</CardTitle>
                    <span class="text-xs text-gray-500">Mobile API, sync, cache exclusions</span>
                  </div>
                </div>
                <div class="flex gap-2">
                  <Button
                    v-if="isConnected('field', [props.fieldIntegration].filter(Boolean))"
                    variant="outline"
                    size="sm"
                    @click="openFieldDialog"
                  >
                    Configure
                  </Button>
                  <Button
                    v-if="isConnected('field', [props.fieldIntegration].filter(Boolean))"
                    variant="destructive"
                    size="sm"
                    @click="handleDisconnect('field', 'field')"
                  >
                    Disconnect
                  </Button>
                  <Button v-else variant="default" size="sm" @click="openFieldDialog">
                    Connect
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent v-if="props.fieldIntegration" class="p-0">
              <Table>
                <TableBody>
                  <TableRow>
                    <TableCell class="p-4 font-medium">Status</TableCell>
                    <TableCell class="p-4">
                      <Badge :variant="props.fieldIntegration.connection_status === 'connected' ? 'default' : 'secondary'">
                        {{ props.fieldIntegration.connection_status.replace('_', ' ') }}
                      </Badge>
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="field in fieldFields" :key="field.key">
                    <TableCell class="p-4 font-medium">{{ field.label }}</TableCell>
                    <TableCell class="p-4 text-gray-600">
                      {{ props.fieldIntegration.config?.[field.key] || '—' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
            <CardContent v-else class="p-8 text-center text-gray-500">
              <p>No Field Channel configuration. Click Connect to set up.</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>

    <Dialog v-model:open="showConnectDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {{ dialogTab === 'chat' || dialogTab === 'ivr' || dialogTab === 'field' ? (isConnected(selectedChannel?.provider, [props[`${dialogTab}Integration`]].filter(Boolean)) ? 'Configure' : 'Connect') + ' ' + selectedChannel?.label : '' }}
            {{ dialogTab === 'social' || dialogTab === 'email' || dialogTab === 'sms' ? selectedChannel?.label : '' }}
          </DialogTitle>
        </DialogHeader>
        <div v-if="selectedChannel" class="space-y-4 py-4">
          <template v-if="dialogTab === 'chat'">
            <div class="space-y-2">
              <Label>Integration Name</Label>
              <Input v-model="channelName" placeholder="Live Chat" />
            </div>
            <div v-for="field in chatFields" :key="field.key" class="space-y-2">
              <Label>
                {{ field.label }}
                <span v-if="field.required" class="text-red-500">*</span>
              </Label>
              <Input
                v-model="formFields[field.key]"
                :type="field.type"
                :placeholder="`Enter ${field.label}`"
              />
            </div>
          </template>
          <template v-else-if="dialogTab === 'ivr'">
            <div class="space-y-2">
              <Label>Integration Name</Label>
              <Input v-model="channelName" placeholder="IVR" />
            </div>
            <div v-for="field in ivrFields" :key="field.key" class="space-y-2">
              <Label>
                {{ field.label }}
                <span v-if="field.required" class="text-red-500">*</span>
              </Label>
              <Input
                v-model="formFields[field.key]"
                :type="field.type"
                :placeholder="`Enter ${field.label}`"
              />
            </div>
          </template>
          <template v-else-if="dialogTab === 'field'">
            <div class="space-y-2">
              <Label>Integration Name</Label>
              <Input v-model="channelName" placeholder="Field Channel" />
            </div>
            <div v-for="field in fieldFields" :key="field.key" class="space-y-2">
              <Label>
                {{ field.label }}
                <span v-if="field.required" class="text-red-500">*</span>
              </Label>
              <Input
                v-model="formFields[field.key]"
                :type="field.type"
                :placeholder="`Enter ${field.label}`"
              />
            </div>
          </template>
          <template v-else>
            <div class="space-y-2">
              <Label>Integration Name</Label>
              <Input v-model="channelName" :placeholder="`My ${selectedChannel?.label} Integration`" />
            </div>
            <div v-for="field in selectedChannel.fields" :key="field.key" class="space-y-2">
              <Label>
                {{ field.label }}
                <span v-if="field.required" class="text-red-500">*</span>
              </Label>
              <Input
                v-model="formFields[field.key]"
                :type="field.type"
                :placeholder="`Enter ${field.label}`"
              />
            </div>
          </template>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="showConnectDialog = false">Cancel</Button>
          <Button @click="handleSave" :disabled="submitting">
            {{ submitting ? 'Saving...' : 'Save Channel' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
