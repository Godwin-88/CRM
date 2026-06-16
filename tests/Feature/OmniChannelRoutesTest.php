<?php

namespace Tests\Feature;

use Tests\TestCase;

class OmniChannelRoutesTest extends TestCase
{
    public function test_it_registers_all_omni_channel_routes(): void
    {
        $required = [
            'admin.omni.dashboard',
            'admin.omni.contact-center',
            'admin.omni.kiosk',
            'admin.interactions.inbox',
            'admin.interactions.channels',
            'admin.interactions.unmatched',
            'admin.email.compose',
            'admin.sms.compose',
            'admin.call.log',
            'admin.chat.inbox',
            'admin.ivr.transcriptions',
            'admin.field-channel',
            'admin.queue-stats',
        ];

        foreach ($required as $name) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($name),
                "Route '{$name}' should be registered"
            );
        }
    }
}
