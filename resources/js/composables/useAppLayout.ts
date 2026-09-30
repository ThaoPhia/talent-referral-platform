import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { LayoutGrid, House, Info, Settings, LogOut, ExternalLink } from '@lucide/vue'
import { MenuItem } from '@/types'
import { route } from '@/utils/route'

export function useAppLayout() {
    const page = usePage()
    const currentRoute = computed(() => page.props.currentRouteName)

    // Menu items
    const menuItems = computed<MenuItem[]>(() => [
        {
            key: 'home',
            label: 'Home',
            lucideIcon: House,
            route: '/',
            active: currentRoute.value == 'welcome',
        },
        {
            key: 'dashboard',
            label: 'Dashboard',
            lucideIcon: LayoutGrid,
            route: route('dashboard'),
            active: currentRoute.value == 'dashboard',
        },
        // {
        //     key: 'resources',
        //     label: 'Resources',
        //     lucideIcon: Info,
        //     items: [
        //         {
        //             key: 'resources-laravel',
        //             label: 'Laravel Docs',
        //             url: 'https://laravel.com/docs/master',
        //             target: '_blank',
        //             lucideIcon: ExternalLink,
        //         },
        //         {
        //             key: 'resources-primevue',
        //             label: 'PrimeVue Docs',
        //             url: 'https://v4.primevue.org/',
        //             target: '_blank',
        //             lucideIcon: ExternalLink,
        //         },
        //     ],
        // },
    ])

    // Check/set expanded PanelMenu items based on active status, for non-persistent layouts
    const expandedKeys = ref<Record<string, boolean>>({})
    const updateExpandedKeys = () => {
        const keys: Record<string, boolean> = {}
        const hasActiveChild = (item: MenuItem): boolean => {
            if (item.items) {
                for (const child of item.items) {
                    if (hasActiveChild(child)) {
                        if (item.key) keys[item.key] = true
                        return true
                    }
                }
            }
            return !!item.active
        }
        menuItems.value.forEach(hasActiveChild)
        expandedKeys.value = keys
    }
    watch(currentRoute, () => {
        updateExpandedKeys()
    }, { immediate: true })

    const logoutForm = useForm({})
    const logout = () => {
        logoutForm.post(route('logout'))
    }
    const userMenuItems: MenuItem[] = [
        {
            label: 'Settings',
            route: route('profile.edit'),
            lucideIcon: Settings,
        },
        {
            separator: true
        },
        {
            label: 'Log out',
            lucideIcon: LogOut,
            command: () => logout(),
        },
    ]

    // Mobile drawer menu
    const mobileMenuOpen = ref(false)
    if (typeof window !== 'undefined') {
        const windowWidth = ref(window.innerWidth)
        const updateWidth = () => {
            windowWidth.value = window.innerWidth
        }
        onMounted(() => {
            window.addEventListener('resize', updateWidth)
        })
        onUnmounted(() => {
            window.removeEventListener('resize', updateWidth)
        })
        watch(windowWidth, (newWidth) => {
            if (newWidth > 1024) {
                mobileMenuOpen.value = false
            }
        })
    }

    return {
        currentRoute,
        menuItems,
        expandedKeys,
        userMenuItems,
        mobileMenuOpen,
        logout,
    }
}
