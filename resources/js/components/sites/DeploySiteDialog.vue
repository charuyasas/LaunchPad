<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertTriangle, FolderGit2, Loader2, UploadCloud } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { tags as tagsRoute } from '@/routes/deploy';
import { deploy } from '@/routes/sites';
import { compareVersions } from './types';
import type { SiteCard } from './types';

const props = defineProps<{
    site: SiteCard | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const tags = ref<string[]>([]);
const loadingTags = ref(false);
const loadError = ref<string | null>(null);
const selectedTag = ref('');
const runTests = ref(true);
const submitting = ref(false);
const refError = ref<string | null>(null);

// Load every tag each time the dialog opens; the newest is picked by default.
watch(open, async (isOpen) => {
    if (!isOpen) {
        return;
    }

    runTests.value = true;
    refError.value = null;
    loadError.value = null;
    loadingTags.value = true;

    try {
        const response = await fetch(tagsRoute().url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                body.message ?? `Couldn't load tags (HTTP ${response.status}).`,
            );
        }

        tags.value = body.tags ?? [];
        selectedTag.value = tags.value[0] ?? '';
    } catch (error) {
        tags.value = [];
        selectedTag.value = '';
        loadError.value =
            error instanceof Error ? error.message : "Couldn't load tags.";
    } finally {
        loadingTags.value = false;
    }
});

// Warn before re-deploying the running version or rolling back to an older one.
const versionNote = computed(() => {
    const current = props.site?.version;

    if (!current || !selectedTag.value) {
        return null;
    }

    const order = compareVersions(selectedTag.value, current);

    if (order === 0) {
        return `${current} is already running. This deploys it again.`;
    }

    if (order < 0) {
        return `${selectedTag.value} is older than the running ${current}. This is a rollback.`;
    }

    return null;
});

function submit() {
    if (!props.site || !selectedTag.value) {
        return;
    }

    router.visit(deploy(props.site.id), {
        data: { ref: selectedTag.value, run_tests: runTests.value },
        preserveScroll: true,
        onStart: () => (submitting.value = true),
        onFinish: () => (submitting.value = false),
        onError: (errors) => (refError.value = errors.ref ?? null),
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Deploy {{ site?.name }}</DialogTitle>
                <DialogDescription>
                    Runs the Paylance “Deploy to VPS” workflow on GitHub for
                    this site's client folder.
                </DialogDescription>
            </DialogHeader>

            <form id="deploy-form" class="grid gap-4" @submit.prevent="submit">
                <div
                    class="flex items-center justify-between gap-3 rounded-xl bg-muted px-3 py-2.5 text-sm"
                >
                    <span class="flex items-center gap-2 text-muted-foreground">
                        <FolderGit2 class="size-4" />
                        Client folder
                    </span>
                    <span class="font-mono font-medium">{{
                        site?.client
                    }}</span>
                </div>

                <div class="grid gap-2">
                    <Label for="deploy-tag">Tag</Label>
                    <div
                        v-if="loadingTags"
                        class="flex h-9 items-center gap-2 rounded-md border px-3 text-sm text-muted-foreground"
                    >
                        <Loader2 class="size-4 animate-spin" />
                        Loading tags from GitHub…
                    </div>
                    <p
                        v-else-if="loadError"
                        class="rounded-md border border-down/40 bg-down/10 px-3 py-2 text-sm text-down"
                        role="alert"
                    >
                        {{ loadError }}
                    </p>
                    <select
                        v-else
                        id="deploy-tag"
                        v-model="selectedTag"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 font-mono text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    >
                        <option
                            v-for="(tag, index) in tags"
                            :key="tag"
                            :value="tag"
                        >
                            {{ tag }}{{ index === 0 ? ' (latest)' : ''
                            }}{{
                                tag === site?.version ? ' · running now' : ''
                            }}
                        </option>
                    </select>
                    <p
                        v-if="versionNote"
                        class="flex items-start gap-1.5 text-xs text-slow"
                    >
                        <AlertTriangle class="mt-px size-3.5 shrink-0" />
                        {{ versionNote }}
                    </p>
                    <p
                        v-else-if="!loadingTags && !loadError && site?.version"
                        class="text-xs text-muted-foreground"
                    >
                        Running now:
                        <span class="font-mono">{{ site.version }}</span>
                    </p>
                    <InputError :message="refError ?? undefined" />
                </div>

                <div class="grid gap-2">
                    <span class="text-sm font-medium"
                        >Run tests before deploy</span
                    >
                    <div
                        class="inline-flex w-fit rounded-lg bg-muted p-0.5"
                        role="radiogroup"
                        aria-label="Run tests before deploy"
                    >
                        <button
                            v-for="option in [
                                { value: true, label: 'Yes' },
                                { value: false, label: 'No' },
                            ]"
                            :key="option.label"
                            type="button"
                            role="radio"
                            :aria-checked="runTests === option.value"
                            class="rounded-md px-4 py-1 text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            :class="
                                runTests === option.value
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                            @click="runTests = option.value"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </form>

            <DialogFooter class="gap-2">
                <Button type="button" variant="outline" @click="open = false">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    form="deploy-form"
                    :disabled="submitting || loadingTags || !selectedTag"
                >
                    <Spinner v-if="submitting" />
                    <UploadCloud v-else />
                    Deploy {{ selectedTag }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
