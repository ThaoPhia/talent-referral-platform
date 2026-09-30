<script setup lang="ts">
import { Link as InertiaLink, usePage } from '@inertiajs/vue3'
import { 
    LayoutGrid,
    LogIn,
    UserPlus,
} from '@lucide/vue'
import AppHead from '@/components/AppHead.vue'
import Container from '@/components/Container.vue'
import JobCard from '@/components/JobCard.vue'
import { route } from '@/utils/route' 

interface Job {
    id: number,
    title: string,
    description: string,
    location: string,
    post_on: string,
}

const props = defineProps<{
    laravelVersion: string,
    phpVersion: string,
    jobs: Job[],
}>()

const page = usePage()

const heroHeadingEnterClass = 'welcome-animate-enter-soft animate-duration-1250 [animation-delay:100ms] animate-fill-backwards'
const heroCopyEnterClass = 'welcome-animate-enter-soft animate-duration-1250 [animation-delay:200ms] animate-fill-backwards'
const heroActionsEnterClass = 'welcome-animate-enter-soft animate-duration-1250 [animation-delay:300ms] animate-fill-backwards'

const formatPostedDate = (value: string) => new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
})

</script>

<template>
    <AppHead
        title="Talent Referral Platform"
        description="Connect open roles with trusted candidate referrals."
    />

    <main
        class="relative min-h-svh overflow-hidden bg-surface-50 text-surface-950 transition-colors dark:bg-surface-950 dark:text-surface-0"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute inset-0 bg-linear-to-b from-surface-50 via-surface-0 to-surface-100 dark:from-surface-950 dark:via-surface-950 dark:to-surface-900"
            />
            <div
                class="absolute -top-20 left-1/2 h-[34rem] w-[90rem] -translate-x-1/2 rotate-[-10deg] bg-linear-to-r from-red-500/14 via-blue-500/16 to-emerald-500/14 blur-3xl dark:from-red-500/10 dark:via-blue-500/14 dark:to-emerald-500/10"
            />
            <div
                class="absolute top-20 left-1/2 h-[28rem] w-[72rem] -translate-x-1/2 rotate-[12deg] bg-linear-to-r from-transparent via-blue-400/14 to-transparent blur-3xl dark:via-blue-400/10"
            />
            <div
                class="absolute -top-10 -left-20 h-[30rem] w-[32rem] rounded-full bg-red-500/18 blur-3xl dark:bg-red-500/14"
            />
            <div
                class="absolute top-8 left-[42%] h-[34rem] w-[34rem] -translate-x-1/2 rounded-full bg-blue-500/18 blur-3xl dark:bg-blue-500/16"
            />
            <div
                class="absolute -top-6 right-[-4rem] h-[28rem] w-[30rem] rounded-full bg-emerald-500/16 blur-3xl dark:bg-emerald-500/14"
            />
            <div
                class="absolute top-28 left-[28%] h-[20rem] w-[28rem] rotate-[18deg] rounded-full bg-red-300/12 blur-3xl dark:bg-red-300/6"
            />
            <div
                class="absolute top-36 right-[22%] h-[20rem] w-[28rem] rotate-[-16deg] rounded-full bg-emerald-300/12 blur-3xl dark:bg-emerald-300/6"
            />
            <div
                class="absolute bottom-28 left-[14%] h-[18rem] w-[24rem] rotate-[18deg] rounded-full bg-blue-400/12 blur-3xl dark:bg-blue-400/8"
            />
            <div
                class="absolute right-[10%] bottom-16 h-[18rem] w-[24rem] rotate-[-18deg] rounded-full bg-emerald-400/10 blur-3xl dark:bg-emerald-400/8"
            />
            <div
                class="absolute inset-x-0 top-0 h-screen bg-linear-to-b from-white/34 via-white/4 to-transparent dark:from-white/4 dark:via-transparent dark:to-transparent"
            />
            <div
                class="absolute inset-x-0 bottom-0 h-64 bg-linear-to-t from-surface-100/90 via-surface-50/60 to-transparent dark:from-surface-950 dark:via-surface-950/90 dark:to-transparent"
            />
        </div>

        <Container fluid>
            <div class="relative mx-auto flex min-h-svh max-w-7xl flex-col px-4 py-6 sm:px-6 md:px-8 lg:px-10 lg:py-8">
                <section
                    aria-labelledby="welcome-heading"
                    class="flex flex-1 items-center py-16 sm:py-20 lg:py-24"
                >
                    <div class="mx-auto flex w-full max-w-5xl flex-col items-center text-center">
                        <h1
                            id="welcome-heading"
                            v-animateonscroll.once="{ enterClass: heroHeadingEnterClass, threshold: [0.1], rootMargin: '0px 0px -8% 0px' }"
                            class="mt-8 max-w-4xl text-5xl font-semibold tracking-tight text-balance text-surface-950 sm:text-6xl lg:text-7xl dark:text-white dark:text-shadow-lg"
                        >
                            Talent Referral Platform
                        </h1>

                        <p
                            v-animateonscroll.once="{ enterClass: heroCopyEnterClass, threshold: [0.1], rootMargin: '0px 0px -8% 0px' }"
                            class="mt-6 max-w-3xl text-base leading-8 text-surface-600 sm:text-lg dark:text-surface-300"
                        >
                            Help great candidates reach the right opportunities.
                        </p>

                        <nav
                            v-animateonscroll.once="{ enterClass: heroActionsEnterClass, threshold: [0.1], rootMargin: '0px 0px -8% 0px' }"
                            aria-label="Primary actions"
                            class="mt-10 flex flex-wrap items-center justify-center gap-3"
                        >
                            <Button
                                v-if="page.props.auth.user"
                                :as="InertiaLink"
                                :href="route('dashboard')"
                                label="Dashboard"
                                size="large"
                                raised
                            >
                                <template #icon>
                                    <LayoutGrid />
                                </template>
                            </Button>
                            <template v-else>
                                <Button
                                    :as="InertiaLink"
                                    :href="route('register')"
                                    label="Sign up"
                                    size="large"
                                    raised
                                >
                                    <template #icon>
                                        <UserPlus />
                                    </template>
                                </Button>
                                <Button
                                    :as="InertiaLink"
                                    :href="route('login')"
                                    label="Log in"
                                    severity="secondary"
                                    variant="outlined"
                                    size="large"
                                >
                                    <template #icon>
                                        <LogIn />
                                    </template>
                                </Button>   
                            </template>
                        </nav>
                    </div>
                </section>

                <section
                    aria-labelledby="jobs-heading"
                    class="mx-auto w-full max-w-6xl py-14 sm:py-16"
                >
                    <div class="mx-auto max-w-2xl text-center">
                        <h2
                            id="jobs-heading"
                            class="text-3xl font-semibold tracking-tight text-surface-950 sm:text-4xl dark:text-white"
                        >
                            Current openings
                        </h2>
                        <p class="mt-4 text-base leading-8 text-surface-600 dark:text-surface-300">
                            Explore roles that are open for applications.
                        </p>
                    </div>

                    <div
                        v-if="props.jobs.length"
                        class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <JobCard
                            v-for="job in props.jobs"
                            :key="job.id"
                            :job="job"
                            :posted-date="formatPostedDate(job.post_on)"
                        />
                    </div>

                    <Message
                        v-else
                        severity="secondary"
                        :closable="false"
                        class="mx-auto mt-12 max-w-2xl"
                    >
                        There are no active openings right now. Please check back soon.
                    </Message>
                </section>
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
