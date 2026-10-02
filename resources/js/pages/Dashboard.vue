<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ReferralList from '@/components/ReferralList.vue'
import type { Referral } from '@/types'

defineProps<{
    referrals: Referral[]
}>()

const breadcrumbs = [{ label: 'Dashboard' }]
</script>

<template>
    <AppLayout
        title="Dashboard"
        description="Application dashboard."
        :breadcrumbs
    >
        <Card>
            <template #content>
                <p class="m-0"> 
                    Welcome to your dashboard!
                </p>
            </template>
        </Card>
        <Card class="mt-6">
            <template #title>
                My referrals
            </template>
            <template #content>
                <ReferralList :referrals="referrals" />
            </template>
        </Card>
    </AppLayout>
</template>
