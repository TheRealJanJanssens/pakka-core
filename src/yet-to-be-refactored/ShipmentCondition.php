<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class ShipmentCondition extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shipment_option_id', 'operator', 'value', 'type',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'shipment_option_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'shipment_option_id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Stores Condition
    |
    | $id = Item id
    | $array = array with all the values
    |------------------------------------------------------------------------------------
    */

    public static function storeCondition($id, $array)
    {
        //deleting and inserting again is not the most efficient way to update these rows
        //updating is much better but the way the form is build is difficult to detect deleted rows without ajax request

        //delete all data
        ShipmentCondition::where('shipment_option_id', $id)->delete();

        foreach ($array as $item) {
            $condition = new ShipmentCondition();
            $condition->shipment_option_id = $id;
            $condition->operator = $item['operator'];
            $condition->type = $item['type'];
            $condition->value = $item['value'];
            $condition->save();
        }
    }

    /*
    |------------------------------------------------------------------------------------
    | Get conditions with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getConditions($id)
    {
        $result = ShipmentCondition::select([
        'shipment_conditions.shipment_option_id',
        'shipment_conditions.operator',
        'shipment_conditions.value',
        'shipment_conditions.type',
        ])
        ->where('shipment_conditions.shipment_option_id', $id)
        ->get()->toArray();

        return $result; //outputs array
    }
}
