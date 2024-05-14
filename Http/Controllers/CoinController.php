<?php

namespace Modules\PaymentMethodBsc\Http\Controllers;

use App\Engine\TatumEngine;
use App\Engine\TatumEngineV4;
use App\Http\Controllers\Controller;
use App\Library\Modules\Interfaces\interfacePaymentMethod;
use App\Library\Modules\Interfaces\Modularizable;
use App\Model\Invoice;
use App\Model\InvoicePartialPayment;
use App\Model\MetaData;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class CoinController extends Controller implements Modularizable, interfacePaymentMethod
{

    public function install()
    {

    }

    public function unistall()
    {

    }

    public function config()
    {

    }


    private function getUrlPayment($invoiceData)
    {
        if (empty($invoiceData->transaction)) {
            $chain = 'bsc';
            $oldEngine = new TatumEngine($chain);
            $index = $invoiceData->id;
            $response = $oldEngine->genBscAddress($index);
            $wallet = $response['address'];
            try {
                $engineV4 = new TatumEngine($chain, 'v4');
                $engineV4->watchTokenEvent($wallet);
            } catch (\Exception $e) {
            }
        }
        $invoiceData->transaction = $wallet;


        $btcValue = getUsdToEurQuote($invoiceData->getTotal());
        $hash = \UtilsLibrary::secureHash();
        $url = url('module/payment-method-bsc/pay/' . $hash);

        $invoiceData->rmExtraField('payment-method-bsc_link');
        $invoiceData->rmExtraField('payment-method-bsc_txn_id');

        $invoiceData->addExtraField('payment-method-bsc_link', $url);
        $invoiceData->addExtraField('payment-method-bsc_txn_id', $hash);

        $invoiceData->cps_id = $hash;

        $invoiceData->total_btc = $btcValue;
        $invoiceData->saveOrFail();
        return $url;
    }

    public function generatePaymentLink()
    {
        try {
            $post = \Request::all();
            $post['atualizar_link'] = 1;
            $invoiceData = Invoice::findOrFail($post['invoice_id']);
            if (!$invoiceData->isPending()) {
                $return['error'] = true;
                $return['error_message'] = ['slug' => \Lang::get("invoice.fatura_ja_processada")];
            } else
                if (@$post['atualizar_link'] == 1 && $invoiceData->getExtraFieldValue($post['slug'] . '_link')) {
                    $return['success'] = true;
                    $return['sucess_message'] = \Lang::get('invoice.redirecting');
                    $return['redirect'] = true;
                    $return['redirect_url'] = $invoiceData->getExtraFieldValue($post['slug'] . '_link');
                } else {
                    $url = $this->getUrlPayment($invoiceData);
                    $return['sucess_message'] = \Lang::get('invoice.redirecting');
                    $return['redirect'] = true;
                    $return['redirect_url'] = $url;
                }
        } catch (\Exception $ex) {
            \App\Model\Log::createNew($ex);
            $return['error'] = true;
            $return['error_message'] = ['slug' => $ex->getMessage()];
        }

        if (@$post['send_method'] == 'E-mail') {
            $invoiceData->sendLinkByEmail();
        } else if (@$post['send_method'] == 'SMS') {
            $invoiceData->sendLinkBySms();
        }

        echo json_encode($return);
    }

    public function pay($id)
    {

        $indexJson = $id . '_payment-method-bsc_link';
        if (!isset($_GET['ajax'])) {
            $invoiceOldCps = Invoice::where("extra_field", "like", "%$indexJson%")->first();
            if (isset($invoiceOldCps['id'])) {
                $url = $invoiceOldCps->getExtraFieldValue('payment-method-bsc_link');
                return redirect($url . "?renewAlert=1");
            }
        }

        $invoiceData = Invoice::where("cps_id", $id)->firstOrFail();

        $time_left = (strtotime(date('Y-m-d H:i:s')) - strtotime($invoiceData->updated_at));
        // $time_left = (strtotime($invoiceData->updated_at) + (60 * 60 * 48)) - time();

        if ($time_left >= env('INVOICE_UPDATE_TIME', 1800)) {
            $invoiceData->addExtraField($id . '_payment-method-bsc_link', url('module/payment-method-bsc/pay/' . $id));

            $invoiceData->addExtraField($id . '_payment-method-bsc_txn_id', $id);
            $invoiceData->addExtraField($id . '_payment-method-transacion', $invoiceData->transaction);

            $url = $this->getUrlPayment($invoiceData);

            if (isset($_GET['ajax'])) {
                $amount = $invoiceData->getAmountWithFee(2);

                $data = [
                    'time_left' => (strtotime(date('Y-m-d H:i:s')) - strtotime($invoiceData->updated_at)),
                    'quote_update' => true,
                    'payment_url' => url('bo/module/payment-method-bsc/pay/' . $invoiceData->cps_id),
                    'amount' => $amount,
                    'transaction' => $invoiceData->transaction,
                    'status' => $invoiceData->invoice_status,
                    'total_btc' => $invoiceData->total_btc,
                    'total_btc_paid' => $invoiceData->total_btc_paid,
                    'is_partial' => $invoiceData->isIncomplete(),
                    'url_qrcode' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=usdt:" . $invoiceData->transaction . "?amount=" . $amount,
                    'status_trans' => $invoiceData->getInvoiceStatusTranslated()
                ];

                return json_encode($data);
            } else {
                return redirect($url)->with("error", Lang::get('shared.bitcoin_quota_updated'));
            }
        }

        if (isset($_GET['ajax'])) {
            $amount = $invoiceData->getAmountWithFee(2);

            $data = [
                'quote_update' => false,
                'amount' => $amount,
                'transaction' => $invoiceData->transaction,
                'status' => $invoiceData->invoice_status,
                'total_btc' => bcmul(1, $invoiceData->total_btc, 2),
                'total_btc_paid' => bcmul(1, $invoiceData->total_btc_paid, 2),
                'is_partial' => $invoiceData->isIncomplete(),
                'url_qrcode' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=usdt:" . $invoiceData->transaction . "?amount=" . $amount,
                'status_trans' => $invoiceData->getInvoiceStatusTranslated(),
                'blockchain_confirmations' => $invoiceData->blockchain_confirmations
            ];

            return json_encode($data);
        } else {
            $view = 'payment-method-bsc::pay';
        }

        return view($view, ['time_left' => $time_left, 'invoice' => $invoiceData]);
    }


    public function processNotifications()
    {

        $token = env("TATUM_ERC20_TOKEN");
        $requestData = \Request::all();
/**
 * undocumented constant
 **/
        if (!isset($requestData['subscriptionType']) || $requestData['subscriptionType'] != 'INCOMING_FUNGIBLE_TX') {
            abort(403);
        }
        if (!isset($requestData['chain']) || $requestData['chain'] != 'bsc-mainnet') {
            abort(403);
        }
        if (!isset($requestData['contractAddress']) || $requestData['contractAddress'] != $token) {
            abort(403);
        }
        if (!isset($requestData['amount'])) {
            abort(403);
        }

        $post = [
            'balance_change' => $requestData['amount'],
            'address' => $requestData['address'],
            'txid' => $requestData['txId'],
            'confirmations' => 1
        ];
        $postData = $post;
        if ($postData['balance_change'] > 0) {
            $address = $postData['address'];
            $invoiceData = Invoice::where("transaction", $address)->first();
            $invoice = $invoiceData;
            
            if ($postData['confirmations'] >= 1 && $postData['balance_change'] >= $invoice->btcPaymentLeft() && $invoice->isPending()) {
                $invoice->confirmPayment('BSC');
                $invoiceData->saveOrFail();
            } else if ($postData['balance_change'] >= 0 && $invoice->isPending()) {


                $count = InvoicePartialPayment::where("invoice_id", $invoice->id)->where("txid", $postData['txid'])->count();
                if ($count == 0 && $postData['balance_change'] < $invoice->btcPaymentLeft()) {
                    try {
                        $model = new InvoicePartialPayment();
                        $model->txid = $postData['txid'];
                        $model->amount = $postData['balance_change'];
                        $model->invoice_id = $invoiceData->getId();
                        $model->saveOrFail();
                        $invoiceData->is_partial = true;
                        $invoiceData->total_btc_paid = bcadd($invoiceData->total_btc_paid, $postData['balance_change'], 8);
                        $invoiceData->saveOrFail();
                        $invoiceData->sendIncompletePaymentEmail();
                    } catch (\Exception $e) {
                        registerLog($e);
                    }
                }
                $invoiceData->blockchain_confirmations = $postData['confirmations'];
                $invoiceData->save();
            }
        }
    }
}
