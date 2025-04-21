<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class CollectionSet extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'collection_id', 'product_id',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'collection_id' => "required",
            'product_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'collection_id' => "required",
            'product_id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get service with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    /*
        public static function getService($id,$mode = 1){

            switch (true) {
                case $mode == 1:
                    $locale = app()->getLocale();

                    $result = Service::select([
                    'services.id',
                    'services.price',
                    'services.duration',
                    DB::raw('(SELECT `translations`.`text`
                            FROM `translations`
                            WHERE `translations`.`translation_id` = `services`.`name` AND `translations`.`language_code` = "'.$locale.'")
                            AS name'),
                    DB::raw('(SELECT `translations`.`text`
                            FROM `translations`
                            WHERE `translations`.`translation_id` = `services`.`description` AND `translations`.`language_code` = "'.$locale.'")
                            AS description'),
                    ])
                      ->where('services.id', $id)
                      //->orderBy('service.position')
                    ->get()->toArray();
                    //dd($result);
                    $result = $result[0];

                    break;
                case $mode == 2:

                    $queryResult = Service::select([
                    'services.id',
                    'services.price',
                    'services.duration',
                    DB::raw('(SELECT
                                GROUP_CONCAT(
                                    CASE
                                        WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
                                        WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
                                    END SEPARATOR "(~)"
                                )
                            FROM `translations`
                            WHERE `translations`.`translation_id` = `services`.`name`)
                            AS language_code'),
                    DB::raw('(SELECT
                                GROUP_CONCAT(
                                    CASE
                                        WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                        WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                    END SEPARATOR "(~)"
                                )
                            FROM `translations`
                            WHERE `translations`.`translation_id` = `services`.`name`)
                            AS name'),
                    DB::raw('(SELECT
                                GROUP_CONCAT(
                                    CASE
                                        WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                        WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                    END SEPARATOR "(~)"
                                )
                            FROM `translations`
                            WHERE `translations`.`translation_id` = `services`.`description`)
                            AS description'),
                    DB::raw('`services`.`name` AS name_trans'),
                    DB::raw('`services`.`description` AS description_trans'),
                      ])
                      ->where('services.id', $id)
                      //->orderBy('service.position')
                    ->get()->toArray();

                    foreach($queryResult as $item){
                        $i=0;
                        $languageCodes = explode("(~)", $item['language_code']);
                        $names = explode("(~)", $item['name']);
                        $descriptions = explode("(~)", $item['description']);

                        foreach($item as $key => $input){
                            $result[$key] = $input;
                        }

                        foreach($languageCodes as $languageCode){
                            $result[$languageCode.':name:translation_id'] = $item['name_trans'];
                            $result[$languageCode.':description:translation_id'] = $item['description_trans'];

                            $result[$languageCode.':name'] = $names[$i];
                            $result[$languageCode.':description'] = $descriptions[$i];

                            $i++;
                        }
                    }

                    unset($result['name_trans']);
                    unset($result['description_trans']);
                    unset($result['name']);
                    unset($result['description']);
                    unset($result['language_code']);

                    break;
            }

            $providers = ServiceAssignment::getProviders($id);
            $result['providers'] = $providers;

            return $result; //outputs array
        }
    */
}
