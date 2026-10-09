import { useState } from 'react';
import { Form, Link } from '@inertiajs/react';
import { Download, Paperclip, Upload } from 'lucide-react';
import { ConfirmSubmit } from '@/components/confirm-submit';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    store as storeAttachment,
    download as downloadAttachment,
    destroy as destroyAttachment,
} from '@/routes/weddings/attachments';

export type AttachmentItem = {
    uuid: string;
    filenameOriginal: string;
    mimeType: string;
    sizeBytes: number;
    createdAt: string;
};

const formatBytes = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const fileKind = (mimeType: string): string => {
    if (mimeType === 'application/pdf') return 'PDF';
    if (mimeType.startsWith('image/')) return 'Gambar';
    return 'Berkas';
};

/**
 * Private attachment list with upload and short-lived signed download,
 * per D-028/D-029. Uploads require a verified editor (server-enforced);
 * the panel hides the form for viewers but the routes stay guarded.
 */
export function AttachmentsPanel({
    weddingUuid,
    module,
    subjectUuid,
    attachments,
    canEdit,
    context,
}: {
    weddingUuid: string;
    module: string;
    subjectUuid: string;
    attachments: AttachmentItem[];
    canEdit: boolean;
    context: string;
}) {
    const [fileError, setFileError] = useState<string | null>(null);

    return (
        <section
            aria-label={`Lampiran ${context}`}
            className="bg-card mt-6 overflow-hidden rounded-2xl border shadow-sm"
        >
            <div className="border-b p-5">
                <h2 className="flex items-center gap-2 text-lg font-semibold">
                    <Paperclip className="size-5" aria-hidden="true" />
                    Lampiran
                </h2>
                <p className="text-muted-foreground mt-1 text-sm">
                    PDF, JPG, PNG, atau WebP hingga 10 MB. Tautan unduhan
                    bersifat sementara dan hanya untuk anggota.
                </p>
            </div>

            {canEdit && (
                <Form
                    action={storeAttachment.url({ wedding: weddingUuid })}
                    method="post"
                    resetOnSuccess
                    errorBag={`attachment-upload-${subjectUuid}`}
                    className="bg-muted/25 border-b p-4"
                >
                    {({ processing, errors }) => (
                        <div className="flex flex-wrap items-end gap-3">
                            <input type="hidden" name="module" value={module} />
                            <input
                                type="hidden"
                                name="subject_uuid"
                                value={subjectUuid}
                            />
                            <div className="grid flex-1 gap-1">
                                <Label htmlFor={`file-${subjectUuid}`}>
                                    Unggah berkas
                                </Label>
                                <Input
                                    id={`file-${subjectUuid}`}
                                    name="file"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    required
                                    disabled={processing}
                                    onChange={(event) => {
                                        const file =
                                            event.currentTarget.files?.[0];
                                        setFileError(
                                            file && file.size > 10 * 1024 * 1024
                                                ? 'Ukuran berkas melebihi 10 MB.'
                                                : null,
                                        );
                                    }}
                                />
                                <InputError
                                    message={errors.file ?? fileError}
                                />
                            </div>
                            <Button
                                disabled={processing || fileError !== null}
                                className="min-h-11"
                            >
                                <Upload aria-hidden="true" />
                                {processing ? 'Mengunggah…' : 'Unggah'}
                            </Button>
                        </div>
                    )}
                </Form>
            )}

            <ul className="divide-y">
                {attachments.map((attachment) => (
                    <li
                        key={attachment.uuid}
                        className="flex flex-wrap items-center justify-between gap-3 p-4"
                    >
                        <div className="min-w-0">
                            <p className="truncate font-medium">
                                {attachment.filenameOriginal}
                            </p>
                            <p className="text-muted-foreground mt-0.5 text-xs">
                                {fileKind(attachment.mimeType)} ·{' '}
                                {formatBytes(attachment.sizeBytes)} ·{' '}
                                {new Intl.DateTimeFormat('id-ID', {
                                    dateStyle: 'medium',
                                }).format(new Date(attachment.createdAt))}
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="min-h-9"
                            >
                                <Link
                                    href={downloadAttachment.url({
                                        wedding: weddingUuid,
                                        attachment: attachment.uuid,
                                    })}
                                    prefetch={false}
                                >
                                    <Download
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Unduh
                                </Link>
                            </Button>
                            {canEdit && (
                                <ConfirmSubmit
                                    form={destroyAttachment.form([
                                        weddingUuid,
                                        attachment.uuid,
                                    ])}
                                    label="hapus"
                                    context={`lampiran ${attachment.filenameOriginal}`}
                                />
                            )}
                        </div>
                    </li>
                ))}
            </ul>

            {attachments.length === 0 && (
                <p className="text-muted-foreground p-8 text-center text-sm">
                    Belum ada lampiran.
                </p>
            )}
        </section>
    );
}
