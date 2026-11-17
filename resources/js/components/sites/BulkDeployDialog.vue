<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowRight, Info, UploadCloud } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useReleaseTags } from '@/composables/useReleaseTags';
import { bulk } from '@/routes/deploy';
import RunTestsToggle from './RunTestsToggle.vue';
import TagSelect from './TagSelect.vue';
import { compareVersions } from './types';
import type { SiteCard } from './types';

const props = defineProps<{
    sites: SiteCard[];
}>();

const open = defineModel<boolean>('open', { required: true });

const {
    tags,
    selected: selectedTag,
    loading: loadingTags,
    error: loadError,
    load,
} = useReleaseTags();
const runTests = ref(true);
const chosen = ref<string[]>([]);
const submitting = ref(false);
const errors = ref<{ ref?: string; sites?: string }>({});

const deployable = computed(() => props.sites.filter((site) => site.client));
const notDeployable = computed(() =>
    props.sites.filter((site) => !site.client),
);

watch(open, (isOpen) => {
    if (isOpen) {
        runTests.value = true;
        errors.value = {};
        void load();
    }
});

// Picking a tag pre-selects every site that isn't already running it.
watch(selectedTag, (tag) => {
    chosen.value = deployable.value
        .filter((site) => site.version !== tag)
        .map((site) => site.id);
});

function toggle(id: string, on: boolean) {
    chosen.value = on
        ? [...new Set([...chosen.value, id])]
        : chosen.value.filter((chosenId) => chosenId !== id);
}

function selectAll() {
    chosen.value = deployable.value.map((site) => site.id);
}

function selectNone() {
    chosen.value = [];
}

/** How this site moves if deployed to the selected tag. */
function change(site: SiteCard): 'same' | 'upgrade' | 'rollback' | 'unknown' {
    if (!site.version || !selectedTag.value) {
        return 'unknown';
    }

    const order = compareVersions(selectedTag.value, site.version);

    return order === 0 ? 'same' : order > 0 ? 'upgrade' : 'rollback';
}

const count = computed(() => chosen.value.length);

function submit() {
    if (!selectedTag.value || count.value === 0) {
        return;
    }

    // Keep the order the sites appear in the list.
    const sites = deployable.value
        .filter((site) => chosen.value.includes(site.id))
        .map((site) => site.id);

    router.visit(bulk(), {
        data: { ref: selectedTag.value, run_tests: runTests.value, sites },
        preserveScroll: true,
        onStart: () => (submitting.value = true),
        onFinish: () => (submitting.value = false),
        onError: (failed) => (errors.value = failed),
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Deploy all sites</DialogTitle>
                <DialogDescription>
                    Starts one “Deploy to VPS” run on GitHub per selected site,
                    all on the same tag. They run in parallel.
                </DialogDescription>
            </DialogHeader>

            <form
                id="bulk-deploy-form"
                class="grid gap-4"
                @submit.prevent="submit"
            >
                <TagSelect
                    v-model="selectedTag"
                    :tags="tags"
                    :loading="loadingTags"
                    :error="loadError"
                >
                    <InputError :message="errors.ref" />
                </TagSelect>

                <!-- Site selection -->
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">
                            Sites
                            <span class="font-normal text-muted-foreground">
                                · {{ count }} of
                                {{ deployable.length }} selected
                            </span>
                        </span>
                        <span class="flex gap-1">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-7 px-2 text-xs"
                                @click="selectAll"
                            >
                                All
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-7 px-2 text-xs"
                                @click="selectNone"
                            >
                                None
                            </Button>
                        </span>
                    </div>

                    <ul
                        class="max-h-64 divide-y overflow-y-auto rounded-xl border"
                    >
                        <li v-for="site in deployable" :key="site.id">
                            <label
                                class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition-colors hover:bg-muted/60"
                                :class="{
                                    'opacity-60': !chosen.includes(site.id),
                                }"
                            >
                                <Checkbox
                                    :model-value="chosen.includes(site.id)"
                                    :aria-label="`Deploy ${site.name}`"
                                    @update:model-value="
                                        toggle(site.id, $event === true)
                                    "
                                />
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-sm font-medium"
                                        >{{ site.name }}</span
                                    >
                                    <span
                                        class="block truncate font-mono text-xs text-muted-foreground"
                                        >{{ site.client }}</span
                                    >
                                </span>

                                <span
                                    v-if="change(site) === 'same'"
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    already on
                                    <span class="font-mono">{{
                                        site.version
                                    }}</span>
                                </span>
                                <span
                                    v-else-if="change(site) === 'unknown'"
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    version unknown
                                </span>
                                <span
                                    v-else
                                    class="flex shrink-0 items-center gap-1 font-mono text-xs"
                                    :class="
                                        change(site) === 'rollback'
                                            ? 'text-slow'
                                            : 'text-muted-foreground'
                                    "
                                    :title="
                                        change(site) === 'rollback'
                                            ? 'Rollback to an older version'
                                            : undefined
                                    "
                                >
                                    {{ site.version }}
                                    <ArrowRight class="size-3" />
                                    <span
                                        :class="
                                            change(site) === 'rollback'
                                                ? ''
                                                : 'font-medium text-foreground'
                                        "
                                        >{{ selectedTag }}</span
                                    >
                                </span>
                            </label>
                        </li>
                    </ul>
                    <InputError :message="errors.sites" />

                    <p
                        v-if="notDeployable.length > 0"
                        class="flex items-start gap-1.5 text-xs text-muted-foreground"
                    >
                        <Info class="mt-px size-3.5 shrink-0" />
                        <span>
                            Not listed (no client folder):
                            {{
                                notDeployable
                                    .map((site) => site.name)
                                    .join(', ')
                            }}.
                        </span>
                    </p>
                </div>

                <RunTestsToggle v-model="runTests">
                    <p v-if="runTests" class="text-xs text-muted-foreground">
                        Each run tests the tag before deploying its site.
                    </p>
                </RunTestsToggle>
            </form>

            <DialogFooter class="gap-2">
                <Button type="button" variant="outline" @click="open = false">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    form="bulk-deploy-form"
                    :disabled="
                        submitting || loadingTags || !selectedTag || count === 0
                    "
                >
                    <Spinner v-if="submitting" />
                    <UploadCloud v-else />
                    Deploy {{ selectedTag }} to {{ count }}
                    {{ count === 1 ? 'site' : 'sites' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
