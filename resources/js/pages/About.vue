<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from '@/utils/route'
</script>

<template>
    <AppLayout
        title="About us"
        description="Learn how Talent Referral Platform connects trusted referrals with open roles."
    >
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-3xl font-semibold">
                About us
            </h1>
            <p class="text-lg leading-relaxed text-muted-color">
                Talent Referral Platform connects open roles with candidates recommended by people who know them.
                We help referrers and recruiting teams keep the referral process clear and connected.
            </p>
            <Card>
                <template #title>
                    Hiring through trusted connections
                </template>
                <template #content>
                    <ul class="m-0 list-disc space-y-3 pl-5 leading-relaxed">
                        <li>Referrers recommend candidates for open roles and share a personal note.</li>
                        <li>Candidates review the opportunity and choose whether to accept or decline.</li>
                        <li>Recruiting teams track referrals and candidate responses in one workspace.</li>
                    </ul>
                </template>
            </Card>
            <p class="leading-relaxed">
                Have a question or feedback?
                <InertiaLink
                    :href="route('contact')"
                    class="text-primary underline"
                >
                    Contact us
                </InertiaLink>.
            </p>
        </div>
    </AppLayout>
</template>
