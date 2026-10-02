<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Pencil } from '@lucide/vue'
import InputErrors from '@/components/InputErrors.vue'
import { useToast } from 'primevue/usetoast'
import { route } from '@/utils/route'
import type { Referral } from '@/types'

const props = defineProps<{
    referrals: Referral[],
    canEdit?: boolean,
}>()

const toast = useToast()
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
    <DataTable :value="props.referrals" stripedRows>
        <Column header="Candidate">
            <template #body="{ data }">
                <div class="flex flex-col">
                    <span class="font-medium">{{ data.candidate.name }}</span>
                    <span class="text-sm text-muted-color">{{ data.candidate.email }}</span>
                </div>
            </template>
        </Column>
        <Column header="Referred by">
            <template #body="{ data }">
                <div class="flex flex-col">
                    <span>{{ data.recruiter.name }}</span>
                    <span class="text-sm text-muted-color">{{ data.recruiter.email }}</span>
                </div>
            </template>
        </Column>
        <Column field="job.title" header="Job" />
        <Column field="status" header="Status" />
        <Column v-if="props.canEdit" header="Actions">
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
                    :options="['pending', 'viewed', 'accepted', 'rejected']"
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
