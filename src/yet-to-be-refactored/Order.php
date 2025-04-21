<?php

namespace TheRealJanJanssens\Pakka\Models;

use Carbon\Carbon;
use Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Mail;
use Session;
use TheRealJanJanssens\Pakka\Mails\OrderConfirmationClient;
use TheRealJanJanssens\Pakka\Mails\OrderConfirmationCompany;
use TheRealJanJanssens\Pakka\Mails\OrderShipment as MailOrderShipment;

//use Illuminate\Support\Facades\Mail;

class Order extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'instructions', 'note', 'financial_status', 'fulfillment_status', 'coupon_id', 'taxes_included', 'cancel_reason', 'created_at', 'updated_at', 'closed_at', 'cancelled_at',
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
            'name' => "required",
        ]);
    }

    public static function constructOrderName($prefix, $noCount, $mode = 1)
    {
        $currentYear = date("Y");
        $order = Order::latest('created_at')->whereYear('created_at', $currentYear)->first();

        //Construct prefix
        $prefix = str_replace('{Y}', date("Y"), $prefix);
        $prefix = str_replace('{y}', date("y"), $prefix);
        $prefix = str_replace('{m}', date("m"), $prefix);
        $prefix = str_replace('{d}', date("d"), $prefix);
        $prefix = str_replace('{g}', date("g"), $prefix);
        $prefix = str_replace('{i}', date("i"), $prefix);

        if (! isset($order)) {
            //Start new count
            $order_name = 0;
        } else {
            //Continue current count
            $order_name = $order->name;
        }

        if ($mode == 1) {
            //remove prefix, extracts number, adds 1, formats the number and adds the prefix back
            $order_name = str_ireplace($prefix, "", $order_name); //remove prefix
            $order_name = preg_replace('/[^0-9]/', '', $order_name); //remove all charachters
            $order_name = substr($order_name, -$noCount); //takes only the last numbers (total $noCount) to make sure the right numbers are taken
            $order_name = intval($order_name) + 1; //Converts to int and adds 1
            $order_name = $prefix.sprintf("%'.0".$noCount."d", $order_name); //Formats the number and adds prefix
        } else {
            //returns only the prefix as numbering (for quotations or project numbers)
            $order_name = $prefix;
        }

        return $order_name;
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Order
    |
    | $id = order id
    | $user_id = user id can be used as verification for the order you are trying to get
    |------------------------------------------------------------------------------------
    */

    public static function getOrder($id, $user_id = null)
    {
        $details = OrderDetail::where('order_id', $id)->first();

        if (isset($user_id) && intval($user_id) !== $details->user_id) {
            return null;
        }

        $result = Order::findOrFail($id)->toArray();
        $result['details'] = $details->toArray();
        $result['items'] = OrderItem::where('order_id', $id)->get()->toArray();
        $result['coupon'] = Coupon::getCoupon($result['coupon_id']);

        $shipment = OrderShipment::where('order_id', $id)->first();
        if (! empty($shipment)) {
            $result['shipment'] = $shipment->toArray();
        }

        $result['documents'] = OrderDocument::getDocuments($id)->toArray();
        $result['payment'] = OrderPayment::getPayment($id);

        return $result;
    }

    public static function getOrders()
    {
        $result = Order::select([
        'orders.id',
        'orders.name as order_id',
        'orders.created_at',
        'orders.financial_status',
        'orders.fulfillment_status',
        'order_details.user_id',
        'order_details.firstname',
        'order_details.lastname',
        'order_details.address',
        'order_details.city',
        'order_details.zip',
        'order_details.country',
        'order_details.email',
        DB::raw('(SELECT DISTINCT SUM(ABS(`order_items`.`price`*`order_items`.`quantity`)) FROM `order_items` WHERE `order_items`.`order_id` = `orders`.`id`) AS pos'),
        DB::raw('(SELECT DISTINCT SUM(`order_items`.`price`*`order_items`.`quantity`) FROM `order_items` WHERE `order_items`.`order_id` = `orders`.`id`) AS total'),
        ])
        ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
        ->orderBy('orders.created_at', 'desc')
        ->get();

        return $result;
    }

    public static function getCompletedOrders()
    {
        $result = Order::select([
        'orders.id',
        'orders.name as order_id',
        'orders.created_at',
        'orders.financial_status',
        'orders.fulfillment_status',
        'order_details.user_id',
        'order_details.firstname',
        'order_details.lastname',
        'order_details.address',
        'order_details.city',
        'order_details.zip',
        'order_details.country',
        'order_details.email',
        DB::raw('(SELECT DISTINCT SUM(ABS(`order_items`.`price`*`order_items`.`quantity`)) FROM `order_items` WHERE `order_items`.`order_id` = `orders`.`id`) AS pos'),
        DB::raw('(SELECT DISTINCT SUM(`order_items`.`price`*`order_items`.`quantity`) FROM `order_items` WHERE `order_items`.`order_id` = `orders`.`id`) AS total'),
        ])
        ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
        ->orderBy('orders.created_at', 'desc')
        ->where('orders.financial_status', 1)
        ->get();

        return $result;
    }

    /*
    |------------------------------------------------------------------------------------
    | Prepare Order
    |
    | $array = the request array generated out of the chackout form. So it would mainly store client details
    |------------------------------------------------------------------------------------
    */

    public static function prepare($array)
    {
        /*
        |------------------------------------------------------------------------------------
        | 1. Create and store order
        |------------------------------------------------------------------------------------
        */
        $settings = Session::get('settings');
        $prefix = $settings['order_name_prefix'];
        $noCount = $settings['order_name_number_count'];
        $order_name = Order::constructOrderName($prefix, $noCount);
        $order = Order::create(['name' => $order_name, 'instructions' => $array['instructions']]);

        /*
        |------------------------------------------------------------------------------------
        | 2. Store order details
        |------------------------------------------------------------------------------------
        */
        $array['order_id'] = $order->id;
        OrderDetail::create($array);

        /*
        |------------------------------------------------------------------------------------
        | 3. Store order items and redact quantitys from stock
        |------------------------------------------------------------------------------------
        */
        $items = Cart::getContent();
        $insert['order_id'] = $order->id;
        foreach ($items as $item) {
            $insert['sku'] = $item->id;
            $insert['product_id'] = $item->attributes['id'];
            $insert['name'] = $item->name;
            //$insert['price'] = number_format(getExclAmount($item->price),3);
            $insert['price'] = $item->price; //insert incl. amount
            $insert['quantity'] = $item->quantity;
            $insert['weight'] = $item->weight;
            $insert['vat'] = $settings['shop_general_vat'];

            //Stores as Order Item
            OrderItem::create($insert);

            //Decrement current Stock quantity
            Stock::where('sku', $item->id)->decrement('quantity', $item->quantity);
        }

        if (Cart::getConditionsByType('service')) {
            $services = Cart::getConditionsByType('service');
            foreach ($services as $service) {
                $attributes = $service->getAttributes();

                $insert['sku'] = null;
                $insert['product_id'] = null;
                $insert['name'] = $attributes['name'];
                //$insert['price'] = number_format(getExclAmount($service->parsedRawValue),3);
                $insert['price'] = $service->parsedRawValue;
                $insert['quantity'] = 1;
                $insert['weight'] = 0;
                $insert['vat'] = $settings['shop_general_vat'];

                //Stores as Order Item
                OrderItem::create($insert);
            }
        }

        if (Cart::getCondition('COUPON')) {
            $coupon = Cart::getCondition('COUPON');
            $attributes = $coupon->getAttributes();

            $insert['sku'] = null;
            $insert['product_id'] = null;
            $insert['name'] = $attributes['name'];
            //$insert['price'] = number_format(-getExclAmount($coupon->parsedRawValue),3);
            $insert['price'] = -$coupon->parsedRawValue;
            $insert['quantity'] = 1;
            $insert['weight'] = 0;
            $insert['vat'] = $settings['shop_general_vat'];

            //Stores as Order Item
            OrderItem::create($insert);

            //Stores in Order
            //$order->coupon_code = $attributes['code'];
            $order->coupon_id = $attributes['id'];
            $order->save();
        }

        /*
        |------------------------------------------------------------------------------------
        | 4. Store order shipment
        |------------------------------------------------------------------------------------
        */
        if (Cart::getCondition('SHIPPING')) {
            $shipping = Cart::getCondition('SHIPPING');
            $attributes = $shipping->getAttributes();

            $insert['sku'] = null;
            $insert['product_id'] = null;
            $insert['name'] = $attributes['name'];
            //$insert['price'] = number_format(getExclAmount($shipping->parsedRawValue),3);
            $insert['price'] = $shipping->parsedRawValue;
            $insert['quantity'] = 1;
            $insert['weight'] = 0;
            $insert['vat'] = $settings['shop_general_vat'];

            //Stores as Order Item
            OrderItem::create($insert);

            //Stores Order Shipment
            $array['option_id'] = $attributes['id'];
            $array['option_name'] = $attributes['name'];
            $array['carrier'] = $attributes['carrier'];
            $array['weight'] = Cart::getTotalWeight();
            OrderShipment::create($array);
        }

        return $order;
    }

    public static function confirm($id)
    {
        $order = Order::findOrFail($id);
        $result = $order->toArray();

        //only engage confirmation is the financial status isn't set yet | failsafe for multiple webhook calls
        if ($order->financial_status == 0) {
            /*
            |------------------------------------------------------------------------------------
            | 1. Change order status
            |------------------------------------------------------------------------------------
            */
            $order->financial_status = 1;
            $order->save();

            /*
            |------------------------------------------------------------------------------------
            | 2. Generate Invoice
            |------------------------------------------------------------------------------------
            */
            $document_info = Invoice::getNewInvoiceDetails();
            $client = OrderDetail::where('order_id', $id)->first();
            $result['details'] = $client->toArray();

            $invoice_id = generateString(8);

            $insert['id'] = $invoice_id;
            $insert['invoice_no'] = $document_info['document_numbers'][1];
            $insert['status'] = 3;
            $insert['type'] = 1;
            $insert['client_id'] = $client->user_id;
            $insert['date'] = $document_info['date'];
            $insert['due_date'] = $document_info['due_date'];

            $invoice = Invoice::create($insert);
            $result['invoice'] = $invoice->toArray();

            $insert = null; //resets $insert
            $insert['invoice_id'] = $invoice_id;

            if (isset($client->company_name)) {
                $insert['client_name'] = $client->company_name;
            } else {
                $insert['client_name'] = $client->firstname.' '.$client->lastname;
            }

            $insert['client_address'] = $client->address;
            $insert['client_city'] = $client->city;
            $insert['client_zip'] = $client->zip;
            $insert['client_country'] = $client->country;
            $insert['client_vat'] = $client->vat;
            $insert['client_email'] = $client->email;
            $insert['client_phone'] = $client->phone;

            InvoiceDetail::create($insert);

            $i = 1;
            $insert = null; //resets $insert
            $items = OrderItem::where('order_id', $id)->get();
            $result['items'] = $items->toArray();

            foreach ($items as $item) {
                $insert['invoice_id'] = $invoice_id;

                if (isset($item->sku)) {
                    $insert['name'] = $item->name.' ('.$item->sku.')';
                } else {
                    $insert['name'] = $item->name;
                }

                $insert['price'] = number_format(getExclAmount($item->price), 2); //rounds to 2 decimals (legal guide lines)
                $insert['quantity'] = $item->quantity;
                $insert['vat'] = $item->vat;
                $insert['position'] = $i;

                //Stores as Order Item
                InvoiceItem::create($insert);
                $i++;
            }

            /*
            |------------------------------------------------------------------------------------
            | 3. Link invoice with order in order documents
            |------------------------------------------------------------------------------------
            */
            $insert = null; //resets $insert
            $insert['order_id'] = $id;
            $insert['document_id'] = $invoice_id;
            $result['invoice']['id'] = $invoice_id;
            OrderDocument::create($insert);

            /*
            |------------------------------------------------------------------------------------
            | 4. Manage shipping through carrier API
            |------------------------------------------------------------------------------------
            */
            $shipment = OrderShipment::where('order_id', $id)->first();
            $result['shipment'] = $shipment->toArray();

            /*
            |------------------------------------------------------------------------------------
            | 5. Send comfirmation mail
            |------------------------------------------------------------------------------------
            */
            Order::sendConfirmationMail($result);

            /*
            |------------------------------------------------------------------------------------
            | 6. Clear cart
            |------------------------------------------------------------------------------------
            */
            Cart::clear();
        }
    }

    public static function cancel($id, $status = 2)
    {
        $order = Order::findOrFail($id);
        //only engage cancelation if the cancelled timestamp isn't set | failsafe for multiple webhook calls
        //if($order->cancelled_at <= 0){
        if ($order->fulfillment_status < 2) {
            /*
            |------------------------------------------------------------------------------------
            | 1. Change order status
            |------------------------------------------------------------------------------------
            */

            if ($order->financial_status !== 1) {
                $order->financial_status = 2;
            }

            $order->fulfillment_status = $status;
            //$order->cancel_reason = $status;
            $order->closed_at = Carbon::now();
            $order->cancelled_at = Carbon::now();
            $order->save();

            /*
            |------------------------------------------------------------------------------------
            | 2. Restock items
            |------------------------------------------------------------------------------------
            */
            $items = OrderItem::where('order_id', $id)->get();

            foreach ($items as $item) {
                if (isset($item->sku)) {
                    //Restocks product
                    Stock::where('sku', $item->sku)->increment('quantity', $item->quantity);
                }
            }
        }
    }

    public static function resendConfirmationMail($id)
    {
        $result = Order::findOrFail($id)->toArray();
        $result['details'] = OrderDetail::where('order_id', $id)->first()->toArray();
        $result['items'] = OrderItem::where('order_id', $id)->get()->toArray();
        $result['shipment'] = OrderShipment::where('order_id', $id)->first()->toArray();

        $document = OrderDocument::where('order_id', $id)->first()->toArray();
        $result['invoice'] = Invoice::where('id', $document['document_id'])->first()->toArray();

        Order::sendConfirmationMail($result);
    }

    public static function resendShippingMail($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['fulfillment_status' => 1]);
        $result = $order->toArray();
        $result['details'] = OrderDetail::where('order_id', $id)->first()->toArray();
        $result['items'] = OrderItem::where('order_id', $id)->get()->toArray();
        $result['shipment'] = OrderShipment::where('order_id', $id)->first()->toArray();
        $result['shipment']['option'] = ShipmentOption::where('id', $result['shipment']['option_id'])->first()->toArray();

        $document = OrderDocument::where('order_id', $id)->first()->toArray();
        $result['invoice'] = Invoice::where('id', $document['document_id'])->first()->toArray();

        Order::sendShippingMail($result);
    }

    public static function sendShippingMail($array)
    {
        $settings = Session::get('settings');
        $array = Invoice::calculateInvoice($array, true);

        $company_mail = $settings['company_email'];
        $client_mail = $array['details']['email'];

        //Client mail
        $data['replyTo'] = $company_mail;

        Mail::to($client_mail)->send(new MailOrderShipment($array));
        //Mail::to('debug@janjanssens.be')->send(new OrderConfirmationClient($array));

        //Company mail
        /*
                $data['replyTo'] = $client_mail;

                Mail::to($company_mail)->send(new MailOrderShipment($array));
        */
        //Mail::to('debug@janjanssens.be')->send(new OrderConfirmationCompany($array));
    }

    private static function sendConfirmationMail($array)
    {
        $settings = Session::get('settings');
        $array = Invoice::calculateInvoice($array, true);

        $company_mail = $settings['company_email'];
        $client_mail = $array['details']['email'];

        //Client mail
        $data['replyTo'] = $company_mail;

        Mail::to($client_mail)->send(new OrderConfirmationClient($array));
        //Mail::to('debug@janjanssens.be')->send(new OrderConfirmationClient($array));

        //Company mail
        $data['replyTo'] = $client_mail;

        Mail::to($company_mail)->send(new OrderConfirmationCompany($array));
        //Mail::to('debug@janjanssens.be')->send(new OrderConfirmationCompany($array));
    }
}
