<script setup lang="ts">
import AppHead from '@/components/AppHead.vue'
import BodyLayout from '@/layouts/app/BodyLayout.vue'
import { MenuItem } from '@/types'

const props = withDefaults(defineProps<{
    title: string,
    description?: string,
    breadcrumbs?: MenuItem[],
}>(), {
    breadcrumbs: () => [],
    description: 'Manage your referrals and recruiting workspace.',
})
</script>

<template>
    <AppHead
        :title="props.title"
        :description="props.description"
    >
        <slot name="head" />
    </AppHead>

    <BodyLayout :breadcrumbs="props.breadcrumbs">
        <slot />
    </BodyLayout>
</template>
