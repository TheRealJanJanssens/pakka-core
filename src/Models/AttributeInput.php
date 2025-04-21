<?php

namespace TheRealJanJanssens\PakkaCore\Models;

use TheRealJanJanssens\PakkaCore\Models\AttributeOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Session;

class AttributeInput extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'input_id',
        'set_id',
        'position',
        'label',
        'name',
        'type',
        'required',
        'attributes',
        'created_at',
        'updated_at',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'set_id' => "required",
            'name' => "required",
            'type' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'set_id' => "required",
            'name' => "required",
            'type' => "required",
        ]);
    }

    public function getOptions()
    {
        // Accessing comments posted by a user
        return $this->hasMany(AttributeOption::class);
    }

    public static function prepareAttributes($array)
    {
        $attributeInputs = ["input_width"];

        foreach ($attributeInputs as $attributeInput) {
            if (isset($array[$attributeInput])) {
                $array['attributes'][$attributeInput] = $array[$attributeInput];
                unset($array[$attributeInput]);
            }
        }

        if (isset($array['attributes'])) {
            $array['attributes'] = json_encode($array['attributes']);
        }

        return $array;
    }

    public function constructAttributes($array)
    {
        $array['attributes'] = json_decode($array['attributes']);

        if (! empty($array['attributes'])) {
            foreach ($array['attributes'] as $key => $value) {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Input
    | $id = select input with its id
    | $mode = display (1), edit (2) (currently only used in editing)
    |------------------------------------------------------------------------------------
    */

    public static function getInput($id, $mode = 2)
    {
        //No seperate display mode present
        $queryResult = AttributeInput::select([
        'attribute_inputs.id',
        'attribute_inputs.input_id',
        'attribute_inputs.set_id',
        'attribute_inputs.position',
        DB::raw('(SELECT
                    GROUP_CONCAT(
                        CASE
                            WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
                            WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
                        END SEPARATOR "(~)"
                    )
                FROM `translations`
                WHERE `translations`.`translation_id` = `attribute_inputs`.`label`)
                AS language_code'),
        DB::raw('IFNULL((SELECT
                    GROUP_CONCAT(
                        CASE
                            WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                            WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                        END SEPARATOR "(~)"
                    )
                FROM `translations`
                WHERE `translations`.`translation_id` = `attribute_inputs`.`label`), `attribute_inputs`.`label`)
                AS label'),
        DB::raw('`attribute_inputs`.`label` AS label_trans'),
        'attribute_inputs.name',
        'attribute_inputs.type',
        'attribute_inputs.attributes',
        'attribute_inputs.required',
        ])
        ->where('attribute_inputs.id', $id)
        ->orderBy('attribute_inputs.position');

        $input = constructTranslatableValues($queryResult->get(), ['label']);

        if ($input["type"] == "select" || $input["type"] == "checkbox") {
            $options = AttributeOption::select([
                'attribute_options.id',
                'attribute_options.input_id',
                'attribute_options.option_id',
                'attribute_options.language_code',
            'attribute_options.value',
            'attribute_options.position',
            ])
            ->where('attribute_options.input_id', $input["input_id"])
            ->orderBy('attribute_options.position')
            ->get()->toArray();

            foreach ($options as $option) {
                $name = $option['language_code'].":option";

                if (! isset($input[$name])) {
                    $input[$name] = [];
                }
                array_push($input[$name], $option);
            }
        }

        $input = parent::constructAttributes($input);

        //dd($input);
        return $input; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Inputs
    | selects all the inputs related to the moduleId stored in session
    |------------------------------------------------------------------------------------
    */

    public static function getInputs($id = null)
    {
        $id = isset($id) ? $id : Session::get('set_id');

        $locale = app()->getLocale();
        $queryResult = AttributeInput::select([
        'attribute_inputs.id',
        'attribute_inputs.input_id',
        'attribute_inputs.set_id',
        'attribute_inputs.position',
        DB::raw('IFNULL((SELECT `translations`.`text`
                    FROM `translations`
                    WHERE `translations`.`translation_id` = `attribute_inputs`.`label` AND `translations`.`language_code` = "'.$locale.'"), `attribute_inputs`.`label`)
                    AS label'),
        'attribute_inputs.name',
        'attribute_inputs.type',
        'attribute_inputs.attributes',
        'attribute_inputs.required',
        ])
        //Session set_id is converted into a string because there is a small chance that the set_id (for example 9 (int)) already "exists" as component set_id like 9DxQLZ4.
        //because it begins with a 9 the query doesn't distinguish them and treats them as the same.
        //to prevent this session set_id needs to be converted to a string
        //a better solution (long term) would be to get rid of the menu_item IDs as int and make them like the item IDs
        ->where('attribute_inputs.set_id', (string) $id)
        ->orderBy('attribute_inputs.position')
        ->get()->toArray();

        $i = 0;
        foreach ($queryResult as $item) {
            if ($item["type"] == "select" || $item["type"] == "checkbox") {
                $options = AttributeOption::select([
                    'attribute_options.id',
                    'attribute_options.input_id',
                    'attribute_options.option_id',
                    'attribute_options.language_code',
                'attribute_options.value',
                'attribute_options.position',
                ])
                ->where('attribute_options.input_id', $item["input_id"])
                ->where('attribute_options.language_code', Session::get("locale"))
                ->orderBy('attribute_options.position')
                ->get()->toArray();

                foreach ($options as $option) {
                    $name = $option['language_code'].":option";

                    if (! isset($queryResult[$i][$name])) {
                        $queryResult[$i][$name] = [];
                    }
                    array_push($queryResult[$i][$name], $option);
                }
            }

            $queryResult[$i] = parent::constructAttributes($queryResult[$i]);

            $i++;
        }

        return $queryResult; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Gets a checklist for Inputs
    | selects all the inputs (name => input_id) related to the moduleId stored in session
    |------------------------------------------------------------------------------------
    */

    public static function getInputsChecklist()
    {
        $result = null;
        $queryResult = AttributeInput::select([
        'attribute_inputs.input_id',
        'attribute_inputs.name',
        ])
        ->where('attribute_inputs.set_id', Session::get('set_id'))
        ->orderBy('attribute_inputs.position')
        ->get();

        foreach ($queryResult as $item) {
            $result[$item->name] = $item->input_id;
        }

        return $result; //outputs array
    }
}
