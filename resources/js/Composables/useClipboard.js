import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';
import { dialog } from '#nativephp'
import { usePage } from '@inertiajs/vue3';

export function useClipboard() {
    const copied = ref(false);
    const toast = useToast();
    const page = usePage()

    async function copy(text) {
        try {
            await navigator.clipboard.writeText(text);
            copied.value = true;

            if(page.props.platform.isAndroid || page.props.platform.isIos) {
                await dialog.toast('Copied to clipboard')
            } else {
                toast.show('Copied to clipboard')
            }

            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } catch (err) {
            
            fallbackCopy(text);
        }
    }

    async function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            copied.value = true;
            if(page.props.platform.isAndroid || page.props.platform.isIos) {
                await dialog.toast('Copied to clipboard')
            } else {
                toast.show('Copied to clipboard')
            }
        } catch (err) {
            if(page.props.platform.isAndroid || page.props.platform.isIos) {
                await dialog.toast('Failed to copy')
            } else {
                toast.show('Failed to copy')
            }
        } finally {
            document.body.removeChild(textarea);
        }
    }

    return { copy, copied };
}