<script setup lang="ts">
import { Link as InertiaLink, usePage } from '@inertiajs/vue3'
import {
    ArrowRight,
    Briefcase,
    CircleCheck,
    LayoutGrid,
    MapPin,
    Send,
    UserPlus,
    Users,
} from '@lucide/vue'
import AppHead from '@/components/AppHead.vue'
import Container from '@/components/Container.vue'
import JobListing from '@/components/JobListing.vue'
import NavLogoLink from '@/components/NavLogoLink.vue'
import { route } from '@/utils/route'
import type { Job, LengthAwarePaginator } from '@/types'

const props = defineProps<{
    jobs: LengthAwarePaginator<Job>,
    search: string,
}>()

const page = usePage()

const heroHeadingEnterClass = 'welcome-animate-enter-soft animate-duration-1250 [animation-delay:100ms] animate-fill-backwards'
const heroCopyEnterClass = 'welcome-animate-enter-soft animate-duration-1250 [animation-delay:200ms] animate-fill-backwards'
const heroActionsEnterClass = 'welcome-animate-enter-soft animate-duration-1250 [animation-delay:300ms] animate-fill-backwards'
const heroVisualEnterClass = 'welcome-animate-enter-soft-up animate-duration-1250 [animation-delay:400ms] animate-fill-backwards'

const highlights = [
    'Members refer candidates they trust',
    'Recruiters review every referral in one place',
    'Track each referral from pending to accepted',
]

// Decorative sample data for the hero illustration.
const sampleReferrals = [
    { initials: 'AR', name: 'Alex Rivera', role: 'Senior Backend Engineer', status: 'Accepted', severity: 'success' },
    { initials: 'JK', name: 'Jordan Kim', role: 'Product Designer', status: 'Pending', severity: 'warn' },
    { initials: 'SP', name: 'Sam Patel', role: 'Data Analyst', status: 'Pending', severity: 'warn' },
]
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
                <section
                    aria-labelledby="welcome-heading"
                    class="grid items-center gap-14 py-16 sm:py-20 lg:grid-cols-2 lg:gap-12 lg:py-28"
                >
                    <div class="text-center lg:text-left">
                        <Tag
                            v-animateonscroll.once="{ enterClass: heroHeadingEnterClass, threshold: [0.1] }"
                            severity="secondary"
                            rounded
                            class="px-3 py-1"
                        >
                            <Users class="size-3.5" />
                            Referral-driven hiring
                        </Tag>

                        <h1
                            id="welcome-heading"
                            v-animateonscroll.once="{ enterClass: heroHeadingEnterClass, threshold: [0.1] }"
                            class="mt-6 text-4xl font-semibold tracking-tight text-balance text-surface-950 sm:text-5xl lg:text-6xl dark:text-white"
                        >
                            Hire through the people
                            <span class="text-primary">you already trust.</span>
                        </h1>

                        <p
                            v-animateonscroll.once="{ enterClass: heroCopyEnterClass, threshold: [0.1] }"
                            class="mx-auto mt-6 max-w-xl text-base leading-8 text-surface-600 sm:text-lg lg:mx-0 dark:text-surface-300"
                        >
                            Talent Referral Platform connects open roles with candidates recommended by your network,
                            so great people reach the right opportunities faster.
                        </p>

                        <div
                            v-animateonscroll.once="{ enterClass: heroActionsEnterClass, threshold: [0.1] }"
                            class="mt-10 flex flex-wrap items-center justify-center gap-3 lg:justify-start"
                        >
                            <Button
                                as="a"
                                href="#jobs-heading"
                                label="Browse openings"
                                size="large"
                                raised
                            >
                                <template #icon>
                                    <ArrowRight class="size-5" />
                                </template>
                            </Button>
                            <Button
                                v-if="!page.props.auth.user"
                                :as="InertiaLink"
                                :href="route('register')"
                                label="Become a recruiter"
                                severity="secondary"
                                variant="outlined"
                                size="large"
                            >
                                <template #icon>
                                    <UserPlus class="size-5" />
                                </template>
                            </Button>
                        </div>

                        <ul
                            v-animateonscroll.once="{ enterClass: heroActionsEnterClass, threshold: [0.1] }"
                            class="mx-auto mt-10 flex max-w-md flex-col gap-3 text-left text-sm text-surface-600 lg:mx-0 dark:text-surface-300"
                        >
                            <li
                                v-for="highlight in highlights"
                                :key="highlight"
                                class="flex items-center gap-3"
                            >
                                <CircleCheck class="size-5 shrink-0 text-primary" />
                                {{ highlight }}
                            </li>
                        </ul>
                    </div>

                    <!-- Hero illustration -->
                    <div
                        v-animateonscroll.once="{ enterClass: heroVisualEnterClass, threshold: [0.1] }"
                        aria-hidden="true"
                        class="relative mx-auto w-full max-w-lg"
                    >
                        <div class="absolute -inset-6 rounded-[2rem] bg-linear-to-br from-primary-500/20 via-transparent to-emerald-500/15 blur-2xl" />

                        <div class="relative rounded-2xl border border-surface-200 bg-surface-0/90 p-6 shadow-2xl shadow-surface-900/10 backdrop-blur dark:border-surface-800 dark:bg-surface-900/90">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="m-0 text-sm text-muted-color">
                                        Recent referrals
                                    </p>
                                    <p class="m-0 mt-1 text-2xl font-semibold">
                                        This week
                                    </p>
                                </div>
                                <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <Send class="size-5" />
                                </div>
                            </div>

                            <ul class="mt-6 flex flex-col gap-3">
                                <li
                                    v-for="referral in sampleReferrals"
                                    :key="referral.name"
                                    class="flex items-center gap-4 rounded-xl border border-surface-200 p-3 dark:border-surface-800"
                                >
                                    <Avatar
                                        :label="referral.initials"
                                        shape="circle"
                                        class="shrink-0 bg-primary/10 font-semibold text-primary"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="m-0 truncate font-medium">
                                            {{ referral.name }}
                                        </p>
                                        <p class="m-0 truncate text-sm text-muted-color">
                                            {{ referral.role }}
                                        </p>
                                    </div>
                                    <Tag
                                        :value="referral.status"
                                        :severity="referral.severity"
                                        rounded
                                    />
                                </li>
                            </ul>
                        </div>

                        <div class="absolute -bottom-14 -left-6 hidden rounded-xl border border-surface-200 bg-surface-0 p-4 shadow-xl sm:block dark:border-surface-800 dark:bg-surface-900">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <Briefcase class="size-5" />
                                </div>
                                <div>
                                    <p class="m-0 text-sm font-semibold">
                                        New role posted
                                    </p>
                                    <p class="m-0 flex items-center gap-1 text-xs text-muted-color">
                                        <MapPin class="size-3" />
                                        Remote
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <JobListing
                    :jobs="props.jobs"
                    :search="props.search"
                />
            </div>
        </Container>
    </main>
</template>

<style scoped>
@keyframes welcome-enter-soft {
    from {
        opacity: 0;
        transform: scale(0.97);
        filter: blur(8px);
    }

    to {
        opacity: 1;
        transform: scale(1);
        filter: blur(0);
    }
}

@keyframes welcome-enter-soft-up {
    from {
        opacity: 0;
        transform: translateY(14px) scale(0.98);
        filter: blur(8px);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

.welcome-animate-enter-soft {
    animation-name: welcome-enter-soft;
    animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
    will-change: opacity, transform, filter;
}

.welcome-animate-enter-soft-up {
    animation-name: welcome-enter-soft-up;
    animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
    will-change: opacity, transform, filter;
}
</style>
