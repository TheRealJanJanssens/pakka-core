<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Session;

class ShipmentOption extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status', 'name', 'description', 'price', 'carrier', 'delivery', 'region', 'match',
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
    | Get shipment option with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getShipment($id, $mode = 1)
    {
        switch (true) {
            case $mode == 1:
                $locale = app()->getLocale();

                $result = ShipmentOption::select([
                'shipment_options.id',
                'shipment_options.status',
                'shipment_options.price',
                'shipment_options.carrier',
                'shipment_options.delivery',
                'shipment_options.region',
                'shipment_options.match',
                DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `shipment_options`.`name` AND `translations`.`language_code` = "'.$locale.'") 
						AS name'),
                DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `shipment_options`.`description` AND `translations`.`language_code` = "'.$locale.'") 
						AS description'),
                ])
                ->where('shipment_options.id', $id)
                ->get()->toArray();

                $result = $result[0];
                $result['conditions'] = CollectionCondition::getConditions($result['id']);

                break;
            case $mode == 2:

                $queryResult = ShipmentOption::select([
                'shipment_options.id',
                'shipment_options.status',
                'shipment_options.price',
                'shipment_options.carrier',
                'shipment_options.delivery',
                'shipment_options.region',
                'shipment_options.match',
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
									WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `shipment_options`.`name`) 
						AS language_code'),
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `shipment_options`.`name`) 
						AS name'),
                DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `shipment_options`.`description`) 
						AS description'),
                DB::raw('`shipment_options`.`name` AS name_trans'),
                DB::raw('`shipment_options`.`description` AS description_trans'),
                ])
                ->where('shipment_options.id', $id)
                ->get();

                $result = constructTranslatableValues($queryResult, ['name','description']);
                $result['conditions'] = ShipmentCondition::getConditions($result['id']);

                break;
        }

        return $result; //outputs array
    }

    public static function getShipments($conditions = false)
    {
        $locale = app()->getLocale();

        $query = ShipmentOption::select([
        'shipment_options.id',
        'shipment_options.status',
        'shipment_options.price',
        'shipment_options.carrier',
        'shipment_options.delivery',
        'shipment_options.region',
        'shipment_options.match',
        DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `shipment_options`.`name` AND `translations`.`language_code` = "'.$locale.'") 
				AS name'),
        DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `shipment_options`.`description` AND `translations`.`language_code` = "'.$locale.'") 
				AS description'),
        ]);

        if ($conditions == true) {
            $query->where('shipment_options.status', '=', '1');
        }

        $query->orderBy('shipment_options.region')
        ->orderBy('shipment_options.delivery')
        ->orderBy('shipment_options.price');

        $result = $query->get()->toArray();

        if ($conditions == true) {
            $i = 0;
            foreach ($result as $item) {
                $result[$i]['conditions'] = ShipmentCondition::getConditions($item['id']);
                $i++;
            }
        }

        return $result;
    }

    public static function getAvailableRegions()
    {
        $locale = app()->getLocale();

        if (! Session::has('checkout.helpers.regions.'.$locale)) {
            $array = ShipmentOption::select(['shipment_options.region'])
            ->where('shipment_options.status', '=', 1)
            ->get()->toArray();

            $regions = config('pakka.regions');

            foreach ($array as $item) {
                $result[$item['region']] = trans($regions[$item['region']]);
            }
            Session::put('checkout.helpers.regions.'.$locale, $result);
        } else {
            $result = Session::get('checkout.helpers.regions.'.$locale);
        }

        //set default region if it isn't set
        if (! Session::has('checkout.details.country')) {
            $region = key($result); //gets first region in array
            Session::put('checkout.details.country', $region);
        }

        return $result;
    }

    public static function getAvailableOptions($region = null, $price = null, $weight = null)
    {
        $locale = app()->getLocale();

        //set region
        if (! isset($region)) {
            $region = Session::get('checkout.details.country');
        }

        //set price
        if (! isset($price)) {
            if (Cart::getCondition('COUPON')) {
                $condition = Cart::getCondition('COUPON');
                $price = Cart::getSubTotal() - $condition->getCalculatedValue(Cart::getSubTotal());
            } else {
                $price = Cart::getSubTotal();
            }
        }

        //set weight
        if (! isset($weight)) {
            $weight = Cart::getTotalWeight();
        }

        //get or construct options
        if (! Session::has('checkout.helpers.shipment_options.'.$locale)) {
            $array = ShipmentOption::getShipments(true);

            foreach ($array as $item) {
                if (! isset($options[$item['region']][$item['delivery']])) {
                    $options[$item['region']][$item['delivery']] = [];
                }
                array_push($options[$item['region']][$item['delivery']], $item);
            }
            Session::put('checkout.helpers.shipment_options.'.$locale, $options);
        } else {
            $options = Session::get('checkout.helpers.shipment_options.'.$locale);
        }

        //construct available options
        $delivery_options = config('pakka.shipment_delivery');
        $set_delivery = true;
        $i = 0; //available option count

        if (Cart::getCondition('SHIPPING')) {
            $set_delivery = false;
            $is_option_visible = false;
        }

        foreach ($options[$region] as $key => $delivery) {
            $result[$key]['title'] = trans($delivery_options[$key]);

            foreach ($delivery as $option) {
                $option_available = false; //indicates if option is available
                if (! isset($result[$key]['options'])) {
                    $result[$key]['options'] = [];
                }

                if (! empty($option['conditions'])) {
                    $iC = 0; //condition iteration
                    $iV = 0; //validation iteration
                    $condition_count = count($option['conditions']);
                    foreach ($option['conditions'] as $condition) {
                        $validation = false;
                        $iC++;
                        switch ($condition['type']) {
                            case 1:
                                //eur
                                $compare_value = $price;

                                break;
                            case 2:
                                //gewicht
                                $compare_value = $weight;

                                break;
                        }

                        switch ($condition['operator']) {
                            case 1:
                                //vanaf
                                //true if $compare_value is strictly less then $condition['value']
                                if ($compare_value >= $condition['value']) {
                                    $validation = true;
                                }

                                break;
                            case 2:
                                //tot en met
                                //true if $compare_value is strictly greater then $condition['value']
                                if ($compare_value <= $condition['value']) {
                                    $validation = true;
                                }

                                break;
                        }

                        if ($validation == true) {
                            $iV++;
                        }
                    }

                    //if validation meets all the requirements push delivery option
                    switch ($option['match']) {
                        case 1:
                            // All conditions have to be met
                            if ($iC == $iV) {
                                array_push($result[$key]['options'], $option);
                                $option_available = true;
                            }

                            break;
                        case 2:
                            // Any condition has to be met
                            if ($iV > 0) {
                                array_push($result[$key]['options'], $option);
                                $option_available = true;
                            }

                            break;
                    }
                } else {
                    array_push($result[$key]['options'], $option);
                    $option_available = true;
                }

                //check if set option is still visible current array of options
                $set_delivery_option = Session::get('checkout.helpers.set_delivery_option');
                if ($option['id'] == $set_delivery_option && $option_available) {
                    $is_option_visible = true;
                }

                //sets default delivery method if there isn't one set
                if ($set_delivery == true && $i == 0 && $option_available == true) {
                    if (! Session::has('checkout.helpers.preferred_delivery_method')) {
                        $condition = new \Darryldecode\Cart\CartCondition([
                            'name' => 'SHIPPING',
                            'type' => 'shipping',
                            'target' => 'total',
                            'value' => $option['price'],
                            'attributes' => $option,
                        ]);

                        Cart::condition($condition);
                        Session::put('checkout.helpers.set_delivery_option', $option['id']);
                        Session::put('checkout.helpers.preferred_delivery_method', $option['delivery']);
                    }
                }

                $i++;
            }
        }

        //if due changes in the cart made the set option not visible anymore get next best one.
        //check for region switch and set proper shipping option
        $prefered_delivery_method = Session::get('checkout.helpers.preferred_delivery_method');
        $set_shipment_region = Session::get('checkout.helpers.set_shipment_region');

        if (($set_shipment_region !== $region) || (isset($is_option_visible) && ! $is_option_visible)) {
            if (isset($result[$prefered_delivery_method])) {
                $option_key = key($result[$prefered_delivery_method]['options']);
                $option = $result[$prefered_delivery_method]['options'][$option_key];
            } else {
                $method_key = key($result);
                $option_key = key($result[$method_key]['options']);
                $option = $result[$method_key]['options'][$option_key];
            }

            $condition = new \Darryldecode\Cart\CartCondition([
                'name' => 'SHIPPING',
                'type' => 'shipping',
                'target' => 'total',
                'value' => $option['price'],
                'attributes' => $option,
            ]);

            Cart::condition($condition);

            Session::put('checkout.helpers.set_delivery_option', $option['id']);
            Session::put('checkout.helpers.preferred_delivery_method', $option['delivery']);
        }

        Session::put('checkout.helpers.set_shipment_region', $region);

        return $result;
    }
}
