<?php

namespace TheRealJanJanssens\PakkaCore\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Centralized default
    }

    // Add common methods if needed
}
