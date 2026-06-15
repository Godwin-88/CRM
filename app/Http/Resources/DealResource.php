<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'account_id' => $this->account_id,
            'contact_id' => $this->contact_id,
            'pipeline_id' => $this->pipeline_id,
            'stage' => $this->stage,
            'value' => $this->value,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'expected_close_date' => $this->expected_close_date,
            'owner_id' => $this->owner_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}