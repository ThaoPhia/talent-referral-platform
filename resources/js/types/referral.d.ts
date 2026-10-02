export interface Referral {
    id: number
    status: 'pending' | 'accepted' | 'rejected'
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