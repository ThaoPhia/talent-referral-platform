<script setup lang="ts">
import { Link as InertiaLink, usePage } from '@inertiajs/vue3'
import { Menu as MenuIcon } from '@lucide/vue'
import Container from '@/components/Container.vue'
import NavLogoLink from '@/components/NavLogoLink.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import PopupMenuButton from '@/components/PopupMenuButton.vue'
import Menubar from '@/components/router-link-menus/Menubar.vue'
import { MenuItem } from '@/types'
import { route } from '@/utils/route'

const props = defineProps<{
    currentRoute?: string | null,
    menuItems: MenuItem[],
    userMenuItems: MenuItem[],
}>()

const emit = defineEmits<{
    openMobileMenu: [],
}>()

const page = usePage()
</script>

<template>
    <nav class="dynamic-bg shadow-sm">
        <Container>
            <Menubar
                :key="props.currentRoute ?? page.url"
                :model="props.menuItems"
                pt:root:class="px-0 py-0 border-0 rounded-none bg-transparent h-[var(--header-height)]!"
                pt:button:class="hidden"
            >
                <template #start>
                    <div class="shrink-0 flex items-center mr-5">
                        <NavLogoLink />
                    </div>
                </template>
                <template #end>
                    <div class="flex items-center gap-1">
                        <NotificationBell />

                        <!-- Guest Actions -->
                        <div
                            v-if="!page.props.auth.user"
                            class="hidden items-center gap-2 lg:flex"
                        >
                            <Button
                                :as="InertiaLink"
                                :href="route('login')"
                                label="Log in"
                                severity="secondary"
                                variant="text"
                            />
                            <Button
                                :as="InertiaLink"
                                :href="route('register')"
                                label="Sign up"
                            />
                        </div>

                        <!-- User Dropdown Menu -->
                        <div
                            v-else
                            class="hidden lg:flex"
                        >
                            <PopupMenuButton
                                name="desktop-user-menu-dd"
                                side="right"
                                severity="secondary"
                                :menu-items="props.userMenuItems"
                                :label="page.props.auth.user?.name"
                                text
                            />
                        </div>

                        <!-- Mobile Menu Toggle -->
                        <div class="flex lg:hidden">
                            <Button
                                severity="secondary"
                                class="p-1!"
                                aria-label="Open menu"
                                text
                                @click="emit('openMobileMenu')"
                            >
                                <template #icon>
                                    <MenuIcon class="size-6!" />
                                </template>
                            </Button>
                        </div>
                    </div>
                </template>
            </Menubar>
        </Container>
    </nav>
</template>
