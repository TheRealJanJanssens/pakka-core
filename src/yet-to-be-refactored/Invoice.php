<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Session;

class Invoice extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'invoice_no', 'type', 'status', 'description', 'client_id', 'date', 'due_date', 'sended_at', 'sended_to', 'received_at', 'canceled_at', 'updated_at',
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
            'id' => "required",
            'client_id' => "required",
        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'id' => "required",
            'client_id' => "required",
        ]);
    }

    public static function constructDocumentId($document, $prefix, $noCount, $mode = 1)
    {
        //Construct prefix
        $prefix = str_replace('{Y}', date("Y"), $prefix);
        $prefix = str_replace('{y}', date("y"), $prefix);
        $prefix = str_replace('{m}', date("m"), $prefix);
        $prefix = str_replace('{d}', date("d"), $prefix);
        $prefix = str_replace('{g}', date("g"), $prefix);
        $prefix = str_replace('{i}', date("i"), $prefix);

        if (! isset($document)) {
            //Start new count
            $document_no = 0;
        } else {
            //Continue current count
            $document_no = $document->invoice_no;
        }

        if ($mode == 1) {
            //remove prefix, extracts number, adds 1, formats the number and adds the prefix back
            $document_no = str_ireplace($prefix, "", $document_no); //remove prefix
            $document_no = preg_replace('/[^0-9]/', '', $document_no); //remove all charachters
            $document_no = substr($document_no, -$noCount); //takes only the last numbers (total $noCount) to make sure the right numbers are taken
            $document_no = intval($document_no) + 1; //Converts to int and adds 1
            $document_no = $prefix.sprintf("%'.0".$noCount."d", $document_no); //Formats the number and adds prefix
        } else {
            //returns only the prefix as numbering (for quotations or project numbers)
            $document_no = $prefix;
        }

        return $document_no;
    }

    public static function getNewInvoiceDetails()
    {
        $settings = Session::get('settings');
        $prefix = $settings['invoice_prefix'];
        $noCount = $settings['invoice_number_count'];
        $duePeriod = $settings['invoice_due_period'];
        $multipleNo = $settings['invoice_multiple_numbers'];
        $quotationFormat = $settings['invoice_quotation_format'];

        //Gets latest invoice of the current year
        $currentYear = date("Y");

        //loops through document types
        foreach (config('pakka.document_type') as $key => $value) {
            switch ($key) {
                case 1:
                    $document = Invoice::latest('created_at')->where('type', $key)->whereYear('created_at', $currentYear)->first();
                    $documentNo[$key] = Invoice::constructDocumentId($document, $prefix, $noCount);

                    break;
                case 2:
                    if ($multipleNo == "1") {
                        //seperate credit invoice number
                        $document = Invoice::latest('created_at')->where('type', $key)->whereYear('created_at', $currentYear)->first();
                        $documentNo[$key] = Invoice::constructDocumentId($document, $prefix, $noCount);
                    } else {
                        //same numbering as invoice
                        $documentNo[$key] = $documentNo[1];
                    }

                    break;
                case 3:
                    $documentNo[$key] = "";

                    break;
                case 4:
                    $documentNo[$key] = Invoice::constructDocumentId(null, $quotationFormat, null, 2);

                    break;
                case 5:
                    $documentNo[$key] = Invoice::constructDocumentId(null, $quotationFormat, null, 2);

                    break;
            }
        }

        $result['document_numbers'] = $documentNo;

        //Constructs dates
        $result['date'] = date('Y-m-d');
        $result['due_date'] = date('Y-m-d', strtotime($result['date'].' '.$duePeriod));

        return $result;
    }

    public static function constructItem($array)
    {
        $i = 0;
        $names = explode("(~)", $array['invoice_items_name']);
        $prices = explode("(~)", $array['invoice_items_price']);
        $vats = explode("(~)", $array['invoice_items_vat']);
        $quantities = explode("(~)", $array['invoice_items_quantity']);

        foreach ($names as $name) {
            $array['items'][$i]['name'] = $name;
            $array['items'][$i]['price'] = $prices[$i];
            $array['items'][$i]['vat'] = $vats[$i];
            $array['items'][$i]['quantity'] = $quantities[$i];
            $i++;
        }

        unset($array['invoice_items_name']);
        unset($array['invoice_items_price']);
        unset($array['invoice_items_vat']);
        unset($array['invoice_items_quantity']);

        return $array;
    }

    public static function constructItems($array)
    {
        $iI = 0;
        foreach ($array as $invoice) {
            $array[$iI] = Invoice::constructItem($invoice);
            $iI++;
        }

        return $array;
    }

    public static function calculateInvoice($array, $vatIncl = false)
    {
        $items = $array['items'];

        $vatTotal = 0;
        $subTotal = 0;
        $total = 0;
        $i = 0;
        foreach ($items as $item) {
            if ($vatIncl == true) {
                $p = getExclAmount($item['price'], $item['vat']);
            } else {
                $p = floatval($item['price']);
            }

            $v = floatval($item['vat']) / 100;
            $q = floatval($item['quantity']);

            $itemSubTotal = $p * $q;
            $itemVatTotal = $v * $itemSubTotal;
            $itemTotal = $itemVatTotal + $itemSubTotal;

            //voorbeeld afwijking
            //product 1: €49
            //aantal: 2
            //subtotaal: €98
            //als de btw berekend wordt op eind bedrag dan 80,99 | 40,495... (btw 17,01)
            //als btw berekend wordt per item dan 81 | 40,5*2 (btw 17,01)

            //dd(40.495867768595041 * 1.21);
            //dd(98 - (98 / 121 * 21)); //result from getExclAmount without formatting
            //dd(number_format(49 - (49 / 121 * 21), 2, ',', ' ')); //result from getExclAmount with formatting
            //dd(number_format(48.998, 2, ',', ' '));

            $array["items"][$i]['subtotal'] = $itemSubTotal;
            $array["items"][$i]['vattotal'] = $itemVatTotal;
            $array["items"][$i]['total'] = $itemTotal;

            $subTotal = $subTotal + $itemSubTotal;
            $vatTotal = $vatTotal + $itemVatTotal;
            $total = $total + $itemTotal;
            $i++;
        }



        $array['subtotal'] = $subTotal;
        $array['vattotal'] = $vatTotal;
        $array['total'] = $total;

        return $array;
    }

    public static function calculateInvoices($array)
    {
        $iI = 0;
        foreach ($array as $invoice) {
            $array[$iI] = Invoice::calculateInvoice($invoice);
            $iI++;
        }

        return $array;
    }

    public static function getInvoices($type = null)
    {
        //Sets the max char length of group_concat (1024 to 1000000 chars)
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $result = Invoice::select([
        'invoices.id',
        'invoices.invoice_no',
        'invoices.status',
        'invoices.type',
        'invoices.description',
        'invoices.date',
        'invoices.due_date',
        'invoices.client_id',
        'invoice_details.client_name',
        'invoice_details.client_address',
        'invoice_details.client_city',
        'invoice_details.client_zip',
        'invoice_details.client_country',
        'invoice_details.client_vat',
        'invoice_details.client_email',
        'invoice_details.client_phone',
        'invoice_details.ship_name',
        'invoice_details.ship_address',
        'invoice_details.ship_city',
        'invoice_details.ship_zip',
        'invoice_details.ship_country',
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.name,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_name"),
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.price,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_price"),
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.quantity,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_quantity"),
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.vat,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_vat"),
        ])
        ->leftJoin('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
        ->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id');

        if ($type) {
            $result = $result->where('invoices.type', $type);
        }

        $result = $result->orderBy('invoices.created_at', "desc")
        ->groupBy('invoices.id')
        ->get()->toArray();

        $result = Invoice::constructItems($result);
        $result = Invoice::calculateInvoices($result);

        return $result; //outputs array
    }

    public static function getInvoice($id)
    {
        //Sets the max char length of group_concat (1024 to 1000000 chars)
        DB::statement("SET SESSION group_concat_max_len = 1000000;");

        $result = Invoice::select([
        'invoices.id',
        'invoices.invoice_no',
        'invoices.status',
        'invoices.type',
        'invoices.description',
        'invoices.date',
        'invoices.due_date',
        'invoices.client_id',
        'invoice_details.client_name',
        'invoice_details.client_address',
        'invoice_details.client_city',
        'invoice_details.client_zip',
        'invoice_details.client_country',
        'invoice_details.client_vat',
        'invoice_details.client_email',
        'invoice_details.client_phone',
        'invoice_details.ship_name',
        'invoice_details.ship_address',
        'invoice_details.ship_city',
        'invoice_details.ship_zip',
        'invoice_details.ship_country',
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.name,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_name"),
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.price,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_price"),
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.quantity,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_quantity"),
        DB::raw("GROUP_CONCAT( IFNULL(invoice_items.vat,'') ORDER BY invoice_items.position SEPARATOR '(~)') as invoice_items_vat"),
        ])
        ->where('invoices.id', $id)
        ->leftJoin('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
        ->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
        ->orderBy('invoices.created_at', "desc")
        ->groupBy('invoices.id')
        ->get()->toArray();

        $result = Invoice::constructItem($result[0]);
        $result = Invoice::calculateInvoice($result);

        return $result; //outputs array
    }
}
