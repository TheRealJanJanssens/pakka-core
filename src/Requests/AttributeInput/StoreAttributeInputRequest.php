<?php

namespace TheRealJanJanssens\PakkaCore\Requests\AttributeValue;

use TheRealJanJanssens\PakkaCore\Requests\BaseRequest;

class StoreAttributeInputRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'set_id'      => ['required', 'string'],
            'name'        => ['required', 'string'],
            'type'        => ['required', 'string'],
        ];
    }
}
