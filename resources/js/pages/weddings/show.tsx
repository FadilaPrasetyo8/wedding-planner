import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarDays,
    CheckCircle2,
    ChevronRight,
    ClipboardList,
    Clock3,
    ShieldCheck,
} from 'lucide-react';
import WeddingEventController from '@/actions/App/Http/Controllers/Weddings/WeddingEventController';
import { Button } from '@/components/ui/button';

type PageProps = {
    activeWedding: {
        uuid: string;
        name: string;
        partnerNames: string;
        weddingDate: string;
    };
    activeMembership: { role: 'owner' | 'editor' | 'viewer' };
};

type DashboardTask = {
    uuid: string;
    title: string;
    dueDate: string | null;
    status: string;
    category: string;
};
type Group = { uuid: string; name: string; color: string; count: number };
type Dashboard = {
    primaryEvent: {
        uuid: string;
        name: string;
        date: string;
        daysUntil: number;
    } | null;
    taskSummary: {
        total: number;
        completed: number;
        completionPercentage: number;
    };
    overdueCount: number;
    overdueTasks: DashboardTask[];
    upcomingTasks: DashboardTask[];
    upcomingEvents: {
        uuid: string;
        name: string;
        date: string;
        isPrimary: boolean;
    }[];
    statusGroups: (Group & { behavior: string })[];
    categoryGroups: Group[];
};

