<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Forms extends Model
{
    use Notifiable;

    protected $table = 'forms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'set_id','name','type',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'set_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'set_id' => 'required',
        ]);
    }

    public static function getFormsLinks()
    {
        $result = [];
        $forms = Forms::select([
            'forms.set_id',
            'forms.name',
        ])
        ->get();

        if (! empty($forms)) {
            foreach ($forms as $form) {
                $result[$form->set_id] = $form->name;
            }
        }

        return $result;
    }
}
