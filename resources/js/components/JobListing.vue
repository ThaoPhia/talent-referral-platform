<script setup lang="ts">
import JobCard from '@/components/JobCard.vue'
import type { Job } from '@/types'

const props = defineProps<{
    jobs: Job[],
}>()

const formatPostedDate = (value: string) => new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
})
</script>

<template>
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
</template>
