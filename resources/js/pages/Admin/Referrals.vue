<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ReferralList from '@/components/ReferralList.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'

const props = defineProps<{
    referrals: Array<Record<string, unknown>>
}>()
</script>

<template>
    <AppLayout
        title="Referrals"
        description="Review and update candidate referrals."
    >
        <AdminBreadcrumbs :items="[{ label: 'Referrals' }]" />
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
