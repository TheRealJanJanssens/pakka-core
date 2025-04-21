<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Session;

class Item extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','module_id','slug','status','created_at','created_by','updated_at','updated_by',
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
            'module_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'id' => "required",
            'module_id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Construct the attributes (and images) of items
    |
    | $items = array of items
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    /*
        public static function constructAttributes($items,$mode = 1){

            $i = 0;

            switch ($mode) {
                case 1:

                    foreach($items as $item){

                        $attributeNames = explode("(~)", $item['attribute_names']);
                        $attributeValues = explode("(~)", $item['attribute_values']);

                        //CONSTRUCT IMAGES
                        if($item['images'] !== null){
                            $images = explode("(~)", $item['images']);
                            $items[$i]["images"] = $images;
                        }

                        //CONSTRUCT ATTRIBUTES
                        $iA = 0; // general attribute int
                        foreach($attributeNames as $attributeName){
                            if($attributeName == "title"){
                                //exception for title (put outside the attribute array
                                $items[$i]['title'] = $attributeValues[$iA];
                            }else{
                                $items[$i]["attributes"][$attributeName] = $attributeValues[$iA]; //$items[$i]["attributes"][$iA][$attributeLabel]
                            }

                            $iA++;
                        }

                        unset($items[$i]["attribute_names"]);
                        unset($items[$i]["attribute_values"]);

                        $i++;
                    }
                    break;
                case 2:

                    foreach($items as $item){

                        $attributeNames = explode("(~)", $item['attribute_names']);
                        $attributeLangCodes = explode("(~)", $item['attribute_language_codes']);
                        $attributeValues = explode("(~)", $item['attribute_values']);

                        //CONSTRUCT SLUG
                        if(isset($item['slug']) && !empty($item['slug'])){
                            $langs = Session::get('lang');

                            $slugs = explode("(~)", $item['slug']);
                            $iS=0;

                            foreach($langs as $lang){

                                //SAFETY INCASE OF NEW LANGUAGES
                                if ( ! isset($slugs[$iS])) {
                                   $slugs[$iS] = null;
                                }

                                $items[$i][$lang["language_code"].":slug:translation_id"] = $items[$i]['translation_id_slug'];
                                $items[$i][$lang["language_code"].":slug"] = $slugs[$iS];
                                $iS++;
                            }
                            unset($items[$i]['translation_id_slug']);
                        }

                        //CONSTRUCT IMAGES
                        if($item['images'] !== null){
                            $images = explode("(~)", $item['images']);
                            $items[$i]["images"] = $images;
                        }

                        //CONSTRUCT ATTRIBUTES
                        $iA = 0; // general attribute int
                        foreach($attributeNames as $attributeName){

                            $langCode = $attributeLangCodes[$iA];

                            if(!isset($langCode) || empty($langCode)){
                                $items[$i][$attributeName] = $attributeValues[$iA];
                            }else{
                                $items[$i][$langCode.':'.$attributeName] = $attributeValues[$iA];
                            }

                            $iA++;
                        }

                        unset($items[$i]["attribute_names"]);
                        unset($items[$i]["attribute_values"]);
                        unset($items[$i]["attribute_language_codes"]);

                        $i++;
                    }
                    break;
            }

            return $items;
        }
    */

    /*
    |------------------------------------------------------------------------------------
    | Get item with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getItem($id, $mode = 1)
    {
        //Sets the max char length of group_concat (1024 to 1000000 chars)
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        switch ($mode) {
            case 1:
                //Display mode
                $optionAttr = "attributes.option_value";
                $valueAttr = "attributes.name, IFNULL(attributes.value, '')";
                $slug = DB::raw('(SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `items`.`slug`) 
						AS slug');

                break;
            case 2:
                //Edit mode
                $optionAttr = "attributes.option_id";
                $valueAttr = "attributes.language_code, attributes.name, IFNULL(attributes.value, '')";
                $slug = DB::raw('`items`.`slug` AS translation_id_slug, (SELECT 
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							) 
						FROM `translations` 
						WHERE `translations`.`translation_id` = `items`.`slug`) 
						AS slug');

                break;
        }

        $result = Item::select([
        'items.id',
        'items.module_id',
        'items.status',
        $slug,
        'items.created_at',
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
			) as attributes"), 'items.id', '=', 'attributes.item_id')
/*
                WHERE attribute_options.language_code = '".$locale."'
                OR attribute_values.language_code = '".$locale."'
*/
        ->leftJoin('images', 'items.id', '=', 'images.item_id')
        ->where('items.id', $id)
        ->orderBy('items.created_at')
        ->groupBy('items.id')
        //->toSql();
        ->get(); //->toArray()

        $result = constructAttributes($result, $mode);

        //dd($result);

        if (isset($result[0])) {
            $result = $result[0]; //removes associate construct
        }

        return $result; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get items with translations
    |
    | $moduleId = The module id you want to pull items from
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getItems($moduleId, $status = null, $sort = "desc", $limit = null)
    {
        $locale = app()->getLocale();

        //Sets the max char length of group_concat (1024 to 1000000 chars)
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $optionAttr = "attributes.option_value";
        $valueAttr = "attributes.name, IFNULL(attributes.value, '')";

        $result = Item::select([
        'items.id',
        'items.module_id',
        'items.status',
        DB::raw('(SELECT 
        			IFNULL(`translations`.`text`, "")
				FROM `translations` 
				WHERE `translations`.`translation_id` = `items`.`slug` AND `translations`.`language_code` = "'.$locale.'") 
				AS slug'),
        'items.created_at',
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
			) as attributes"), 'items.id', '=', 'attributes.item_id')
        ->leftJoin('images', 'items.id', '=', 'images.item_id')
        ->where('items.module_id', $moduleId);

        if ($status !== null) {
            $result = $result->where('items.status', $status);
        }

        $result = $result
        ->orderBy('items.created_at', $sort)
        ->groupBy('items.id');

        if ($limit !== null) {
            $result = $result->limit($limit);
        }

        $result = $result->get();

        $result = constructAttributes($result);

        return $result; //outputs array
    }
}
