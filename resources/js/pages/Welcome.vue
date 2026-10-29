<script setup lang="ts">
import { Link as InertiaLink, usePage } from '@inertiajs/vue3'
import { LayoutGrid } from '@lucide/vue'
import AppHead from '@/components/AppHead.vue'
import Container from '@/components/Container.vue'
import HomeHero from '@/components/HomeHero.vue'
import JobListing from '@/components/JobListing.vue'
import NavLogoLink from '@/components/NavLogoLink.vue'
import { route } from '@/utils/route'
import type { Job, LengthAwarePaginator } from '@/types'

const props = defineProps<{
    jobs: LengthAwarePaginator<Job>,
    search: string,
}>()

const page = usePage()
</script>

<template>
    <AppHead
        title="Talent Referral Platform"
        description="Connect open roles with trusted candidate referrals."
    />

    <main
        class="relative min-h-svh overflow-x-clip bg-surface-50 text-surface-950 transition-colors dark:bg-surface-950 dark:text-surface-0"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute inset-0 bg-linear-to-b from-surface-50 via-surface-0 to-surface-100 dark:from-surface-950 dark:via-surface-950 dark:to-surface-900"
            />
            <div
                class="absolute inset-x-0 top-0 h-[44rem] bg-[linear-gradient(to_right,rgb(0_0_0/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(0_0_0/0.04)_1px,transparent_1px)] bg-size-[3.5rem_3.5rem] mask-[radial-gradient(ellipse_at_top,black_30%,transparent_75%)] dark:bg-[linear-gradient(to_right,rgb(255_255_255/0.05)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.05)_1px,transparent_1px)]"
            />
            <div
                class="absolute -top-32 right-[-10rem] h-[36rem] w-[36rem] rounded-full bg-primary-500/15 blur-3xl dark:bg-primary-500/12"
            />
            <div
                class="absolute top-40 -left-40 h-[28rem] w-[28rem] rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-500/8"
            />
        </div>

        <header class="sticky top-0 z-50 border-b border-surface-200/70 bg-surface-0/75 backdrop-blur-md dark:border-surface-800/70 dark:bg-surface-950/70">
            <Container>
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 py-3">
                    <NavLogoLink />
                    <nav
                        aria-label="Main"
                        class="flex items-center gap-2"
                    >
                        <Button
                            as="a"
                            href="#jobs-heading"
                            label="Openings"
                            severity="secondary"
                            variant="text"
                            class="hidden sm:inline-flex"
                        />
                        <Button
                            v-if="page.props.auth.user"
                            :as="InertiaLink"
                            :href="route('dashboard')"
                            label="Dashboard"
                        >
                            <template #icon>
                                <LayoutGrid class="size-4" />
                            </template>
                        </Button>
                        <template v-else>
                            <Button
                                :as="InertiaLink"
                                :href="route('login')"
                                label="Log in"
                                severity="secondary"
                                variant="text"
                            />
                            <Button
                                :as="InertiaLink"
                                :href="route('register')"
                                label="Sign up"
                            />
                        </template>
                    </nav>
                </div>
            </Container>
        </header>

        <Container>
            <div class="relative mx-auto max-w-7xl">
                <HomeHero />

                <JobListing
                    :jobs="props.jobs"
                    :search="props.search"
                />
            </div>
        </Container>
    </main>
</template>
