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
        title: 'Satu ruang tenang',
        description:
            'Kumpulkan rencana, tanggal, dan keputusan dalam satu tempat terpercaya.',
    },
    {
        icon: WalletCards,
        title: 'Rencana keuangan yang jelas',
        description:
            'Siapkan anggaran dan termin pembayaran yang luwes tanpa spreadsheet berantakan.',
    },
    {
        icon: CheckCircle2,
        title: 'Kemajuan yang bisa dipercaya',
        description:
            'Detail penting tetap terlihat selagi hari bahagia dirangkai.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Rencana pernikahan yang tertata" />

            <div className="bg-background text-foreground min-h-svh">
                <header className="border-border/70 bg-background/95 border-b">
                    <div className="mx-auto flex h-18 w-full max-w-6xl items-center justify-between px-6 lg:px-8">
                        <Link
                            href={home()}
                            className="focus-visible:ring-ring flex items-center gap-3 rounded-md focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <span
                                className="bg-primary text-primary-foreground ring-primary/20 flex size-9 items-center justify-center rounded-[var(--radius)] ring-4"
                                aria-hidden="true"
                            >
                                <CalendarHeart className="size-4.5" />
                            </span>
                            <span className="font-semibold tracking-tight">
                                Wedding Planner
                            </span>
                        </Link>

                        <nav
                            aria-label="Navigasi akun"
                            className="flex items-center gap-2"
                        >
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Buka ruang kerja
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Masuk</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>Buat akun</Link>
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
                                    Rencana dengan tenang
                                </p>
                                <h1 className="font-display text-4xl leading-tight font-semibold tracking-[-0.03em] text-balance sm:text-5xl lg:text-6xl">
                                    Rumah yang tertata untuk setiap detail
                                    pernikahan Anda.
                                </h1>
                                <p className="text-muted-foreground mt-6 max-w-xl text-lg leading-8 text-pretty">
                                    Susun pekerjaan di balik hari bahagia Anda
                                    dalam ruang kerja yang tenang, aman, dan
                                    dirancang untuk sesi perencanaan yang
                                    sesungguhnya.
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
                                                ? 'Buka ruang kerja'
                                                : 'Mulai merencanakan'}
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
                                                Saya sudah punya akun
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <aside
                                className="border-border/80 bg-card rounded-[var(--radius)] border p-7 shadow-[0_2px_0_rgb(23_21_18_/_0.08)] sm:p-9"
                                aria-label="Status fondasi"
                            >
                                <p className="text-muted-foreground text-sm font-medium">
                                    Fondasi perencanaan Anda
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
