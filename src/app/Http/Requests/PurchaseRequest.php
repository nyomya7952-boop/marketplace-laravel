<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class PurchaseRequest extends FormRequest
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
            'payment_method_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'payment_method_id.required' => '支払方法を選択してください',
            'shipping' => '配送先を設定してください',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $postalCode = session('shipping_postal_code', $user->postal_code);
            $address = session('shipping_address', $user->address);

            if (empty($postalCode) || $postalCode === '000' || empty($address) || $address === '住所未設定') {
                $validator->errors()->add('shipping', '配送先を設定してください');
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // AJAX（expectsJson）の場合はJSONで返す（購入画面のJSがflash領域に描画する）
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        // 通常のフォーム送信はリダイレクトで返す（flash.blade.phpが表示される）
        throw new HttpResponseException(
            redirect()->back()
                ->withErrors($validator)
                ->withInput()
        );
    }
}
