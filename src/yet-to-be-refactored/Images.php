<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Images extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','item_id','position','file','created_at','updated_at',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'item_id' => "required",
            'file' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'item_id' => "required",
            'file' => "required",
        ]);
    }
}
