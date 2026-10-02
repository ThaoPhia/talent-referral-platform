export interface Referral {
    id: number
    status: 'pending' | 'viewed' | 'accepted' | 'rejected'
    recruiter: {
        name: string
        email: string
    }
    candidate: {
        name: string
        email: string
        resume_url: string | null
        note: string | null
    }
    job: {
        title: string
    }
}