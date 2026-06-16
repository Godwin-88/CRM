<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomFieldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomField::query()->orderBy('entity_type')->orderBy('name');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        return response()->json($query->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:text,number,date,select,boolean'],
            'entity_type' => ['required', 'string', 'in:contact,account'],
            'options' => ['nullable', 'array'],
        ]);

        $field = CustomField::create($validated);

        return response()->json($field, 201);
    }

    public function show(CustomField $customField): JsonResponse
    {
        return response()->json($customField);
    }

    public function update(Request $request, CustomField $customField): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:text,number,date,select,boolean'],
            'entity_type' => ['sometimes', 'string', 'in:contact,account'],
            'options' => ['nullable', 'array'],
        ]);

        $customField->update($validated);

        return response()->json($customField);
    }

    public function destroy(CustomField $customField): JsonResponse
    {
        $customField->delete();

        return response()->json(null, 204);
    }
}
