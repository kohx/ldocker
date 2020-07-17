<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhoto extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * ユーザーにこのリクエストを行う権限があるかどうかをチェックする
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        //
    }

    /**
     * Get the validation rules that apply to the request.
     * バリデーションをここに書く
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 必須入力、ファイル、ファイルタイプが jpg,jpeg,png,gif であることをルールとして定義
            'photo' => 'required|file|mimes:jpg,jpeg,png,gif'
        ];
    }


    /**
     * エラーメッセージのカスタマイズ
     * エラーメッセージのカスタマイズをする場合は以下のように書く
     * @return array
     */
    public function messages()
    {
        return [
            // 'photo.required' => __('Please enter your name.'),
        ];
    }

    /**
     * 独自処理を追加する
     * 独自処理を追加する場合は以下のように書く
     * @param $validator
     */
    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {
        // if ($this->somethingElseIsInvalid()) {
        //     $validator->errors()->add('field', __('Something is wrong with this field!'));
        // }
        // });
    }
}
