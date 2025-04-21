<?php

namespace TheRealJanJanssens\Pakka\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class OrderDocument extends Model
{
    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id', 'document_id',
    ];

    /*
    |------------------------------------------------------------------------------------
    | Validations
    |------------------------------------------------------------------------------------
    */
    public static function rules($update = false, $id = null)
    {
        $commun = [
            'order_id' => "required",
            'document_id' => "required",

        ];

        if ($update) {
            return $commun;
        }

        return array_merge($commun, [
            'order_id' => "required",
            'document_id' => "required",
        ]);
    }

    /*
    |------------------------------------------------------------------------------------
    | Get Documents
    |
    | $id = order id
    |------------------------------------------------------------------------------------
    */

    public static function getDocuments($id)
    {
        $result = OrderDocument::select([
        'order_documents.id',
        'order_documents.order_id',
        'order_documents.document_id',
        'invoices.invoice_no',
        'invoices.client_id',
        'invoices.type',
        'invoices.status', ])
        ->leftJoin('invoices', 'order_documents.document_id', '=', 'invoices.id')
        ->where('order_documents.order_id', $id)
        ->get();

        return $result;
    }
}
