<script setup lang="ts">
import HomeHero from '@/components/HomeHero.vue'
import JobListing from '@/components/JobListing.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { Job, LengthAwarePaginator } from '@/types'

const props = defineProps<{
    jobs: LengthAwarePaginator<Job>,
    search: string,
}>()
</script>

<template>
    <AppLayout
        title="Talent Referral Platform"
        description="Connect open roles with trusted candidate referrals."
    >
        <div class="mx-auto max-w-7xl">
            <HomeHero />

            <JobListing
                :jobs="props.jobs"
                :search="props.search"
            />
        </div>
    </AppLayout>
</template>
