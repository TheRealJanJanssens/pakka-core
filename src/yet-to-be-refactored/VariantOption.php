<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class VariantOption extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'variant_id', 'product_id', 'name',
    ];

    protected $casts = ['id' => 'string'];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'variant_id' => "required",
            'product_id' => "required",
            'name' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'variant_id' => "required",
            'product_id' => "required",
            'name' => "required",
        ]);
    }
}
