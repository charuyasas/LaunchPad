<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Password" />

    <Heading
        variant="small"
        title="Change password"
        description="Use a long, random password that you don't use anywhere else."
    />

    <Form
        v-bind="PasswordController.update.form()"
        :options="{ preserveScroll: true }"
        reset-on-success
        :reset-on-error="[
            'password',
            'password_confirmation',
            'current_password',
        ]"
        class="mt-6 grid gap-5 sm:max-w-md"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="current_password">Current password</Label>
            <PasswordInput
                id="current_password"
                name="current_password"
                autocomplete="current-password"
            />
            <InputError :message="errors.current_password" />
        </div>

        <div class="grid gap-2">
            <Label for="password">New password</Label>
            <PasswordInput
                id="password"
                name="password"
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">Confirm new password</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <div>
            <Button :disabled="processing" data-test="update-password-button">
                <Spinner v-if="processing" />
                Update password
            </Button>
        </div>
    </Form>
</template>
