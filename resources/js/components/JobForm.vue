<script setup lang="ts">
import { computed } from 'vue'
import { useForm, Link as InertiaLink } from '@inertiajs/vue3'
import InputErrors from '@/components/InputErrors.vue'
import { route } from '@/utils/route'

type JobStatus = 'draft' | 'active' | 'archived'

const MAX_OPEN_MONTHS = 3

interface Job {
    id?: number
    title: string
    description: string
    location: string
    post_on: string
    closed_date: string
    status: JobStatus
}

const props = defineProps<{
    job?: Job
}>()

const formatDateTimeLocal = (value?: string) => value
    ? value.replace(' ', 'T').slice(0, 16)
    : ''

const formatDate = (value?: string) => value ? value.slice(0, 10) : ''

const addMonthsNoOverflow = (date: string, months: number) => {
    const [year, month, day] = date.split('-').map(Number)
    const target = new Date(Date.UTC(year, month - 1 + months, 1))
    const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth() + 1, 0)).getUTCDate()
    target.setUTCDate(Math.min(day, lastDay))

    return target.toISOString().slice(0, 10)
}

const form = useForm({
    title: props.job?.title ?? '',
    description: props.job?.description ?? '',
    location: props.job?.location ?? '',
    post_on: formatDateTimeLocal(props.job?.post_on),
    closed_date: formatDate(props.job?.closed_date),
    status: props.job?.status ?? 'active' as JobStatus,
})

const minClosedDate = computed(() => formatDate(form.post_on) || undefined)
const maxClosedDate = computed(() => minClosedDate.value
    ? addMonthsNoOverflow(minClosedDate.value, MAX_OPEN_MONTHS)
    : undefined)

const isEditing = Boolean(props.job?.id)

const submit = () => {
    if (isEditing && props.job?.id) {
        form.patch(route('admin.jobs.update', { job: props.job.id }), {
            preserveScroll: true,
        })

        return
    }

    form.post(route('admin.jobs.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <form
        class="space-y-6"
        @submit.prevent="submit"
    >
        <div class="grid gap-6 md:grid-cols-2">
            <div class="flex flex-col gap-2">
                <label for="job-title">Title</label>
                <InputText
                    id="job-title"
                    v-model="form.title"
                    :invalid="Boolean(form.errors.title)"
                    required
                    fluid
                />
                <InputErrors :errors="form.errors.title" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="job-location">Location</label>
                <InputText
                    id="job-location"
                    v-model="form.location"
                    :invalid="Boolean(form.errors.location)"
                    required
                    fluid
                />
                <InputErrors :errors="form.errors.location" />
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <label for="job-description">Description</label>
            <Textarea
                id="job-description"
                v-model="form.description"
                :invalid="Boolean(form.errors.description)"
                rows="6"
                autoResize
                required
                fluid
            />
            <InputErrors :errors="form.errors.description" />
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="flex flex-col gap-2">
                <label for="job-post-on">Post date</label>
                <InputText
                    id="job-post-on"
                    v-model="form.post_on"
                    :invalid="Boolean(form.errors.post_on)"
                    type="datetime-local"
                    required
                    fluid
                />
                <InputErrors :errors="form.errors.post_on" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="job-closed-date">Close date</label>
                <InputText
                    id="job-closed-date"
                    v-model="form.closed_date"
                    :invalid="Boolean(form.errors.closed_date)"
                    :min="minClosedDate"
                    :max="maxClosedDate"
                    type="date"
                    required
                    fluid
                />
                <small class="text-muted-color">Up to {{ MAX_OPEN_MONTHS }} months after the post date.</small>
                <InputErrors :errors="form.errors.closed_date" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="job-status">Status</label>
                <Select
                    v-model="form.status"
                    :options="['draft', 'active', 'archived']"
                    inputId="job-status"
                    :invalid="Boolean(form.errors.status)"
                    fluid
                />
                <InputErrors :errors="form.errors.status" />
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <Button
                type="submit"
                :loading="form.processing"
                :label="isEditing ? 'Update job' : 'Create job'"
            />
            <Button
                label="Cancel"
                severity="secondary"
                variant="outlined"
                :as="InertiaLink"
                :href="route('admin.jobs.index')"
            />
        </div>
    </form>
</template>
