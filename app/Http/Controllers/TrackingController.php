<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    public function redirect(string $token): RedirectResponse
    {
        $recipient = CampaignRecipient::where('tracking_token', $token)->first();

        if ($recipient) {
            $recipient->update([
                'clicked_at' => now(),
                'status' => 'clicked',
            ]);

            if ($recipient->contact) {
                $recipient->contact->interactions()->create([
                    'type' => 'email_click',
                    'description' => 'Clicked campaign link',
                ]);
            }
        }

        return $recipient ? redirect()->away($recipient->redirect_url ?? 'https://example.com') : redirect()->away('https://example.com');
    }

    public function openPixel(string $token): Response
    {
        $recipient = CampaignRecipient::where('tracking_token', $token)->first();

        if ($recipient) {
            $recipient->update([
                'opened_at' => now(),
                'status' => 'opened',
            ]);

            if ($recipient->contact) {
                $recipient->contact->interactions()->create([
                    'type' => 'email_open',
                    'description' => 'Opened campaign email',
                ]);
            }
        }

        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGU2QDhEAQAAAABJRU5ErkJggg==');
        
        return response($pixel, 200)->header('Content-Type', 'image/png')->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
