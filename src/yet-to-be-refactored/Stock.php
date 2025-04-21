<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Stock extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'product_id', 'sku_id', 'sku', 'price', 'quantity', 'weight',
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
            'sku_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'product_id' => "required",
            'sku_id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Stock inputs for the given product
    |
    | $id = Product id
    |------------------------------------------------------------------------------------
    */

    public static function getStockInputs($id)
    {
        $result = Stock::select([
            'stocks.id',
            'stocks.product_id',
            'stocks.sku',
            'stocks.price',
            'stocks.quantity',
            'stocks.weight',
            DB::raw("GROUP_CONCAT( DISTINCT variant_values.id ORDER BY variant_options.id SEPARATOR ',') as option_ids"),
            DB::raw("GROUP_CONCAT( DISTINCT variant_values.option_id ORDER BY variant_options.id SEPARATOR ',') as option_option_ids"),
            DB::raw("GROUP_CONCAT( DISTINCT
					CASE variant_values.option_id
				    WHEN variant_options.id THEN variant_options.name
				    END
					ORDER BY variant_options.id SEPARATOR ',') as option_values"),

        ])
        ->leftJoin('variant_values', 'stocks.id', '=', 'variant_values.stock_id')
        ->leftJoin('variant_options', 'variant_values.variant_id', '=', 'variant_options.variant_id')
        ->where('stocks.product_id', $id)
        ->orderBy('stocks.id')
        ->groupBy('stocks.id');

        $result = Cache::tags('collections')->remember('product:stock:'.$id, 60 * 60 * 24, function () use ($result) {
            return $result->get();
        });


        return $result->toArray();
    }
}
