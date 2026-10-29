<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { CalendarClock, CalendarDays, MapPin } from '@lucide/vue'
import InputErrors from '@/components/InputErrors.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from '@/utils/route'
import { formatCalendarDate, formatDateTime, referralStatusLabel, referralStatusSeverity } from '@/utils'
import type { ReferralResumeType, ReferralStatus } from '@/types'

const props = defineProps<{
    token: string
    referral: {
        status: ReferralStatus
        responded_at: string | null
        can_respond: boolean
        referrer_name: string
        referrer_note: string | null
        candidate_note: string | null
        resume_type: ReferralResumeType | null
        candidate_name: string
    }
    job: {
        title: string
        description: string | null
        location: string
        post_on: string
        closed_on: string
    }
}>()

const resumeModes = [
    { value: 'link', label: 'Paste a link' },
    { value: 'file', label: 'Upload a file' },
] as const
const resumeMode = ref<ReferralResumeType>('link')
const fileUpload = ref<{ clear: () => void } | null>(null)

const form = useForm({
    decision: '' as '' | 'accepted' | 'rejected',
    candidate_note: '',
    resume_url: '',
    resume_file: null as File | null,
})

const onResumeSelect = (event: { files: File[] }) => {
    form.resume_file = event.files[0] ?? null
    form.clearErrors('resume_file')
}

const clearResumeFile = () => {
    form.resume_file = null
    fileUpload.value?.clear()
}

const respond = (decision: 'accepted' | 'rejected') => {
    form.decision = decision
    form
        // Only send the resume the candidate chose, and none at all when declining.
        .transform(({ resume_url, resume_file, ...data }) => ({
            ...data,
            ...(decision === 'accepted' && resumeMode.value === 'link' ? { resume_url } : {}),
            ...(decision === 'accepted' && resumeMode.value === 'file' ? { resume_file } : {}),
        }))
        .post(route('referrals.invitation.respond', { token: props.token }), {
            preserveScroll: true,
        })
}

const closedMessage = computed(() => props.referral.status === 'pending'
    ? 'This job is no longer accepting responses.'
    : null)
</script>

