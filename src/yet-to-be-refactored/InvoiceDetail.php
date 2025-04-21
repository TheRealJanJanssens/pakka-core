<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class InvoiceDetail extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'invoice_id', 'client_name', 'client_address', 'client_city', 'client_zip', 'client_country', 'client_phone', 'client_email', 'client_vat', 'ship_name', 'ship_address', 'ship_city', 'ship_zip', 'ship_country',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'invoice_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'invoice_id' => "required",
        ]);
    }
}
