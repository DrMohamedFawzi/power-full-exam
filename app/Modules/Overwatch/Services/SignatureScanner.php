<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Services;

use App\Modules\Overwatch\Enums\AttackType;

/**
 * Regex signatures for the four attack classes the WAF looks for.
 *
 * Every pattern here is deliberately narrow rather than broad: it must require a
 * structural marker of the attack (a SQL keyword combination, an HTML tag, a
 * repeated traversal sequence, a shell metacharacter next to a command word),
 * not a single English word that could plausibly appear in ordinary prose, a
 * JSON payload, or a code-flavoured exam answer. Each pattern documents why it
 * is shaped the way it is, so a reviewer can see what class of false positive
 * it was designed to avoid.
 *
 * Arabic prose, quotes, angle brackets, and JSON structures are exercised by
 * tests/Unit/Overwatch/SignatureScannerTest.php and must never match.
 */
final class SignatureScanner
{
    /**
     * @var array<string, list<string>>
     */
    private const SIGNATURES = [
        // SQL injection: requires a recognisable SQL clause combination or a
        // classic tautology injected next to a quote — never a bare keyword
        // like "select" or "from", which are common English/Arabic-adjacent
        // words in free text and JSON keys.
        'sql_injection' => [
            '/\bunion\s+(all\s+)?select\b/i',
            '/\bselect\b.{0,80}\bfrom\b.{0,40}\binformation_schema\b/is',
            '/\bdrop\s+table\b/i',
            '/\binsert\s+into\s+\w+\s*\(.*\)\s*values\s*\(/is',
            '/\bdelete\s+from\s+\w+\s+where\b/i',
            "/['\"]\\s*(or|and)\\s+['\"]?\\d+['\"]?\\s*=\\s*['\"]?\\d+/i",
            '/;\s*(drop|delete|update|insert|alter)\s+\w+/i',
            '/\bxp_cmdshell\b/i',
        ],

        // Cross-site scripting: requires an actual tag opener or an inline
        // event-handler *assignment* (`onerror="` / `onerror=alert(`), not the
        // bare word "onerror" which can appear in a technical support answer.
        'xss' => [
            '/<script[\s>\/]/i',
            '/<iframe[\s>]/i',
            '/<svg[^>]*onload\s*=/i',
            '/\bon(error|load|click|mouseover|focus|pointerover)\s*=\s*["\']?\s*[\w(]/i',
            '/javascript:\s*\S/i',
            '/<img[^>]+onerror\s*=/i',
        ],

        // Path traversal: a single "../" is common in legitimate relative
        // references (and in prose describing file paths), so we require at
        // least two chained traversal segments, or an explicit sensitive
        // target file, before flagging it.
        'path_traversal' => [
            '/(?:\.\.(?:\/|\\\\)){2,}/',
            '/\betc\/passwd\b/i',
            '/\bwin(dows)?\\\\system32\b/i',
            '/\bboot\.ini\b/i',
        ],

        // Command injection: a shell metacharacter immediately followed by a
        // known command word — not the command word alone, since "cat" or
        // "ls" can be substrings of ordinary answers (e.g. "category").
        'command_injection' => [
            '/(;|\|\||&&|\|)\s*(cat|ls|whoami|wget|curl|nc|bash|sh|powershell|cmd\.exe|rm\s+-rf)\b/i',
            '/`\s*(cat|ls|rm|wget|curl|whoami)\b/i',
            '/\$\(\s*(cat|rm|wget|curl|whoami)\b.*\)/i',
        ],
    ];

    /**
     * Scan a single string value. Returns the first matching attack type, or
     * null when nothing in the signature set matches.
     */
    public function scan(string $value): ?AttackType
    {
        if ($value === '') {
            return null;
        }

        foreach (self::SIGNATURES as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value) === 1) {
                    return AttackType::from($type);
                }
            }
        }

        return null;
    }

    /**
     * Scan a nested array of request input (query or body). Returns the first
     * attack type found, or null. Non-scalar values are traversed recursively;
     * everything else (bool, int, null, files) is skipped, since only strings
     * can carry an injection payload.
     *
     * @param  array<array-key, mixed>  $input
     */
    public function scanArray(array $input): ?AttackType
    {
        foreach ($input as $value) {
            if (is_array($value)) {
                if ($found = $this->scanArray($value)) {
                    return $found;
                }

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if ($found = $this->scan($value)) {
                return $found;
            }
        }

        return null;
    }
}
