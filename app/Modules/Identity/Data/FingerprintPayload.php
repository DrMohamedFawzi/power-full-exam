<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

/**
 * A single client-side fingerprint snapshot. Built by whatever caller has the
 * raw signal (the proctoring client today) and handed to BindDevice.
 */
final readonly class FingerprintPayload
{
    public function __construct(
        public ?string $userAgent = null,
        public ?string $screenResolution = null,
        public ?string $timezone = null,
        public ?string $canvasHash = null,
        public ?string $webglVendor = null,
        public ?string $webglRenderer = null,
        public bool $isHeadless = false,
        public array $raw = [],
    ) {}
}
