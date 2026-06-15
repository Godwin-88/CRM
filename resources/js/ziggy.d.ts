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

declare module "ziggy-js" {
    export function route(name: string, params?: Record<string, any> | string | number, absolute?: boolean): string;
    export { RouteConfig };
    export const ZiggyVue: { install: (app: any, config?: RouteConfig) => void };
    export const useRoute: (config?: RouteConfig) => typeof route;
}

declare global {
    function route(name: string, params?: Record<string, any> | string | number, absolute?: boolean): string;
    const route: typeof import("ziggy-js").route;
    interface Window {
        Ziggy: RouteConfig;
    }
}

export {};