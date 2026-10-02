<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'
</script>

<template>
    <AppLayout
        title="Admin Dashboard"
        description="Manage jobs and referrals."
    >
        <AdminBreadcrumbs />
        <Card>
            <template #content>
                <div class="flex flex-col gap-2">
                    <h1 class="text-xl font-semibold">
                        Admin workspace
                    </h1>
                    <p class="m-0 text-muted-color">
                        Create and manage job listings and referrals.
                    </p>
                    <div class="mt-20 flex flex-wrap gap-3">
                        <InertiaLink
                            :href="route('admin.jobs.index')"
                            class="inline-flex"
                        >
                            <Button label="View jobs" />
                        </InertiaLink>
                        <InertiaLink
                            :href="route('admin.referrals.index')"
                            class="inline-flex"
                        >
                            <Button label="View referrals" severity="secondary" />
                        </InertiaLink>
                        <InertiaLink
                            :href="route('admin.recruiters.index')"
                            class="inline-flex"
                        >
                            <Button label="Review recruiters" severity="secondary" />
                        </InertiaLink>
                    </div>
                </div>
            </template>
        </Card>
    </AppLayout>
</template>
