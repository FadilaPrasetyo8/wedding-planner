import { Link, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    CalendarDays,
    ClipboardList,
    FileCheck,
    Gift,
    HeartHandshake,
    Home,
    ListPlus,
    Music4,
    Settings,
    Store,
    Users,
    UsersRound,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain, type NavGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { WeddingSwitcher } from '@/components/wedding-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { show } from '@/routes/weddings';
import { index as eventsIndex } from '@/routes/weddings/events';
import { index as financeIndex } from '@/routes/weddings/finance';
import { index as guestsIndex } from '@/routes/weddings/guests';
import { index as planningIndex } from '@/routes/weddings/planning';
import { index as vendorsIndex } from '@/routes/weddings/vendors';
import { index as membersIndex } from '@/routes/weddings/members';
import { edit as settingsEdit } from '@/routes/weddings/settings';
import { index as checklistsIndex } from '@/routes/weddings/checklists';
import { index as rundownIndex } from '@/routes/weddings/rundown';
import { index as playlistIndex } from '@/routes/weddings/playlist';
import { index as customFieldsIndex } from '@/routes/weddings/custom-fields';
import type { ActiveWedding, WeddingCapabilities } from '@/types';

export function AppSidebar() {
    const { activeWedding, capabilities } = usePage<{
        activeWedding: ActiveWedding | null;
        capabilities: WeddingCapabilities | null;
    }>().props;
    const navGroups: NavGroup[] = activeWedding
        ? [
              {
                  label: 'Utama',
                  items: [
                      {
                          title: 'Ringkasan',
                          href: show(activeWedding.uuid),
                          icon: Home,
                      },
                      {
                          title: 'Rencana tugas',
                          href: planningIndex(activeWedding.uuid),
                          icon: ClipboardList,
                      },
                      {
                          title: 'Acara',
                          href: eventsIndex(activeWedding.uuid),
                          icon: CalendarDays,
                      },
                  ],
              },
              {
                  label: 'Keuangan',
                  items: [
                      {
                          title: 'Anggaran & pembayaran',
                          href: financeIndex(activeWedding.uuid),
                          icon: Wallet,
                      },
                  ],
              },
              {
                  label: 'Persiapan',
                  items: [
                      {
                          title: 'Seserahan',
                          href: checklistsIndex([
                              activeWedding.uuid,
                              'seserahan',
                          ]),
                          icon: Gift,
                      },
                      {
                          title: 'Administrasi',
                          href: checklistsIndex([
                              activeWedding.uuid,
                              'administration',
                          ]),
                          icon: FileCheck,
                      },
                      {
                          title: 'Vendor',
                          href: vendorsIndex(activeWedding.uuid),
                          icon: Store,
                      },
                      {
                          title: 'Tamu',
                          href: guestsIndex(activeWedding.uuid),
                          icon: UsersRound,
                      },
                  ],
              },
              {
                  label: 'Hari acara',
                  items: [
                      {
                          title: 'Rundown',
                          href: rundownIndex(activeWedding.uuid),
                          icon: CalendarClock,
                      },
                      {
                          title: 'Playlist',
                          href: playlistIndex(activeWedding.uuid),
                          icon: Music4,
                      },
                  ],
              },
              {
                  label: 'Ruang kerja',
                  items: [
                      {
                          title: 'Kustomisasi',
                          href: customFieldsIndex(activeWedding.uuid),
                          icon: ListPlus,
                      },
                      {
                          title: 'Anggota',
                          href: membersIndex(activeWedding.uuid),
                          icon: Users,
                      },
                      ...(capabilities?.editWedding
                          ? [
                                {
                                    title: 'Pengaturan',
                                    href: settingsEdit(activeWedding.uuid),
                                    icon: Settings,
                                },
                            ]
                          : []),
                  ],
              },
          ]
        : [];

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="border-sidebar-border/80"
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            className="rounded-[var(--radius)]"
                            asChild
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <div className="group-data-[collapsible=icon]:hidden">
                    <WeddingSwitcher />
                </div>
            </SidebarHeader>
            <SidebarContent>
                {activeWedding ? (
                    <NavMain groups={navGroups} />
                ) : (
                    <div className="text-muted-foreground mx-3 mt-4 rounded-2xl border border-dashed p-4 text-sm leading-6 group-data-[collapsible=icon]:hidden">
                        <HeartHandshake
                            className="text-primary mb-3 size-5"
                            aria-hidden="true"
                        />
                        Buat ruang pernikahan untuk memulai.
                    </div>
                )}
            </SidebarContent>
            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
