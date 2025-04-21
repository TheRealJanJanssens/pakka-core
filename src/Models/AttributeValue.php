<?php

namespace TheRealJanJanssens\PakkaCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class AttributeValue extends Model
{
    use Notifiable;

    public $timestamps = false;

    protected $fillable = [
        'input_id',
        'item_id',
        'language_code',
        'option_id',
        'value',
    ];
}
