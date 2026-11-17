<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Plus,
    RefreshCw,
    Rocket,
    Search,
    SearchX,
    UploadCloud,
    X,
} from '@lucide/vue';
import { onKeyStroke, useTimeAgo } from '@vueuse/core';
import { computed, ref, useTemplateRef, watch } from 'vue';
import AppDock from '@/components/launcher/AppDock.vue';
import DockButton from '@/components/launcher/DockButton.vue';
import BulkDeployDialog from '@/components/sites/BulkDeployDialog.vue';
import DeleteSiteDialog from '@/components/sites/DeleteSiteDialog.vue';
import DeploySiteDialog from '@/components/sites/DeploySiteDialog.vue';
import SiteFormDialog from '@/components/sites/SiteFormDialog.vue';
import SiteIcon from '@/components/sites/SiteIcon.vue';
import StatusSummary from '@/components/sites/StatusSummary.vue';
import type { SiteCard, StatusFilter } from '@/components/sites/types';
import { Button } from '@/components/ui/button';
import {
    refresh as refreshRoute,
    refreshAll as refreshAllRoute,
} from '@/routes/sites';

const props = defineProps<{
    sites: SiteCard[];
    /** Check every site (set when the dashboard is opened). */
    refresh: boolean;
    /** Check just this site (set after adding or editing it). */
    checkSite: string | null;
    /** A repository and token are configured, so sites with a client folder can be deployed. */
    deployEnabled: boolean;
}>();

// --- Checking state -------------------------------------------------------

const checkingAll = ref(false);
const checkingId = ref<string | null>(null);

function refreshAll(manual = true) {
    if (checkingAll.value || props.sites.length === 0) {
        return;
    }

    router.visit(refreshAllRoute(), {
        data: manual ? { manual: true } : {},
        preserveScroll: true,
        onStart: () => (checkingAll.value = true),
        onFinish: () => (checkingAll.value = false),
    });
}

function refreshSite(id: string) {
    router.visit(refreshRoute(id), {
        preserveScroll: true,
        onStart: () => (checkingId.value = id),
        onFinish: () => (checkingId.value = null),
    });
}

// The server says what to check: everything when the dashboard is first
// opened, or a single site right after it was added or edited.
watch(
    () => [props.refresh, props.checkSite] as const,
    ([shouldRefresh, siteId]) => {
        if (shouldRefresh) {
            refreshAll(false);
        } else if (siteId) {
            refreshSite(siteId);
        }
    },
    { immediate: true },
);

const isChecking = (id: string) => checkingAll.value || checkingId.value === id;

const lastChecked = computed(() => {
    const times = props.sites
        .map((site) => site.checked_at)
        .filter((time): time is string => time !== null)
        .sort();

    return times.at(-1) ?? null;
});
const lastCheckedAgo = useTimeAgo(
    computed(() => lastChecked.value ?? new Date(0)),
    { updateInterval: 30_000 },
);

// --- Filtering ------------------------------------------------------------

const statusFilter = ref<StatusFilter>('all');
const search = ref('');
const searchInput = useTemplateRef<HTMLInputElement>('searchInput');

const visibleSites = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.sites.filter(
        (site) =>
            (statusFilter.value === 'all' ||
                site.status === statusFilter.value) &&
            (term === '' ||
                site.name.toLowerCase().includes(term) ||
                site.base_url.toLowerCase().includes(term)),
    );
});

function clearFilters() {
    statusFilter.value = 'all';
    search.value = '';
}

