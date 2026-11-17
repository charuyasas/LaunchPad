<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { destroy } from '@/routes/sites';
import type { SiteCard } from './types';

const props = defineProps<{
    site: SiteCard | null;
}>();

const open = defineModel<boolean>('open', { required: true });
const deleting = ref(false);

function confirmDelete() {
    if (!props.site) {
        return;
    }

    router.visit(destroy(props.site.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Remove {{ site?.name }}?</DialogTitle>
                <DialogDescription>
                    LaunchPad will stop monitoring this site and delete its
                    saved screenshot. The site itself isn't affected.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="open = false">Cancel</Button>
                <Button
                    variant="destructive"
                    :disabled="deleting"
                    @click="confirmDelete"
                >
                    <Spinner v-if="deleting" />
                    Remove site
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
