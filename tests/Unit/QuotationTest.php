<?php

namespace Tests\Unit;

use App\Services\Quotation;
use PHPUnit\Framework\TestCase;

final class QuotationTest extends TestCase
{
    public function test_fractional_quantities_round_half_up_per_line(): void
    {
        $quote = Quotation::calculate(['items' => [
            ['quantity' => '0.5', 'unit_price' => '0.01'],
            ['quantity' => '0.25', 'unit_price' => '0.03'],
            ['quantity' => '1.500', 'unit_price' => '0.1'],
        ]]);

        $this->assertSame([1, 1, 15], array_column($quote['items'], 'amount_minor'));
        $this->assertSame('0.17', Quotation::money($quote['total_minor']));
    }
}
