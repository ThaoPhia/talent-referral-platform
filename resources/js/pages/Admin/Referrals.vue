<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ReferralList from '@/components/ReferralList.vue'
import { route } from '@/utils/route'

const props = defineProps<{
    referrals: Array<Record<string, unknown>>
}>()

const breadcrumbs = [
    { label: 'Admin Dashboard', route: route('admin.dashboard') },
    { label: 'Referrals' },
]
</script>

<template>
    <AppLayout
        title="Referrals"
        description="Review and update candidate referrals."
        :breadcrumbs
    >
        <Card>
            <template #content>
                <div class="flex flex-col gap-4">
                    <div>
                        <h1 class="text-xl font-semibold">Referrals</h1>
                        <p class="m-0 text-muted-color">Review candidate referrals and update their status.</p>
                    </div>
                    <ReferralList
                        :referrals="props.referrals"
                        can-edit
                    />
                </div>
            </template>
        </Card>
    </AppLayout>
</template>
