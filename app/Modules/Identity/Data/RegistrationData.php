<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

use App\Support\Enums\Role;

/**
 * Everything collected on the registration form, shaped once so the
 * RegisterUser action never touches a raw request array.
 */
final readonly class RegistrationData
{
    public function __construct(
        public Role $role,
        public string $username,
        public string $email,
        public string $password,
        public string $officialName,
        public ?int $institutionId = null,
        public ?string $institutionName = null,
        public ?string $institutionCode = null,
        public ?string $institutionContactEmail = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            role: Role::from($validated['role']),
            username: $validated['username'],
            email: $validated['email'],
            password: $validated['password'],
            officialName: $validated['official_name'],
            institutionId: isset($validated['institution_id']) ? (int) $validated['institution_id'] : null,
            institutionName: $validated['institution_name'] ?? null,
            institutionCode: $validated['institution_code'] ?? null,
            institutionContactEmail: $validated['institution_contact_email'] ?? null,
        );
    }
}
