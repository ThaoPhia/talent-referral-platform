<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue'
import { route } from '@/utils/route'

const props = defineProps<{
    referrer: { id: number, name: string, email: string, status: string, created_at: string },
}>()

const processing = ref(false)
const decide = (decision: 'approve' | 'deny') => {
    router.post(route(`admin.referrers.${decision}`, { referrer: props.referrer.id }), {}, {
        onStart: () => processing.value = true,
        onFinish: () => processing.value = false,
    })
}
</script>

<template>
    <AppLayout
        title="Review referrer"
        description="Review a referrer application."
    >
        <AdminBreadcrumbs
            :items="[
                { label: 'Referrer applications', route: route('admin.referrers.index') },
                { label: props.referrer.name },
            ]"
        />
        <h1 class="text-xl font-semibold">
            {{ props.referrer.name }}
        </h1>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-muted-color">
                    Email
                </dt>
                <dd class="mt-1">
                    {{ props.referrer.email }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-color">
                    Status
                </dt>
                <dd class="mt-1 capitalize">
                    {{ props.referrer.status }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-color">
                    Applied
                </dt>
                <dd class="mt-1">
                    {{ new Date(props.referrer.created_at).toLocaleDateString() }}
                </dd>
            </div>
        </dl>
        <div
            v-if="props.referrer.status === 'pending'"
            class="mt-8 flex flex-wrap gap-3"
        >
            <Button
                label="Approve referrer"
                :loading="processing"
                @click="decide('approve')"
            />
            <Button
                label="Deny referrer"
                severity="danger"
                variant="outlined"
                :disabled="processing"
                @click="decide('deny')"
            />
        </div>
    </AppLayout>
</template>