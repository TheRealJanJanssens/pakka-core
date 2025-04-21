<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class CollectionCondition extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'collection_id', 'type', 'string', 'created_at', 'created_by', 'updated_at', 'updated_by',
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
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'collection_id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Stores Condition
    |
    | $id = Item id
    | $array = array with all the values
    |------------------------------------------------------------------------------------
    */

    public static function storeCondition($id, $array)
    {
        //deleting and inserting again is not the most efficient way to update these rows
        //updating is much better but the way the form is build is difficult to detect deleted rows without ajax request

        //delete all data
        CollectionCondition::where('collection_id', $id)->delete();

        foreach ($array as $item) {
            /*
                        //efficienter for db queries
                        if($item['id'] == 0){
                            //new schedule item
                            $schedule = new ProviderSchedule;
                        }else{
                            //existing schedule item
                            $schedule = ProviderSchedule::find($item['id']);
                        }
            */
            $condition = new CollectionCondition();
            $condition->collection_id = $id;
            $condition->input = $item['input'];
            $condition->operator = $item['operator'];
            $condition->string = $item['string'];
            $condition->save();
        }
    }

    /*
    |------------------------------------------------------------------------------------
    | Get conditions with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getConditions($id, $mode = 1)
    {
        $result = null;

        switch (true) {
            case $mode == 1:
                $locale = app()->getLocale();

                $result = CollectionCondition::select([
                'collection_conditions.collection_id',
                'collection_conditions.input',
                'collection_conditions.operator',
                DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `collection_conditions`.`string` AND `translations`.`language_code` = "'.$locale.'") 
						AS string'),
                'collection_conditions.created_at',
                'collection_conditions.created_by',
                'collection_conditions.updated_at',
                'collection_conditions.updated_by',
                ])
                ->where('collection_conditions.collection_id', $id);
                //->orderBy('service.position')
                //->get()->toArray();

                $result = Cache::tags('collections')->remember('conditions:'.$id, 60 * 60 * 24, function () use ($result) {
                    return $result->get();
                });

                $result = $result->toArray();

                break;
            case $mode == 2:

                $queryResult = CollectionCondition::select([
                'collection_conditions.collection_id',
                'collection_conditions.input',
                'collection_conditions.operator',
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
									WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `collection_conditions`.`string`) 
						AS language_code'),
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `collection_conditions`.`string`) 
						AS string'),
                DB::raw('`collection_conditions`.`string` AS string_trans'),
                'collection_conditions.created_at',
                'collection_conditions.created_by',
                'collection_conditions.updated_at',
                'collection_conditions.updated_by',
                ])
                ->where('collection_conditions.collection_id', $id)
                ->get()->toArray();

                $result = constructTranslatableValues($queryResult, ['string'], true);

                break;
        }

        return $result; //outputs array
    }
}
