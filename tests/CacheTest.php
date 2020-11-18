<?php 

namespace Switchover;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use Switchover\Exceptions\CacheArgumentException;

class CacheTest extends TestCase
{
    public function testGet() {

        $cache = new KeyValueCache();
        $value = $cache->get('SDK_KEY1', null);

        $this->assertNull($value);

        $value = $cache->get('SDK_KEY2', 'simple_string');

        $this->assertEquals('simple_string', $value);
    }

    public function testSetBasic() {

        $cache = new KeyValueCache();
        $success = $cache->set('SDK_KEY1', 'simple_string');
        $this->assertTrue($success);

        $value = $cache->get('SDK_KEY1', null);
        $this->assertEquals('simple_string', $value);
    }

    public function testSetWithTTL() {

        $cache = new KeyValueCache();
        $success = $cache->set('SDK_KEY1', 'simple_string', 2);

        $this->assertTrue($success);

        $value = $cache->get('SDK_KEY1', null);
        $this->assertEquals('simple_string', $value);
    }

    public function testSetWithTTLExpired() {
        $cache = new KeyValueCache();
        $success = $cache->set('SDK_KEY1', 'simple_string', -1);

        $this->assertTrue($success);

        $value = $cache->get('SDK_KEY1', null);
        $this->assertNull($value);
    }


    public function testHas() {
        $cache = new KeyValueCache();

        $this->assertFalse($cache->has('SDK_KEY'));

        $cache->set('SDK_KEY', 'simple_string');
        $this->assertTrue($cache->has('SDK_KEY'));
    }

    public function testDelete() {
        $cache = new KeyValueCache();
        $cache->set('SDK_KEY', 'simple_string');
        $cache->set('SOME_OTHER_KEY', 'a_Value_001');
        $this->assertTrue($cache->has('SDK_KEY'));

        $cache->delete('SDK_KEY');
        $this->assertFalse($cache->has('SDK_KEY'));

        $this->assertTrue($cache->has('SOME_OTHER_KEY'));
    }

    public function testClear() {
        $cache = new KeyValueCache();
        $cache->set('SDK_KEY', 'simple_string');
        $cache->set('SOME_OTHER_KEY', 'a_Value_001');

        $cache->clear();
        $this->assertFalse($cache->has('SDK_KEY'));
        $this->assertFalse($cache->has('SOME_OTHER_KEY'));
    }

    public function testExceptionOnWrongTTLFormat() {
        $this->expectException(CacheArgumentException::class);

        $cache = new KeyValueCache();
        $cache->set('SDK_KEY', 'simple_string', '34sec');
    }

    public function testExceptionKeyNotAString() {
        $this->expectException(CacheArgumentException::class);

        $cache = new KeyValueCache();
        $cache->set(4711, 'value');
    }
}