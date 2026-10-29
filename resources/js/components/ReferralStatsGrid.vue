<script setup lang="ts">
import { computed } from 'vue'
import { CircleCheck, CircleX, Eye, Hourglass, Percent, Send } from '@lucide/vue'
import type { ReferralStats } from '@/types'

const props = defineProps<{
    stats: ReferralStats
}>()

const formatRate = (rate: number | null) => rate === null ? '—' : `${rate}%`

const cards = computed(() => [
    { key: 'total', label: 'Referrals', value: props.stats.total, hint: 'All time', icon: Send, color: 'text-primary' },
    { key: 'pending', label: 'Awaiting response', value: props.stats.pending, hint: `${props.stats.not_viewed} not yet viewed`, icon: Hourglass, color: 'text-amber-500' },
    { key: 'viewed', label: 'Viewed', value: props.stats.viewed, hint: `${formatRate(props.stats.view_rate)} of referrals`, icon: Eye, color: 'text-sky-500' },
    { key: 'accepted', label: 'Accepted', value: props.stats.accepted, hint: 'Candidate said yes', icon: CircleCheck, color: 'text-emerald-500' },
    { key: 'rejected', label: 'Rejected', value: props.stats.rejected, hint: 'Candidate declined', icon: CircleX, color: 'text-red-500' },
    { key: 'acceptance', label: 'Acceptance rate', value: formatRate(props.stats.acceptance_rate), hint: 'Of answered referrals', icon: Percent, color: 'text-violet-500' },
])
</script>

<template>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <Card
            v-for="card in cards"
            :key="card.key"
            :data-stat="card.key"
            pt:body:class="p-4 gap-1"
        >
            <template #content>
                <div class="flex items-center justify-between gap-2 text-sm text-muted-color">
                    <span>{{ card.label }}</span>
                    <component
                        :is="card.icon"
                        :class="['size-4 shrink-0', card.color]"
                    />
                </div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ card.value }}
                </div>
                <div class="text-xs text-muted-color">
                    {{ card.hint }}
                </div>
            </template>
        </Card>
    </div>
</template>
