<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Section extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_id',
        'position',
        'status',
        'type',
        'name',
        'section',
        'classes',
        'attributes',
        'extras',
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
            //'page_id'    => "required",
            'position' => "required",
            'section' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            //'page_id'    => "required",
            'position' => "required",
            'section' => "required",
        ]);
    }

    public static function getSections($mode)
    {
        switch (true) {
            case $mode == 1:

                break;
            case $mode == 2:
                //section editing in page module (add/edit)
                $queryResult = Section::select([
                'sections.id',
                'sections.name',
                'sections.page_id',
                ])->get();

                //formats the sections so it can be used in db
                foreach ($queryResult as $section) {
                    $id = $section['id'];
                    $name = $section['name']." (page:".$section['page_id'].")";
                    $result[$id] = $name;
                }

                return $result;

                break;
        }
    }

    /*
    |------------------------------------------------------------------------------------
    | Get sections with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getSection($id, $mode)
    {
        switch (true) {
            case $mode == 1:

                break;
            case $mode == 2:
                //section editing in page module (add/edit)
                $queryResult = Section::select([
                'sections.id',
                'sections.name',
                'sections.page_id',
                ])->get();

                //formats the sections so it can be used in db
                foreach ($queryResult as $section) {
                    $id = $section['id'];
                    $name = $section['name']." (page:".$section['page_id'].")";
                    $result[$id] = $name;
                }

                /*
                //query if you want to translate the name attibute
                $queryResult = Section::select([
                'sections.id',
                'sections.page_id',
                'sections.type',
                'sections.position',
                'sections.section',
                'sections.attributes',
                DB::raw('(SELECT
                            GROUP_CONCAT(
                                CASE
                                    WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
                                    WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
                                END SEPARATOR "(~)"
                            )
                        FROM `translations`
                        WHERE `translations`.`translation_id` = `sections`.`name`)
                        AS language_code'),
                DB::raw('(SELECT
                            GROUP_CONCAT(
                                CASE
                                    WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                    WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                END SEPARATOR "(~)"
                            )
                        FROM `translations`
                        WHERE `translations`.`translation_id` = `sections`.`name`)
                        AS name'),
                DB::raw('`sections`.`name` AS name_trans'),
                  ])
                  ->where('sections.id', $id)
                  ->orderBy('sections.position')
                ->get()->toArray();

                foreach($queryResult as $item){
                    $i=0;
                    $languageCodes = explode("(~)", $item['language_code']);
                    $names = explode("(~)", $item['name']);

                    foreach($item as $key => $input){
                        $result[$key] = $input;
                    }

                    foreach($languageCodes as $languageCode){
                        $result[$languageCode.':name:translation_id'] = $item['name_trans'];
                        $result[$languageCode.':name'] = $names[$i];

                        $i++;
                    }
                }

                unset($result['name_trans']);
                unset($result['name']);
                unset($result['language_code']);

                break;
                */
        }

        return $result; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get sections by type
    |
    | $type
    |------------------------------------------------------------------------------------
    */

    public static function getSectionsByType($type, $id = null, $status = null)
    {
        if (! empty($id)) {
            $result = Section::select([
            'sections.id',
            'sections.status',
            'sections.name',
            'sections.page_id',
            'sections.position',
            'sections.type',
            'section_items.section',
            'sections.classes',
            'sections.attributes',
            'sections.extras',
            ])
            ->where('page_id', $id)
            ->where('sections.type', $type);

            if (isset($status)) {
                $result->where('sections.status', $status);
            }

            $result->leftJoin('section_items', 'section_items.id', '=', 'sections.section')
            ->orderBy('position');

            $result = Cache::tags('content')->remember('sectionsByPage:'.$id, 60 * 60 * 24, function () use ($result) {
                return $result->get();
            });
        } else {
            $result = Section::select([
            'sections.id',
            'sections.status',
            'sections.name',
            'sections.page_id',
            'sections.position',
            'sections.type',
            'section_items.section',
            'sections.classes',
            'sections.attributes',
            'sections.extras',
            ])
            ->where('sections.type', $type);

            if (isset($status)) {
                $result->where('sections.status', $status);
            }

            $result->leftJoin('section_items', 'section_items.id', '=', 'sections.section')
            ->orderBy('position');

            $result = Cache::tags('content')->remember('sectionsByType:'.$type, 60 * 60 * 24, function () use ($result) {
                return $result->get();
            });
        }

        return $result;
    }

    public static function generateSectionTemplate($type, $id = null, $status = null)
    {
        $result = Section::select([
        'sections.id',
        'sections.status',
        'sections.name',
        'sections.page_id',
        'sections.position',
        'sections.type',
        'sections.section',
        'section_items.section as slug',
        'sections.classes',
        'sections.attributes',
        'sections.extras',
        ])
        ->where('page_id', $id)
        ->where('sections.type', $type);

        if ($status !== null) {
            $result->where('sections.status', $status);
        }

        $result->leftJoin('section_items', 'section_items.id', '=', 'sections.section')
        ->orderBy('position');

        return $result->get();
    }
}
