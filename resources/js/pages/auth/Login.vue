<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Sign in to LaunchPad',
        description: 'Monitor your client deployments in one place.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Sign in" />

    <div
        v-if="status"
        class="mb-4 rounded-md bg-live/10 px-3 py-2 text-center text-sm font-medium text-live"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="grid gap-5"
    >
        <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input
                id="email"
                type="email"
                name="email"
                required
                v-focus
                autocomplete="email"
                placeholder="you@company.com"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="password">Password</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <InputError :message="errors.password" />
        </div>

        <Label for="remember" class="flex items-center gap-2.5 font-normal">
            <Checkbox id="remember" name="remember" />
            Keep me signed in
        </Label>

        <Button
            type="submit"
            class="mt-1 w-full"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            Sign in
        </Button>
    </Form>
</template>
