<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Contact::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'email', 
                \Illuminate\Validation\Rule::unique('contacts', 'email')->whereNull('deleted_at')
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'in:lead,prospect,customer,partner'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'in:active,inactive,churned,reactivated'],
            'source' => ['nullable', 'string', 'max:50'],
            'loyalty_tier' => ['nullable', 'in:bronze,silver,gold,platinum'],
            'preferred_channel' => ['nullable', 'string', 'max:20'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
