<?php

namespace App\Support;

use App\Models\Referral;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts the job_id + candidate_id pair that identifies a referral into a URL-safe token.
 */
class ReferralInvitationToken
{
    public static function findReferral(string $token): Referral
    {
        $pair = self::decode($token);

        abort_if($pair === null, 404);

        return Referral::query()
            ->with(['job', 'referrer', 'candidate'])
            ->where('job_id', $pair['job_id'])
            ->where('candidate_id', $pair['candidate_id'])
            ->firstOrFail();
    }

    public static function encode(Referral $referral): string
    {
        $payload = json_encode([
            'job_id' => $referral->job_id,
            'candidate_id' => $referral->candidate_id,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');
    }

    /**
     * @return array{job_id: int, candidate_id: int}|null
     */
    public static function decode(string $token): ?array
    {
        $encrypted = base64_decode(strtr($token, '-_', '+/'), true);

        if ($encrypted === false) {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($encrypted), true, 2, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($payload) || ! is_int($payload['job_id'] ?? null) || ! is_int($payload['candidate_id'] ?? null)) {
            return null;
        }

        return ['job_id' => $payload['job_id'], 'candidate_id' => $payload['candidate_id']];
    }
}
