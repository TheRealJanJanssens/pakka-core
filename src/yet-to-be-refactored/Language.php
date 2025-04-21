<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Language extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'language_code','name',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'name' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'name' => 'required',
        ]);
    }

    public static function getLangCodes()
    {
        $langs = Language::select(['languages.language_code'])->get();

        $i = 0;
        foreach ($langs as $lang) {
            $result[$i] = $lang['language_code'];
            $i++;
        }

        return $result;
    }
}
