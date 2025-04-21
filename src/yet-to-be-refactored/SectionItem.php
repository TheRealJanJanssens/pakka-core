<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SectionItem extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'section',
        'name',
        'tags',
        'permission',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'section' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'section' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get sections by type
    |
    | $type
    |------------------------------------------------------------------------------------
    */

    public static function getSectionItemsByType($type, $role = null)
    {
        $result = SectionItem::select([
        'section_items.id',
        'section_items.name',
        'section_items.section',
        'section_items.tags',
        ])
        ->where('section_items.type', $type);

        if ($role) {
            $result = $result->where('section_items.permission', '<=', $role);
        }

        $result = $result->orderBy('name')
        ->get();

        return $result;
    }
}
