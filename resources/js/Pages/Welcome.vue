<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { ChevronRight, BarChart3, Users, Target, Shield, Zap, MessageSquare, FileText, PieChart, Phone, Mail, Globe } from 'lucide-vue-next';

const features = [
  {
    icon: Users,
    title: 'Contact & Account Management',
    description: 'Unified customer profiles with dynamic segmentation, CLV scoring, and omnichannel interaction history.',
  },
  {
    icon: Target,
    title: 'Sales Pipeline & Deal Management',
    description: 'Multi-pipeline Kanban boards, automated stage workflows, and predictive revenue forecasting.',
  },
  {
    icon: MessageSquare,
    title: 'Omni-Channel Engagement',
    description: 'Email, SMS, voice, live chat, and social integrations with real-time WebSocket updates.',
  },
  {
    icon: BarChart3,
    title: 'Marketing & Campaigns',
    description: 'Multi-channel campaign builder with A/B testing, segmentation, and ROI analytics.',
  },
  {
    icon: Shield,
    title: 'Enterprise Security',
    description: 'Role-based access control, MFA, SSO (OAuth2/SAML), and GDPR compliance.',
  },
  {
    icon: PieChart,
    title: 'Analytics & Intelligence',
    description: 'Descriptive, diagnostic, and predictive analytics with custom report builder.',
  },
  {
    icon: FileText,
    title: 'Contracts & E-Signature',
    description: 'Contract lifecycle management with DocuSign integration and automated renewals.',
  },
  {
    icon: Zap,
    title: 'API & Integrations',
    description: 'RESTful API v1, webhooks, and pre-built connectors for Xero, QuickBooks, and Slack.',
  },
];

const channels = [
  { icon: Globe, label: 'Web Portal' },
  { icon: Phone, label: 'Voice' },
  { icon: Mail, label: 'Email' },
  { icon: MessageSquare, label: 'SMS' },
  { icon: Phone, label: 'Mobile' },
  { icon: Globe, label: 'Kiosk' },
];
</script>

<template>
  <Head title="Enterprise CRM - Unified Customer Relationship Platform" />
  
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <header class="border-b border-gray-200 bg-white/80 backdrop-blur-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-sm">CRM</span>
            </div>
            <span class="text-xl font-semibold text-gray-900">Enterprise CRM</span>
          </div>
          <nav class="flex items-center space-x-4">
            <Link href="/login" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">Sign In</Link>
            <Button as-child>
              <Link href="/register">Get Started</Link>
            </Button>
          </nav>
        </div>
      </div>
    </header>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="text-center max-w-3xl mx-auto">
        <h1 class="text-5xl font-bold text-gray-900 tracking-tight">
          Transform Customer Relationships into Revenue
        </h1>
        <p class="mt-6 text-xl text-gray-600">
          Enterprise-grade CRM platform unifying contacts, sales, marketing, support, and analytics across all channels.
          Built for scale, designed for growth.
        </p>
        <div class="mt-10 flex items-center justify-center gap-x-6">
          <Button size="lg" as-child>
            <Link href="/register" class="flex items-center">
              Start Free Trial
              <ChevronRight class="ml-2 h-4 w-4" />
            </Link>
          </Button>
          <Link href="/demo" class="text-sm font-medium text-gray-600 hover:text-gray-900">
            Watch Demo →
          </Link>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
      <div class="bg-blue-600 rounded-2xl p-8 lg:p-12">
        <h2 class="text-2xl font-bold text-white mb-8 text-center">Omni-Channel Capability</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
          <div v-for="channel in channels" :key="channel.label" class="flex flex-col items-center justify-center p-4 bg-white/10 rounded-xl backdrop-blur-sm">
            <component :is="channel.icon" class="h-8 w-8 text-white mb-2" />
            <span class="text-sm text-white/90">{{ channel.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="text-center mb-16">
        <h2 class="text-3xl font-bold text-gray-900">Complete CRM Suite</h2>
        <p class="mt-4 text-lg text-gray-600">
          All capabilities from the Digital Capability Canvas, unified in one platform.
        </p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card v-for="feature in features" :key="feature.title" class="border border-gray-200 hover:shadow-lg transition-shadow">
          <CardHeader>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
              <component :is="feature.icon" class="h-6 w-6 text-blue-600" />
            </div>
            <CardTitle class="text-lg">{{ feature.title }}</CardTitle>
            <CardDescription>{{ feature.description }}</CardDescription>
          </CardHeader>
        </Card>
      </div>
    </section>

    <section class="bg-gray-900 py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">For Enterprise Scale & Compliance</h2>
        <p class="text-lg text-gray-300 mb-8 max-w-2xl mx-auto">
          Built on Laravel with MySQL/PostgreSQL, featuring enterprise security, audit trails, 
          GDPR compliance, and integrations with your existing tools.
        </p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-3xl mx-auto">
          <div>
            <div class="text-3xl font-bold text-blue-400">11</div>
            <div class="text-sm text-gray-400 mt-1">Digital Domains Covered</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-blue-400">80+</div>
            <div class="text-sm text-gray-400 mt-1">Business Capabilities</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-blue-400">15+</div>
            <div class="text-sm text-gray-400 mt-1">Integrations Ready</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-blue-400">SOC2</div>
            <div class="text-sm text-gray-400 mt-1">Compliance Ready</div>
          </div>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 lg:p-12">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">AI-Powered Customer Insights</h3>
            <p class="text-gray-600 mb-6">
              Predictive lead scoring, CLV analytics, churn prediction, and automated recommendations 
              powered by cognitive intelligence engine.
            </p>
            <ul class="space-y-3 text-gray-600">
              <li class="flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                Customer Lifetime Value predictions
              </li>
              <li class="flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                Deal win probability scoring
              </li>
              <li class="flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                Automated reactivation triggers
              </li>
              <li class="flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                Cohort analysis & retention trends
              </li>
            </ul>
          </div>
          <div class="bg-gray-100 rounded-xl aspect-video flex items-center justify-center">
            <BarChart3 class="h-24 w-24 text-gray-400" />
          </div>
        </div>
      </div>
    </section>

    <footer class="bg-gray-50 border-t border-gray-200 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between">
          <div class="flex items-center space-x-2 mb-4 md:mb-0">
            <div class="w-6 h-6 bg-blue-600 rounded flex items-center justify-center">
              <span class="text-white font-bold text-xs">CRM</span>
            </div>
            <span class="text-sm text-gray-600">Enterprise CRM Platform</span>
          </div>
          <div class="flex space-x-6 text-sm text-gray-600">
            <Link href="/privacy" class="hover:text-gray-900">Privacy</Link>
            <Link href="/terms" class="hover:text-gray-900">Terms</Link>
            <Link href="/docs" class="hover:text-gray-900">Documentation</Link>
            <Link href="/api/docs" class="hover:text-gray-900">API Docs</Link>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
