import { Form, Head } from '@inertiajs/react';
import { HeartHandshake } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    token: string;
    invitation: {
        weddingName: string;
        partnerNames: string;
        inviterName: string;
        role: 'editor' | 'viewer';
        expiresAt: string;
    };
};

export default function Invitation({ token, invitation }: Props) {
    const role = invitation.role === 'editor' ? 'Editor' : 'Hanya lihat';

    return (
        <>
            <Head title="Undangan pernikahan" />
            <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
                <section
                    className="bg-card w-full max-w-xl rounded-3xl border p-6 text-center shadow-sm sm:p-10"
                    aria-labelledby="invitation-title"
                >
                    <span className="bg-accent text-accent-foreground mx-auto flex size-14 items-center justify-center rounded-2xl">
                        <HeartHandshake className="size-7" aria-hidden="true" />
                    </span>
                    <p className="text-primary mt-6 text-sm font-semibold tracking-[0.14em] uppercase">
                        Undangan ruang pernikahan
                    </p>
                    <h1
                        id="invitation-title"
                        className="font-display mt-3 text-3xl font-semibold tracking-tight"
                    >
                        {invitation.partnerNames}
                    </h1>
                    <p className="text-muted-foreground mt-4 leading-7">
                        <strong className="text-foreground font-medium">
                            {invitation.inviterName}
                        </strong>{' '}
                        mengundang Anda ke {invitation.weddingName} sebagai{' '}
                        <strong className="text-foreground font-medium">
                            {role}
                        </strong>
                        .
                    </p>
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Form
                            action={`/invitations/${token}/decline`}
                            method="post"
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full sm:w-auto"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}Tolak undangan
                                </Button>
                            )}
                        </Form>
                        <Form
                            action={`/invitations/${token}/accept`}
                            method="post"
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    className="w-full sm:w-auto"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}Terima undangan
                                </Button>
                            )}
                        </Form>
                    </div>
                </section>
            </main>
        </>
    );
}
