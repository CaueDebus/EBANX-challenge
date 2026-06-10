<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:deposit,withdraw,transfer',
            'amount' => 'required|numeric|gt:0',
            // different:origin impede transferência para a mesma conta de origem
            'destination' => 'required_if:type,deposit|required_if:type,transfer|string|different:origin',
            'origin' => 'required_if:type,withdraw|required_if:type,transfer|string',
        ];
    }
}
