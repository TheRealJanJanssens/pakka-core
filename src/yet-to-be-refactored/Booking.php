<?php

namespace TheRealJanJanssens\Pakka\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Booking extends Model
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
        'client_id',
        'start_at',
        'end_at',
        'title',
        'price',
        'description',
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
            'title' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'title' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Convert dates to right format
    |
    | $mode = 1: insert to db, 2: show in datepicker
    |------------------------------------------------------------------------------------
    */

    public static function convertDates($array, $mode = 1)
    {
        switch ($mode) {
            case 1:
                if (isset($array['start_at'])) {
                    $array['start_at'] = Carbon::parse($array['start_at'])->format('Y-m-d H:i:s');
                }

                if (isset($array['end_at'])) {
                    $array['end_at'] = Carbon::parse($array['end_at'])->format('Y-m-d H:i:s');
                }

                break;
            case 2:
                if (isset($array['start_at'])) {
                    $array['start_at'] = Carbon::parse($array['start_at'])->format('d-m-Y H:i');
                }

                if (isset($array['end_at'])) {
                    $array['end_at'] = Carbon::parse($array['end_at'])->format('d-m-Y H:i');
                }

                break;
        }



        return $array;
    }

    public static function getBooking($id)
    {
        $result = Booking::select([
            'bookings.id',
            'bookings.service_id',
            'bookings.provider_id',
            'bookings.client_id',
            'bookings.start_at',
            'bookings.end_at',
            'bookings.title',
            'bookings.price',
            'bookings.description', ])
            ->where('bookings.id', '=', $id)
            ->first();

        $result = $result;
        $result = Booking::convertDates($result, 2);

        return $result;
    }

    public static function getBookings($date = null)
    {
        if ($date == null) {
            $date = Carbon::today()->subDays(30);
        }

        $bookings = Booking::select([
        'bookings.id',
        'bookings.service_id',
        'bookings.provider_id',
        'bookings.client_id',
        'bookings.start_at',
        'bookings.end_at',
        'bookings.title',
        'bookings.price',
        'bookings.description', ])
        ->whereDate('bookings.start_at', '>=', $date)
        ->get();

        if (count($bookings) > 0) {
            $i = 0;
            foreach ($bookings as $booking) {
                $result[$i]['bookingId'] = $booking['id'];
                $result[$i]['title'] = $booking['title'];
                $result[$i]['start'] = $booking['start_at'];
                $result[$i]['end'] = $booking['end_at'];
                $result[$i]['desc'] = $booking['description'];

                if (! isset($booking['end_at'])) {
                    $result[$i]['allDay'] = true;
                } else {
                    $result[$i]['allDay'] = false;
                }

                $i++;
            }
            $result = $result;
        } else {
            $result = $bookings;
        }

        return $result;
    }

    public static function getUpcomingBookings($limit = 5)
    {
        $date = Carbon::now();
        $result = [];

        $bookings = Booking::select([
        'bookings.id',
        'bookings.service_id',
        'bookings.provider_id',
        'bookings.client_id',
        'bookings.start_at',
        'bookings.end_at',
        'bookings.title',
        'bookings.price',
        'bookings.description', ])
        ->whereDate('bookings.start_at', '>=', $date)
        ->orderBy('bookings.start_at', 'asc')
        ->limit($limit)
        ->get();

        if ($bookings) {
            $i = 0;
            foreach ($bookings as $booking) {
                $result[$i] = Booking::convertDates($booking, 2);
                $i++;
            }
        }

        return $result;
    }
}