// Like an app launcher: start typing anywhere to search. Escape clears it.
onKeyStroke((event) => {
    const target = event.target as HTMLElement | null;
    const typingElsewhere =
        target?.closest(
            'input, textarea, select, [contenteditable], [role="dialog"], [role="menu"]',
        ) && target !== searchInput.value;

    if (typingElsewhere || event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    if (event.key === 'Escape' && search.value !== '') {
        search.value = '';
        searchInput.value?.blur();

        return;
    }

    if (event.key.length === 1 && target !== searchInput.value) {
        searchInput.value?.focus();
    }
});

// --- Dialogs --------------------------------------------------------------

const formOpen = ref(false);
const editingSite = ref<SiteCard | null>(null);
const deleteOpen = ref(false);
const deletingSite = ref<SiteCard | null>(null);

function openAdd() {
    editingSite.value = null;
    formOpen.value = true;
}

function openEdit(site: SiteCard) {
    editingSite.value = site;
    formOpen.value = true;
}

const bulkDeployOpen = ref(false);
const canBulkDeploy = computed(
    () => props.deployEnabled && props.sites.some((site) => site.client),
);

const deployOpen = ref(false);
const deployingSite = ref<SiteCard | null>(null);

function openDeploy(site: SiteCard) {
    deployingSite.value = site;
    deployOpen.value = true;
}

function openDelete(site: SiteCard) {
    deletingSite.value = site;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Sites" />

    <div class="flex flex-col items-center gap-5 pb-28">
        <!-- Search, like the top of an app launcher -->
        <div class="w-full max-w-md">
            <label class="relative flex items-center rounded-xl glass">
                <Search
                    class="pointer-events-none absolute left-3.5 size-4 text-foreground/50"
                />
                <input
                    ref="searchInput"
                    v-model="search"
                    type="search"
                    placeholder="Search sites"
                    aria-label="Search sites"
                    class="h-10 w-full rounded-xl bg-transparent pr-3 pl-10 text-sm text-foreground outline-none placeholder:text-foreground/50 focus-visible:ring-[3px] focus-visible:ring-ring/40"
                />
            </label>
            <p
                class="mt-2 text-center text-xs text-foreground/60 text-label"
                aria-live="polite"
            >
                <template v-if="checkingAll">
                    Checking {{ sites.length }}
                    {{ sites.length === 1 ? 'site' : 'sites' }}…
                </template>
                <template v-else-if="lastChecked">
                    Last checked {{ lastCheckedAgo }}
                </template>
                <template v-else>Start typing to search</template>
            </p>
        </div>

        <StatusSummary
            v-if="sites.length > 0"
            v-model="statusFilter"
            :sites="sites"
        />

        <!-- No sites yet -->
        <div
            v-if="sites.length === 0"
            class="mt-8 flex max-w-md flex-col items-center rounded-3xl glass px-8 py-12 text-center"
        >
            <span
                class="flex size-16 items-center justify-center rounded-[26%] bg-primary text-primary-foreground shadow-lg shadow-primary/40"
            >
                <Rocket class="size-8" />
            </span>
            <h1 class="mt-5 text-lg font-semibold">Nothing on the pad yet</h1>
            <p class="mt-1 text-sm text-foreground/70">
                Add a deployment to see whether it's up, which version is
                running, and what its homepage looks like right now.
            </p>
            <Button class="mt-6" @click="openAdd">
                <Plus />
                Add your first site
            </Button>
        </div>

        <!-- Filters hide everything -->
        <div
            v-else-if="visibleSites.length === 0"
            class="mt-8 flex flex-col items-center rounded-3xl glass px-8 py-10 text-center"
        >
            <SearchX class="size-6 text-foreground/60" />
            <p class="mt-3 text-sm font-medium">No sites match</p>
            <Button
                variant="ghost"
                size="sm"
                class="mt-3"
                @click="clearFilters"
            >
                <X />
                Clear filters
            </Button>
        </div>

        <!-- The launcher grid -->
        <template v-else>
            <h1 class="sr-only">Sites</h1>
            <ul
                class="mt-4 flex w-full max-w-6xl flex-wrap justify-center gap-x-3 gap-y-8 sm:gap-x-8 sm:gap-y-10 lg:gap-x-10 lg:gap-y-12"
            >
                <li
                    v-for="site in visibleSites"
                    :key="site.id"
                    class="w-24 min-w-0 sm:w-28 lg:w-32"
                >
                    <SiteIcon
                        :site="site"
                        :checking="isChecking(site.id)"
                        :deploy-enabled="deployEnabled"
                        @refresh="refreshSite(site.id)"
                        @edit="openEdit(site)"
                        @delete="openDelete(site)"
                        @deploy="openDeploy(site)"
                    />
                </li>
            </ul>
        </template>
    </div>

    <AppDock>
        <DockButton
            label="Refresh all"
            :disabled="sites.length === 0 || checkingAll"
            @click="refreshAll()"
        >
            <RefreshCw :class="{ 'animate-spin': checkingAll }" />
        </DockButton>
        <DockButton
            v-if="canBulkDeploy"
            label="Deploy all"
            @click="bulkDeployOpen = true"
        >
            <UploadCloud />
        </DockButton>
        <DockButton label="Add site" primary @click="openAdd">
            <Plus />
        </DockButton>
    </AppDock>

    <SiteFormDialog v-model:open="formOpen" :site="editingSite" />
    <DeleteSiteDialog v-model:open="deleteOpen" :site="deletingSite" />
    <DeploySiteDialog v-model:open="deployOpen" :site="deployingSite" />
    <BulkDeployDialog v-model:open="bulkDeployOpen" :sites="sites" />
</template>
