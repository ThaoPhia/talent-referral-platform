<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ReferralList from '@/components/ReferralList.vue'
import ReferralStatsGrid from '@/components/ReferralStatsGrid.vue'
import type { Referral, ReferralStats } from '@/types'

defineProps<{
    referrals: Referral[]
    stats: ReferralStats
}>()

const breadcrumbs = [{ label: 'Dashboard' }]
</script>

<template>
    <AppLayout
        title="Dashboard"
        description="Application dashboard."
        :breadcrumbs
    >
        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-xl font-semibold">
                    Your referrals
                </h1>
                <p class="m-0 text-muted-color">
                    Candidates get an email with a link to the job. You'll be notified when they accept or decline.
                </p>
            </div>
            <ReferralStatsGrid :stats="stats" />
            <Card>
                <template #title>
                    My referrals
                </template>
                <template #content>
                    <ReferralList :referrals="referrals" />
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
