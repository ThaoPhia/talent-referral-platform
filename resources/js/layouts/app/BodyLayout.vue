<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3'
import { route } from '@/utils/route'
import { useAppLayout } from '@/composables/useAppLayout'
import Breadcrumb from '@/components/router-link-menus/Breadcrumb.vue'
import ClientOnly from '@/components/ClientOnly.vue'
import Container from '@/components/Container.vue'
import FlashMessages from '@/components/FlashMessages.vue'
import MobileDrawerMenu from '@/components/MobileDrawerMenu.vue'
import PrimaryNavigation from '@/components/PrimaryNavigation.vue'
import { MenuItem } from '@/types'

const props = withDefaults(defineProps<{
    breadcrumbs?: MenuItem[],
}>(), {
    breadcrumbs: () => [],
})

const copyrightYear = new Date().getFullYear()

const {
    currentRoute,
    mobileMenuOpen,
    menuItems,
    expandedKeys,
    userMenuItems,
} = useAppLayout()
</script>

<template>
    <div>
        <ClientOnly>
            <Teleport to="body">
                <MobileDrawerMenu
                    v-model:visible="mobileMenuOpen"
                    v-model:expanded-keys="expandedKeys"
                    :menu-items="menuItems"
                    :user-menu-items="userMenuItems"
                />
                <ScrollTop
                    :buttonProps="{ class: 'fixed! right-4! bottom-4! md:right-8! md:bottom-8! z-[1000]!', rounded: true, raised: true }"
                />
            </Teleport>
        </ClientOnly>
        <div class="flex min-h-screen flex-col overflow-x-clip">
            <PrimaryNavigation
                :current-route="currentRoute"
                :menu-items="menuItems"
                :user-menu-items="userMenuItems"
                @open-mobile-menu="mobileMenuOpen = true"
            />

            <main class="flex-1">
                <Container vertical>
                    <!-- Session-based Flash Messages -->
                    <FlashMessages />

                    <!-- Breadcrumbs -->
                    <Breadcrumb
                        v-if="props.breadcrumbs.length"
                        :model="props.breadcrumbs"
                    />

                    <!-- Page Content -->
                    <slot />
                </Container>
            </main>

            <footer class="border-t border-surface-200 py-6 text-sm text-muted-color dark:border-surface-800">
                <Container>
                    <nav
                        aria-label="Footer"
                        class="mb-3 flex justify-center gap-6"
                    >
                        <InertiaLink
                            :href="route('about')"
                            class="hover:underline"
                        >
                            About us
                        </InertiaLink>
                        <InertiaLink
                            :href="route('contact')"
                            class="hover:underline"
                        >
                            Contact us
                        </InertiaLink>
                    </nav>
                    <p class="m-0 text-center">
                        &copy; {{ copyrightYear }} Talent Referral Platform. All rights reserved.
                    </p>
                </Container>
            </footer>
        </div>
    </div>
</template>
