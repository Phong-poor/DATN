<?php

namespace Tests\Feature;

use Tests\TestCase;

class RefundFileSecurityTest extends TestCase
{
    public function test_refund_files_are_not_publicly_accessible(): void
    {
        $this->getJson('/api/refund-file?path=refund_proofs/example.jpg')
            ->assertUnauthorized();
    }
}
