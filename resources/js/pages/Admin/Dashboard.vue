<script setup lang="ts">
import { computed } from 'vue'
import { Link as InertiaLink } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import ReferralStatsGrid from '@/components/ReferralStatsGrid.vue'
import { route } from '@/utils/route'
import { formatDateTime, referralStatusLabel, referralStatusSeverity } from '@/utils'
import type { ReferralStats, ReferralStatus } from '@/types'

const props = defineProps<{
    stats: ReferralStats
    jobStats: Record<'draft' | 'active' | 'archived', number>
    topJobs: { id: number, title: string, status: string, referrals_count: number, accepted_referrals_count: number }[]
    topReferrers: { id: number, name: string, email: string, referrals_count: number, accepted_referrals_count: number }[]
    recentResponses: {
        id: number
        status: ReferralStatus
        responded_at: string
        referrer: { name: string }
        candidate: { name: string }
        job: { title: string }
    }[]
}>()

const jobCards = computed(() => [
    { key: 'active', label: 'Active jobs', value: props.jobStats.active },
    { key: 'draft', label: 'Drafts', value: props.jobStats.draft },
    { key: 'archived', label: 'Archived', value: props.jobStats.archived },
])
</script>

<template>
    <AppLayout
        title="Admin Dashboard"
        description="Manage jobs and referrals."
    >
        <AdminBreadcrumbs />
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">
                        Admin workspace
                    </h1>
                    <p class="m-0 text-muted-color">
                        Create and manage job listings and referrals.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
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
                        <Button
                            label="View referrals"
                            severity="secondary"
                        />
                    </InertiaLink>
                </div>
            </div>

            <ReferralStatsGrid :stats="props.stats" />

            <div class="grid grid-cols-3 gap-4">
                <Card
                    v-for="card in jobCards"
                    :key="card.key"
                    :data-job-stat="card.key"
                    pt:body:class="p-4 gap-1"
                >
                    <template #content>
                        <div class="text-sm text-muted-color">
                            {{ card.label }}
                        </div>
                        <div class="mt-1 text-2xl font-semibold">
                            {{ card.value }}
                        </div>
                    </template>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <template #title>
                        Top jobs
                    </template>
                    <template #content>
                        <DataTable
                            :value="props.topJobs"
                            size="small"
                        >
                            <template #empty>
                                No referrals yet.
                            </template>
                            <Column
                                field="title"
                                header="Job"
                            />
                            <Column
                                field="referrals_count"
                                header="Referrals"
                            />
                            <Column
                                field="accepted_referrals_count"
                                header="Accepted"
                            />
                        </DataTable>
                    </template>
                </Card>
                <Card>
                    <template #title>
                        Top referrers
                    </template>
                    <template #content>
                        <DataTable
                            :value="props.topReferrers"
                            size="small"
                        >
                            <template #empty>
                                No referrals yet.
                            </template>
                            <Column header="Referrer">
                                <template #body="{ data }">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ data.name }}</span>
                                        <span class="text-sm text-muted-color">{{ data.email }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column
                                field="referrals_count"
                                header="Referrals"
                            />
                            <Column
                                field="accepted_referrals_count"
                                header="Accepted"
                            />
                        </DataTable>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>
                    Recent candidate responses
                </template>
                <template #content>
                    <DataTable
                        :value="props.recentResponses"
                        size="small"
                    >
                        <template #empty>
                            No candidate responses yet.
                        </template>
                        <Column
                            field="candidate.name"
                            header="Candidate"
                        />
                        <Column
                            field="job.title"
                            header="Job"
                        />
                        <Column
                            field="referrer.name"
                            header="Referred by"
                        />
                        <Column header="Response">
                            <template #body="{ data }">
                                <div class="flex flex-col items-start gap-1">
                                    <Tag
                                        :value="referralStatusLabel(data.status)"
                                        :severity="referralStatusSeverity(data.status)"
                                    />
                                    <span
                                        v-if="data.candidate_note"
                                        class="line-clamp-2 max-w-xs whitespace-pre-line text-xs text-muted-color"
                                    >{{ data.candidate_note }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="When">
                            <template #body="{ data }">
                                <span class="text-sm">{{ formatDateTime(data.responded_at) }}</span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
