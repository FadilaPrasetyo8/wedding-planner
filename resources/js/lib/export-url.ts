export type ExportFilterValue = string | number | boolean | null | undefined;

export function exportUrl(
    baseUrl: string,
    filters: Record<string, ExportFilterValue>,
): string {
    const url = new URL(baseUrl, window.location.origin);

    Object.entries(filters).forEach(([key, value]) => {
        if (
            key === 'page' ||
            value === null ||
            value === undefined ||
            value === '' ||
            value === 'all' ||
            value === false
        ) {
            return;
        }

        url.searchParams.set(key, String(value));
    });

    return `${url.pathname}${url.search}`;
}
