<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile" />

    <Heading
        variant="small"
        title="Profile"
        description="Your name and the email you sign in with."
    />

    <Form
        v-bind="ProfileController.update.form()"
        class="mt-6 grid gap-5 sm:max-w-md"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                :default-value="user.name"
                required
                autocomplete="name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input
                id="email"
                type="email"
                name="email"
                :default-value="user.email"
                required
                autocomplete="username"
            />
            <InputError :message="errors.email" />
        </div>

        <div>
            <Button :disabled="processing" data-test="update-profile-button">
                <Spinner v-if="processing" />
                Save changes
            </Button>
        </div>
    </Form>
</template>
