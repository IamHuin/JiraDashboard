<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'username' => trim($this->username),
            'password' => trim($this->password),
        ]);
    }
    
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required',
            'password.required' => 'Password is required',
        ];
    }
}
