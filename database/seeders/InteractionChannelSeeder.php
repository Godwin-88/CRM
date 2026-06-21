<?php

namespace Database\Seeders;

use App\Models\InteractionChannel;
use Illuminate\Database\Seeder;

class InteractionChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            ['name' => 'call', 'display_name' => 'Call', 'icon' => 'Phone'],
            ['name' => 'email', 'display_name' => 'Email', 'icon' => 'Mail'],
            ['name' => 'chat', 'display_name' => 'Live Chat', 'icon' => 'MessageSquare'],
            ['name' => 'sms', 'display_name' => 'SMS', 'icon' => 'MessageSquare'],
            ['name' => 'whatsapp', 'display_name' => 'WhatsApp', 'icon' => 'MessageSquare'],
            ['name' => 'facebook', 'display_name' => 'Facebook', 'icon' => 'Facebook'],
            ['name' => 'linkedin', 'display_name' => 'LinkedIn', 'icon' => 'Linkedin'],
            ['name' => 'instagram', 'display_name' => 'Instagram', 'icon' => 'Instagram'],
            ['name' => 'tiktok', 'display_name' => 'TikTok', 'icon' => 'Music2'],
            ['name' => 'in_person', 'display_name' => 'In-Person', 'icon' => 'User'],
            ['name' => 'field_visit', 'display_name' => 'Field Visit', 'icon' => 'MapPin'],
            ['name' => 'kiosk', 'display_name' => 'Kiosk', 'icon' => 'PanelBottom'],
        ];

        foreach ($channels as $channel) {
            InteractionChannel::create($channel);
        }
    }
}