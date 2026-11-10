<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Fallback exchange rates against USD in case Frankfurter API is unreachable.
     */
    protected const FALLBACK_RATES = [
        'USD' => 1.0,
        'IDR' => 18077.0,
        'EUR' => 0.8934,
        'SGD' => 1.2801,
        'JPY' => 157.95,
        'AUD' => 1.4505,
        'CNY' => 6.6728,
        'GBP' => 0.7454,
    ];

    /**
     * Map country name or code to official currency.
     */
    protected const COUNTRY_CURRENCIES = [
        'Indonesia' => ['code' => 'IDR', 'symbol' => 'Rp', 'name' => 'Indonesian Rupiah'],
        'Netherlands' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'Germany' => ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
        'Singapore' => ['code' => 'SGD', 'symbol' => 'S$', 'name' => 'Singapore Dollar'],
        'Japan' => ['code' => 'JPY', 'symbol' => '¥', 'name' => 'Japanese Yen'],
        'United States' => ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'],
        'Australia' => ['code' => 'AUD', 'symbol' => 'A$', 'name' => 'Australian Dollar'],
        'China' => ['code' => 'CNY', 'symbol' => '¥', 'name' => 'Chinese Yuan'],
        'United Kingdom' => ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound'],
    ];

    /**
     * Retrieve live or cached currency exchange rates against USD from Frankfurter API.
     *
     * @return array
     */
    public function getRates(): array
    {
        return Cache::remember('frankfurter_rates_usd', 3600, function () {
            try {
                $response = Http::timeout(4)
                    ->get('https://api.frankfurter.dev/v1/latest', [
                        'base' => 'USD',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rates = $data['rates'] ?? [];
                    $rates['USD'] = 1.0;
                    return array_merge(self::FALLBACK_RATES, $rates);
                }
            } catch (\Throwable $e) {
                Log::warning('CurrencyService: Failed to fetch Frankfurter API, using fallback rates', [
                    'error' => $e->getMessage(),
                ]);
            }

            return self::FALLBACK_RATES;
        });
    }

    /**
     * Convert an amount between two currencies.
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @return float
     */
    public function convert(float $amount, string $from = 'USD', string $to = 'IDR'): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to || $amount <= 0) {
            return $amount;
        }

        $rates = $this->getRates();

        $fromRate = $rates[$from] ?? (self::FALLBACK_RATES[$from] ?? 1.0);
        $toRate = $rates[$to] ?? (self::FALLBACK_RATES[$to] ?? 1.0);

        // Convert from source currency to USD, then from USD to target currency
        $amountInUsd = $amount / $fromRate;
        $converted = $amountInUsd * $toRate;

        return round($converted, 2);
    }

    /**
     * Format an amount nicely with currency symbol.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function format(float $amount, string $currency = 'USD'): string
    {
        $currency = strtoupper(trim($currency));

        return match ($currency) {
            'IDR' => 'Rp ' . number_format($amount, 0, ',', '.'),
            'EUR' => '€ ' . number_format($amount, 2, ',', '.'),
            'GBP' => '£ ' . number_format($amount, 2, '.', ','),
            'JPY' => '¥ ' . number_format($amount, 0, '.', ','),
            'SGD' => 'S$ ' . number_format($amount, 2, '.', ','),
            'AUD' => 'A$ ' . number_format($amount, 2, '.', ','),
            'CNY' => '¥ ' . number_format($amount, 2, '.', ','),
            default => '$ ' . number_format($amount, 2, '.', ','),
        };
    }

    /**
     * Resolve currency metadata based on country name or location.
     *
     * @param string|null $country
     * @return array
     */
    public function getCurrencyForCountry(?string $country): array
    {
        if (! $country) {
            return ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'];
        }

        foreach (self::COUNTRY_CURRENCIES as $key => $meta) {
            if (stripos($country, $key) !== false) {
                return $meta;
            }
        }

        return ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar'];
    }

    /**
     * Calculate comprehensive valuation for a shipment (Base + Destination Currency + Origin Currency).
     *
     * @param float $baseAmount
     * @param string $baseCurrency
     * @param string|null $originCountry
     * @param string|null $destinationCountry
     * @return array
     */
    public function calculateShipmentValuation(
        float $baseAmount,
        string $baseCurrency = 'USD',
        ?string $originCountry = null,
        ?string $destinationCountry = null
    ): array {
        $destCurrency = $this->getCurrencyForCountry($destinationCountry);
        $originCurrency = $this->getCurrencyForCountry($originCountry);

        $destAmount = $this->convert($baseAmount, $baseCurrency, $destCurrency['code']);
        $originAmount = $this->convert($baseAmount, $baseCurrency, $originCurrency['code']);

        return [
            'base' => [
                'amount' => $baseAmount,
                'currency' => $baseCurrency,
                'formatted' => $this->format($baseAmount, $baseCurrency),
            ],
            'destination' => [
                'country' => $destinationCountry,
                'code' => $destCurrency['code'],
                'symbol' => $destCurrency['symbol'],
                'name' => $destCurrency['name'],
                'amount' => $destAmount,
                'formatted' => $this->format($destAmount, $destCurrency['code']),
            ],
            'origin' => [
                'country' => $originCountry,
                'code' => $originCurrency['code'],
                'symbol' => $originCurrency['symbol'],
                'name' => $originCurrency['name'],
                'amount' => $originAmount,
                'formatted' => $this->format($originAmount, $originCurrency['code']),
            ],
            'exchange_rate' => [
                'pair' => "1 {$baseCurrency} = " . number_format($this->convert(1.0, $baseCurrency, $destCurrency['code']), 4) . " {$destCurrency['code']}",
                'source' => 'Frankfurter API / European Central Bank',
            ],
        ];
    }
}
