import { Link, usePage } from '@inertiajs/react';
import { HeartHandshake, Home, Settings, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
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
import { index as membersIndex } from '@/routes/weddings/members';
import { edit as settingsEdit } from '@/routes/weddings/settings';
import type { ActiveWedding, NavItem, WeddingCapabilities } from '@/types';

export function AppSidebar() {
    const { activeWedding, capabilities } = usePage<{
        activeWedding: ActiveWedding | null;
        capabilities: WeddingCapabilities | null;
    }>().props;
    const mainNavItems: NavItem[] = activeWedding
        ? [
              {
                  title: 'Ringkasan',
                  href: show(activeWedding.uuid),
                  icon: Home,
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
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
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
                    <NavMain items={mainNavItems} />
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