<template>
    <AppLayout
        :title="`${props.job.title} referral`"
        :description="`${props.referral.referrer_name} referred you for ${props.job.title}.`"
    >
        <div class="mx-auto flex max-w-3xl flex-col gap-6">
            <Message
                severity="info"
                :closable="false"
            >
                Hi {{ props.referral.candidate_name }}, <strong>{{ props.referral.referrer_name }}</strong> referred you for this role.
            </Message>

            <Card v-if="props.referral.referrer_note">
                <template #title>
                    <h2 class="text-base font-semibold">
                        A note from {{ props.referral.referrer_name }}
                    </h2>
                </template>
                <template #content>
                    <p class="m-0 whitespace-pre-line leading-relaxed">
                        {{ props.referral.referrer_note }}
                    </p>
                </template>
            </Card>

            <Card>
                <template #title>
                    <h1 class="text-2xl font-semibold">
                        {{ props.job.title }}
                    </h1>
                </template>
                <template #subtitle>
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <span class="inline-flex items-center gap-1.5">
                            <MapPin class="size-4" />{{ props.job.location }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <CalendarDays class="size-4" />Posted {{ formatDateTime(props.job.post_on) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <CalendarClock class="size-4" />Closes {{ formatCalendarDate(props.job.closed_on) }}
                        </span>
                    </div>
                </template>
                <template #content>
                    <p
                        v-if="props.job.description"
                        class="m-0 whitespace-pre-line leading-relaxed"
                    >
                        {{ props.job.description }}
                    </p>
                    <p
                        v-else
                        class="m-0 text-muted-color"
                    >
                        The details for this job are no longer available.
                    </p>
                </template>
            </Card>

            <Card>
                <template #content>
                    <div
                        v-if="props.referral.can_respond"
                        class="flex flex-col gap-4"
                    >
                        <p class="m-0">
                            Would you like to be considered for this role?
                        </p>
                        <div class="flex flex-col gap-2">
                            <label for="candidate-note">Note to {{ props.referral.referrer_name }} <span class="text-muted-color">(optional)</span></label>
                            <Textarea
                                id="candidate-note"
                                v-model="form.candidate_note"
                                :invalid="Boolean(form.errors.candidate_note)"
                                rows="3"
                                maxlength="5000"
                                autoResize
                                fluid
                            />
                            <InputErrors :errors="form.errors.candidate_note" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <span
                                id="resume-label"
                                class="font-medium"
                            >Resume <span class="font-normal text-muted-color">(required to accept)</span></span>
                            <SelectButton
                                v-model="resumeMode"
                                :options="[...resumeModes]"
                                optionLabel="label"
                                optionValue="value"
                                :allowEmpty="false"
                                aria-labelledby="resume-label"
                                class="self-start"
                            />
                            <template v-if="resumeMode === 'link'">
                                <InputText
                                    id="resume-url"
                                    v-model="form.resume_url"
                                    type="url"
                                    placeholder="https://"
                                    aria-labelledby="resume-label"
                                    :invalid="Boolean(form.errors.resume_url)"
                                    fluid
                                />
                                <InputErrors :errors="form.errors.resume_url" />
                            </template>
                            <template v-else>
                                <div class="flex flex-wrap items-center gap-3">
                                    <FileUpload
                                        ref="fileUpload"
                                        mode="basic"
                                        name="resume_file"
                                        accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                        :maxFileSize="2 * 1024 * 1024"
                                        chooseLabel="Choose file"
                                        customUpload
                                        :auto="false"
                                        :invalid="Boolean(form.errors.resume_file || form.errors.resume_url)"
                                        @select="onResumeSelect"
                                        @clear="form.resume_file = null"
                                    />
                                    <Button
                                        v-if="form.resume_file"
                                        label="Remove"
                                        severity="secondary"
                                        text
                                        size="small"
                                        @click="clearResumeFile"
                                    />
                                </div>
                                <small class="text-muted-color">PDF, DOC or DOCX, up to 2 MB.</small>
                                <InputErrors :errors="form.errors.resume_file || form.errors.resume_url" />
                            </template>
                        </div>
                        <div class="flex justify-end gap-3">
                            <Button
                                label="Decline"
                                severity="secondary"
                                variant="outlined"
                                :loading="form.processing && form.decision === 'rejected'"
                                :disabled="form.processing"
                                @click="respond('rejected')"
                            />
                            <Button
                                label="Accept referral"
                                :loading="form.processing && form.decision === 'accepted'"
                                :disabled="form.processing"
                                @click="respond('accepted')"
                            />
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-wrap items-center gap-3"
                    >
                        <template v-if="closedMessage">
                            <Tag
                                value="Closed"
                                severity="secondary"
                            />
                            <span>{{ closedMessage }}</span>
                        </template>
                        <template v-else>
                            <Tag
                                :value="referralStatusLabel(props.referral.status)"
                                :severity="referralStatusSeverity(props.referral.status)"
                            />
                            <span>
                                You {{ props.referral.status === 'accepted' ? 'accepted' : 'declined' }} this referral<template v-if="props.referral.responded_at">
                                    on {{ formatDateTime(props.referral.responded_at) }}</template>.
                            </span>
                        </template>
                        <p
                            v-if="props.referral.resume_type"
                            class="m-0 w-full text-muted-color"
                        >
                            Your resume {{ props.referral.resume_type === 'link' ? 'link' : 'file' }} was shared with the hiring team.
                        </p>
                        <p
                            v-if="props.referral.candidate_note"
                            class="m-0 w-full whitespace-pre-line text-muted-color"
                        >
                            Your note: {{ props.referral.candidate_note }}
                        </p>
                    </div>
                    <InputErrors
                        :errors="form.errors.decision"
                        class="mt-2"
                    />
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
