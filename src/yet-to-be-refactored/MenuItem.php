<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class MenuItem extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'menu','parent','position', 'icon', 'name', 'link','permission',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'link' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'link' => 'required',
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get menu item with translations (mainly for editing)
    | $id = menu id
    | $locale = request specific locale (optional)
    | $permission = permission lvl (0,5,10)
    |------------------------------------------------------------------------------------
    */

    public static function getMenuItem($id, $locale = null)
    {
        if (! empty($locale) && isset($locale)) {
            //this isn't currently used because this function is only used for the edit form: !! please review !!
            $queryResult = MenuItem::select([
            'menu_items.id',
            'menu_items.menu',
            'menu_items.parent',
            'menu_items.icon',
            'menu_items.link',
            'menu_items.permission',
            'translations.language_code',
            'translations.input_name',
            'translations.text AS name', //DB::raw('GROUP_CONCAT( DISTINCT translations.text SEPARATOR "(~)") AS name')
            'menu_items.name AS translation_id', // important to update or delete translation items
            ])
            ->join('translations', 'menu_items.name', '=', 'translations.translation_id')
            ->where('menu_items.id', $id)
            ->where('translations.language_code', $locale)
            ->whereRaw('`menu_items`.`name` = `translations`.`translation_id`')
            ->orderBy('menu_items.position')
            ->get();
        } else {
            $queryResult = MenuItem::select([
            'menu_items.id',
            'menu_items.menu',
            'menu_items.parent',
            'menu_items.icon',
            'menu_items.link',
            'menu_items.permission',
             DB::raw('(SELECT
	        			GROUP_CONCAT(
	        				CASE
								WHEN `translations`.`language_code` IS NOT NULL THEN `translations`.`language_code`
								WHEN `translations`.`language_code` IS NULL THEN IFNULL(`translations`.`language_code`, "")
							END SEPARATOR "(~)"
						)
					FROM `translations`
					WHERE `translations`.`translation_id` = `menu_items`.`name`)
					AS language_code'),
            DB::raw('(SELECT
	        			GROUP_CONCAT(
	        				CASE
								WHEN `translations`.`text` IS NOT NULL THEN `translations`.`text`
								WHEN `translations`.`text` IS NULL THEN IFNULL(`translations`.`text`, "")
							END SEPARATOR "(~)"
						)
					FROM `translations`
					WHERE `translations`.`translation_id` = `menu_items`.`name`)
					AS name'),
            DB::raw('`menu_items`.`name` AS name_trans'),
            ])
            ->where('menu_items.id', $id)
            ->get();
        }

        $result = constructTranslatableValues($queryResult, ['name']);

        return $result; //outputs array
    }

    /*
    |------------------------------------------------------------------------------------
    | Get menu item with translations by Link
    | $link = menu id
    |------------------------------------------------------------------------------------
    */

    //Niet meer nodig?

    /*
        public static function getMenuItemByLink($link)
        {
            $locale = app()->getLocale();

            $query = MenuItem::select([
                'menu_items.id',
                'menu_items.menu',
                'menu_items.parent',
                'menu_items.icon',
                'menu_items.link',
                'menu_items.permission',
                'translations.language_code',
                'translations.input_name',
                'translations.text AS name', //DB::raw('GROUP_CONCAT( DISTINCT translations.text SEPARATOR "(~)") AS name')
                'menu_items.name AS translation_id' // important to update or delete translation items
                  ])
                  ->join('translations', 'menu_items.name', '=', 'translations.translation_id')
                ->where('menu_items.link', $link)
                ->where('translations.language_code', $locale)
                ->whereRaw('`menu_items`.`name` = `translations`.`translation_id`')
                ->orderBy('menu_items.position')
                ->get()->toArray();

            foreach($query as $item){
                $result[$item['id']] = $item['name'];
            }

            return $result; //outputs array
        }
    */

    /*
    |------------------------------------------------------------------------------------
    | Get menu items with translations
    | $id = menu id
    | $permission = permission lvl (0,5,10)
    |------------------------------------------------------------------------------------
    */

    public static function getMenuItems($id, $permission = 0, $lang = null)
    {
        $locale = empty($lang) ? app()->getLocale() : $lang;

        if ($id == 1) {
            //QUERY FOR ADMIN PANEL MENU
            $result = MenuItem::select([
            'menu_items.id',
            'menu_items.menu',
            'menu_items.parent',
            'menu_items.icon',
            'menu_items.link',
            'menu_items.permission',
            'translations.text AS name', //DB::raw('GROUP_CONCAT( DISTINCT translations.text SEPARATOR "(~)") AS name')
            ])
            ->join('translations', 'menu_items.name', '=', 'translations.translation_id')
            ->where('menu_items.menu', $id)
            ->where('menu_items.permission', '<=', $permission)
            ->whereRaw('`menu_items`.`name` = `translations`.`translation_id`')
            ->where('translations.language_code', $locale)
            ->orderBy('menu_items.position')
            ->get();
        } else {
            //QUERY FOR ALL OTHER MENUS
            $result = MenuItem::select([
            'menu_items.id',
            'menu_items.menu',
            'menu_items.parent',
            'menu_items.position',
            DB::raw('(SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `menu_items`.`link` AND `translations`.`language_code` = "'.$locale.'") AS link'),
            'menu_items.permission',
            DB::raw('(SELECT `translations`.`text` FROM `translations` WHERE `translations`.`translation_id` = `menu_items`.`name` AND `translations`.`language_code` = "'.$locale.'") AS name'),
            'menu_items.link as page_uid',
            ])
            ->where('menu_items.menu', $id)
            ->where('menu_items.permission', '<=', $permission)
            ->orderBy('menu_items.position')
            ->get();
        }

        //remove the slashes from the menu item name
        $i = 0;
        foreach ($result as $item) {
            $result[$i]['name'] = stripslashes($item['name']);
            $i++;
        }

        return $result; //outputs array
    }
}
