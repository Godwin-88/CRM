<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import { ChevronDown, ChevronRight, Menu, HelpCircle, Settings2, Users, Megaphone, Shield, Award, MessageSquare, Briefcase, FileText, DollarSign, BarChart3 } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { setI18nLocale, getI18nLocale, supportedLocales } from "@/lib/i18n";
import HelpPanel from "@/Components/HelpPanel.vue";
import AssistantIcon from "@/Components/CRM/AssistantIcon.vue";
import ToastNotification from "@/Components/ToastNotification.vue";
import AssistantChatPopup from "@/Components/CRM/AssistantChatPopup.vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CheckCircle2, Circle, X } from "lucide-vue-next";

interface User {
    id: string;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
}

interface ChecklistItem {
    key: string;
    title: string;
    description: string;
    route: string;
    article_slug: string;
    completed: boolean;
    dismissed: boolean;
}

const page = usePage();
const user = computed(() => page.props.user as User | null);
const userRoles = computed(() => user.value?.roles || []);
const userPermissions = computed(() => user.value?.permissions || []);
const isPrivileged = computed(() => page.props.is_privileged as boolean);

const isExpanded = ref(true);
const isHovered = ref(false);
const isMobileMenuOpen = ref(false);
const openMenuItem = ref<string | null>(null);
const showHelpPanel = ref(false);
const showOnboardingDialog = ref(false);
const checklist = ref<ChecklistItem[]>([]);

const onboardingChecklistSeen = computed({
    get: () => localStorage.getItem('onboarding_checklist_seen') === 'true',
    set: (value: boolean) => localStorage.setItem('onboarding_checklist_seen', String(value)),
});

const hasIncompleteItems = computed(() => 
    checklist.value.some(item => !item.completed && !item.dismissed)
);

const currentRoute = computed(() => page.url || '');

const hasPermission = (permission: string): boolean => {
    return userPermissions.value.includes(permission);
};

const hasAnyRole = (...roles: string[]): boolean => {
    return roles.some(r => userRoles.value.includes(r));
};

const canViewAdmin = computed(() => hasAnyRole('admin', 'manager'));

const sidebarWidth = computed(() => {
    if (!isExpanded.value && !isHovered.value) return "w-20";
    return "w-64";
});

