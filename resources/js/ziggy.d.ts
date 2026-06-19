export interface RouteConfig {
    routes: Record<string, {
        uri: string;
        methods: string[];
        bindings?: Record<string, string>;
        wheres?: Record<string, string>;
        domain?: string;
    }>;
    url: string;
    location?: { host: string; pathname: string; search: string };
    absolute?: boolean;
}

declare global {
    function route(name: string, params?: any, absolute?: boolean): string;
    interface Window {
        Ziggy: RouteConfig;
        userId?: number;
    }
}

declare module 'vue' {
    export interface ComponentCustomProperties {
        $route: typeof route;
        $ziggy: RouteConfig;
    }
}
