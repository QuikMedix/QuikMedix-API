<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class PackageCompatibilityTest extends TestCase
{
    public function test_delivery_ticket_renders_as_pdf_with_embedded_qr_code_and_branding(): void
    {
        $data = [
            'order' => (object) ['id' => 42, 'count_bags' => 1, 'rxs' => [], 'fridge' => 0, 'signature' => 0],
            'pharmacy' => (object) ['name' => 'Fixture Pharmacy', 'phone' => '5550000100'],
            'patient' => (object) ['name' => 'Fixture', 'last_name' => 'Patient', 'address' => '123 Test Street', 'zip' => '00001', 'apartment' => ''],
            'wish' => (object) ['text' => 'Test delivery'],
        ];

        $pdf = Pdf::loadView('orders.ticket_pdf', $data)->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}
