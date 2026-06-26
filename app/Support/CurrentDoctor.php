<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\DoctorCredential;
use App\Models\HIPUser;
use Illuminate\Support\Facades\Auth;

class CurrentDoctor
{
    public static function resolve(): ?Doctor
    {
        $doctorId = session('doctor_id');
        if (filled($doctorId)) {
            return Doctor::query()->find($doctorId);
        }

        $doctorId = self::resolveDoctorId();
        if (! filled($doctorId)) {
            return null;
        }

        session()->put('doctor_id', (string) $doctorId);

        return Doctor::query()->find($doctorId);
    }

    public static function resolveDoctorId(): ?string
    {
        $doctorId = session('doctor_id');
        if (filled($doctorId)) {
            return (string) $doctorId;
        }

        $user = Auth::guard('filament')->user() ?? Auth::user();
        if (! $user) {
            return null;
        }

        $authDoctorId = $user->doctor_id ?? null;
        if (filled($authDoctorId)) {
            return (string) $authDoctorId;
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '') {
            $credentialDoctorId = DoctorCredential::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->value('doctor_id');
            if (filled($credentialDoctorId)) {
                return (string) $credentialDoctorId;
            }

            $doctorIdByEmail = Doctor::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->value('id');
            if (filled($doctorIdByEmail)) {
                return (string) $doctorIdByEmail;
            }
        }

        $organizationId = (int) ($user->organization_id ?? 0);
        $name = strtolower(trim((string) ($user->name ?? $user->full_name ?? '')));
        if ($organizationId > 0 && $name !== '') {
            $doctorIdByName = Doctor::query()
                ->where('organization_id', $organizationId)
                ->whereRaw('LOWER(name) = ?', [$name])
                ->value('id');
            if (filled($doctorIdByName)) {
                return (string) $doctorIdByName;
            }
        }

        $mobile = preg_replace('/\D+/', '', (string) ($user->mobile_num ?? $user->mobile_number ?? ''));
        if ($mobile !== '') {
            $doctorIdByMobile = Doctor::query()
                ->when($organizationId > 0, fn ($q) => $q->where('organization_id', $organizationId))
                ->whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(mobile_number, ''), '+', ''), '-', ''), ' ', ''), '(', ''), ')', '') = ?",
                    [$mobile]
                )
                ->value('id');
            if (filled($doctorIdByMobile)) {
                return (string) $doctorIdByMobile;
            }
        }

        if ($organizationId > 0) {
            $singleDoctorInOrg = Doctor::query()
                ->where('organization_id', $organizationId)
                ->orderBy('name')
                ->value('id');
            if (filled($singleDoctorInOrg)) {
                return (string) $singleDoctorInOrg;
            }
        }

        return null;
    }

    /**
     * HIP user IDs that can log in as this doctor (profile email, credential emails, current session).
     *
     * @return list<string>
     */
    public static function linkedUserIds(?string $doctorId = null): array
    {
        $doctorId = $doctorId ?? self::resolveDoctorId();

        if (! filled($doctorId)) {
            $authId = Auth::guard('filament')->id();

            return $authId ? [(string) $authId] : [];
        }

        $doctor = Doctor::query()->find($doctorId);
        if (! $doctor) {
            $authId = Auth::guard('filament')->id();

            return $authId ? [(string) $authId] : [];
        }

        $orderedIds = [];

        $doctorEmail = strtolower(trim((string) ($doctor->email ?? '')));
        if ($doctorEmail !== '') {
            $id = HIPUser::query()
                ->whereRaw('LOWER(email) = ?', [$doctorEmail])
                ->value('id');
            if (filled($id)) {
                $orderedIds[] = (string) $id;
            }
        }

        $credentialEmails = DoctorCredential::query()
            ->where('doctor_id', $doctorId)
            ->pluck('email');

        foreach ($credentialEmails as $email) {
            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }

            $id = HIPUser::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->value('id');

            if (filled($id) && ! in_array((string) $id, $orderedIds, true)) {
                $orderedIds[] = (string) $id;
            }
        }

        $authId = Auth::guard('filament')->id();
        if ($authId && ! in_array((string) $authId, $orderedIds, true)) {
            $orderedIds[] = (string) $authId;
        }

        return $orderedIds;
    }

    public static function primaryLinkedUserId(?string $doctorId = null): ?string
    {
        $ids = self::linkedUserIds($doctorId);

        return $ids[0] ?? null;
    }
}
