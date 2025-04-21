<?php

namespace TheRealJanJanssens\Pakka\Models;

use App\ServiceAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Service extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'price',
        'duration',
        'name',
        'description',
    ];

    protected $casts = ['id' => 'string'];

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
            'name' => "required",
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

    public static function getService($id, $mode = 1)
    {
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
                      ->first();

                $result = $result;

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
                ->get();

                $result = constructTranslatableValues($queryResult, ['name','description']);

                break;
        }

        $providers = ServiceAssignment::getProviders($id);
        $result['providers'] = $providers;

        return $result; //outputs array
    }

    public static function getServices()
    {
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
    ->get();

        return $result;
    }

    public static function constructSelect()
    {
        $services = Service::getServices();

        $result = [];
        foreach ($services as $service) {
            $result[$service->id] = $service->name;
        }

        if (! empty($result)) {
            array_unshift($result, trans("app.select_option"));
        }

        return $result;
    }
}