const menuItems = computed(() => [
    {
        title: "Overview",
        icon: BarChart3,
        show: true,
        children: [
            { href: "/admin/analytics/dashboard", label: "Dashboard" },
        ],
    },
    {
        title: "Account Management",
        icon: Users,
        show: true,
        children: [
            { href: "/accounts", label: "Accounts" },
            { href: "/contacts", label: "Contacts" },
        ],
    },
    {
        title: "Campaigns",
        icon: Megaphone,
        show: hasAnyRole('admin', 'manager'),
        children: [
            { href: "/admin/campaigns", label: "Campaigns" },
            { href: "/admin/campaign-templates", label: "Campaign Templates" },
            { href: "/admin/drip-sequences", label: "Drip Sequences" },
            { href: "/admin/social-posts", label: "Social Posts" },
            { href: "/admin/analytics/campaigns-dashboard", label: "Analytics Dashboard" },
            { href: "/admin/tags", label: "Tags" },
        ],
    },
    {
        title: "Admin",
        icon: Shield,
        show: hasAnyRole('admin', 'manager'),
        children: [
            { href: "/admin/pipelines", label: "Pipelines", requiredPermission: "pipelines.manage" },
            { href: "/admin/win-loss-reasons", label: "Win/Loss Reasons", requiredPermission: "win_loss_reasons.manage" },
            { href: "/admin/quote-templates", label: "Quote Templates", requiredPermission: "quote_templates.manage" },
            { href: "/admin/quotes", label: "Quotes", requiredPermission: "quotes.view" },
            { href: "/admin/scoring-rules", label: "Scoring Rules", requiredPermission: "scoring_rules.manage" },
            { href: "/admin/custom-fields", label: "Custom Fields", requiredPermission: "custom_fields.manage" },
            { href: "/admin/duplicates", label: "Duplicate Merge" },
            { href: "/admin/rbac", label: "Role Management" },
        ],
    },
    {
        title: "Loyalty & CX",
        icon: Award,
        show: hasAnyRole('admin', 'manager'),
        children: [
            { href: "/admin/loyalty-programs", label: "Programs", requiredPermission: "loyalty.adjust" },
            { href: "/admin/loyalty/ledger", label: "Points Ledger", requiredPermission: "loyalty.adjust" },
            { href: "/admin/cx-insights", label: "Insights" },
            { href: "/admin/service-delivery", label: "Service" },
            { href: "/admin/customer-journeys", label: "Journeys" },
        ],
    },
    {
        title: "OmniChannel",
        icon: MessageSquare,
        show: hasAnyRole('admin', 'manager', 'agent'),
        children: [
            { href: "/admin/omni/workspace", label: "Workspace" },
            { href: "/admin/omni/tools", label: "Agent Tools", requiredPermission: "contacts.edit" },
            { href: "/admin/omni/supervisor", label: "Supervisor", requiredPermission: "security.events" },
            { href: "/admin/omni/settings", label: "Settings", requiredPermission: "integrations.manage" },
        ],
    },
    {
        title: "Deal Management",
        icon: Briefcase,
        show: hasAnyRole('admin', 'manager', 'agent'),
        children: [
            { href: "/deals", label: "Deals", requiredPermission: "deals.view" },
            { href: "/deals/board", label: "Kanban Board", requiredPermission: "deals.view" },
            { href: "/admin/deal-automations", label: "Automations" },
            { href: "/quotes", label: "Quotes" },
            { href: "/analytics/forecast", label: "Forecast" },
        ],
    },
    {
        title: "Contracts & Legal",
        icon: FileText,
        show: hasAnyRole('admin', 'manager', 'agent'),
        children: [
            { href: "/contracts", label: "Contracts", requiredPermission: "contracts.view" },
            { href: "/legal", label: "Legal Matters", requiredPermission: "legal_matters.view" },
        ],
    },
    {
        title: "Finance & Procurement",
        icon: DollarSign,
        show: hasAnyRole('admin', 'manager', 'finance-manager', 'operations-manager'),
        children: [
            { href: "/invoices", label: "Invoices", requiredPermission: "invoices.view" },
            { href: "/vendors", label: "Vendors", requiredPermission: "vendors.view" },
            { href: "/purchase-orders", label: "Purchase Orders", requiredPermission: "procurement.create" },
            { href: "/assets", label: "Assets", requiredPermission: "assets.view" },
            { href: "/banking", label: "Banking", requiredPermission: "banking.view" },
            { href: "/employees", label: "Employees" },
            { href: "/finance", label: "Finance Dashboard" },
        ],
    },
    {
        title: "Analytics & Intelligence",
        icon: BarChart3,
        show: hasAnyRole('admin', 'manager'),
        children: [
            { href: "/admin/analytics/customer", label: "Customer Analytics" },
            { href: "/admin/analytics/growth", label: "Growth Analytics" },
            { href: "/admin/analytics/finance", label: "Finance Analytics", requiredPermission: "analytics.finance" },
            {
                href: "/admin/analytics/predictive-scoring",
                label: "Predictive Scoring",
            },
            {
                href: "/admin/analytics/report-builder",
                label: "Report Builder",
            },
            { href: "/admin/clv-analytics", label: "CLV Analytics" },
        ],
    },
    {
        title: "Integrations & API",
        icon: Settings2,
        show: hasAnyRole('admin', 'manager'),
        children: [
            { href: "/admin/integrations", label: "Service Registry" },
            { href: "/admin/integrations/marketplace", label: "Marketplace" },
            { href: "/admin/api-tokens", label: "API Tokens" },
            { href: "/admin/oauth-clients", label: "OAuth2 Apps" },
            { href: "/admin/integrations/webhooks", label: "Webhooks" },
            { href: "/docs", label: "Developer Portal" },
        ],
    },
]);

