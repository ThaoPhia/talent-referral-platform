<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import JobForm from '@/components/JobForm.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'

interface Job {
    id: number
    title: string
    description: string
    location: string
    post_on: string
    status: 'active' | 'archived'
}

const props = defineProps<{
    job: Job
}>()

const breadcrumbs = [
    { label: 'Jobs', route: route('admin.jobs.index') },
    { label: 'Edit job' },
]
</script>

<template>
    <AppLayout
        title="Edit job"
        description="Update an existing job listing."
    >
        <AdminBreadcrumbs :items="breadcrumbs" />
        <Card>
            <template #title>
                Edit job
            </template>
            <template #subtitle>
                Update the details and publication status for this job.
            </template>
            <template #content>
                <JobForm :job="props.job" />
            </template>
        </Card>
    </AppLayout>
</template>
