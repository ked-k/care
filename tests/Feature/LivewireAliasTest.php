<?php

namespace Tests\Feature;

use Livewire\Livewire;
use Tests\TestCase;

class LivewireAliasTest extends TestCase
{
    public function testCareCallTimelineAliasIsRegistered(): void
    {
        $this->assertTrue(Livewire::exists('care-timeline.care-call-timeline'));
    }
}
