<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import JobForm from '@/components/JobForm.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'

const breadcrumbs = [
    { label: 'Jobs', route: route('admin.jobs.index') },
    { label: 'Add job' },
]
</script>

<template>
    <AppLayout
        title="Add job"
        description="Create a new job listing."
    >
        <AdminBreadcrumbs :items="breadcrumbs" />
        <Card>
            <template #title>
                Add job
            </template>
            <template #subtitle>
                Create a job listing for members to discover.
            </template>
            <template #content>
                <JobForm />
            </template>
        </Card>
    </AppLayout>
</template>
