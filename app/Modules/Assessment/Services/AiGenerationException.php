<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Services;

use RuntimeException;

/** Carries an Arabic, user-safe message — never let a Gemini failure surface as a 500. */
final class AiGenerationException extends RuntimeException {}
