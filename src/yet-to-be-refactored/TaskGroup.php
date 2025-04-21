<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TaskGroup extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','project_id','name','position','color',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'project_id' => "required",
            'name' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'project_id' => "required",
            'name' => "required",
        ]);
    }
}
