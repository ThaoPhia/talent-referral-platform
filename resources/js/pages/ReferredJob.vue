<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AppHead from '@/components/AppHead.vue'
import Container from '@/components/Container.vue'

const props = defineProps<{
    job: { title: string, location: string, post_on: string, description: string },
    accepted: boolean,
    acceptUrl: string,
}>()

const form = useForm({})
</script>

<template>
    <AppHead
        :title="props.job.title"
        description="Review the role you were referred for."
    />
    <main class="min-h-svh bg-surface-50 py-12 text-surface-950 dark:bg-surface-950 dark:text-surface-0">
        <Container>
            <div class="mx-auto max-w-2xl">
                <p class="mb-3 text-sm font-medium text-primary">
                    Referred opportunity
                </p>
                <h1 class="text-3xl font-semibold">
                    {{ props.job.title }}
                </h1>
                <p class="mt-3 text-muted-color">
                    {{ props.job.location }}
                </p>
                <p
                    class="mt-8 whitespace-pre-line leading-7"
                    v-text="props.job.description"
                />
                <div v-if="props.accepted" class="mt-8">
                    <Message
                        severity="success"
                        :closable="false"
                    >
                        You accepted this referral.
                    </Message>
                </div>
                <div
                    v-else
                    class="mt-8"
                >
                    <p class="leading-7 text-muted-color">
                        A recruiter has referred you for this role. You can accept the referral after reviewing the job.
                    </p>
                    <Button
                        label="Accept referral"
                        :loading="form.processing"
                        @click="form.post(props.acceptUrl)"
                    />
                </div>
            </div>
        </Container>
    </main>
</template>