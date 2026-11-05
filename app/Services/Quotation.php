<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

final class Quotation
{
    public static function validate(array $data): array
    {
        return Validator::make($data, [
            'reference' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'issued_on' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'seller' => ['required', 'string', 'max:120'],
            'customer' => ['required', 'string', 'max:120'],
            'currency' => ['required', 'in:EGP,SAR,USD'],
            'language' => ['required', 'in:en,ar,bilingual'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:80'],
            'items.*' => ['required', 'array:description,quantity,unit_price'],
            'items.*.description' => ['required', 'string', 'max:240'],
            'items.*.quantity' => ['required', 'string', 'regex:/\A(?:0|[1-9]\d{0,4})(?:\.\d{1,3})?\z/', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'string', 'regex:/\A(?:0|[1-9]\d{0,6})(?:\.\d{1,2})?\z/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();
    }

    public static function calculate(array $quote): array
    {
        $total = 0;
        foreach ($quote['items'] as &$item) {
            $quantity = self::scaled($item['quantity'], 3);
            $unit = self::scaled($item['unit_price'], 2);
            // Round each line to the nearest cent, with half cents rounded up.
            $item['amount_minor'] = intdiv($quantity * $unit + 500, 1000);
            $item['unit_minor'] = $unit;
            $total += $item['amount_minor'];
        }
        unset($item);

        return $quote + ['total_minor' => $total];
    }

    public static function money(int $minor): string
    {
        return number_format(intdiv($minor, 100), 0, '.', ',').'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    private static function scaled(string $value, int $places): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return (int) ($whole.str_pad($fraction, $places, '0'));
    }

    public static function sample(): array
    {
        return [
            'reference' => 'QT-1042',
            'issued_on' => '2026-11-05',
            'valid_until' => '2026-11-19',
            'seller' => 'Northline Studio | استوديو نورث لاين',
            'customer' => 'Cedar Trading | شركة الأرز للتجارة',
            'currency' => 'EGP',
            'language' => 'bilingual',
            'items' => [
                ['description' => 'Website design | تصميم الموقع', 'quantity' => '1', 'unit_price' => '12000.00'],
                ['description' => 'Product photography | تصوير المنتجات', 'quantity' => '3', 'unit_price' => '1500.00'],
                ['description' => 'Launch support | دعم الإطلاق', 'quantity' => '2.5', 'unit_price' => '600.00'],
            ],
            'notes' => 'Delivery within 10 working days. | التسليم خلال ١٠ أيام عمل.',
        ];
    }
}
