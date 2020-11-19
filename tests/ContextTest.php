<?php

declare(strict_types=1);

namespace Switchover;

use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{

    public function testSetValue()
    {
        $ctx = new Context();

        $ctx->set('userId', 'aUserId001f');

        $this->assertEquals($ctx->get('userId'), 'aUserId001f');
    }

    public function testGetEmptyValue()
    {
        $ctx = new Context();
        $userId = $ctx->get('userId');

        $this->assertNull($userId);
    }

    public function testSetValuesViaCtorArray() {
        $ctx = new Context( [
            'userId' => 'aUserId001',
            'email' => 'user@mail.com'
        ] );

        $this->assertEquals('aUserId001', $ctx->get('userId'));
        $this->assertEquals('user@mail.com', $ctx->get('email'));
    }
}
