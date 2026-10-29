<script setup lang="ts">
import { computed, useTemplateRef } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Bell } from '@lucide/vue'
import { route } from '@/utils/route'
import { formatDateTime } from '@/utils'

const page = usePage()
const popover = useTemplateRef<{ toggle: (event: Event) => void }>('popover')

const notifications = computed(() => page.props.notifications)
const unreadCount = computed(() => notifications.value?.unread_count ?? 0)

const markAllRead = () => {
    router.post(route('notifications.read'), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['notifications'],
    })
}
</script>

<template>
    <div v-if="notifications">
        <Button
            severity="secondary"
            text
            rounded
            :aria-label="unreadCount ? `Notifications (${unreadCount} unread)` : 'Notifications'"
            class="relative"
            @click="popover?.toggle($event)"
        >
            <template #icon>
                <Bell class="size-5" />
                <Badge
                    v-if="unreadCount"
                    :value="unreadCount > 9 ? '9+' : unreadCount"
                    severity="danger"
                    size="small"
                    class="absolute -top-0.5 -right-0.5"
                />
            </template>
        </Button>
        <Popover
            ref="popover"
            class="w-80 max-w-[90vw]"
        >
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">Notifications</span>
                    <Button
                        v-if="unreadCount"
                        label="Mark all read"
                        size="small"
                        text
                        @click="markAllRead"
                    />
                </div>
                <p
                    v-if="!notifications.items.length"
                    class="m-0 text-sm text-muted-color"
                >
                    You're all caught up.
                </p>
                <ul
                    v-else
                    class="m-0 flex max-h-80 list-none flex-col gap-2 overflow-y-auto p-0"
                >
                    <li
                        v-for="notification in notifications.items"
                        :key="notification.id"
                        class="flex gap-2 rounded-md p-2 text-sm"
                        :class="notification.read_at ? 'text-muted-color' : 'bg-emphasis'"
                    >
                        <span
                            class="mt-1.5 size-2 shrink-0 rounded-full"
                            :class="notification.read_at ? 'bg-transparent' : notification.status === 'accepted' ? 'bg-emerald-500' : 'bg-red-500'"
                        />
                        <div class="flex flex-col gap-0.5">
                            <span>{{ notification.message }}</span>
                            <span
                                v-if="notification.candidate_note"
                                class="line-clamp-3 whitespace-pre-line italic"
                            >“{{ notification.candidate_note }}”</span>
                            <span class="text-xs text-muted-color">{{ formatDateTime(notification.created_at) }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </Popover>
    </div>
</template>
