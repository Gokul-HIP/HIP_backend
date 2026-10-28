<?php

namespace Tests\Unit;

use App\Services\Hms\HmsMobile;
use PHPUnit\Framework\TestCase;

class HmsMobileTest extends TestCase
{
    public function test_accepted_formats_match_the_stored_ten_digit_number(): void
    {
        $expected = '8296951192';

        foreach ([
            '8296951192',
            ' 8296951192 ',
            '08296951192',
            '+918296951192',
            '918296951192',
            '+91 82969 51192',
            '82969-51192',
            '0918296951192',
            '9108296951192',
            '+91 (829) 695-1192',
        ] as $mobile) {
            $this->assertSame($expected, HmsMobile::normalize($mobile), $mobile);
        }
    }

    public function test_a_ten_digit_number_starting_with_91_is_kept(): void
    {
        $this->assertSame('9123456789', HmsMobile::normalize('9123456789'));
        $this->assertSame('9123456789', HmsMobile::normalize('09123456789'));
        $this->assertSame('9123456789', HmsMobile::normalize('+91 9123456789'));
    }

    public function test_values_with_no_digits_are_empty(): void
    {
        $this->assertSame('', HmsMobile::normalize(''));
        $this->assertSame('', HmsMobile::normalize('   '));
        $this->assertSame('', HmsMobile::normalize('000'));
        $this->assertSame('', HmsMobile::normalize('+--'));
    }
}
