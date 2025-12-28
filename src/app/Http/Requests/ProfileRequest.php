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
            'name' => 'required|max:20',
            'postal_code' => 'required|regex:/^\d{3}-\d{4}$/',
            'address' => 'required',
            'profile_image_path' => 'nullable|image|mimetypes:image/jpeg,image/png',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'お名前を入力してください',
            'name.max' => 'お名前は20文字以内で入力してください',
            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号は3桁-4桁の形式で入力してください',
            'address.required' => '住所を入力してください',
            'profile_image_path.image' => '画像ファイルを選択してください',
            'profile_image_path.mimetypes' => '画像はjpeg,png形式で選択してください',
        ];
    }
}
