<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','language_code','value','name','created_at','updated_at',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'value' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'value' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get settings
    |
    | $locale = isset (display), null (edit)
    | $userId = get specific settings for user
    |
    |------------------------------------------------------------------------------------
    */

    public static function getSettings($locale = null, $userId = null)
    {
        // Cache::rememberForever('statements.group_concat_max_len:', function () {
        //     return DB::statement("SET SESSION group_concat_max_len = 1000000;");
        // });

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        if ($userId !== null) {
            $where = "`settings`.`user_id`=$userId OR `settings`.`user_id` IS NULL";
        } else {
            $where = "`settings`.`user_id` IS NULL";
        }

        switch (empty($locale)) {
            case true:
                //select without locale (only for edit mode)
                $queryResult = Setting::select([
                'settings.id',
                'settings.user_id',
                'settings.name',
                'translations.language_code',
                'settings.value AS translation_id', //DB::raw('GROUP_CONCAT( DISTINCT translations.text SEPARATOR "(~)") AS name')
                'translations.text AS value',
                DB::raw('CASE WHEN translations.language_code IS NOT NULL THEN translations.text WHEN translations.language_code IS NULL THEN settings.value END AS value'),
                ])
                ->leftJoin('translations', 'settings.value', '=', 'translations.translation_id')
                //->where('translations.language_code', $locale)
                ->whereRaw($where)
                ->get()->toArray();

                break;
            case false:
                //select with locale
                $queryResult = Setting::select([
                'settings.id',
                'settings.user_id',
                'settings.name',
                'translations.language_code',
                'settings.value AS translation_id', //DB::raw('GROUP_CONCAT( DISTINCT translations.text SEPARATOR "(~)") AS name')
                //'translations.text AS value',
                DB::raw('CASE WHEN translations.language_code IS NOT NULL THEN translations.text WHEN translations.language_code IS NULL THEN settings.value END AS value'),
                ])
                ->leftJoin('translations', 'settings.value', '=', 'translations.translation_id')
                ->where('translations.language_code', $locale)
                ->orWhere('translations.language_code', null)
                ->whereRaw($where)
                ->get()->toArray();

                break;
        }

        $i = 0;
        if ($queryResult) {
            foreach ($queryResult as $item) {
                if (isset($item['language_code']) && $locale == null) {
                    //edit mode
                    $languageCode = $item['language_code'];
                    $inputName = $item['name'];
                    $name = $languageCode.':'.$inputName;
                    $result[$name] = $item['value'];
                    $result["translation_id"][$inputName] = $item['translation_id'];

                    //set backup translation_id for editing purpose (used for translatable selects like page select)
                    $result[$inputName] = $item['translation_id'];
                } else {
                    //display purpose
                    $name = $item['name'];
                    $result[$name] = $item['value'];
                }

                //$result["translation_id"] = array_merge($result["translation_id"],array($item['translation_id']));
                $i++;
            }

            return $result;
        }
    }
}
