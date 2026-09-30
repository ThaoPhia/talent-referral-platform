<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3'
import { Pencil, Plus } from '@lucide/vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from '@/utils/route'

const props = defineProps<{
    jobs: Array<{
        id: number,
        title: string,
        location: string,
        post_on: string,
        status: 'active' | 'archived',
    }>,
}>()

const breadcrumbs = [
    { label: 'Admin Dashboard', route: route('admin.dashboard') },
    { label: 'Jobs' },
]
</script>

<template>
    <AppLayout
        title="Jobs"
        description="Manage job listings."
        :breadcrumbs
    >
        <Card>
            <template #content>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-semibold">Job listings</h1>
                            <p class="m-0 text-muted-color">Create, update, and archive jobs.</p>
                        </div>
                        <Button
                            :as="InertiaLink"
                            :href="route('admin.jobs.create')"
                            label="Add job"
                        >
                            <template #icon>
                                <Plus class="size-4" />
                            </template>
                        </Button>
                    </div>

                    <DataTable :value="props.jobs" stripedRows>
                        <Column field="title" header="Title" />
                        <Column field="location" header="Location" />
                        <Column field="post_on" header="Posted" />
                        <Column field="status" header="Status" />
                        <Column header="Actions">
                            <template #body="{ data }">
                                <Button
                                    v-tooltip.top="'Edit job'"
                                    :as="InertiaLink"
                                    :href="route('admin.jobs.edit', { job: data.id })"
                                    aria-label="Edit job"
                                    severity="secondary"
                                    text
                                >
                                    <template #icon>
                                        <Pencil class="size-4" />
                                    </template>
                                </Button>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </template>
        </Card>
    </AppLayout>
</template>
