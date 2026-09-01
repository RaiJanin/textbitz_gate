import { dialog } from '#nativephp'
import { useToast } from '@/Composables/useToast'
import { usePage } from '@inertiajs/vue3'

const toast = useToast()
const page = usePage()

function crossPlatformToast() {
    async function show(message) {
        if(page.props.platform.isAndroid || page.props.platform.isIos) {
            await dialog.toast(message)
        } else {
            toast.show(message)
        }
    }

    async function success(message) {
        if(page.props.platform.isAndroid || page.props.platform.isIos) {
            await dialog.toast(message)
        } else {
            toast.success(message)
        }
    }

    async function error(message) {
        if(page.props.platform.isAndroid || page.props.platform.isIos) {
            await dialog.alert(message)
        } else {
            toast.error(message)
        }
    }

    return { show, success, error }
}

export default crossPlatformToast