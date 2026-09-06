import type { Auth } from '@/types/auth';
import type {
    ActiveWedding,
    WeddingCapabilities,
    WeddingRole,
    WeddingSummary,
} from '@/types/wedding';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            weddings: WeddingSummary[];
            activeWedding: ActiveWedding | null;
            activeMembership: { role: WeddingRole } | null;
            capabilities: WeddingCapabilities | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
