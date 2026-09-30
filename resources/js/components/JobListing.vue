<script setup lang="ts">
import { ref, useTemplateRef } from 'vue'
import { router } from '@inertiajs/vue3'
import { watchDebounced } from '@vueuse/core'
import { LoaderCircle, Search } from '@lucide/vue'
import type { PageState } from 'primevue/paginator'
import JobCard from '@/components/JobCard.vue'
import type { Job, LengthAwarePaginator } from '@/types'

const DEFAULT_PER_PAGE = 10

const props = defineProps<{
    jobs: LengthAwarePaginator<Job>,
    search: string,
}>()

const sectionRef = useTemplateRef<HTMLElement>('section')
const searchQuery = ref(props.search)
const searching = ref(false)

const fetchJobs = (params: { search: string, page: number, perPage: number }, onSuccess?: () => void) => {
    const query: Record<string, string | number> = {}
    if (params.search) {
        query.search = params.search
    }
    if (params.page > 1) {
        query.page = params.page
    }
    if (params.perPage !== DEFAULT_PER_PAGE) {
        query.per_page = params.perPage
    }

    // route('welcome') yields '//' (generator bug for the root URI), so use the current path.
    router.get(window.location.pathname, query, {
        only: ['jobs', 'search'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => searching.value = true,
        onFinish: () => searching.value = false,
        onSuccess,
    })
}

watchDebounced(searchQuery, (value) => {
    fetchJobs({ search: value.trim(), page: 1, perPage: props.jobs.per_page })
}, { debounce: 1000 })

const onPage = (event: PageState) => {
    fetchJobs(
        { search: props.search, page: event.page + 1, perPage: event.rows },
        () => sectionRef.value?.scrollIntoView({ behavior: 'smooth' }),
    )
}

const formatPostedDate = (value: string) => new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
})
</script>

<template>
    <section
        ref="section"
        aria-labelledby="jobs-heading"
        class="mx-auto w-full max-w-6xl scroll-mt-8 py-14 sm:py-16"
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
            v-if="props.jobs.total || props.search"
            class="mx-auto mt-10 max-w-xl"
        >
            <IconField>
                <InputIcon>
                    <LoaderCircle
                        v-if="searching"
                        class="size-4 animate-spin"
                    />
                    <Search
                        v-else
                        class="size-4"
                    />
                </InputIcon>
                <InputText
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search by title, description, or location"
                    aria-label="Search jobs"
                    fluid
                />
            </IconField>
        </div>

        <template v-if="props.jobs.data.length">
            <div class="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <JobCard
                    v-for="job in props.jobs.data"
                    :key="job.id"
                    :job="job"
                    :posted-date="formatPostedDate(job.post_on)"
                />
            </div>

            <!-- Show paginator only if there are more jobs than the default per page -->
            <Paginator
                v-if="props.jobs.total > DEFAULT_PER_PAGE"
                :rows="props.jobs.per_page"
                :total-records="props.jobs.total"
                :first="(props.jobs.current_page - 1) * props.jobs.per_page"
                :rows-per-page-options="[10, 20, 30]"
                class="mt-10 bg-transparent"
                @page="onPage"
            />
        </template>

        <Message
            v-else
            severity="secondary"
            :closable="false"
            class="mx-auto mt-12 max-w-2xl"
        >
            <template v-if="props.search">
                No openings match "{{ props.search }}".
            </template>
            <template v-else>
                There are no active openings right now. Please check back soon.
            </template>
        </Message>
    </section>
</template>
