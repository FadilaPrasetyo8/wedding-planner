import { Head } from '@inertiajs/react';
import { CalendarHeart, CheckCircle2, LockKeyhole } from 'lucide-react';
import { dashboard } from '@/routes';

const readinessItems = [
    {
        icon: CheckCircle2,
        title: 'Account ready',
        description: 'Your secure planning account is set up.',
    },
    {
        icon: LockKeyhole,
        title: 'Private by default',
        description:
            'Your account and settings are protected by Laravel session authentication.',
    },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div className="mx-auto w-full max-w-5xl">
                    <div className="max-w-2xl">
                        <p className="text-primary text-sm font-semibold tracking-[0.14em] uppercase">
                            Welcome
                        </p>
                        <h1 className="font-display mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Your planning space is ready.
                        </h1>
                        <p className="text-muted-foreground mt-4 text-base leading-7">
                            The secure foundation is in place. Wedding setup and
                            planning tools will arrive in the next milestone.
                        </p>
                    </div>

                    <div className="mt-10 grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
                        <section
                            className="bg-card rounded-[var(--radius)] border p-6 shadow-[0_2px_0_rgb(23_21_18_/_0.08)] sm:p-8"
                            aria-labelledby="workspace-status-title"
                        >
                            <div
                                className="bg-accent text-accent-foreground flex size-11 items-center justify-center rounded-xl"
                                aria-hidden="true"
                            >
                                <CalendarHeart className="size-5" />
                            </div>
                            <h2
                                id="workspace-status-title"
                                className="mt-6 text-xl font-semibold"
                            >
                                A clear beginning
                            </h2>
                            <p className="text-muted-foreground mt-3 max-w-xl leading-7">
                                This foundation keeps authentication, profile
                                security, and application navigation dependable
                                before planning data is introduced.
                            </p>
                            <div className="bg-muted/40 mt-8 rounded-xl border border-dashed p-5">
                                <p className="font-medium">
                                    No wedding workspace yet
                                </p>
                                <p className="text-muted-foreground mt-1 text-sm leading-6">
                                    Workspace creation belongs to M1 and is
                                    intentionally not available in this
                                    foundation milestone.
                                </p>
                            </div>
                        </section>

                        <aside
                            className="bg-card rounded-[var(--radius)] border p-6 shadow-[0_2px_0_rgb(23_21_18_/_0.08)]"
                            aria-labelledby="readiness-title"
                        >
                            <h2 id="readiness-title" className="font-semibold">
                                Foundation status
                            </h2>
                            <div className="mt-6 space-y-6">
                                {readinessItems.map(
                                    ({ icon: Icon, title, description }) => (
                                        <div key={title} className="flex gap-3">
                                            <Icon
                                                className="text-primary mt-0.5 size-5 shrink-0"
                                                aria-hidden="true"
                                            />
                                            <div>
                                                <h3 className="text-sm font-medium">
                                                    {title}
                                                </h3>
                                                <p className="text-muted-foreground mt-1 text-sm leading-6">
                                                    {description}
                                                </p>
                                            </div>
                                        </div>
                                    ),
                                )}
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
