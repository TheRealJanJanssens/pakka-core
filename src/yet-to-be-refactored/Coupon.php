<?php

namespace TheRealJanJanssens\Pakka\Models;

use App\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Coupon extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'collection_id', 'name', 'status', 'code', 'discount', 'type', 'is_fixed', 'expiry_date',
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
            'code' => "required|unique:coupons,code,".$id,
            'discount' => "required",
            'type' => "required",
            'is_fixed' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'name' => "required",
            'code' => "required|unique:coupons,code,".$id,
            'discount' => "required",
            'type' => "required",
            'is_fixed' => "required",
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
                if (isset($array['expiry_date'])) {
                    $array['expiry_date'] = Carbon::parse($array['expiry_date'])->format('Y-m-d H:i:s');
                }

                break;
            case 2:
                if (isset($array['expiry_date'])) {
                    $array['expiry_date'] = Carbon::parse($array['expiry_date'])->format('d-m-Y H:i');
                }

                break;
        }

        return $array;
    }

    public static function getCoupon($id)
    {
        $result = Coupon::select([
        'coupons.id',
        'coupons.collection_id',
        'coupons.status',
        'coupons.name',
        'coupons.code',
        'coupons.discount',
        'coupons.type',
        'coupons.is_fixed',
        'coupons.expiry_date',
        'coupons.created_at',
        'coupons.updated_at', ])
        ->where('coupons.id', '=', $id)
        ->get()->toArray();

        if (isset($result[0])) {
            $result = $result[0];
            $result = Coupon::convertDates($result, 2);

            //dd($result);
            return $result;
        }
    }

    public static function getCoupons()
    {
        $result = Coupon::select([
        'coupons.id',
        'coupons.collection_id',
        'coupons.status',
        'coupons.name',
        'coupons.code',
        'coupons.discount',
        'coupons.type',
        'coupons.is_fixed',
        'coupons.expiry_date',
        'coupons.created_at',
        'coupons.updated_at', ])
        ->get()->toArray();

        /*
                $result = $result[0];
                $result = Coupon::convertDates($result,2);
        */
        //dd($result);
        return $result;
    }

    public static function redeem($string)
    {
        $date = Carbon::now();

        $result = Coupon::select([
        'coupons.id',
        'coupons.collection_id',
        'coupons.status',
        'coupons.name',
        'coupons.code',
        'coupons.discount',
        'coupons.type',
        'coupons.is_fixed',
        'coupons.expiry_date',
        'coupons.created_at',
        'coupons.updated_at', ])
        //->leftJoin('uses', 'coupons.code', '=', 'orders.coupon_code')
        ->where('coupons.code', '=', $string)
        ->where('coupons.status', '=', 1)
        //->whereNull('coupons.uses')
        ->whereDate('coupons.expiry_date', '>=', $date)
        ->get()->toArray();

        if (isset($result[0])) {
            $result = $result[0];

            //checks if vouchers or giftcards are already used
            if ($result['type'] == 1) {
                $order = Order::latest('created_at')->where('coupon_id', $result['id'])->where(function ($query) {
                    $query->where('financial_status', 0) //open
                        ->orWhere('financial_status', 1); //paid
                })->first();
                if ($order) {
                    return null;
                }
            }

            return $result;
        }
    }
}
