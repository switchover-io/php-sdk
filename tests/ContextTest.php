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
}
