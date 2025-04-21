<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class ServiceAssignment extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'service_id',
        'provider_id',
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
            'service_id' => "required",
            'provider_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'service_id' => "required",
            'provider_id' => "required",
        ]);
    }

    public static function storeAssignments($id, $array)
    {
        //deleting and inserting again is not the most efficient way to update these rows
        //updating is much better but the way the form is build is difficult to detect deleted rows without ajax request

        //delete all data
        ServiceAssignment::where('service_id', $id)->delete();

        foreach ($array as $item) {
            //if all days are 0 skip loop iteration
            if ($item == 0) {
                continue;
            }

            /*
                        //efficienter for db queries
                        if($item['id'] == 0){
                            //new schedule item
                            $schedule = new ProviderSchedule;
                        }else{
                            //existing schedule item
                            $schedule = ProviderSchedule::find($item['id']);
                        }
            */
            $schedule = new ServiceAssignment();
            $schedule->service_id = $id;
            $schedule->provider_id = $item;
            $schedule->save();
        }
    }

    public static function getProviders($id)
    {
        $result = ServiceAssignment::select([
        'service_assignments.provider_id', ])
        ->where('service_assignments.service_id', $id)
        ->get();

        foreach ($result as $key => $value) {
            $result[$key] = $value['provider_id'];
        }

        return $result;
    }
}
