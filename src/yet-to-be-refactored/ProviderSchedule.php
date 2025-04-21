<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class ProviderSchedule extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_id',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
        'sat',
        'sun',
        'start_at',
        'end_at',
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
            'start_at' => "required",
            'end_at' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'start_at' => "required",
            'end_at' => "required",
        ]);
    }

    public static function storeSchedule($id, $array)
    {
        //deleting and inserting again is not the most efficient way to update these rows
        //updating is much better but the way the form is build is difficult to detect deleted rows without ajax request

        //delete all data
        ProviderSchedule::where('provider_id', $id)->delete();

        foreach ($array as $item) {
            //if all days are 0 skip loop iteration
            if ($item['mon'] == 0
                && $item['tue'] == 0
                && $item['wed'] == 0
                && $item['thu'] == 0
                && $item['fri'] == 0
                && $item['sat'] == 0
                && $item['sun'] == 0) {
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
            $schedule = new ProviderSchedule();
            $schedule->provider_id = $id;
            $schedule->mon = $item['mon'];
            $schedule->tue = $item['tue'];
            $schedule->wed = $item['wed'];
            $schedule->thu = $item['thu'];
            $schedule->fri = $item['fri'];
            $schedule->sat = $item['sat'];
            $schedule->sun = $item['sun'];
            $schedule->start_at = $item['start_at'];
            $schedule->end_at = $item['end_at'];
            $schedule->save();
        }
    }

    public static function getSchedule($id)
    {
        $result = ProviderSchedule::select([
        'provider_schedules.id',
        'provider_schedules.mon',
        'provider_schedules.tue',
        'provider_schedules.wed',
        'provider_schedules.thu',
        'provider_schedules.fri',
        'provider_schedules.sat',
        'provider_schedules.sun',
        'provider_schedules.start_at',
        'provider_schedules.end_at', ])
        ->where('provider_schedules.provider_id', $id)
        ->get();

        return $result;
    }

    public static function getSchedules()
    {
        return "";
    }

    public static function getTimeSlots()
    {
        return "";
    }
}
