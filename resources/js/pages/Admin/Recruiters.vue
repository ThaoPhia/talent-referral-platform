<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'

defineProps<{
    recruiters: Array<{ id: number, name: string, email: string, created_at: string }>,
}>()
</script>

<template>
    <AppLayout
        title="Recruiter applications"
        description="Review new recruiter accounts."
    >
        <AdminBreadcrumbs :items="[{ label: 'Recruiter applications' }]" />
        <h1 class="text-xl font-semibold">
            Pending recruiters
        </h1>
        <DataTable
            v-if="recruiters.length"
            :value="recruiters"
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
                        :href="route('admin.recruiters.show', { recruiter: data.id })"
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
            No pending recruiter applications.
        </Message>
    </AppLayout>
</template>