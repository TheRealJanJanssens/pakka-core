<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Variant extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'product_id', 'name',
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
            'product_id' => "required",
            'name' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'product_id' => "required",
            'name' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Variant with its options for the given product
    |
    | $id = Product id
    |------------------------------------------------------------------------------------
    */

    public static function getVariantInputs($id)
    {
        $result = Variant::select([
            'variants.id',
            'variants.product_id',
            'variants.name',
            DB::raw("GROUP_CONCAT( IFNULL(variant_options.name,'') ORDER BY variant_options.id SEPARATOR ',') as option_values"),
            DB::raw("GROUP_CONCAT( IFNULL(variant_options.id,'') ORDER BY variant_options.id SEPARATOR ',') as option_ids"),
        ])
        ->leftJoin('variant_options', 'variants.id', '=', 'variant_options.variant_id')
        ->where('variants.product_id', $id)
        ->orderBy('variants.id')
        ->groupBy('variants.id')
        //->toSql();
        ->get()->toArray();

        return $result;
    }
}
