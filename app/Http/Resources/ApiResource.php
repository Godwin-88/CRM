<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'data' => $this->resolveData($request),
        ];
    }

    protected function resolveData($request): array
    {
        return parent::toArray($request);
    }
}
