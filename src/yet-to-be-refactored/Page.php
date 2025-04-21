<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Schema;
use Session;

class Page extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'position',
        'status',
        'slug',
        'name',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'template',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'slug' => "required",
            'name' => "required",
            'template' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'slug' => "required",
            'name' => "required",
            'template' => "required",
        ]);
    }

    public static function getPageBySlug($slug)
    {
        $locale = app()->getLocale();

        $result = Page::select([
        'pages.id',
        'pages.status',
        'pages.template',
        'pages.position',
        DB::raw('(SELECT `translations`.`text`
  				FROM `translations`
  				WHERE `translations`.`translation_id` = `pages`.`slug` AND `translations`.`language_code` = '.$locale.')
  				AS slug'),
            ])
        ->where('slug', $slug)
        ->get();

        return $result;
    }

    /*
    |------------------------------------------------------------------------------------
    | Get page with translations
    |
    | $id = Item id
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getPage($id, $mode)
    {
        switch (true) {
            case $mode == 1:
                $locale = app()->getLocale();

                $result = Page::select([
                   'pages.id',
                   'pages.status',
                   'pages.template',
                   'pages.position',
                   DB::raw('(SELECT `translations`.`text`
                    FROM `translations`
                    WHERE `translations`.`translation_id` = `pages`.`slug` AND `translations`.`language_code` = "'.$locale.'")
                    AS slug'),
                   DB::raw('(SELECT `translations`.`text`
                    FROM `translations`
                    WHERE `translations`.`translation_id` = `pages`.`name` AND `translations`.`language_code` = "'.$locale.'")
                    AS name'),
                   DB::raw('(SELECT `translations`.`text`
                    FROM `translations`
                    WHERE `translations`.`translation_id` = `pages`.`meta_title` AND `translations`.`language_code` = "'.$locale.'")
                    AS meta_title'),
                   DB::raw('(SELECT `translations`.`text`
                    FROM `translations`
                    WHERE `translations`.`translation_id` = `pages`.`meta_description` AND `translations`.`language_code` = "'.$locale.'")
                    AS meta_description'),
                   DB::raw('(SELECT `translations`.`text`
                    FROM `translations`
                    WHERE `translations`.`translation_id` = `pages`.`meta_keywords` AND `translations`.`language_code` = "'.$locale.'")
                    AS meta_keywords'),
                   ])
                   ->where('pages.id', $id)
                   ->orderBy('pages.position');

                $result = Cache::tags('content')->remember('page:'.$id.':1', 60 * 60 * 24, function () use ($result) {
                    return $result->first();
                });

                break;
            case $mode == 2:

                $queryResult = Page::select([
                   'pages.id',
                   'pages.status',
                   'pages.template',
                   'pages.position',
                   DB::raw('(SELECT
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
									WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
								END SEPARATOR "(~)"
							)
						FROM `translations`
						WHERE `translations`.`translation_id` = `pages`.`slug`)
						AS language_code'),
                   DB::raw('(SELECT
		        			GROUP_CONCAT(
		        				CASE
									WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
									WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
								END SEPARATOR "(~)"
							)
						FROM `translations`
						WHERE `translations`.`translation_id` = `pages`.`slug`)
						AS slug'),
                   DB::raw('(SELECT
                            GROUP_CONCAT(
                                CASE
                                    WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                    WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                END SEPARATOR "(~)"
                            )
                        FROM `translations`
                        WHERE `translations`.`translation_id` = `pages`.`name`)
                        AS name'),
                   DB::raw('(SELECT
                            GROUP_CONCAT(
                                CASE
                                    WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                    WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                END SEPARATOR "(~)"
                            )
                        FROM `translations`
                        WHERE `translations`.`translation_id` = `pages`.`meta_title`)
                        AS meta_title'),
                   DB::raw('(SELECT
                            GROUP_CONCAT(
                                CASE
                                    WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                    WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                END SEPARATOR "(~)"
                            )
                        FROM `translations`
                        WHERE `translations`.`translation_id` = `pages`.`meta_description`)
                        AS meta_description'),
                   DB::raw('(SELECT
                            GROUP_CONCAT(
                                CASE
                                    WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
                                    WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
                                END SEPARATOR "(~)"
                            )
                        FROM `translations`
                        WHERE `translations`.`translation_id` = `pages`.`meta_keywords`)
                        AS meta_keywords'),
                   DB::raw('`pages`.`slug` AS slug_trans'),
                   DB::raw('`pages`.`name` AS name_trans'),
                   DB::raw('`pages`.`meta_title` AS meta_title_trans'),
                   DB::raw('`pages`.`meta_description` AS meta_description_trans'),
                   DB::raw('`pages`.`meta_keywords` AS meta_keywords_trans'),
                   ])
                   ->where('pages.id', $id)
                   ->orderBy('pages.position');

                $queryResult = Cache::tags('content')->remember('page:'.$id.':2', 60 * 60 * 24, function () use ($queryResult) {
                    return $queryResult->get();
                });

                $result = constructTranslatableValues($queryResult, ['slug','name','meta_title','meta_description','meta_keywords']);

                break;
        }

        return $result; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get pages with translations
    |
    | $mode = construct attributes for display (1) or edit (2) purpose
    |------------------------------------------------------------------------------------
    */

    public static function getPages($mode = 1, $status = null)
    {
        $locale = app()->getLocale();

        switch ($mode) {
            case 1:
                $result = Page::select([
                  'pages.id',
                  'pages.status',
                  DB::raw(' (SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`slug` AND `translations`.`language_code` = "'.$locale.'") AS slug'),
                  'pages.template',
                  DB::raw(' (SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`name` AND `translations`.`language_code` = "'.$locale.'") AS name'),
                  'pages.name AS page_uid',
                  'pages.slug as link',
                  ])
                  ->orderBy('pages.position')
                  ->get();

                break;
            case 2:
                $result = [];
                if (Schema::hasTable('languages')) {
                    $langs = Language::all(); //Session::get('lang') session not accesable in route sessionstart happens after route
                    foreach ($langs as $lang) {
                        $locale = $lang->language_code;

                        $resultPages = Page::select([
                            'pages.id',
                            'pages.position',
                            'pages.status',
                            DB::raw(' (SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`slug` AND `translations`.`language_code` = "'.$locale.'") AS slug'),
                            'pages.template',
                            DB::raw(' (SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`name` AND `translations`.`language_code` = "'.$locale.'") AS name'),
                            'pages.slug AS page_uid',
                        ]);

                        if ($status !== null) {
                            $resultPages->where('pages.status', $status);
                            //dd($status);
                        }

                        $resultPages = $resultPages->orderBy('pages.position')->get();

                        foreach ($resultPages as $p) {
                            array_push($result, $p);
                        }
                    }
                }

                break;
        }

        return $result;
    }

    /*
    |------------------------------------------------------------------------------------
    | Gets all the pages in a link array
    |
    | This is used in the menu module to display the pages in the create and update menuitem views
    | The slug is can be translated with setting trans = true
    | Outputs [slug => name ,...]
    |------------------------------------------------------------------------------------
    */

    public static function getPagesLinks($trans = false)
    {
        $locale = app()->getLocale();
        $result = [];

        if ($trans == true) {
            $pages = Page::select([
            DB::raw(' (SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`name` AND `translations`.`language_code` = "'.$locale.'") AS name'),
            '(SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`slug` AND `translations`.`language_code` = "'.$locale.'") as slug',
            ])
            ->orderBy('pages.position')
            ->get();
        } else {
            $pages = Page::select([
            DB::raw(' (SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `pages`.`name` AND `translations`.`language_code` = "'.$locale.'") AS name'),
            'pages.slug as slug',
            ])
            ->orderBy('pages.position')
            ->get();
        }

        if (! empty($pages)) {
            foreach ($pages as $page) {
                $result[$page->slug] = $page->name;
            }
        }

        return $result;
    }

    public static function generateTemplate($id, $content = true)
    {
        $sections = Section::generateSectionTemplate(2, $id)->toArray();
        $i = 0;
        foreach ($sections as $section) {
            $result['sections'][$i] = $section;
            $components = getCompMeta($section['slug'], "component");

            if ($content == true) {
                $iC = 0;
                $componentInputs = Component::getSectionContent($section["id"], 1)->toArray();
                if (! empty($components)) {
                    foreach ($components as $component) {
                        $iI = 0;
                        foreach ($component['inputs'] as $input) {
                            $key = $components[$iC]['inputs'][$iI]["name"];
                            if (isset($componentInputs[$iC][$key]) && ! is_array($componentInputs[$iC][$key])) {
                                $components[$iC]['inputs'][$iI]["default"] = $componentInputs[$iC][$key];
                            }
                            $iI++;
                        }
                        $iC++;
                    }
                }
            }

            $result['sections'][$i]['components'] = $components;
            $i++;
        }

        return json_encode($result);
    }
}
