import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message, {
            action: data.link
                ? {
                      label: data.link.label,
                      onClick: () =>
                          window.open(data.link!.url, '_blank', 'noopener'),
                  }
                : undefined,
            duration: data.link ? 10_000 : undefined,
        });
    });
}
