<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class UserDetail extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'address',
        'city',
        'zip',
        'country',
        'phone',
        'company_name',
        'vat',
        'marketing_consent',
        'terms_consent',
        'birthday',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'user_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'user_id' => "required",
        ]);
    }
}
