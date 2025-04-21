<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Session;

class Product extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','slug','status','name','description','base_price','compare_price','created_at','created_by','updated_at','updated_by',
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
            'id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Construct the attributes (and images) of Products
    |
    | $Products = array of Products
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    /*
        public static function constructAttributes($Products,$mode = 1){

            $i = 0;

            switch ($mode) {
                case 1:

                    foreach($Products as $Product){

                        $attributeNames = explode("(~)", $Product['attribute_names']);
                        $attributeValues = explode("(~)", $Product['attribute_values']);

                        //CONSTRUCT IMAGES
                        if($Product['images'] !== null){
                            $images = explode("(~)", $Product['images']);
                            $Products[$i]["images"] = $images;
                        }

                        //CONSTRUCT ATTRIBUTES
                        $iA = 0; // general attribute int
                        foreach($attributeNames as $attributeName){
                            if($attributeName == "name"){
                                //exception for name (put outside the attribute array
                                $Products[$i]['name'] = $attributeValues[$iA];
                            }else{
                                $Products[$i]["attributes"][$attributeName] = $attributeValues[$iA]; //$Products[$i]["attributes"][$iA][$attributeLabel]
                            }

                            $iA++;
                        }

                        unset($Products[$i]["attribute_names"]);
                        unset($Products[$i]["attribute_values"]);

                        $i++;
                    }
                    break;
                case 2:

                    foreach($Products as $Product){

                        $attributeNames = explode("(~)", $Product['attribute_names']);
                        $attributeLangCodes = explode("(~)", $Product['attribute_language_codes']);
                        $attributeValues = explode("(~)", $Product['attribute_values']);

                        //CONSTRUCT SLUG
                        if(isset($Product['slug']) && !empty($Product['slug'])){
                            $langs = Session::get('lang');

                            $slugs = explode("(~)", $Product['slug']);
                            $iS=0;

                            foreach($langs as $lang){

                                //SAFETY INCASE OF NEW LANGUAGES
                                if ( ! isset($slugs[$iS])) {
                                   $slugs[$iS] = null;
                                }

                                $Products[$i][$lang["language_code"].":slug:translation_id"] = $Products[$i]['translation_id_slug'];
                                $Products[$i][$lang["language_code"].":slug"] = $slugs[$iS];
                                $iS++;
                            }
                            unset($Products[$i]['translation_id_slug']);
                        }

                        //CONSTRUCT IMAGES
                        if($Product['images'] !== null){
                            $images = explode("(~)", $Product['images']);
                            $Products[$i]["images"] = $images;
                        }

                        //CONSTRUCT ATTRIBUTES
                        $iA = 0; // general attribute int
                        foreach($attributeNames as $attributeName){

                            $langCode = $attributeLangCodes[$iA];

                            if(!isset($langCode) || empty($langCode)){
                                $Products[$i][$attributeName] = $attributeValues[$iA];
                            }else{
                                $Products[$i][$langCode.':'.$attributeName] = $attributeValues[$iA];
                            }

                            $iA++;
                        }

                        unset($Products[$i]["attribute_names"]);
                        unset($Products[$i]["attribute_values"]);
                        unset($Products[$i]["attribute_language_codes"]);

                        $i++;
                    }
                    break;
            }

            return $Products;
        }
    */

    /*
    |------------------------------------------------------------------------------------
    | Get Product with translations
    |
    | $id = Product id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getProduct($id, $mode = 1)
    {
        //Sets the max char length of group_concat (1024 to 1000000 chars)
        // Cache::rememberForever('statements.group_concat_max_len:', function () {
        //     return DB::statement("SET SESSION group_concat_max_len = 1000000;");
        // });

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $extra = [];

        switch ($mode) {
            case 1:
                $locale = app()->getLocale();

                //Display mode
                $optionAttr = "attributes.option_value";
                $valueAttr = "attributes.name, IFNULL(attributes.value, '')";
                $slug = DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`slug` AND `translations`.`language_code` = "'.$locale.'") 
						AS slug');
                $name = DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`name` AND `translations`.`language_code` = "'.$locale.'") 
						AS name');

                $description = DB::raw('(SELECT `translations`.`text` 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`description` AND `translations`.`language_code` = "'.$locale.'") 
						AS description');

                break;
            case 2:
                //Edit mode
                $optionAttr = "attributes.option_id";
                $valueAttr = "attributes.language_code, attributes.name, IFNULL(attributes.value, '')";
                $languageCode = DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
									WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`slug`) 
						AS language_code');

                $slug = DB::raw('`products`.`slug` AS translation_id_slug, (SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`slug`) 
						AS slug');

                $name = DB::raw('`products`.`name` AS translation_id_name, (SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`name`) 
						AS name');

                $description = DB::raw('`products`.`description` AS translation_id_description, (SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `products`.`description`) 
						AS description');

                array_push($extra, $languageCode);
                array_push($extra, DB::raw('`products`.`slug` AS slug_trans'));
                array_push($extra, DB::raw('`products`.`name` AS name_trans'));
                array_push($extra, DB::raw('`products`.`description` AS description_trans'));

                break;
        }

        $selectArray = [
            'products.id',
            'products.status',
            $name,
            $description,
            $slug,
            'products.base_price',
            'products.compare_price',
            'products.created_at',
            DB::raw("GROUP_CONCAT( DISTINCT
					CASE
				    WHEN attributes.option_id IS NOT NULL THEN CONCAT_WS('(:)', attributes.name, ".$optionAttr.")
				    WHEN attributes.option_id IS NULL AND attributes.value IS NOT NULL THEN CONCAT_WS('(:)', ".$valueAttr.")
				    END
				 ORDER BY attributes.position SEPARATOR '(~)') as attributes"),
            DB::raw("GROUP_CONCAT( DISTINCT images.file ORDER BY images.position SEPARATOR '(~)') as images"),
        ];

        if ($extra) {
            $selectArray = array_merge($selectArray, $extra);
        }

        $result = Product::select($selectArray)
        ->leftJoin(DB::raw("(
				SELECT 
			    attribute_values.input_id,
			    attribute_values.option_id,
			    attribute_values.item_id,
			    attribute_values.language_code,
			    attribute_values.value,
			    attribute_inputs.name,
			    attribute_inputs.position,
			    attribute_options.value AS option_value
			    FROM 
			    attribute_values 
			    LEFT JOIN attribute_inputs ON attribute_values.input_id = attribute_inputs.input_id
				LEFT JOIN attribute_options ON attribute_values.option_id = attribute_options.option_id
			) as attributes"), 'products.id', '=', 'attributes.item_id')
/*
                WHERE attribute_options.language_code = '".$locale."'
                OR attribute_values.language_code = '".$locale."'
*/
        ->leftJoin('images', 'products.id', '=', 'images.item_id')
        ->where('products.id', $id)
        ->orderBy('products.created_at')
        ->groupBy('products.id')
        //->toSql();
        ->get();

        if (! empty($result)) {
            $result = constructAttributes($result, $mode);
            $result = $mode == 2 ? constructTranslatableValues($result, ['slug','name','description']) : $result[0]->toArray();
            $result['variants'] = Variant::getVariantInputs($id);
            $result['stocks'] = Stock::getStockInputs($id);

            return $result; //outputs array
        } else {
            return null;
        }
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Products with translations
    |
    | $status
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getProducts($status = null, $sort = "desc", $limit = null)
    {
        $locale = app()->getLocale();

        //Sets the max char length of group_concat (1024 to 1000000 chars)
        // Cache::rememberForever('statements.group_concat_max_len:', function () {
        //     return DB::statement("SET SESSION group_concat_max_len = 1000000;");
        // });

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $optionAttr = "attributes.option_value";
        $valueAttr = "attributes.name, IFNULL(attributes.value, '')";

        $result = Product::select([
        'products.id',
        'products.base_price',
        'products.compare_price',
        'products.status',
        DB::raw('(SELECT 
        			IFNULL(`translations`.`text`, "")
				FROM `translations` 
				WHERE `translations`.`translation_id` = `products`.`slug` AND `translations`.`language_code` = "'.$locale.'") 
				AS slug'),
        DB::raw('(SELECT 
        			IFNULL(`translations`.`text`, "")
				FROM `translations` 
				WHERE `translations`.`translation_id` = `products`.`name` AND `translations`.`language_code` = "'.$locale.'") 
				AS name'),
        DB::raw('(SELECT 
        			IFNULL(`translations`.`text`, "")
				FROM `translations` 
				WHERE `translations`.`translation_id` = `products`.`description` AND `translations`.`language_code` = "'.$locale.'") 
				AS description'),
        'products.created_at',
        DB::raw("GROUP_CONCAT( DISTINCT
				CASE
			    WHEN attributes.option_id IS NOT NULL THEN CONCAT_WS('(:)', attributes.name, ".$optionAttr.")
			    WHEN attributes.option_id IS NULL AND attributes.value IS NOT NULL THEN CONCAT_WS('(:)', ".$valueAttr.")
			    END
			 ORDER BY attributes.position SEPARATOR '(~)') as attributes"),
        DB::raw("GROUP_CONCAT( DISTINCT images.file ORDER BY images.position SEPARATOR '(~)') as images"),
        ])
        ->leftJoin(DB::raw("(
				SELECT 
			    attribute_values.input_id,
			    attribute_values.option_id,
			    attribute_values.item_id,
			    attribute_values.language_code,
			    attribute_values.value,
			    attribute_inputs.name,
			    attribute_inputs.position,
			    attribute_options.value AS option_value
			    FROM 
			    attribute_values 
			    LEFT JOIN attribute_inputs ON attribute_values.input_id = attribute_inputs.input_id
				LEFT JOIN attribute_options ON attribute_values.option_id = attribute_options.option_id
				WHERE attribute_options.language_code = '".$locale."'
				OR attribute_values.language_code = '".$locale."'
			) as attributes"), 'products.id', '=', 'attributes.item_id')
        ->leftJoin('images', 'products.id', '=', 'images.item_id');

        if ($status !== null) {
            $result = $result->where('products.status', $status);
        }

        $result = $result
        ->orderBy('products.created_at', $sort)
        ->groupBy('products.id')
        ->limit($limit);

        $result = Cache::tags('collections')->remember('products:all-'.$status.'-'.$sort.'-'.$limit, 60 * 60 * 24, function () use ($result) {
            return $result->get();
        });

        $result = constructAttributes($result);

        $i = 0;
        foreach ($result as $product) {
            $result[$i]['stocks'] = Stock::getStockInputs($product['id']);
            $i++;
        }

        return $result; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Products with translations by collection
    |
    |
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getProductsByCollection($id, $status = null, $sort = "desc", $limit = null)
    {
        $locale = app()->getLocale();

        //Sets the max char length of group_concat (1024 to 1000000 chars)
        // Cache::rememberForever('statements.group_concat_max_len:', function () {
        //     return DB::statement("SET SESSION group_concat_max_len = 1000000;");
        // });

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $collection = Collection::getCollection($id, $status);
        //dd($collection);

        if (empty($collection)) {
            $result = Product::getProducts(1, $sort, $limit);
        } else {
            $optionAttr = "attributes.option_value";
            $valueAttr = "attributes.name, IFNULL(attributes.value, '')";

            switch ($collection['type']) {
                case 1:
                    //Manual

                    break;
                case 2:
                    //Automatic
                    $result = Product::select([
                    'products.id',
                    'products.base_price',
                    'products.compare_price',
                    'products.status',
                    DB::raw('(SELECT 
			        			IFNULL(`translations`.`text`, "")
							FROM `translations` 
							WHERE `translations`.`translation_id` = `products`.`slug` AND `translations`.`language_code` = "'.$locale.'") 
							AS slug'),
                    DB::raw('(SELECT 
			        			IFNULL(`translations`.`text`, "")
							FROM `translations` 
							WHERE `translations`.`translation_id` = `products`.`name` AND `translations`.`language_code` = "'.$locale.'") 
							AS name'),
                    DB::raw('(SELECT 
			        			IFNULL(`translations`.`text`, "")
							FROM `translations` 
							WHERE `translations`.`translation_id` = `products`.`description` AND `translations`.`language_code` = "'.$locale.'") 
							AS description'),
                    'products.created_at',
                    DB::raw("GROUP_CONCAT( DISTINCT
							CASE
						    WHEN attributes.option_id IS NOT NULL THEN CONCAT_WS('(:)', attributes.name, ".$optionAttr.")
						    WHEN attributes.option_id IS NULL AND attributes.value IS NOT NULL THEN CONCAT_WS('(:)', ".$valueAttr.")
						    END
						 ORDER BY attributes.position SEPARATOR '(~)') as attributes"),
                    DB::raw("GROUP_CONCAT( DISTINCT images.file ORDER BY images.position SEPARATOR '(~)') as images"),
                    ])
                    ->leftJoin(DB::raw("(
							SELECT 
						    attribute_values.input_id,
						    attribute_values.option_id,
						    attribute_values.item_id,
						    attribute_values.language_code,
						    attribute_values.value,
						    attribute_inputs.name,
						    attribute_inputs.position,
						    attribute_options.value AS option_value
						    FROM 
						    attribute_values 
						    LEFT JOIN attribute_inputs ON attribute_values.input_id = attribute_inputs.input_id
							LEFT JOIN attribute_options ON attribute_values.option_id = attribute_options.option_id
							WHERE attribute_options.language_code = '".$locale."'
							OR attribute_values.language_code = '".$locale."'
						) as attributes"), 'products.id', '=', 'attributes.item_id')
                    ->leftJoin('images', 'products.id', '=', 'images.item_id');

                    foreach ($collection['conditions'] as $condition) {
                        $string = $condition['string'];
                        //$input = "products.".$condition['input'];
                        $input = DB::raw('SELECT 
			        			IFNULL(`translations`.`text`, "")
							FROM `translations` 
							WHERE `translations`.`translation_id` = `products`.`name` AND `translations`.`language_code` = "'.$locale.'"');

                        switch ($condition['operator']) {
                            case 1:
                                //equal
                                $operator = "=";

                                break;
                            case 2:
                                //not equal
                                $operator = "!=";

                                break;
                            case 3:
                                //start with
                                $operator = "LIKE";
                                $string = $string."_";

                                break;
                            case 4:
                                //ends with
                                $operator = "LIKE";
                                $string = "_".$string;

                                break;
                            case 5:
                                //contains
                                $operator = "LIKE";
                                $string = "%$string%";

                                break;
                            case 6:
                                //doesn't contain
                                $operator = "NOT LIKE";
                                $string = "%$string%";

                                break;
                        }

                        switch ($collection['match']) {
                            case 1:
                                //AND
                                $result = $result->where(function ($q) use ($locale) {
                                    $q->from('translations')
                                    ->selectRaw('IFNULL(`translations`.`text`, "")')
                                    //->where('translations.translation_id', '=', 'products.name')
                                    ->whereRaw('`translations`.`translation_id` = `products`.`name`')
                                    ->where('translations.language_code', '=', $locale);
                                }, $operator, $string);

                                break;
                            case 2:
                                //OR
                                $result = $result->orWhere(function ($q) use ($locale) {
                                    $q->from('translations')
                                    ->selectRaw('IFNULL(`translations`.`text`, "")')
                                    //->where('translations.translation_id', '=', 'products.name')
                                    ->whereRaw('`translations`.`translation_id` = `products`.`name`')
                                    ->where('translations.language_code', '=', $locale);
                                }, $operator, $string);

                                break;
                        }
                    }

                    if ($status !== null) {
                        $result = $result->where('products.status', $status);
                    }

                    $result = $result
                    ->orderBy('products.created_at', $sort)
                    ->groupBy('products.id')
                    ->limit($limit);

                    $result = Cache::tags('collections')->remember('products:'.$id.'-'.$status.'-'.$sort.'-'.$limit, 60 * 60 * 24, function () use ($result) {
                        return $result->get();
                    });

                    break;
            }
            $result = $result;
            $result = constructAttributes($result);
        }

        $i = 0;
        foreach ($result as $product) {
            $result[$i]['stocks'] = Stock::getStockInputs($product['id']);
            $i++;
        }

        return $result; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Product by SKU (with translations)
    |
    | $sku = Product sku
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getProductBySKU($sku)
    {
        //Sets the max char length of group_concat (1024 to 1000000 chars)
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $locale = app()->getLocale();

        $result = Product::select([
            'products.id',
            'stocks.sku',
            'products.status',
            DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `products`.`name` AND `translations`.`language_code` = "'.$locale.'") 
				AS name'),
            DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `products`.`description` AND `translations`.`language_code` = "'.$locale.'") 
				AS description'),
            DB::raw('(SELECT `translations`.`text` 
				FROM `translations` 
				WHERE `translations`.`translation_id` = `products`.`slug` AND `translations`.`language_code` = "'.$locale.'") 
				AS slug'),
            DB::raw("GROUP_CONCAT( DISTINCT
					CASE variant_values.option_id
				    WHEN variant_options.id THEN variant_options.name
				    END
					ORDER BY variant_options.id SEPARATOR ',') as option_values"),
            'stocks.price',
            'stocks.quantity',
            'stocks.weight',
            'products.compare_price',
            'products.created_at',
            DB::raw("GROUP_CONCAT( DISTINCT images.file ORDER BY images.position SEPARATOR '(~)') as images"),
        ])
        ->leftJoin('images', 'products.id', '=', 'images.item_id')
        ->leftJoin('stocks', 'products.id', '=', 'stocks.product_id')
        ->leftJoin('variant_values', 'stocks.id', '=', 'variant_values.stock_id')
        ->leftJoin('variant_options', 'variant_values.variant_id', '=', 'variant_options.variant_id')
        ->where('stocks.sku', $sku)
        ->orderBy('products.created_at')
        ->groupBy('products.id')
        ->get();

        $result = constructAttributes($result);

        return $result[0]->toArray(); //outputs array
    }

    public static function getStockJson($stocks)
    {
        foreach ($stocks as $stock) {
            $key = str_replace(",", "", $stock['option_option_ids']);
            $result[$key]['sku'] = $stock['sku'];
            $result[$key]['quantity'] = $stock['quantity'];
            $result[$key]['price'] = $stock['price'];
        }

        return json_encode($result);
    }
}
