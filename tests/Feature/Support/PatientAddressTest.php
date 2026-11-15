<?php

namespace Tests\Feature\Support;

use App\Support\PatientAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PatientAddressTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string|null}>
     */
    public static function patients(): array
    {
        $locations = ['location' => '40.1,-74.1', 'location2' => '40.2,-74.2', 'location3' => '40.3,-74.3'];

        return [
            'first address' => [$locations + ['primary_address' => 1], '40.1,-74.1'],
            'second address' => [$locations + ['primary_address' => 2], '40.2,-74.2'],
            'third address, stored as text' => [$locations + ['primary_address' => '3'], '40.3,-74.3'],
            'unset primary means the first address' => [$locations + ['primary_address' => null], '40.1,-74.1'],
            'unknown primary means the first address' => [$locations + ['primary_address' => 7], '40.1,-74.1'],
            'row without the column' => [$locations, '40.1,-74.1'],
            'primary address without a location' => [['location2' => ''] + $locations + ['primary_address' => 2], null],
            'no locations at all' => [['primary_address' => 1, 'location' => null], null],
        ];
    }

    /**
     * @param array<string, mixed> $patient
     */
    #[DataProvider('patients')]
    public function test_primary_location(array $patient, ?string $expected): void
    {
        $this->assertSame($expected, PatientAddress::primaryLocation((object) $patient));
    }
}
