<?php

namespace TheRealJanJanssens\PakkaCore\Requests\AttributeValue;

use Illuminate\Foundation\Http\FormRequest;
use TheRealJanJanssens\PakkaCore\Requests\BaseRequest;

class StoreAttributeValueRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'input_id'      => ['required', 'integer'],
            'item_id'       => ['required', 'integer'],
            'language_code' => ['nullable', 'string', 'max:10'],
            'option_id'     => ['nullable', 'integer'],
            'value'         => ['nullable', 'string'],
        ];
    }
}
