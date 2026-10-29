<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Download, ExternalLink, Pencil } from '@lucide/vue'
import InputErrors from '@/components/InputErrors.vue'
import { useToast } from 'primevue/usetoast'
import { route } from '@/utils/route'
import { formatDateTime, referralStatusLabel, referralStatusSeverity } from '@/utils'
import type { Referral } from '@/types'

const props = defineProps<{
    referrals: Referral[],
    canEdit?: boolean,
}>()

const toast = useToast()
const statusOptions = (['pending', 'accepted', 'rejected'] as const)
    .map((value) => ({ value, label: referralStatusLabel(value) }))
const editDialogOpen = ref(false)
const selectedReferral = ref<Referral | null>(null)
const editForm = useForm({
    status: 'pending' as Referral['status'],
})

const editReferral = (referral: Referral) => {
    selectedReferral.value = referral
    editForm.status = referral.status
    editForm.clearErrors()
    editDialogOpen.value = true
}

const updateReferral = () => {
    if (!selectedReferral.value) {
        return
    }

    editForm.patch(route('admin.referrals.update', { referral: selectedReferral.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false
            toast.add({
                severity: 'success',
                summary: 'Referral updated',
                detail: 'The referral status was updated successfully.',
                life: 4000,
            })
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Update failed',
                detail: 'The referral status could not be updated.',
                life: 5000,
            })
        },
    })
}
</script>

<template>
    <DataTable
        :value="props.referrals"
        stripedRows
    >
        <template #empty>
            No referrals yet.
        </template>
        <Column header="Candidate">
            <template #body="{ data }">
                <div class="flex flex-col">
                    <template v-if="data.candidate">
                        <span class="font-medium">{{ data.candidate.name }}</span>
                        <span class="text-sm text-muted-color">{{ data.candidate.email }}</span>
                    </template>
                    <span
                        v-else
                        class="text-muted-color italic"
                    >Deleted candidate</span>
                </div>
            </template>
        </Column>
        <Column header="Referred by">
            <template #body="{ data }">
                <div class="flex flex-col">
                    <template v-if="data.referrer">
                        <span>{{ data.referrer.name }}</span>
                        <span class="text-sm text-muted-color">{{ data.referrer.email }}</span>
                    </template>
                    <span
                        v-else
                        class="text-muted-color italic"
                    >Deleted user</span>
                </div>
            </template>
        </Column>
        <Column header="Job">
            <template #body="{ data }">
                <span v-if="data.job">{{ data.job.title }}</span>
                <span
                    v-else
                    class="text-muted-color italic"
                >Deleted job</span>
            </template>
        </Column>
        <Column header="Resume">
            <template #body="{ data }">
                <a
                    v-if="data.resume_link"
                    :href="data.resume_link"
                    class="inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
                    :target="data.resume_type === 'link' ? '_blank' : undefined"
                    :rel="data.resume_type === 'link' ? 'noopener noreferrer' : undefined"
                >
                    <ExternalLink
                        v-if="data.resume_type === 'link'"
                        class="size-4"
                    />
                    <Download
                        v-else
                        class="size-4"
                    />
                    {{ data.resume_type === 'link' ? 'Open link' : 'Download' }}
                </a>
                <span
                    v-else
                    class="text-sm text-muted-color"
                >None</span>
            </template>
        </Column>
        <Column header="Notes">
            <template #body="{ data }">
                <div
                    v-if="data.referrer_note || data.candidate_note"
                    class="flex max-w-xs flex-col gap-2 text-sm"
                >
                    <div v-if="data.referrer_note">
                        <span class="text-xs font-medium text-muted-color">Referrer</span>
                        <p class="m-0 line-clamp-3 whitespace-pre-line">
                            {{ data.referrer_note }}
                        </p>
                    </div>
                    <div v-if="data.candidate_note">
                        <span class="text-xs font-medium text-muted-color">Candidate</span>
                        <p class="m-0 line-clamp-3 whitespace-pre-line">
                            {{ data.candidate_note }}
                        </p>
                    </div>
                </div>
                <span
                    v-else
                    class="text-sm text-muted-color"
                >None</span>
            </template>
        </Column>
        <Column header="Referred">
            <template #body="{ data }">
                <span class="text-sm">{{ formatDateTime(data.created_at) }}</span>
            </template>
        </Column>
        <Column header="Viewed">
            <template #body="{ data }">
                <span
                    v-if="data.viewed_at"
                    class="text-sm"
                >{{ formatDateTime(data.viewed_at) }}</span>
                <span
                    v-else
                    class="text-sm text-muted-color"
                >Not yet</span>
            </template>
        </Column>
        <Column header="Status">
            <template #body="{ data }">
                <div class="flex flex-col items-start gap-1">
                    <Tag
                        :value="referralStatusLabel(data.status)"
                        :severity="referralStatusSeverity(data.status)"
                    />
                    <span
                        v-if="data.responded_at"
                        class="text-xs text-muted-color"
                    >{{ formatDateTime(data.responded_at) }}</span>
                </div>
            </template>
        </Column>
        <Column
            v-if="props.canEdit"
            header="Actions"
        >
            <template #body="{ data }">
                <Button
                    v-tooltip.top="'Edit referral'"
                    aria-label="Edit referral"
                    severity="secondary"
                    text
                    @click="editReferral(data)"
                >
                    <template #icon>
                        <Pencil class="size-4" />
                    </template>
                </Button>
            </template>
        </Column>
    </DataTable>

    <Dialog
        v-model:visible="editDialogOpen"
        modal
        header="Edit referral"
        class="w-full max-w-md"
    >
        <form
            class="space-y-6"
            @submit.prevent="updateReferral"
        >
            <div class="flex flex-col gap-2">
                <label for="referral-status">Status</label>
                <Select
                    v-model="editForm.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    inputId="referral-status"
                    :invalid="Boolean(editForm.errors.status)"
                    fluid
                />
                <InputErrors :errors="editForm.errors.status" />
            </div>

            <div class="flex justify-end gap-3">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    variant="outlined"
                    @click="editDialogOpen = false"
                />
                <Button
                    type="submit"
                    label="Save"
                    :loading="editForm.processing"
                />
            </div>
        </form>
    </Dialog>
</template>
