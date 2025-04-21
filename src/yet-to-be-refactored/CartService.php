<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
//use App\CartServiceCondition;

use Illuminate\Support\Facades\DB;

class CartService extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status', 'name', 'description', 'price', 'icon',
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
            'price' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'name' => "required",
            'price' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get CartService option with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getCartService($id, $mode = 1)
    {
        switch (true) {
            case $mode == 1:
                $locale = app()->getLocale();

                $result = CartService::select([
                'cart_services.id',
                'cart_services.status',
                'cart_services.price',
                'cart_services.icon',
                DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `cart_services`.`name` AND `translations`.`language_code` = "'.$locale.'") 
						AS name'),
                DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `cart_services`.`description` AND `translations`.`language_code` = "'.$locale.'") 
						AS description'),
                ])
                ->where('cart_services.id', $id)
                ->get();

                $result = $result[0];
                //$result['conditions'] = CollectionCondition::getConditions($result['id']);

                break;
            case $mode == 2:

                $queryResult = CartService::select([
                'cart_services.id',
                'cart_services.status',
                'cart_services.price',
                'cart_services.icon',
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
									WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `cart_services`.`name`) 
						AS language_code'),
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `cart_services`.`name`) 
						AS name'),
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `cart_services`.`description`) 
						AS description'),
                DB::raw('`cart_services`.`name` AS name_trans'),
                DB::raw('`cart_services`.`description` AS description_trans'),
                ])
                ->where('cart_services.id', $id)
                ->get();

                $result = constructTranslatableValues($queryResult, ['name','description']);
                //$result['conditions'] = CartServiceCondition::getConditions($result['id']);

                break;
        }

        return $result; //outputs array
    }

    public static function getCartServices($conditions = false)
    {
        $locale = app()->getLocale();

        $query = CartService::select([
        'cart_services.id',
        'cart_services.status',
        'cart_services.price',
        'cart_services.icon',
        DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `cart_services`.`name` AND `translations`.`language_code` = "'.$locale.'") 
				AS name'),
        DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `cart_services`.`description` AND `translations`.`language_code` = "'.$locale.'") 
				AS description'),
        ]);

        if ($conditions == true) {
            $query->where('cart_services.status', '=', '1');
        }

        /*
                $query->orderBy('cart_services.region')
                  ->orderBy('cart_services.delivery')
                  ->orderBy('cart_services.price');
        */

        $result = $query->get();

        /*
                if($conditions == true){
                    $i = 0;
                    foreach($result as $item){
                        $result[$i]['conditions'] = CartServiceCondition::getConditions($item['id']);
                        $i++;
                    }
                }
        */

        return $result;
    }

    public static function getAvailableCartServices($conditions = false)
    {
        $locale = app()->getLocale();

        $query = CartService::select([
        'cart_services.id',
        'cart_services.status',
        'cart_services.price',
        'cart_services.icon',
        DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `cart_services`.`name` AND `translations`.`language_code` = "'.$locale.'") 
				AS name'),
        DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `cart_services`.`description` AND `translations`.`language_code` = "'.$locale.'") 
				AS description'),
        ]);

        $query->where('cart_services.status', '=', '1');

        $result = $query->get();

        /*
                if($conditions == true){
                    $i = 0;
                    foreach($result as $item){
                        $result[$i]['conditions'] = CartServiceCondition::getConditions($item['id']);
                        $i++;
                    }
                }
        */

        return $result;
    }
}