onMounted(() => {
    const saved = localStorage.getItem("sidebarExpanded");
    if (saved !== null) {
        isExpanded.value = JSON.parse(saved);
    }

    // Check if onboarding checklist should be shown (only once per user)
    if (currentRoute.value.includes('dashboard') && !onboardingChecklistSeen.value) {
        fetch('/onboarding/checklist', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(res => res.json())
        .then((data) => {
            checklist.value = data.checklist || [];
            if (checklist.value.some((item: ChecklistItem) => !item.completed && !item.dismissed)) {
                showOnboardingDialog.value = true;
            }
        })
        .catch(() => {});
    }
});

const markChecklistComplete = (key: string) => {
    router.post('/onboarding/checklist/complete', { checklist_item_key: key }, {
        preserveScroll: true,
    });
};

const dismissChecklistItem = (key: string) => {
    router.post('/onboarding/checklist/dismiss', { checklist_item_key: key }, {
        preserveScroll: true,
    });
};

const closeOnboarding = () => {
    onboardingChecklistSeen.value = true;
    showOnboardingDialog.value = false;
};

// Close dialog when all items completed
watch(checklist, () => {
    if (showOnboardingDialog.value && !hasIncompleteItems.value) {
        onboardingChecklistSeen.value = true;
        showOnboardingDialog.value = false;
    }
});

watch(currentRoute, () => {
    isMobileMenuOpen.value = false;
});

watch(isExpanded, (value) => {
    localStorage.setItem("sidebarExpanded", JSON.stringify(value));
});

const toggleSidebar = () => {
    isExpanded.value = !isExpanded.value;
};

const toggleMenuItem = (title: string) => {
    if (openMenuItem.value === title) {
        openMenuItem.value = null;
    } else {
        openMenuItem.value = title;
    }
};
</script>

<template>
    <div class="min-h-screen flex bg-gray-50 overflow-x-hidden">
        <!-- Mobile Sidebar Overlay -->
        <div 
            v-if="isMobileMenuOpen" 
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            @click="isMobileMenuOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="
                cn(
                    'bg-gray-900 text-white transition-all duration-300 ease-in-out fixed lg:sticky top-0 h-screen z-50 flex flex-col',
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                    sidebarWidth,
                )
            "
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
        >
            <!-- Header with toggle -->
            <div
                class="flex items-center justify-between p-4 border-b border-gray-800 shrink-0"
            >
                <div class="flex items-center gap-2 overflow-hidden">
                    <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center shrink-0">
                        <span class="text-white font-bold text-xs">CRM</span>
                    </div>
                    <h2
                        v-show="isExpanded || isHovered"
                        class="text-xl font-bold text-white truncate transition-opacity duration-200"
                    >
                        Enterprise
                    </h2>
                </div>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-gray-400 hover:text-white hover:bg-gray-800 hidden lg:flex"
                    @click="toggleSidebar"
                >
                    <component
                        :is="isExpanded ? ChevronRight : ChevronDown"
                        class="h-5 w-5"
                    />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-gray-400 hover:text-white hover:bg-gray-800 lg:hidden"
                    @click="isMobileMenuOpen = false"
                >
                    <X class="h-5 w-5" />
                </Button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                <div
                    v-for="item in menuItems"
                    :key="item.title"
                    v-show="item.show"
                    class="space-y-1"
                >
                    <Collapsible 
                        :open="openMenuItem === item.title" 
                        @update:open="toggleMenuItem(item.title)"
                    >
                        <CollapsibleTrigger
                            class="flex items-center w-full gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-gray-800 hover:text-blue-400 transition-all group"
                        >
                            <component :is="item.icon" class="h-5 w-5 shrink-0 group-hover:scale-110 transition-transform" />
                            <span
                                v-show="isExpanded || isHovered"
                                class="truncate flex-1 text-left transition-opacity duration-200"
                                >{{ item.title }}</span
                            >
                            <ChevronDown 
                                v-show="isExpanded || isHovered"
                                :class="cn('h-4 w-4 transition-transform duration-200', openMenuItem === item.title && 'rotate-180')" 
                            />
                        </CollapsibleTrigger>
<CollapsibleContent>
                             <div class="mt-1 space-y-1 pl-8 pr-2">
                                 <template v-for="child in item.children" :key="child.isHeader ? 'header-' + child.label : child.href">
                                     <div 
                                         v-if="child.isHeader || (child.requiredPermission && !hasPermission(child.requiredPermission))"
                                         class="text-[9px] uppercase font-bold text-gray-500 tracking-wider pt-2.5 pb-1 px-3 border-t border-gray-800/40 mt-2 first:mt-0 first:border-0"
                                     >
                                         {{ child.label }}
                                     </div>
                                     <Link
                                         v-else-if="!child.requiredPermission || hasPermission(child.requiredPermission)"
                                         :href="child.href"
                                         class="block px-3 py-1.5 text-xs text-gray-450 rounded-md hover:bg-gray-800 hover:text-blue-300 transition-colors truncate"
                                         @click="isMobileMenuOpen = false"
                                     >
                                         {{ child.label }}
                                     </Link>
                                 </template>
                             </div>
                        </CollapsibleContent>
                    </Collapsible>
                </div>
            </nav>

            <!-- User info at bottom -->
            <div
                class="p-4 border-t border-gray-800 bg-gray-900 shrink-0"
            >
                <div class="flex items-center gap-3 mb-4 overflow-hidden" v-show="isExpanded || isHovered">
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-gray-300">{{ user?.name?.charAt(0) }}</span>
                    </div>
                    <div class="truncate">
                        <p class="text-sm font-medium text-white truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ user?.email }}</p>
                    </div>
                </div>
                
                <div
                    v-show="isPrivileged && (isExpanded || isHovered)"
                    class="mb-3"
                >
                    <span
                        class="inline-flex items-center px-2 py-1 text-[10px] font-bold bg-yellow-600/20 text-yellow-500 border border-yellow-600/30 rounded w-full justify-center uppercase tracking-wider"
                    >
                        Privileged Mode
                    </span>
                </div>
                
                <Button
                    variant="ghost"
                    size="sm"
                    class="w-full justify-start text-gray-400 hover:text-white hover:bg-gray-800 transition-colors group"
                    as-child
                >
                    <Link href="/logout" method="post" class="flex items-center gap-3">
                        <X class="h-4 w-4 group-hover:rotate-90 transition-transform" />
                        <span v-show="isExpanded || isHovered">Logout</span>
                    </Link>
                </Button>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-gray-200 px-4 h-16 flex items-center justify-between sticky top-0 z-30 shadow-sm">
                <div class="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="lg:hidden text-gray-500"
                        @click="isMobileMenuOpen = true"
                    >
                        <Menu class="h-6 w-6" />
                    </Button>
                    <div class="text-sm font-medium text-gray-600 truncate hidden sm:block">
                        {{ currentRoute }}
                    </div>
                </div>
                
<div class="flex items-center gap-3">
                     <div v-if="!isExpanded && !isHovered" class="lg:hidden">
                          <span class="text-xl font-bold text-gray-900">CRM</span>
                     </div>
                     <div class="flex-1"></div>
                     <div class="flex items-center gap-2">
                         <AssistantIcon />
                         <span class="hidden sm:inline text-xs font-medium text-purple-700">Ask AI</span>
                         <HelpPanel :current-route="currentRoute" :user-roles="userRoles" v-model:open="showHelpPanel" />
                         <span class="hidden sm:inline text-xs font-medium text-blue-600">See Docs</span>
                     </div>
                 </div>
            </header>
            <div class="flex-1 p-4 lg:p-8">
                <div class="max-w-screen-2xl mx-auto">
                    <slot />
                </div>
            </div>
        </main>

        <!-- Onboarding Checklist Dialog -->
        <Dialog v-model:open="showOnboardingDialog">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Getting Started</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 max-h-[70vh] overflow-y-auto">
                    <div v-for="item in checklist" :key="item.key" :class="{'opacity-60': item.dismissed}">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <component :is="item.completed ? CheckCircle2 : Circle" class="h-5 w-5" :class="item.completed ? 'text-green-600' : 'text-gray-400'" />
                                        {{ item.title }}
                                    </div>
                                    <Button v-if="!item.dismissed" @click="dismissChecklistItem(item.key)" variant="ghost" size="icon" class="text-gray-400 hover:text-gray-600">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </CardTitle>
                            </CardHeader>
                            <CardContent v-show="!item.dismissed">
                                <p class="text-sm text-gray-600 mb-4">{{ item.description }}</p>
                                <div class="flex gap-3">
                                    <Button v-if="!item.completed" @click="markChecklistComplete(item.key)" size="sm">
                                        Mark Complete
                                    </Button>
                                    <Link :href="item.route" class="text-sm text-blue-600 hover:underline">
                                        Go to Screen →
                                    </Link>
                                    <Link v-if="item.article_slug" :href="`/docs/${item.article_slug}`" class="text-sm text-blue-600 hover:underline">
                                        Read Guide
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    <div v-if="checklist.length === 0" class="text-center py-8 text-gray-500">
                        No checklist items available for your role.
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <ToastNotification />
        <AssistantChatPopup />
    </div>
</template>
