<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\RegistrationData;
use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use App\Support\Enums\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The single write operation behind /register, branching per role:
 *   - institution: creates the institutions row plus its admin user
 *   - teacher: created unapproved, pending the institution's sign-off
 *   - student: institution is optional
 */
final class RegisterUser
{
    public function __invoke(RegistrationData $data): User
    {
        return DB::transaction(fn (): User => match ($data->role) {
            Role::Institution => $this->registerInstitution($data),
            Role::Teacher => $this->registerTeacher($data),
            Role::Student => $this->registerStudent($data),
        });
    }

    private function registerInstitution(RegistrationData $data): User
    {
        $institution = Institution::create([
            'name' => $data->institutionName,
            'code' => $data->institutionCode,
            'contact_email' => $data->institutionContactEmail,
        ]);

        return $this->createUser($data, Role::Institution, $institution->id, true);
    }

    private function registerTeacher(RegistrationData $data): User
    {
        return $this->createUser($data, Role::Teacher, $data->institutionId, false);
    }

    private function registerStudent(RegistrationData $data): User
    {
        return $this->createUser($data, Role::Student, $data->institutionId, true);
    }

    private function createUser(RegistrationData $data, Role $role, ?int $institutionId, bool $isApproved): User
    {
        return User::create([
            'username' => $data->username,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'official_name' => $data->officialName,
            'role' => $role,
            'institution_id' => $institutionId,
            'is_approved' => $isApproved,
            'qr_token' => $this->uniqueQrToken(),
        ]);
    }

    private function uniqueQrToken(): string
    {
        do {
            $token = Str::random(64);
        } while (User::where('qr_token', $token)->exists());

        return $token;
    }
}
