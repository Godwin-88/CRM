<?php

namespace Database\Factories;

use App\Models\ServiceCatalogItem;
use App\Models\ServiceCatalogItemVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCatalogItemVersion>
 */
class ServiceCatalogItemVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_catalog_item_id' => ServiceCatalogItem::factory(),
            'version_number' => 1,
            'fields' => [],
            'required_documents' => [],
            'automation_config' => [],
            'customer_instructions' => null,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
