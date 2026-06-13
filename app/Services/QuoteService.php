<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuoteService
{
    public function generateQuote(Deal $deal, QuoteTemplate $template, array $lineItems = [], ?int $validityDays = null): Quote
    {
        $total = 0;

        foreach ($lineItems as $item) {
            $lineTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            $total += $lineTotal;
        }

        $quote = $deal->quotes()->create([
            'quote_template_id' => $template->id,
            'created_by' => auth()->id(),
            'total' => $total,
        ]);

        foreach ($lineItems as $item) {
            $lineTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            $quote->lineItems()->create([
                'product_name' => $item['product_name'],
                'sku' => $item['sku'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'total' => $lineTotal,
            ]);
        }

        $pdf = $this->generatePdf($quote, $template, $validityDays);
        $path = "quotes/{$quote->id}/".Str::slug($deal->title).'.pdf';
        Storage::disk('s3')->put($path, $pdf->output());

        $quote->update([
            'pdf_path' => $path,
            'shareable_link' => route('quotes.show', $quote->id),
        ]);

        activity()
            ->performedOn($deal)
            ->causedBy(auth()->user())
            ->withProperties(['quote_id' => $quote->id, 'template_id' => $template->id])
            ->event('quote_generated')
            ->log('Quote generated for deal');

        return $quote;
    }

    protected function generatePdf(Quote $quote, QuoteTemplate $template, ?int $validityDays = null): mixed
    {
        $quote->load(['deal.account', 'deal.contact', 'deal.owner', 'lineItems']);

        $replacements = [
            '{{deal_value}}' => number_format($quote->deal->value, 2),
            '{{contact_name}}' => $quote->deal->contact->first_name.' '.$quote->deal->contact->last_name,
            '{{account_name}}' => $quote->deal->account->name,
            '{{validity_date}}' => $validityDays ? now()->addDays($validityDays)->format('M j, Y') : null,
            '{{agent_name}}' => $quote->deal->owner->name,
            '{{agent_signature}}' => $quote->deal->owner->name,
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template->content
        );

        return Pdf::loadHtml($content);
    }

    public function markAsAccepted(Quote $quote): Quote
    {
        $quote->update(['status' => 'accepted']);

        activity()
            ->performedOn($quote->deal)
            ->causedBy(auth()->user())
            ->withProperties(['quote_id' => $quote->id])
            ->event('quote_accepted')
            ->log('Quote marked as accepted');

        return $quote;
    }

    public function markAsDeclined(Quote $quote): Quote
    {
        $quote->update(['status' => 'declined']);

        activity()
            ->performedOn($quote->deal)
            ->causedBy(auth()->user())
            ->withProperties(['quote_id' => $quote->id])
            ->event('quote_declined')
            ->log('Quote marked as declined');

        return $quote;
    }
}
