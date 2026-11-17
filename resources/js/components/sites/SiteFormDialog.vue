<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/sites';
import FieldHint from './FieldHint.vue';
import type { SiteCard } from './types';

const props = defineProps<{
    /** The site being edited, or null when adding a new one. */
    site: SiteCard | null;
}>();

// Kept in script: a literal `https://` in a static template attribute trips vue-tsc.
const URL_PLACEHOLDER = 'https://app.example.com';
const URL_HINT = 'Must start with https://';

const open = defineModel<boolean>('open', { required: true });

const editing = computed(() => props.site !== null);
const action = computed(() =>
    props.site ? update.form(props.site.id) : store.form(),
);

// Remount the form each time the dialog opens so fields and errors reset.
const formKey = ref(0);
watch(open, (isOpen) => {
    if (isOpen) {
        formKey.value++;
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Edit site' : 'Add a site'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        editing
                            ? 'Update how LaunchPad reaches this site.'
                            : 'LaunchPad will check it and take a screenshot right away.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <Form
                :key="formKey"
                v-bind="action"
                :options="{ preserveScroll: true }"
                class="grid gap-4"
                @success="open = false"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="site-name">Site name</Label>
                    <Input
                        id="site-name"
                        name="name"
                        :default-value="site?.name ?? ''"
                        placeholder="Paylance"
                        required
                        autocomplete="off"
                        :aria-invalid="!!errors.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="site-url">Base URL</Label>
                    <Input
                        id="site-url"
                        name="base_url"
                        type="url"
                        :default-value="site?.base_url ?? ''"
                        :placeholder="URL_PLACEHOLDER"
                        required
                        autocomplete="off"
                        class="font-mono text-sm"
                        :aria-invalid="!!errors.base_url"
                    />
                    <FieldHint :error="errors.base_url" :hint="URL_HINT" />
                </div>

                <div class="grid gap-2">
                    <Label for="site-version">
                        Version endpoint
                        <span class="font-normal text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Input
                        id="site-version"
                        name="version_path"
                        :default-value="site?.version_path ?? ''"
                        placeholder="/version"
                        autocomplete="off"
                        class="font-mono text-sm"
                        :aria-invalid="!!errors.version_path"
                    />
                    <FieldHint
                        :error="errors.version_path"
                        hint="A path relative to the base URL. Leave empty to hide the version tag."
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="site-client">
                        Client folder
                        <span class="font-normal text-muted-foreground">
                            (Paylance sites only)
                        </span>
                    </Label>
                    <Input
                        id="site-client"
                        name="client"
                        :default-value="site?.client ?? ''"
                        placeholder="paylance"
                        autocomplete="off"
                        class="font-mono text-sm"
                        :aria-invalid="!!errors.client"
                    />
                    <FieldHint
                        :error="errors.client"
                        hint="The folder on the VPS this site deploys to. Leave empty if it isn't deployed from Paylance."
                    />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        {{ editing ? 'Save changes' : 'Add site' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
