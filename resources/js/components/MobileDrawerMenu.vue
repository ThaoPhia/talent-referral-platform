<script setup lang="ts">
import { Link as InertiaLink, usePage } from '@inertiajs/vue3'
import { ChevronsUpDown } from '@lucide/vue'
import PopupMenuButton from '@/components/PopupMenuButton.vue'
import PanelMenu from '@/components/router-link-menus/PanelMenu.vue'
import { MenuItem } from '@/types'
import { route } from '@/utils/route'

const props = defineProps<{
    menuItems: MenuItem[],
    userMenuItems: MenuItem[],
}>()

const visible = defineModel<boolean>('visible', { required: true })
const expandedKeys = defineModel<Record<string, boolean>>('expandedKeys', { required: true })

const page = usePage()
</script>

<template>
    <Drawer
        v-model:visible="visible"
        position="right"
    >
        <div>
            <PanelMenu
                v-model:expandedKeys="expandedKeys"
                :model="props.menuItems"
                class="mt-1 w-full"
            />
        </div>
        <template #footer>
            <div
                v-if="!page.props.auth.user"
                class="flex flex-col gap-2"
            >
                <Button
                    :as="InertiaLink"
                    :href="route('login')"
                    label="Log in"
                    severity="secondary"
                    variant="outlined"
                />
                <Button
                    :as="InertiaLink"
                    :href="route('register')"
                    label="Sign up"
                />
            </div>
            <PopupMenuButton
                v-else
                name="mobile-user-menu-dd"
                severity="secondary"
                size="large"
                :menu-items="props.userMenuItems"
                :label="page.props.auth.user?.name"
            >
                <template #icon>
                    <ChevronsUpDown />
                </template>
            </PopupMenuButton>
        </template>
    </Drawer>
</template>
