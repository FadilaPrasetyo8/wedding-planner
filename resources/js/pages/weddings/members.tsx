import { Form, Head, usePage } from '@inertiajs/react';
import { MailPlus, ShieldCheck, UserRound } from 'lucide-react';
import WeddingInvitationController from '@/actions/App/Http/Controllers/Weddings/WeddingInvitationController';
import WeddingMemberController from '@/actions/App/Http/Controllers/Weddings/WeddingMemberController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ActiveWedding, WeddingCapabilities } from '@/types';

type Member = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'editor' | 'viewer';
    isCurrentUser: boolean;
};
type Invitation = {
    uuid: string;
    email: string;
    role: 'editor' | 'viewer';
    expires_at: string;
};
const roleNames = {
    owner: 'Pemilik',
    editor: 'Editor',
    viewer: 'Hanya lihat',
} as const;

export default function Members({
    members,
    invitations,
}: {
    members: Member[];
    invitations: Invitation[];
}) {
    const { activeWedding, capabilities } = usePage<{
        activeWedding: ActiveWedding;
        capabilities: WeddingCapabilities;
    }>().props;

    return (
        <>
            <Head title="Anggota" />
            <main className="flex flex-1 px-4 py-8 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-5xl">
                    <p className="text-primary text-sm font-semibold tracking-[0.14em] uppercase">
                        {activeWedding.name}
                    </p>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight">
                        Anggota ruang
                    </h1>
                    <p className="text-muted-foreground mt-3 max-w-2xl leading-7">
                        Kelola siapa yang dapat membantu merencanakan atau
                        melihat detail pernikahan.
                    </p>

                    {capabilities.manageMembers && (
                        <section
                            className="bg-card mt-8 rounded-3xl border p-5 shadow-sm sm:p-7"
                            aria-labelledby="invite-title"
                        >
                            <div className="flex gap-3">
                                <MailPlus
                                    className="text-primary mt-0.5 size-5"
                                    aria-hidden="true"
                                />
                                <div>
                                    <h2
                                        id="invite-title"
                                        className="font-semibold"
                                    >
                                        Undang anggota
                                    </h2>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Penerima perlu memverifikasi alamat
                                        email yang sama.
                                    </p>
                                </div>
                            </div>
                            <Form
                                {...WeddingInvitationController.store.form(
                                    activeWedding.uuid,
                                )}
                                className="mt-6 grid gap-4 sm:grid-cols-[1fr_160px_auto] sm:items-start"
                                resetOnSuccess
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email</Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                required
                                                placeholder="nama@contoh.com"
                                            />
                                            <InputError
                                                message={errors.email}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="role">Peran</Label>
                                            <select
                                                id="role"
                                                name="role"
                                                defaultValue="editor"
                                                className="border-input bg-background h-10 rounded-md border px-3 text-sm"
                                            >
                                                <option value="editor">
                                                    Editor
                                                </option>
                                                <option value="viewer">
                                                    Hanya lihat
                                                </option>
                                            </select>
                                            <InputError message={errors.role} />
                                        </div>
                                        <Button
                                            type="submit"
                                            className="sm:mt-6"
                                            disabled={processing}
                                        >
                                            Kirim undangan
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    )}

                    <section
                        className="bg-card mt-6 overflow-hidden rounded-3xl border shadow-sm"
                        aria-labelledby="members-title"
                    >
                        <div className="border-b p-5 sm:p-7">
                            <h2 id="members-title" className="font-semibold">
                                Anggota aktif
                            </h2>
                        </div>
                        <ul className="divide-y">
                            {members.map((member) => (
                                <li
                                    key={member.id}
                                    className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:p-6"
                                >
                                    <span className="bg-muted flex size-10 shrink-0 items-center justify-center rounded-full">
                                        <UserRound
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {member.name}
                                            {member.isCurrentUser && ' (Anda)'}
                                        </p>
                                        <p className="text-muted-foreground truncate text-sm">
                                            {member.email}
                                        </p>
                                    </div>
                                    <span className="bg-accent text-accent-foreground inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium">
                                        <ShieldCheck
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {roleNames[member.role]}
                                    </span>
                                    {capabilities.manageMembers &&
                                        member.role !== 'owner' && (
                                            <Form
                                                {...WeddingMemberController.update.form(
                                                    [
                                                        activeWedding.uuid,
                                                        member.id,
                                                    ],
                                                )}
                                                className="flex gap-2"
                                            >
                                                <select
                                                    name="role"
                                                    defaultValue={member.role}
                                                    aria-label={`Peran ${member.name}`}
                                                    className="border-input bg-background h-9 rounded-md border px-2 text-sm"
                                                >
                                                    <option value="editor">
                                                        Editor
                                                    </option>
                                                    <option value="viewer">
                                                        Hanya lihat
                                                    </option>
                                                </select>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    Simpan
                                                </Button>
                                            </Form>
                                        )}
                                </li>
                            ))}
                        </ul>
                    </section>

                    {capabilities.manageMembers && invitations.length > 0 && (
                        <section className="mt-6">
                            <h2 className="font-semibold">Undangan menunggu</h2>
                            <ul className="mt-3 space-y-2">
                                {invitations.map((invitation) => (
                                    <li
                                        key={invitation.uuid}
                                        className="bg-card flex items-center justify-between gap-4 rounded-2xl border p-4"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {invitation.email}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                {roleNames[invitation.role]}
                                            </p>
                                        </div>
                                        <Form
                                            {...WeddingInvitationController.destroy.form(
                                                [
                                                    activeWedding.uuid,
                                                    invitation.uuid,
                                                ],
                                            )}
                                        >
                                            <Button variant="ghost" size="sm">
                                                Batalkan
                                            </Button>
                                        </Form>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>
            </main>
        </>
    );
}
