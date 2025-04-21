<?php

namespace TheRealJanJanssens\Pakka\Models;

use App\ProviderSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Provider extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'name',
      'user_id',
      'capacity',
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
    ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
      'name' => "required",
    ]);
    }

    public static function getProvider($id)
    {
        $provider = Provider::findOrFail($id);
        $provider['schedule'] = ProviderSchedule::getSchedule($provider->id);

        return $provider;
    }

    public static function getProviders()
    {
        $result = Provider::orderBy('id')->get();

        return $result;
    }

    public static function constructSelect()
    {
        $providers = Provider::orderBy('id')->get();
        $result = [];
        foreach ($providers as $provider) {
            $result[$provider->id] = $provider->name;
        }

        if (! empty($result)) {
            array_unshift($result, trans("app.select_option"));
        }

        return $result;
    }
}
