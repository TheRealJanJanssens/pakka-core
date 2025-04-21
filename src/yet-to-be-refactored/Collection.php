<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Collection extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'position', 'status', 'description', 'slug', 'sort_order', 'type', 'match', 'created_at', 'created_by', 'updated_at', 'updated_by',
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
            'name' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get collection with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getCollection($id, $status = null, $mode = 1)
    {
        switch (true) {
            case $mode == 1:
                $locale = app()->getLocale();

                $result = Collection::select([
                'collections.id',
                'collections.sort_order',
                'collections.type',
                'collections.match',
                DB::raw('(SELECT `translations`.`text`
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`name` AND `translations`.`language_code` = "'.$locale.'")
						AS name'),
                DB::raw('(SELECT `translations`.`text`
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`slug` AND `translations`.`language_code` = "'.$locale.'")
						AS slug'),
                DB::raw('(SELECT `translations`.`text`
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`description` AND `translations`.`language_code` = "'.$locale.'")
						AS description'),
                'collections.created_at',
                'collections.created_by',
                'collections.updated_at',
                'collections.updated_by',
                ])
                ->where('collections.id', $id);
                //->orderBy('service.position')
                //->get()->toArray();

                if ($status !== null) {
                    $result = $result->where('collections.status', $status);
                }

                $result = Cache::tags('collections')->remember('collection:'.$id, 60 * 60 * 24, function () use ($result) {
                    return $result->get();
                });

                $result = $result->toArray();

                if (! empty($result)) {
                    $result = $result[0];

                    switch ($result['type']) {
                        case 1:
                            //manual collection
                            break;
                        case 2:
                            //automatic collection
                            $conditions = CollectionCondition::getConditions($result['id'], $mode);
                            $result['conditions'] = $conditions;

                            break;
                    }
                }

                break;
            case $mode == 2:

                $result = Collection::select([
                'collections.id',
                'collections.sort_order',
                'collections.type',
                'collections.match',
                DB::raw('(SELECT
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
									WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
								END SEPARATOR "(~)"
							)
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`name`)
						AS language_code'),
                DB::raw('(SELECT
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							)
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`name`)
						AS name'),
                DB::raw('(SELECT
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							)
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`slug`)
						AS slug'),
                DB::raw('(SELECT
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							)
						FROM `translations`
						WHERE `translations`.`translation_id` = `collections`.`description`)
						AS description'),
                DB::raw('`collections`.`name` AS name_trans'),
                DB::raw('`collections`.`slug` AS slug_trans'),
                DB::raw('`collections`.`description` AS description_trans'),
                'collections.created_at',
                'collections.created_by',
                'collections.updated_at',
                'collections.updated_by',
                ])
                ->where('collections.id', $id)
                //->orderBy('service.position')
                ->get();

                $result = constructTranslatableValues($result, ['slug','name','description']);

                switch ($result['type']) {
                    case 1:
                        //manual collection
                        break;
                    case 2:
                        //automatic collection
                        $conditions = CollectionCondition::getConditions($result['id'], $mode);
                        $result['conditions'] = $conditions;

                        break;
                }

                break;
        }

        return $result; //outputs array
    }

    public static function getCollections($status = null)
    {
        $locale = app()->getLocale();

        $result = Collection::select([
        'collections.id',
        'collections.status',
        'collections.sort_order',
        'collections.type',
        'collections.match',
        DB::raw('(SELECT `translations`.`text`
				FROM `translations`
				WHERE `translations`.`translation_id` = `collections`.`name` AND `translations`.`language_code` = "'.$locale.'")
				AS name'),
        DB::raw('(SELECT `translations`.`text`
				FROM `translations`
				WHERE `translations`.`translation_id` = `collections`.`slug` AND `translations`.`language_code` = "'.$locale.'")
				AS slug'),
        DB::raw('(SELECT `translations`.`text`
				FROM `translations`
				WHERE `translations`.`translation_id` = `collections`.`description` AND `translations`.`language_code` = "'.$locale.'")
				AS description'),
        ])
        ->orderBy('collections.position');

        if ($status !== null) {
            $result = $result->where('collections.status', $status);
        }

        $result = Cache::tags('collections')->remember('collection:all:'.$status, 60 * 60 * 24, function () use ($result) {
            return $result->get();
        });

        return $result->toArray();
        ;
    }

    public static function getCollectionsSelect()
    {
        $collections = Collection::getCollections();

        if ($collections) {
            $i = 0;
            foreach ($collections as $collection) {
                $result[$i]['option_id'] = $collection['id'];
                $result[$i]['value'] = $collection['name'];
                $i++;
            }

            return $result;
        }
    }

    public static function removeCollectionCache($id)
    {
        Cache::forget('collections.collection:'.$id);
        Cache::forget('products.collection:'.$id);
    }
}
