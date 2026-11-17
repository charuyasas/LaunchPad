export type SiteStatus = 'live' | 'slow' | 'down';

export type StatusFilter = SiteStatus | 'all';

export type SiteCard = {
    id: string;
    name: string;
    base_url: string;
    version_path: string | null;
    status: SiteStatus;
    version: string | null;
    preview_url: string | null;
    checked_at: string | null;
    /** How long the homepage took to answer on the last check, in ms. */
    response_ms: number | null;
    /** Paylance client folder on the VPS; set means the site can be deployed. */
    client: string | null;
    last_deploy: LastDeploy | null;
};

export type LastDeploy = {
    ref: string;
    run_tests: boolean;
    by: string;
    at: string;
};

/** Numeric-aware tag comparison: `v1.0.10` > `v1.0.2`. */
export function compareVersions(a: string, b: string): number {
    return a
        .replace(/^v/, '')
        .localeCompare(b.replace(/^v/, ''), undefined, { numeric: true });
}

/** `https://app.example.com/login` → `app.example.com/login` */
export function displayUrl(url: string): string {
    try {
        const parsed = new URL(url);
        const path = parsed.pathname === '/' ? '' : parsed.pathname;

        return `${parsed.host}${path}`;
    } catch {
        return url.replace(/^https?:\/\//, '');
    }
}
