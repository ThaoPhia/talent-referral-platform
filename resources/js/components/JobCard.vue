<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link as InertiaLink, useForm, usePage } from '@inertiajs/vue3'
import InputErrors from '@/components/InputErrors.vue'
import { useToast } from 'primevue/usetoast'
import { route } from '@/utils/route'

interface Job {
    id: number,
    title: string,
    description: string,
    location: string,
    post_on: string,
}

const props = defineProps<{
    job: Job,
    postedDate: string,
}>()

const page = usePage()
const toast = useToast()
const referralDialogOpen = ref(false)
const loginDialogOpen = ref(false)
const memberOnlyDialogOpen = ref(false)

const referralForm = useForm({
    job_id: 0,
    candidate_name: '',
    candidate_email: '',
    resume_url: '',
    note: '',
})

watch(
    () => referralForm.errors,
    (errors: Record<string, string | string[]>) => {
        for (const key in errors) {
            if (typeof errors[key] === 'string') {
                errors[key] = [errors[key] as string]
            }
        }
    },
    { deep: true },
)

const openReferralForm = () => {
    if (!page.props.auth.user) {
        loginDialogOpen.value = true
        return
    }

    if (page.props.auth.user.type !== 'normal') {
        memberOnlyDialogOpen.value = true
        return
    }

    referralForm.job_id = props.job.id
    referralForm.clearErrors()
    referralDialogOpen.value = true
}

const submitReferral = () => {
    referralForm.post(route('referrals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            referralDialogOpen.value = false
            referralForm.reset()
            toast.add({
                severity: 'success',
                summary: 'Referral submitted',
                detail: 'The candidate referral was submitted successfully.',
                life: 4000,
            })
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Referral failed',
                detail: 'Please review the form and try again.',
                life: 5000,
            })
        },
    })
}

const closeReferralDialog = () => {
    referralDialogOpen.value = false
    referralForm.reset()
    referralForm.clearErrors()
}
</script>

<template>
    <Card
        class="h-full border border-surface-200/80 bg-white/80 backdrop-blur-sm dark:border-surface-800/80 dark:bg-surface-900/70"
    >
        <template #title>
            {{ props.job.title }}
        </template>
        <template #subtitle>
            {{ props.job.location }} · Posted {{ props.postedDate }}
        </template>
        <template #content>
            <p class="m-0 line-clamp-4 leading-7 text-muted-color">
                {{ props.job.description }}
            </p>
            <Button
                class="mt-6"
                label="Refer a candidate"
                @click="openReferralForm"
            />
        </template>
    </Card>

    <Dialog
        v-model:visible="referralDialogOpen"
        modal
        header="Refer a candidate"
        class="w-full max-w-2xl"
        @hide="closeReferralDialog"
    >
        <form
            class="space-y-6"
            @submit.prevent="submitReferral"
        >
            <p class="m-0 text-muted-color">
                Refer a candidate for <strong>{{ props.job.title }}</strong>.
            </p>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <label :for="`candidate-name-${props.job.id}`">Candidate name</label>
                    <InputText
                        :id="`candidate-name-${props.job.id}`"
                        v-model="referralForm.candidate_name"
                        :invalid="Boolean(referralForm.errors.candidate_name)"
                        required
                        fluid
                    />
                    <InputErrors :errors="referralForm.errors.candidate_name" />
                </div>

                <div class="flex flex-col gap-2">
                    <label :for="`candidate-email-${props.job.id}`">Candidate email</label>
                    <InputText
                        :id="`candidate-email-${props.job.id}`"
                        v-model="referralForm.candidate_email"
                        :invalid="Boolean(referralForm.errors.candidate_email)"
                        type="email"
                        required
                        fluid
                    />
                    <InputErrors :errors="referralForm.errors.candidate_email" />
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label :for="`candidate-resume-${props.job.id}`">Resume URL</label>
                <InputText
                    :id="`candidate-resume-${props.job.id}`"
                    v-model="referralForm.resume_url"
                    :invalid="Boolean(referralForm.errors.resume_url)"
                    type="text"
                    required
                    fluid
                />
                <InputErrors :errors="referralForm.errors.resume_url" />
            </div>

            <div class="flex flex-col gap-2">
                <label :for="`candidate-note-${props.job.id}`">Note</label>
                <Textarea
                    :id="`candidate-note-${props.job.id}`"
                    v-model="referralForm.note"
                    :invalid="Boolean(referralForm.errors.note)"
                    rows="5"
                    autoResize
                    required
                    fluid
                />
                <InputErrors :errors="referralForm.errors.note" />
            </div>

            <div class="flex justify-end gap-3">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    variant="outlined"
                    @click="closeReferralDialog"
                />
                <Button
                    type="submit"
                    label="Submit referral"
                    :loading="referralForm.processing"
                />
            </div>
        </form>
    </Dialog>

    <Dialog
        v-model:visible="loginDialogOpen"
        modal
        header="Log in to refer a candidate"
        class="w-full max-w-md"
    >
        <p class="m-0 text-muted-color">
            Please log in before submitting a candidate referral.
        </p>
        <template #footer>
            <Button
                label="Cancel"
                severity="secondary"
                variant="outlined"
                @click="loginDialogOpen = false"
            />
            <Button
                :as="InertiaLink"
                :href="route('login')"
                label="Log in"
                @click="loginDialogOpen = false"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="memberOnlyDialogOpen"
        modal
        header="Member access required"
        class="w-full max-w-md"
    >
        <p class="m-0 text-muted-color">
            Only member accounts can submit candidate referrals.
        </p>
        <template #footer>
            <Button
                label="Close"
                @click="memberOnlyDialogOpen = false"
            />
        </template>
    </Dialog>
</template>
