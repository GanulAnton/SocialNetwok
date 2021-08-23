<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'full_name' => 'nullable|string',
            'nick_name' => 'nullable|string|unique:profiles',
            'date_of_birthday' => 'nullable|string',
            'interests' => 'nullable|string',
            'is_private' => 'boolean'
        ];
    }
}
