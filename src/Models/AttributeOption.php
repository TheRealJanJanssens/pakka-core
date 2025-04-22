<?php

namespace TheRealJanJanssens\PakkaCore\Models;

use TheRealJanJanssens\PakkaCore\Models\AttributeInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Session;

class AttributeOption extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'input_id',
        'option_id',
        'language_code',
        'position',
        'value',
    ];

    public function input()
    {
        return $this->hasOne(AttributeInput::class, 'input_id', 'input_id');
    }

    /**
     * Legacy Code
     *
     * TODO: phase this out
     */

    /*
    |--------------------------------------------------------------------------
    | Constructs language inputs
    |--------------------------------------------------------------------------
    |
    | Constructs POST inputs with language inputs before storing them.
    | $array are all inputs received by POST request
    | $itemObject decides if the translations needs to be stored in
    | table translations or attribute_value
    |
    */

    public static function constructOptions($array)
    {
        //BASE VARIABLES
        $inputId = constructTransId($array['input_id']);// generates input_id
        $translationId = $array['option_id']; //option id is the same as translation id
        $array['input_id'] = $inputId;
        $inputType = isset($array["type"]) ? $array["type"] : null;
        $langs = Session::get('lang');
        $optionsInputs = ["select", "checkbox","radio"];
        $iT = 0; //translation_id count (used for debug)
        $iI = 0; //input count. used to keep track of the custom inputs

        foreach ($array as $key => $value) {
            if (substr($key, 2, 1) === ':' && contains($inputType, $optionsInputs)) {
                //explodes key to extract name and language
                $expKey = explode(":", $key);
                $languageCode = $expKey[0];
                $inputName = $expKey[1];

                //check if value is an option input
                if ($inputName == "option" && $value[0] !== null) {
                    $vI = 0; // value array counter

                    foreach ($value as $item) {
                        //remove duplicate translation_ids and rekey so its the same format like the loop through the inputs
                        $xI = 0;
                        for ($x = 0; $x < count($translationId); $x++) {
                            if ($xI == 0) {
                                $tempId[$x] = $translationId[$x];
                                $tempPos[$x] = $array["option_position"][$x];
                            }
                            $xI++;
                            if ($xI == count($langs)) {
                                $xI = 0;
                            }
                        }
                        $translationId = array_values($tempId);
                        $position = array_values($tempPos);
                        $position = $position[$vI];

                        $optionId = constructTransId($translationId[$vI]);

                        //If multiple languages this prevents multiple translation ids for one input key (nl:title,en:title,fr:title -> key = title)
                        if (! isset($result[$inputName][$vI])) {
                            $result[$inputName][$vI] = $optionId; //stores trans_id per key
                        } else {
                            $optionId = $result[$inputName][$vI];
                        }

                        $debug['translations'][$iT] = ['mode' => 'static', 'option_id' => $optionId, 'language_code' => $languageCode, 'input_id' => $inputId, 'text' => addslashes($item), 'position' => $position, '$iV' => $vI];

                        AttributeOption::updateOrCreate(['option_id' => htmlspecialchars($optionId), 'input_id' => htmlspecialchars($inputId), 'language_code' => htmlspecialchars($languageCode)], ['value' => htmlspecialchars(addslashes($item)), 'position' => htmlspecialchars($position) ]);
                        $vI++;
                        $iT++;
                    }
                } else {
                    //dd($array);
                    //dd("something went wrong check AttributeOption model(".$key.")");
                }
            } else {
                //NON INPUTS (_token,status,...)
                //status is a general input for all items and is set with a hidden input
                $result[$key] = $value;
            }
        }

        $debug["start_array"] = $array;
        $debug["end_array"] = $result;

        return $result;
    }
}
