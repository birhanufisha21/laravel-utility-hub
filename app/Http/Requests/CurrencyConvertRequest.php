<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CurrencyConvertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'from'   => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'to'     => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'from.regex' => 'The from field must be a 3-letter uppercase currency code (e.g. USD).',
            'to.regex'   => 'The to field must be a 3-letter uppercase currency code (e.g. EUR).',
            'amount.gt'  => 'The amount must be greater than 0.',
        ];
    }

    /**
     * Return a JSON 422 response instead of redirecting on validation failure.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
