<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
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
            'name' => 'required',
            'description' => 'required|max:255',
            'image' => 'required|mimes:jpeg,png',
            'category_ids' => 'required',
            'condition_id' => 'required',
            'price' => 'required|numeric|min:0',
        ];
    }

    public function messages() {
        return [
            'name.required' => '商品名を入力してください',
            'description.required' => '商品説明を入力してください',
            'description.max' => '商品説明は255文字以内で入力してください',
            'image.required' => '画像を選択してください',
            'image.mimes' => '画像はjpeg,png形式で選択してください',
            'category_ids.required' => 'カテゴリーを選択してください',
            'condition_id.required' => '商品の状態を選択してください',
            'price.required' => '商品価格を入力してください',
            'price.numeric' => '商品価格は数字で入力してください',
            'price.min' => '商品価格は0以上で入力してください',
        ];
    }
}
