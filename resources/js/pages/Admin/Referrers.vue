<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'

defineProps<{
    referrers: Array<{ id: number, name: string, email: string, created_at: string }>,
}>()
</script>

<template>
    <AppLayout
        title="Referrer applications"
        description="Review new referrer accounts."
    >
        <AdminBreadcrumbs :items="[{ label: 'Referrer applications' }]" />
        <h1 class="text-xl font-semibold">
            Pending referrers
        </h1>
        <DataTable
            v-if="referrers.length"
            :value="referrers"
            stripedRows
        >
            <Column
                field="name"
                header="Name"
            />
            <Column
                field="email"
                header="Email"
            />
            <Column header="Actions">
                <template #body="{ data }">
                    <Button
                        :as="InertiaLink"
                        :href="route('admin.referrers.show', { referrer: data.id })"
                        label="Review"
                        severity="secondary"
                        text
                    />
                </template>
            </Column>
        </DataTable>
        <Message
            v-else
            severity="secondary"
        >
            No pending referrer applications.
        </Message>
    </AppLayout>
</template>