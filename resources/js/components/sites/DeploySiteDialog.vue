<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertTriangle, FolderGit2, UploadCloud } from '@lucide/vue';
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
import { Spinner } from '@/components/ui/spinner';
import { useReleaseTags } from '@/composables/useReleaseTags';
import { deploy } from '@/routes/sites';
import RunTestsToggle from './RunTestsToggle.vue';
import TagSelect from './TagSelect.vue';
import { compareVersions } from './types';
import type { SiteCard } from './types';

const props = defineProps<{
    site: SiteCard | null;
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
const submitting = ref(false);
const refError = ref<string | null>(null);

// Load every tag each time the dialog opens; the newest is picked by default.
watch(open, (isOpen) => {
    if (isOpen) {
        runTests.value = true;
        refError.value = null;
        void load();
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

                <TagSelect
                    v-model="selectedTag"
                    :tags="tags"
                    :loading="loadingTags"
                    :error="loadError"
                    :current-version="site?.version"
                >
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
                </TagSelect>

                <RunTestsToggle v-model="runTests" />
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
