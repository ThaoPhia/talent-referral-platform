<script setup lang="ts">
import { computed } from 'vue'
import Breadcrumb from '@/components/router-link-menus/Breadcrumb.vue'
import { MenuItem } from '@/types'
import { route } from '@/utils/route'

const props = withDefaults(defineProps<{
    items?: MenuItem[],
}>(), {
    items: () => [],
})

const model = computed<MenuItem[]>(() => props.items.length
    ? [{ label: 'Admin Dashboard', route: route('admin.dashboard') }, ...props.items]
    : [{ label: 'Admin Dashboard' }])
</script>

<template>
    <Breadcrumb :model />
</template>
