export type ReferralStatus = 'pending' | 'accepted' | 'rejected'

export type ReferralResumeType = 'link' | 'file'

export interface Referral {
    id: number
    status: ReferralStatus
    referrer_note: string | null
    candidate_note: string | null
    resume_type: ReferralResumeType | null
    resume_link: string | null
    viewed_at: string | null
    responded_at: string | null
    created_at: string
    referrer: {
        name: string
        email: string
    }
    candidate: {
        name: string
        email: string
    }
    job: {
        title: string
    }
}

export interface ReferralStats {
    total: number
    pending: number
    accepted: number
    rejected: number
    viewed: number
    not_viewed: number
    view_rate: number | null
    acceptance_rate: number | null
}

export interface AppNotification {
    id: string
    message: string
    status: ReferralStatus | null
    candidate_note: string | null
    read_at: string | null
    created_at: string
}

export interface NotificationsProps {
    unread_count: number
    items: AppNotification[]
}
