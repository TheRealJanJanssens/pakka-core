<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Component extends Model
{
    use Notifiable;

    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'page_id',
        'section_id',
        'position',
        'slug',
        'name',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'page_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'page_id' => "required",
        ]);
    }

    public function setAttributes()
    {
    }

    /*
    |------------------------------------------------------------------------------------
    | Get content with translations
    |
    | $id = Content id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |
    | NOTE: 'WHEN attributes.value IS NULL AND attributes.name IS NULL THEN attributes.option_value' rule prevents
    | a random empty value at the beginning in 'attribute_values'. It occurs on certain servers with for example mariaDB.
    | Didn't found out yet why it happens but this problem shouldn't occure anymore.
    |------------------------------------------------------------------------------------
    */

    public static function getContent($id, $mode)
    {
        switch ($mode) {
            case 1:
                //Display mode
                $optionAttr = "attributes.option_value";
                $valueAttr = "attributes.name, IFNULL(attributes.value, '')";

                break;
            case 2:
                //Edit mode
                $optionAttr = "attributes.option_id";
                $valueAttr = "attributes.language_code, attributes.name, IFNULL(attributes.value, '')";

                break;
        }

        //Sets the max char length of group_concat (1024 to 1000000 chars)
        // Cache::remember('statements.group_concat_max_len:', 60 * 60 * 24, function () {
        //     return DB::statement("SET SESSION group_concat_max_len = 1000000;");
        // });

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $result = Component::select([
        'components.id',
        'components.page_id',
        'components.section_id',
      'components.slug',
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
			) as attributes"), 'components.id', '=', 'attributes.item_id')
/*
                WHERE attribute_options.language_code = '".$locale."'
                OR attribute_values.language_code = '".$locale."'
*/
        ->leftJoin('images', 'components.id', '=', 'images.item_id')
        ->where('components.id', $id)
        ->orderBy('components.position')
        ->groupBy('components.id')
        ->get();

        if (! empty($result) && isset($result)) {
            $result = constructAttributes($result, $mode);
            $result = $result[0]; //removes associate construct
        }

        return $result; //outputs array
    }

    public static function getSectionContent($id, $mode)
    {
        $locale = app()->getLocale();
        switch ($mode) {
            case 1:
                //Display mode
                $optionAttr = "attributes.option_value";
                $valueAttr = "attributes.name, IFNULL(attributes.value, '')";

                break;
            case 2:
                //Edit mode
                $optionAttr = "attributes.option_id";
                $valueAttr = "attributes.language_code, attributes.name, IFNULL(attributes.value, '')";

                break;
            case 3:
                //Edit mode
                $optionAttr = "attributes.option_id";
                $valueAttr = "attributes.language_code, attributes.name, IFNULL(attributes.value, '')";

                break;
        }

        //Sets the max char length of group_concat (1024 to 1000000 chars)
        // Cache::remember('statements.group_concat_max_len:', 60 * 60 * 24, function () {
        //     return DB::statement("SET SESSION group_concat_max_len = 1000000;");
        // });

        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $result = Component::select([
        'components.id',
        'components.page_id',
        'components.section_id',
      'components.slug',
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
				WHERE (attribute_values.language_code IS NULL AND attribute_options.language_code = '".$locale."')
				OR  (attribute_options.language_code IS NULL AND attribute_values.language_code = '".$locale."')
			) as attributes"), 'components.id', '=', 'attributes.item_id')
        ->leftJoin('images', 'components.id', '=', 'images.item_id')
        ->where('components.section_id', $id)
        ->orderBy('components.position')
        ->groupBy('components.id');

        $result = Cache::tags('content')->remember('sectionContent:'.$id, 60 * 60 * 24, function () use ($result) {
            return $result->get();
        });

        //error in leftjoin: the WHERE clause doesnt work properly in MARIADB (one.com) it gets all the languages bc attribute_options.language_code is not null when empty so the following change has been made.

        //from:
        //WHERE attribute_options.language_code = '".$locale."'
        //OR attribute_values.language_code = '".$locale."'

        //to:
        //WHERE (attribute_values.language_code IS NULL AND attribute_options.language_code = '".$locale."')
        //OR  (attribute_options.language_code IS NULL AND attribute_values.language_code = '".$locale."')

        if (! empty($result) && isset($result)) {
            $result = constructAttributes($result, $mode);
        }

        return $result; //outputs array
    }
}
