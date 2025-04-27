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
            'text'           => ['required', 'string'],
            'language_code'  => ['required', 'string'],
            'translation_id' => ['required', 'string', 'max:8'],
        ];
    }
}
