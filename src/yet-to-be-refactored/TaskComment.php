<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class TaskComment extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','task_id','user_id','created_at','text',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'task_id' => "required",
            'user_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'task_id' => "required",
            'user_id' => "required",
        ]);
    }

    public static function getComments($id)
    {
        $result = TaskComment::select([
        'task_comments.id',
        'task_comments.task_id',
        'task_comments.user_id',
        'task_comments.created_at',
        'task_comments.text',
        DB::raw("(SELECT name FROM users WHERE id = user_id) as user_name"),
        DB::raw("(SELECT avatar FROM users WHERE id = user_id) as avatar"),
        ])
        ->where('task_comments.task_id', $id)
        ->orderBy('task_comments.id', 'desc')
        ->get()->toArray();

        return $result;
    }
}
