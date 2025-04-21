<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class OrderDetail extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id', 'user_id', 'firstname', 'lastname', 'address', 'city', 'zip', 'country', 'phone', 'email', 'company_name', 'vat',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'order_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'order_id' => "required",
        ]);
    }
}
