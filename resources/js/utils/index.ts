import { PrimeVueUIColorSeverity, ReferralStatus } from '@/types'
import { twMerge } from 'tailwind-merge'
import { mergeProps } from 'vue'

export const ptViewMerge = (
    globalPTProps = {} as any,
    selfPTProps = {} as any,
    datasets: any
) => {
    const { class: globalClass, ...globalRest } = globalPTProps
    const { class: selfClass, ...selfRest } = selfPTProps

    return mergeProps(
        { class: twMerge(globalClass, selfClass) },
        globalRest,
        selfRest,
        datasets
    )
}

export const resolveFlashSeverity = (key: string): PrimeVueUIColorSeverity => {
    const [prefix] = key.split('_')

    if (prefix === 'success') {
        return 'success'
    }
    if (prefix === 'info') {
        return 'info'
    }
    if (prefix === 'warn' || prefix === 'warning') {
        return 'warn'
    }
    if (prefix === 'error') {
        return 'error'
    }

    return 'secondary'
}

export const formatDateTime = (value: string | null | undefined): string => value
    ? new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
    : ''

// Date-only values (Y-m-d) are calendar days; parsing them with new Date() would shift them by the UTC offset.
export const formatCalendarDate = (value: string | null | undefined): string => {
    if (!value) {
        return ''
    }

    const [year, month, day] = value.slice(0, 10).split('-').map(Number)

    return new Date(year, month - 1, day).toLocaleDateString(undefined, { dateStyle: 'medium' })
}

export const referralStatusSeverity = (status: ReferralStatus): 'warn' | 'success' | 'danger' => ({
    pending: 'warn',
    accepted: 'success',
    rejected: 'danger',
} as const)[status]

export const referralStatusLabel = (status: ReferralStatus): string => ({
    pending: 'Pending',
    accepted: 'Accepted',
    rejected: 'Rejected',
})[status]
