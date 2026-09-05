<?php

namespace Tests\Unit;

use App\Support\Settings;
use PHPUnit\Framework\TestCase;

class SettingsPhoneTest extends TestCase
{
    public function test_bulgarian_numbers_normalise_to_e164(): void
    {
        $this->assertSame('+359886190124', Settings::toE164('088 619 0124'));
        $this->assertSame('+359886190124', Settings::toE164('0886190124'));
        $this->assertSame('+359886190124', Settings::toE164('+359 88 619 0124'));
        $this->assertSame('+359886190124', Settings::toE164('00359886190124'));
        $this->assertSame('+359886190124', Settings::toE164('886190124'));
        $this->assertSame('+359894200634', Settings::toE164('089 420 0634'));
        $this->assertNull(Settings::toE164(''));
        $this->assertNull(Settings::toE164(null));
    }

    public function test_display_format_is_national_3_3_4(): void
    {
        $this->assertSame('088 619 0124', Settings::phoneDisplay('+359886190124'));
        $this->assertSame('089 420 0634', Settings::phoneDisplay('089 420 0634'));
        $this->assertSame('', Settings::phoneDisplay(null));
        $this->assertSame('+4917612345678', Settings::phoneDisplay('+4917612345678'));
    }

    public function test_tel_href_uses_e164(): void
    {
        $this->assertSame('+359886190124', Settings::phoneHref('088 619 0124'));
    }
}
