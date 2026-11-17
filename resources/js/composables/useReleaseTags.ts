import { ref } from 'vue';
import { tags as tagsRoute } from '@/routes/deploy';

/**
 * Loads every tag in the deploy repository (newest first, from the server)
 * and pre-selects the latest one.
 */
export function useReleaseTags() {
    const tags = ref<string[]>([]);
    const selected = ref('');
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function load(): Promise<void> {
        loading.value = true;
        error.value = null;

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
                    body.message ??
                        `Couldn't load tags (HTTP ${response.status}).`,
                );
            }

            tags.value = body.tags ?? [];
            selected.value = tags.value[0] ?? '';
        } catch (caught) {
            tags.value = [];
            selected.value = '';
            error.value =
                caught instanceof Error
                    ? caught.message
                    : "Couldn't load tags.";
        } finally {
            loading.value = false;
        }
    }

    return { tags, selected, loading, error, load };
}
