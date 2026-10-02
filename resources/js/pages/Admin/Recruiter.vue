<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'

const props = defineProps<{
    recruiter: { id: number, name: string, email: string, status: string, created_at: string },
}>()

const processing = ref(false)
const decide = (decision: 'approve' | 'deny') => {
    router.post(route(`admin.recruiters.${decision}`, { recruiter: props.recruiter.id }), {}, {
        onStart: () => processing.value = true,
        onFinish: () => processing.value = false,
    })
}
</script>

<template>
    <AppLayout
        title="Review recruiter"
        description="Review a recruiter application."
    >
        <AdminBreadcrumbs
            :items="[
                { label: 'Recruiter applications', route: route('admin.recruiters.index') },
                { label: props.recruiter.name },
            ]"
        />
        <h1 class="text-xl font-semibold">
            {{ props.recruiter.name }}
        </h1>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-muted-color">
                    Email
                </dt>
                <dd class="mt-1">
                    {{ props.recruiter.email }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-color">
                    Status
                </dt>
                <dd class="mt-1 capitalize">
                    {{ props.recruiter.status }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-color">
                    Applied
                </dt>
                <dd class="mt-1">
                    {{ new Date(props.recruiter.created_at).toLocaleDateString() }}
                </dd>
            </div>
        </dl>
        <div
            v-if="props.recruiter.status === 'pending'"
            class="mt-8 flex flex-wrap gap-3"
        >
            <Button
                label="Approve recruiter"
                :loading="processing"
                @click="decide('approve')"
            />
            <Button
                label="Deny recruiter"
                severity="danger"
                variant="outlined"
                :disabled="processing"
                @click="decide('deny')"
            />
        </div>
    </AppLayout>
</template>