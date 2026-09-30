<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\WhatsAppPhoneNormalizer;
use Tests\TestCase;

class WhatsAppPhoneNormalizerTest extends TestCase
{
    public function test_strips_plus_spaces_and_leading_zero_then_applies_country_code(): void
    {
        config(['services.whatsapp.default_country_code' => '91']);

        $normalizer = new WhatsAppPhoneNormalizer;

        $this->assertSame('919876543210', $normalizer->normalize('09876543210'));
        $this->assertSame('919999999999', $normalizer->normalize('+91 99999 99999'));
        $this->assertSame('919999999999', $normalizer->normalize('919999999999'));
        $this->assertNull($normalizer->normalize(null));
        $this->assertNull($normalizer->normalize('000'));
    }
}
