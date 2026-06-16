<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import { ChevronDown, ChevronRight, Menu, HelpCircle } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import HelpPanel from "@/Components/HelpPanel.vue";

interface User {
    id: string;
    name: string;
    email: string;
    roles: string[];
}

const page = usePage();
const user = computed(() => page.props.user as User | null);
const userRoles = computed(() => user.value?.roles || []);
const isPrivileged = computed(() => page.props.is_privileged as boolean);
const currentRoute = computed(() => page.url || '');

const isExpanded = ref(true);
const isHovered = ref(false);
const showHelpPanel = ref(false);

const canViewAdmin = computed(
    () =>
        userRoles.value.includes("admin") ||
        userRoles.value.includes("manager"),
);

const sidebarWidth = computed(() => {
    if (!isExpanded.value && !isHovered.value) return "w-20";
    return "w-64";
});

const menuItems = computed(() => [
    {
        title: "Account Management",
        icon: Menu,
        show: true,
        children: [
            { href: "/accounts", label: "Accounts" },
            { href: "/contacts", label: "Contacts" },
        ],
    },
    {
        title: "Campaigns",
        icon: Menu,
        show: canViewAdmin.value,
        children: [
            { href: "/admin/campaigns", label: "Campaigns" },
            { href: "/admin/campaign-templates", label: "Campaign Templates" },
            { href: "/admin/drip-sequences", label: "Drip Sequences" },
            { href: "/admin/social-posts", label: "Social Posts" },
            { href: "/admin/analytics/campaigns", label: "Campaign Analytics" },
        ],
    },
    {
        title: "Admin",
        icon: Menu,
        show: canViewAdmin.value,
        children: [
            { href: "/admin/pipelines", label: "Pipelines" },
            { href: "/admin/win-loss-reasons", label: "Win/Loss Reasons" },
            { href: "/admin/quote-templates", label: "Quote Templates" },
            { href: "/admin/quotes", label: "Quotes" },
            { href: "/admin/scoring-rules", label: "Scoring Rules" },
            { href: "/admin/custom-fields", label: "Custom Fields" },
            { href: "/admin/duplicates", label: "Duplicate Merge" },
        ],
    },
    {
        title: "Loyalty & CX",
        icon: Menu,
        show: canViewAdmin.value,
        children: [
            { href: "/admin/loyalty", label: "Loyalty Program" },
            { href: "/admin/surveys", label: "Surveys" },
            { href: "/admin/sla", label: "SLA Center" },
            { href: "/admin/onboarding", label: "Onboarding" },
            { href: "/admin/journeys", label: "Guided Journeys" },
            { href: "/admin/reactivation", label: "Reactivation" },
            { href: "/admin/clv-analytics", label: "CLV Analytics" },
        ],
    },
    {
        title: "OmniChannel",
        icon: Menu,
        show: canViewAdmin.value,
        children: [
            { href: "/admin/omni/dashboard", label: "Dashboard" },
            { href: "/admin/interactions", label: "Interactions" },
            { href: "/admin/interactions/inbox", label: "Inbox" },
            { href: "/admin/interactions/channels", label: "Channels" },
            { href: "/admin/interactions/unmatched", label: "Unmatched" },
            { href: "/admin/omni/tickets", label: "Tickets" },
            { href: "/admin/omni/contact-center", label: "Contact Center" },
            { href: "/admin/omni/kiosk", label: "Kiosk" },
        ],
    },
    {
        title: "Deal Management",
        icon: Menu,
        show: true,
        children: [
            { href: "/deals", label: "Deals" },
            { href: "/deals/board", label: "Kanban Board" },
            { href: "/admin/deal-automations", label: "Automations" },
            { href: "/quotes", label: "Quotes" },
            { href: "/analytics/forecast", label: "Forecast" },
        ],
    },
    {
        title: "Contracts & Legal",
        icon: Menu,
        show: true,
        children: [
            { href: "/contracts", label: "Contracts" },
            { href: "/legal", label: "Legal Matters" },
        ],
    },
    {
        title: "Finance & Procurement",
        icon: Menu,
        show: true,
        children: [
            { href: "/invoices", label: "Invoices" },
            { href: "/vendors", label: "Vendors" },
            { href: "/purchase-orders", label: "Purchase Orders" },
            { href: "/assets", label: "Assets" },
            { href: "/banking", label: "Banking" },
            { href: "/employees", label: "Employees" },
            { href: "/finance", label: "Finance Dashboard" },
        ],
    },
    {
        title: "Analytics & Intelligence",
        icon: Menu,
        show: true,
        children: [
            {
                href: "/admin/analytics/dashboard",
                label: "Analytics Dashboard",
            },
            { href: "/admin/analytics/customer", label: "Customer Analytics" },
            { href: "/admin/analytics/growth", label: "Growth Analytics" },
            { href: "/admin/analytics/finance", label: "Finance Analytics" },
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
]);

onMounted(() => {
    const saved = localStorage.getItem("sidebarExpanded");
    if (saved !== null) {
        isExpanded.value = JSON.parse(saved);
    }
});

watch(isExpanded, (value) => {
    localStorage.setItem("sidebarExpanded", JSON.stringify(value));
});

const toggleSidebar = () => {
    isExpanded.value = !isExpanded.value;
};
</script>

<template>
    <div class="min-h-screen flex bg-gray-50">
        <!-- Sidebar -->
        <aside
            :class="
                cn(
                    'bg-gray-900 text-white transition-all duration-300 ease-in-out relative h-screen',
                    sidebarWidth,
                )
            "
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
        >
            <!-- Header with toggle -->
            <div
                class="flex items-center justify-between p-4 border-b border-gray-800"
            >
                <h2
                    v-show="isExpanded || isHovered"
                    class="text-xl font-bold text-white truncate"
                >
                    CRM
                </h2>
                <Button
                    variant="ghost"
                    size="icon"
                    class="text-gray-400 hover:text-white hover:bg-gray-800"
                    @click="toggleSidebar"
                >
                    <component
                        :is="isExpanded ? ChevronRight : ChevronDown"
                        class="h-5 w-5"
                    />
                </Button>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-2 overflow-y-auto h-[calc(100vh-8rem)]">
                <div
                    v-for="item in menuItems"
                    :key="item.title"
                    v-show="item.show"
                    class="space-y-1"
                >
                    <Collapsible :default-open="true">
                        <CollapsibleTrigger
                            class="flex items-center w-full gap-3 px-3 py-2 text-sm font-medium rounded-md hover:bg-gray-800 hover:text-blue-400 transition-colors"
                        >
                            <ChevronDown class="h-4 w-4" />
                            <span
                                v-show="isExpanded || isHovered"
                                class="truncate flex-1 text-left"
                                >{{ item.title }}</span
                            >
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <div class="mt-1 space-y-1">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.href"
                                    :href="child.href"
                                    class="block px-6 py-1.5 text-sm text-gray-300 rounded-md hover:bg-gray-800 hover:text-blue-400 transition-colors truncate"
                                >
                                    {{ child.label }}
                                </Link>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>
                </div>
            </nav>

            <!-- User info at bottom -->
            <div
                class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-800 bg-gray-900"
            >
                <div
                    v-show="isExpanded || isHovered"
                    class="text-xs text-gray-400 truncate mb-2"
                >
                    {{ user?.email }}
                </div>
                <div
                    v-show="isPrivileged && (isExpanded || isHovered)"
                    class="mb-2"
                >
                    <span
                        class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-600 text-white rounded"
                    >
                        PRIVILEGED MODE
                    </span>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    class="w-full justify-start text-gray-300 hover:text-white hover:bg-gray-800"
                    as-child
                >
                    <Link href="/logout" method="post">Logout</Link>
                </Button>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 flex flex-col">
            <header class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center">
                <div class="text-sm text-gray-500" v-if="isExpanded || isHovered">
                    {{ currentRoute }}
                </div>
                <div class="flex-1"></div>
                <HelpPanel :current-route="currentRoute" :user-roles="userRoles" v-model:open="showHelpPanel" />
            </header>
            <div class="flex-1 p-6">
                <slot />
            </div>
        </main>
    </div>
</template>
