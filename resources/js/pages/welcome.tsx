import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarHeart,
    CheckCircle2,
    WalletCards,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, register } from '@/routes';

const foundations = [
    {
        icon: CalendarHeart,
        title: 'One calm workspace',
        description:
            'Bring your plans, dates, and decisions into one trusted place.',
    },
    {
        icon: WalletCards,
        title: 'Clear financial planning',
        description:
            'Prepare for budgets and flexible payment schedules without spreadsheet sprawl.',
    },
    {
        icon: CheckCircle2,
        title: 'Progress you can trust',
        description:
            'Keep important details visible while the celebration takes shape.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Wedding planning, thoughtfully organized" />

            <div className="bg-background text-foreground min-h-svh">
                <header className="border-border/70 bg-background/95 border-b">
                    <div className="mx-auto flex h-18 w-full max-w-6xl items-center justify-between px-6 lg:px-8">
                        <Link
                            href={home()}
                            className="focus-visible:ring-ring flex items-center gap-3 rounded-md focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <span
                                className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-full"
                                aria-hidden="true"
                            >
                                <CalendarHeart className="size-4.5" />
                            </span>
                            <span className="font-semibold tracking-tight">
                                Wedding Planner
                            </span>
                        </Link>

                        <nav
                            aria-label="Account navigation"
                            className="flex items-center gap-2"
                        >
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Open workspace
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            Create account
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="border-border/70 relative isolate overflow-hidden border-b">
                        <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,color-mix(in_oklab,var(--primary)_12%,transparent),transparent_42%)]" />
                        <div className="mx-auto grid w-full max-w-6xl gap-14 px-6 py-20 lg:grid-cols-[1.15fr_0.85fr] lg:px-8 lg:py-28">
                            <div className="max-w-2xl">
                                <p className="text-primary mb-5 text-sm font-semibold tracking-[0.16em] uppercase">
                                    Plan with clarity
                                </p>
                                <h1 className="text-4xl leading-tight font-semibold tracking-[-0.035em] text-balance sm:text-5xl lg:text-6xl">
                                    A thoughtful home for every wedding detail.
                                </h1>
                                <p className="text-muted-foreground mt-6 max-w-xl text-lg leading-8 text-pretty">
                                    Organize the work behind your celebration in
                                    a calm, secure workspace designed for real
                                    planning sessions.
                                </p>
                                <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                                    <Button size="lg" asChild>
                                        <Link
                                            href={
                                                auth.user
                                                    ? dashboard()
                                                    : register()
                                            }
                                        >
                                            {auth.user
                                                ? 'Open workspace'
                                                : 'Start planning'}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </Button>
                                    {!auth.user && (
                                        <Button
                                            size="lg"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link href={login()}>
                                                I already have an account
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <aside
                                className="border-border/80 bg-card rounded-3xl border p-7 shadow-sm sm:p-9"
                                aria-label="Foundation status"
                            >
                                <p className="text-muted-foreground text-sm font-medium">
                                    Your planning foundation
                                </p>
                                <div className="mt-8 space-y-7">
                                    {foundations.map(
                                        ({
                                            icon: Icon,
                                            title,
                                            description,
                                        }) => (
                                            <div
                                                key={title}
                                                className="flex gap-4"
                                            >
                                                <span
                                                    className="bg-accent text-accent-foreground flex size-10 shrink-0 items-center justify-center rounded-xl"
                                                    aria-hidden="true"
                                                >
                                                    <Icon className="size-5" />
                                                </span>
                                                <div>
                                                    <h2 className="font-semibold">
                                                        {title}
                                                    </h2>
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
                    </section>
                </main>
            </div>
        </>
    );
}
