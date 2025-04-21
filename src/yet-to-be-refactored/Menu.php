<?php

namespace TheRealJanJanssens\Pakka\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Session;

class Menu extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','name',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'name' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'name' => 'required',
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Gets all the menus in a link array
    |
    | This is used in the layout editor to display the menus in the select menus options
    | Outputs [id => name ,...]
    |------------------------------------------------------------------------------------
    */

    public static function getMenuLinks()
    {
        $pages = Menu::select([
            'menus.id',
            'menus.name',
        ])
        ->orderBy('menus.id')
        ->get()->toArray();

        if (! empty($pages)) {
            foreach ($pages as $page) {
                $result[$page["id"]] = $page['name'];
            }
        } else {
            $result = [];
        }

        return $result;
    }

    public static function getMenuOrFirst($id = null)
    {
        if (empty($id)) {
            $query = Cache::remember('menus.first-menu', 60 * 60 * 24, function () {
                return Menu::select('menus.id')->where('id', '!=', 1)->first();
            });
            $id = $query->id;
        } else {
            $id;
        }

        return Session::get('menus')[$id];
    }

    /*
    |--------------------------------------------------------------------------
    | Constructs Menus
    |--------------------------------------------------------------------------
    |
    | Constructs a menu with all its subitems.
    |
    | $id is the id of the menu that you want to be constructed.
    |
    */
    public static function constructMenu($id = null)
    {
        $result = [];
        $currentAuth = 0; //Default auth role to 0 if not set. It isn't set when 'php artisan route:list' and throws a 'Trying to get property of non-object' error because the value is NULL
        if (! empty(auth()->user()->role)) {
            $currentAuth = auth()->user()->role;
        }

        if ($id !== null) {
            $menus = Menu::where('id', $id)->get()->toArray();
        } else {
            $menus = Menu::get()->toArray();
        }

        $i = 0;
        foreach ($menus as $menu) {
            $menuId = $menu['id'];

            $result[$menuId] = $menu;

            //TODO: currentAuth only get the specific auth and not all lesser auths
            // This is very expensive to include all languages
            $items = [];
            foreach (Language::getLangCodes() as $lang) {
                $its = MenuItem::getMenuItems($menuId, $currentAuth, $lang)->toArray();
                $i = 0;
                foreach ($its as $it) {
                    $its[$i]['locale'] = $lang;
                    $i++;
                }
                $items[] = $its;
            }

            $items = collect($items)->collapse()->reverse()->toArray();

            //Duplicate $items with true position for constructing parents
            //$itemsDuplicate = $items;

            //reverses all menu items so if proper positioned subitems can be constructed
            //$items = array_reverse($items);

            foreach ($items as $masterKey => $item) {
                //edit to allow parents
                if (isset($item['parent'])) {
                    //Loops to find its parent
                    foreach ($items as $key => $subitem) {
                        if ($subitem['id'] == $item['parent']) {
                            if (! isset($items[$key]['items'])) {
                                //makes new item
                                $items[$key]['items'][0] = $items[$masterKey];
                            } else {
                                //Puts new elemen in front to respect correct position
                                array_unshift($items[$key]['items'], $items[$masterKey]);
                            }
                        }
                    }
                }
            }

            //Removes subitems that are not in their parent
            foreach ($items as $key => $item) {
                if (! empty($item["parent"])) { //!isset($item["items"]) &&
                    unset($items[$key]);
                }
            }

            //Restores true position in menu
            $items = array_reverse($items);

            $result[$menuId]['items'] = $items;
        }

        return $result;
    }

    //ROUTE GENERATION (move to service provider?)
    public static function prepareRoute($page, $link, $hasChild = false)
    {
        $result['id'] = $page['id'];
        $result['template'] = $page['template'];
        $result['page_uid'] = $page['page_uid'];
        $slug = ! empty($link['preslug']) ? $link['preslug'].'/'.$link['slug'] : $link['slug'];
        $parameters = $hasChild ? "/{param1?}/{param2?}" : "";
        $langs = Language::getLangCodes();

        //only give it optional parameters if it is last nested item
        if ($page['position'] == 1) {
            $result['slugs']["page.index"] = "";
        }

        $pageAs = 'page.'.$link['slug']; //basic route without nesting
        $menuAs = 'page.menu.'.str_replace('/', '_', $slug); //route with navigation nesting

        $result['slugs'][$pageAs] = $link['slug'].$parameters;
        $result['slugs'][$menuAs] = $slug.$parameters;

        if (count($langs) > 1) {
            $result['slugs']['locale.'.$pageAs] = "{locale?}/".$link['slug'].$parameters;
            $result['slugs']['locale.'.$menuAs] = "{locale?}/".$slug.$parameters;
        }

        return $result;
    }

    public static function linkPages($menuItems, $preslug = null)
    {
        $pages = Page::getPages(2, 1);
        $result = [];
        foreach ($menuItems as $items) {
            $uid = $items['page_uid'];
            $pageKey = array_search($uid, array_column($pages, 'page_uid'));
            $slug = ! empty($preslug) ? $preslug.'/'.$items['link'] : $items['link'];

            if (isset($result[$uid])) {
                $prepare = Menu::prepareRoute($pages[$pageKey], ["preslug" => $preslug, "slug" => $items['link']], ! isset($items['items']));
                $result[$uid]['slugs'] = array_merge($result[$uid]['slugs'], $prepare['slugs']);
            } else {
                $result[$uid] = Menu::prepareRoute($pages[$pageKey], ["preslug" => $preslug, "slug" => $items['link']], ! isset($items['items']));
            }

            if (isset($items['items'])) {
                $result = array_replace_recursive($result, Menu::linkPages($items['items'], $slug));
            }
        }

        return $result;
    }

    public static function generateRoutes()
    {
        $menus = Menu::constructMenu();

        $result = [];
        foreach ($menus as $menu) {
            if ($menu['id'] == 1) {
                continue;
            }

            if ($menu['items']) {
                foreach ($menu['items'] as $items) {
                    $result = Cache::tags('content')->remember('routes.'.$items['page_uid'], 60 * 60 * 24, function () use ($result, $menu) {
                        return array_replace_recursive($result, Menu::linkPages($menu['items']));
                    });
                }
            }
        }

        return $result;
    }
}
