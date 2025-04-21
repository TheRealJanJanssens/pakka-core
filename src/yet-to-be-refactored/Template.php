<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;

class Template extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'file',
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
            'name' => "required",
            'file' => "required|mimetypes:application/json",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'name' => "required",
            'file' => "required",
        ]);
    }

    public static function store(Request $request)
    {
        $fileName = 'template_'.time().'.'.$request->file->extension();
        $request->file->move(storage_path('app/public/templates'), $fileName);

        return Template::create(['name' => $request->name, 'file' => $fileName]);
    }

    public static function getSelect()
    {
        $result = [];
        $templates = Template::all();

        foreach ($templates as $template) {
            $result[$template->file] = $template->name;
        }

        return $result;
    }
}
