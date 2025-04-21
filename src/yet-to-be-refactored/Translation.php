<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Translation extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'text','language_code','translation_id','input_name',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'text' => "required",
            'language_code' => 'required',
            'translation_id' => 'required',

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'text' => 'required',
            'language_code' => 'required',
            'translation_id' => 'required',
        ]);
    }

    public static function getTranslation($string)
    {
        $locale = app()->getLocale();

        $result = Translation::select([
        'translations.text',
        ])
        ->where('translations.translation_id', $string)
        ->where('translations.language_code', $locale);

        //if translation is not set revert back to default
        if (empty($result)) {
            $result = Translation::select([
            'translations.text',
            ])
            ->where('translations.translation_id', $string);
        }

        $result = Cache::tags('translations')->rememberForever('translation:'.$locale.':'.$string, function () use ($result) {
            return $result->get();
        });

        //fail safe if there is no translation. Mostly will result in a 404 error (if slug is empty)
        if ($result->count() > 0) {
            return $result->toArray()[0]['text'];
        }
    }
}
