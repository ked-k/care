<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Settings;

class SettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_get_scalar()
    {
        Settings::set('test.key', 'value123');
        $this->assertEquals('value123', Settings::get('test.key'));
    }

    public function test_set_and_get_array()
    {
        $data = ['a' => 1, 'b' => 'two'];
        Settings::set('test.arr', $data);
        $this->assertEquals($data, Settings::get('test.arr'));
    }

    public function test_forget()
    {
        Settings::set('test.forget', 'x');
        $this->assertTrue(Settings::has('test.forget'));
        Settings::forget('test.forget');
        $this->assertFalse(Settings::has('test.forget'));
    }
}