const roleNames = {
    owner: 'Pemilik',
    editor: 'Editor',
    viewer: 'Hanya lihat',
} as const;
const formatDate = (date: string) =>
    new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${date}T00:00:00`));

export default function WeddingOverview({
    dashboard,
}: {
    dashboard: Dashboard;
    membershipRole: string;
}) {
    const { activeWedding, activeMembership } = usePage<PageProps>().props;
    const base = `/weddings/${activeWedding.uuid}`;
    const countdown = dashboard.primaryEvent
        ? dashboard.primaryEvent.daysUntil > 0
            ? `${dashboard.primaryEvent.daysUntil} hari lagi`
            : dashboard.primaryEvent.daysUntil === 0
              ? 'Hari ini'
              : `${Math.abs(dashboard.primaryEvent.daysUntil)} hari lalu`
        : 'Belum ada acara utama';

    return (
        <>
            <Head title={activeWedding.name} />
            <main className="flex flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl">
                    <header className="flex flex-col gap-5 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-primary text-xs font-semibold tracking-[0.16em] uppercase">
                                Ruang pernikahan
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                                {activeWedding.partnerNames}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {activeWedding.name}
                            </p>
                        </div>
                        <span className="bg-accent text-accent-foreground inline-flex w-fit items-center gap-2 rounded-full px-3 py-2 text-sm font-medium">
                            <ShieldCheck
                                className="size-4"
                                aria-hidden="true"
                            />
                            {roleNames[activeMembership.role]}
                        </span>
                    </header>

                    <section
                        className="bg-card mt-5 grid overflow-hidden rounded-2xl border shadow-sm sm:grid-cols-3"
                        aria-label="Ringkasan perencanaan"
                    >
                        <Summary
                            icon={<CalendarDays />}
                            label={
                                dashboard.primaryEvent?.name ?? 'Acara utama'
                            }
                            value={countdown}
                            detail={
                                dashboard.primaryEvent
                                    ? formatDate(dashboard.primaryEvent.date)
                                    : 'Pilih acara utama untuk menampilkan hitung mundur.'
                            }
                        />
                        <Summary
                            icon={<CheckCircle2 />}
                            label="Kemajuan tugas"
                            value={`${dashboard.taskSummary.completionPercentage}%`}
                            detail={`${dashboard.taskSummary.completed} dari ${dashboard.taskSummary.total} tugas aktif selesai`}
                        />
                        <Summary
                            icon={<AlertTriangle />}
                            label="Perlu perhatian"
                            value={`${dashboard.overdueCount} terlambat`}
                            detail={
                                dashboard.overdueCount
                                    ? 'Tinjau tugas yang melewati tenggat.'
                                    : 'Tidak ada tugas terlambat.'
                            }
                            serious={dashboard.overdueCount > 0}
                        />
                    </section>

                    <div className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(20rem,0.8fr)]">
                        <div className="grid gap-6">
                            <TaskPanel
                                title="Tugas terlambat"
                                description="Prioritas yang sudah melewati tenggat."
                                tasks={dashboard.overdueTasks}
                                empty="Tidak ada tugas terlambat."
                                base={base}
                                serious
                            />
                            <TaskPanel
                                title="Tugas mendatang"
                                description="Lima tenggat terdekat yang masih aktif."
                                tasks={dashboard.upcomingTasks}
                                empty="Belum ada tugas mendatang."
                                base={base}
                            />
                        </div>
                        <div className="grid content-start gap-6">
                            <section className="bg-card rounded-2xl border shadow-sm">
                                <PanelHeader
                                    icon={<Clock3 />}
                                    title="Acara mendatang"
                                    description="Jadwal terdekat dalam ruang ini."
                                />
                                <div className="divide-y">
                                    {dashboard.upcomingEvents.map((event) => (
                                        <Link
                                            key={event.uuid}
                                            href={WeddingEventController.show.url(
                                                [
                                                    activeWedding.uuid,
                                                    event.uuid,
                                                ],
                                            )}
                                            className="focus-visible:ring-ring hover:bg-muted/40 flex min-h-14 items-center justify-between gap-4 px-5 py-3 focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            <span>
                                                <span className="font-medium">
                                                    {event.name}
                                                </span>
                                                {event.isPrimary && (
                                                    <span className="text-primary ml-2 text-xs font-semibold">
                                                        Utama
                                                    </span>
                                                )}
                                            </span>
                                            <time
                                                className="text-muted-foreground text-sm"
                                                dateTime={event.date}
                                            >
                                                {formatDate(event.date)}
                                            </time>
                                        </Link>
                                    ))}
                                </div>
                                {dashboard.upcomingEvents.length === 0 && (
                                    <Empty text="Belum ada acara mendatang." />
                                )}
                                <div className="border-t p-3">
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="min-h-11 w-full justify-between"
                                    >
                                        <Link href={`${base}/events`}>
                                            Kelola acara
                                            <ChevronRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </div>
                            </section>
                            <GroupPanel
                                title="Sebaran status"
                                groups={dashboard.statusGroups}
                                total={dashboard.taskSummary.total}
                            />
                            <GroupPanel
                                title="Sebaran kategori"
                                groups={dashboard.categoryGroups}
                                total={dashboard.statusGroups.reduce(
                                    (sum, group) => sum + group.count,
                                    0,
                                )}
                            />
                        </div>
                    </div>
                </div>
            </main>
        </>
    );
}

function Summary({
    icon,
    label,
    value,
    detail,
    serious = false,
}: {
    icon: React.ReactNode;
    label: string;
    value: string;
    detail: string;
    serious?: boolean;
}) {
    return (
        <article className="border-b p-5 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0">
            <div className={serious ? 'text-destructive' : 'text-primary'}>
                {icon}
            </div>
            <p className="text-muted-foreground mt-4 text-xs font-semibold tracking-wider uppercase">
                {label}
            </p>
            <p className="mt-1 text-2xl font-semibold">{value}</p>
            <p className="text-muted-foreground mt-1 text-sm">{detail}</p>
        </article>
    );
}
function PanelHeader({
    icon,
    title,
    description,
}: {
    icon: React.ReactNode;
    title: string;
    description: string;
}) {
    return (
        <header className="flex items-start gap-3 border-b p-5">
            <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                {icon}
            </span>
            <div>
                <h2 className="font-semibold">{title}</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    {description}
                </p>
            </div>
        </header>
    );
}
function TaskPanel({
    title,
    description,
    tasks,
    empty,
    base,
    serious = false,
}: {
    title: string;
    description: string;
    tasks: DashboardTask[];
    empty: string;
    base: string;
    serious?: boolean;
}) {
    return (
        <section className="bg-card overflow-hidden rounded-2xl border shadow-sm">
            <PanelHeader
                icon={serious ? <AlertTriangle /> : <ClipboardList />}
                title={title}
                description={description}
            />
            <div className="divide-y">
                {tasks.map((task) => (
                    <Link
                        key={task.uuid}
                        href={`${base}/planning/${task.uuid}`}
                        className="focus-visible:ring-ring hover:bg-muted/40 grid min-h-14 gap-1 px-5 py-3 focus-visible:ring-2 focus-visible:outline-none sm:grid-cols-[1fr_auto] sm:items-center"
                    >
                        <span>
                            <span className="font-medium">{task.title}</span>
                            <span className="text-muted-foreground mt-1 block text-xs">
                                {task.category} · {task.status}
                            </span>
                        </span>
                        <time
                            className={
                                serious
                                    ? 'text-destructive text-sm font-medium'
                                    : 'text-muted-foreground text-sm'
                            }
                            dateTime={task.dueDate ?? undefined}
                        >
                            {task.dueDate
                                ? formatDate(task.dueDate)
                                : 'Tanpa tenggat'}
                        </time>
                    </Link>
                ))}
            </div>
            {tasks.length === 0 && <Empty text={empty} />}
            <div className="border-t p-3">
                <Button
                    asChild
                    variant="ghost"
                    className="min-h-11 w-full justify-between"
                >
                    <Link href={`${base}/planning`}>
                        Buka rencana tugas
                        <ChevronRight aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </section>
    );
}
function Empty({ text }: { text: string }) {
    return (
        <p className="text-muted-foreground px-5 py-8 text-center text-sm">
            {text}
        </p>
    );
}
function GroupPanel({
    title,
    groups,
    total,
}: {
    title: string;
    groups: Group[];
    total: number;
}) {
    return (
        <section className="bg-card rounded-2xl border p-5 shadow-sm">
            <h2 className="font-semibold">{title}</h2>
            <div className="mt-4 grid gap-3">
                {groups.map((group) => (
                    <div key={group.uuid}>
                        <div className="flex items-center justify-between gap-4 text-sm">
                            <span>{group.name}</span>
                            <span className="text-muted-foreground tabular-nums">
                                {group.count}
                            </span>
                        </div>
                        <div className="bg-muted mt-1.5 h-1.5 overflow-hidden rounded-full">
                            <span
                                className="bg-primary block h-full rounded-full"
                                style={{
                                    width: `${total ? Math.round((group.count / total) * 100) : 0}%`,
                                }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
