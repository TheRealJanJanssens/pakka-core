<?php

namespace TheRealJanJanssens\Pakka\Models;

use App\TaskComment;
use App\TaskGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Task extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','group_id','project_id','assigned_to','position','status','name','description','priority','tags','finished_at','finished_by','created_at','created_by','updated_at','updated_by',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'group_id' => "required",
            'project_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'group_id' => "required",
            'project_id' => "required",
        ]);
    }

    public static function getTask($id)
    {
        $task = Task::select([
        'tasks.id',
        'tasks.group_id',
        'tasks.project_id',
        'tasks.status',
        'tasks.assigned_to',
        'tasks.name',
        'tasks.description',
        'tasks.priority',
        'tasks.created_at',
        'tasks.updated_at',
        'tasks.finished_at',
        DB::raw("(SELECT name FROM users WHERE id = created_by) as created_by"),
        DB::raw("(SELECT name FROM users WHERE id = updated_by) as updated_by"),
        DB::raw("(SELECT name FROM users WHERE id = finished_by) as finished_by"),
        ])
        ->where('tasks.id', $id)
        ->orderBy('tasks.status')
        ->orderBy('tasks.position')
        ->get()->toArray();

        $comments = TaskComment::getComments($id);

        $result = $task[0];
        $result['comments'] = $comments;

        return $result;
    }

    public static function getTasks($id)
    {
        $taskGroups = TaskGroup::select([
        'task_groups.id',
        'task_groups.project_id',
        'task_groups.position',
        'task_groups.name',
        'task_groups.color',
        ])
        ->where('task_groups.project_id', $id)
        ->orderBy('task_groups.position')
        ->get()->toArray();

        $tasks = Task::select([
        'tasks.id',
        'tasks.group_id',
        'tasks.project_id',
        'tasks.position',
        'tasks.status',
        'tasks.name',
        'tasks.description',
        'tasks.priority',
        DB::raw("(SELECT name FROM users WHERE id = created_by) as created_by"),
        ])
        ->where('tasks.project_id', $id)
        ->orderBy('tasks.status')
        ->orderBy('tasks.position')
        ->get()->toArray();

        $result = [];
        if ($taskGroups) {
            foreach ($taskGroups as $taskGroup) {
                $groupId = $taskGroup['id'];
                $result[$groupId] = $taskGroup;
            }

            $i = 0;
            foreach ($tasks as $task) {
                $groupId = $task['group_id'];
                $result[$groupId]['tasks'][$i] = $task;
                $result[$groupId]['tasks'] = array_values($result[$groupId]['tasks']); //rekey's the tasks array
                $i++;
            }
        }

        return $result;
    }
}
