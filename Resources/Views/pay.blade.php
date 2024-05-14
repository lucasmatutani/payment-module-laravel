@extends(env("TEMPLATE_PATH")."layout")

@section('main-content')

<div id="payContent">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="element-box text-center">

                    <div>
                        <p style="font-size: 30px;font-weight: 700;margin-bottom: 0;">Tether Payment</p>
                        <p style="font-size: 12px;">{{ str_replace("Bitcoin","USDT(BEP20)",Lang::get('shared.pay_security_with_btc')) }}:</p>
                        <hr style="margin-bottom: 30px;">
                    </div>
                    <?php
                    $amount = $invoice->getAmountWithFee(2);
                    ?>


                    <div id="partialPayment" class="explanation mt-4 bg-warning text-center" @if(!$invoice->isIncomplete() && $invoice->status != 'Complete')  style="display:none; text-align: center !important;" @endif>
                        <img src="{{asset('assets/images/core/icons/payment-incomplete.png')}}" style="margin: 0 auto !IMPORTANT; text-align: center; max-width: 80px; margin-bottom: 20px !important;">
                        <div class="row">
                            <div class="col-md-12" style="text-aling: center !important;">
                                <h6 >{{ Lang::get('shared.PaymentIncomplete') }}</h6>
                                <p><?php echo str_replace("BTC","USDT",Lang::get('shared.SendMorePaymentIncomplete', ['totalPayment' => $invoice->total_btc_paid, 'totalNeed' => $invoice->getAmountWithFee(2)*1.05])); ?></p>
                            </div>
                        </div>
                    </div>



                    <div id="normalPayment" class="explanation mt-4" style="text-align: center !important" @if($invoice->isIncomplete() || $invoice->status == 'Complete')  style="display:none; text-align: center !important;" @endif >

                        <img src="{{asset('assets/images/core/icons/secure-payment.png')}}" style="margin: 0 auto !IMPORTANT; text-align: center; max-width: 80px; margin-bottom: 20px !important;">

                        <div id="payment_not_sent" class="row" @if(!is_null($invoice->blockchain_confirmations))  style="display:none" @endif>
                            <div class="col-md-12" style="text-aling: center !important;">
                                <h6 class="statusTranslated" style="font-weight: 700;">{{$invoice->getInvoiceStatusTranslated()}}</h6>
                                <p style="border: 1px dashed red; padding: 15px; font-size: 12px; border-radius: 10px; margin-top: 20px; background: #ff000008;"><?=str_replace("BTC","USDT", Lang::get('shared.bp20_alert')) ?></p>
                            </div>
                        </div>

                        <div id="waiting_confirmation" class="row" @if(is_null($invoice->blockchain_confirmations))  style="display:none; text-align: center !important;" @endif>
                            <img src="{{asset('assets/images/core/icons/waiting-confirmations.png')}}" style="margin: 0 auto !IMPORTANT; text-align: center; max-width: 80px; margin-bottom: 20px !important;">
                            <div class="col-md-12" style="text-align: center !important;">
                                <h6 class="statusTranslated" style="font-weight: 700;">@lang("shared.waiting_confirmations")</h6>
                                <p style="border: 1px dashed red; padding: 15px; font-size: 12px; border-radius: 10px; margin-top: 20px; background: #ff000008;">{{ str_replace("Bitcoin","USDT",Lang::get('shared.payment_confirmations_desc')) }}</p>
                            </div>
                        </div>
                    </div>

                    <img id="imgQrcode" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=usdt:{{$invoice->transaction}}?amount={{$invoice->getAmountWithFee(2)*1.05}}" width="200" style="border-radius:5px">

                    <?php
                    ?>

                    <h3><span id="amountLeft"></span> <span style="font-size: 14px;">USDT BEP20</span></h3>
                    <p>{{$invoice->transaction}}</p>
                    <p id="blockchain_confirmations" @if(is_null($invoice->blockchain_confirmations))  style="display:none" @endif >@lang("shared.Confirmations"): <span id="qntdConfirmations"><?= $invoice->blockchain_confirmations ?></span></p>

                    <p style="margin-top: 10px; color: #444;"><span id="time"></span></p>
                </div>
            </div>
        </div>


    </div>


</div>

<script type="text/javascript">
    var payment_url = "<?= url('bo/module/payment-method-bsc/pay/' . $invoice->cps_id) ?>";
    setInterval(function () {
        $.getJSON(payment_url + "?ajax", function (data) {

            if (data.status == 'Complete' || data.status == 'Paid') {
                location.href = "<?= url('bo/invoice') ?>";

            } 
            if (data.is_partial) {
                $("#normalPayment").hide();
                $("#partialPayment").show();
            } else {
                $("#normalPayment").show();
                $("#partialPayment").hide();

                if (data.blockchain_confirmations === null) {
                    $("#payment_not_sent").show();
                    $("#waiting_confirmation").hide();
                } else {
                    $("#payment_not_sent").hide();
                    $("#waiting_confirmation").show();
                    $("#blockchain_confirmations").show();
                    $("#qntdConfirmations").html(data.blockchain_confirmations);

                }
            }
            if (data.blockchain_confirmations !== null) {
                $("#blockchain_confirmations").show();
                $("#qntdConfirmations").show();
                $("#qntdConfirmations").html(data.blockchain_confirmations);

            }

            $("#totalPayment").html(data.total_btc_paid);
            $("#totalNeed").html(data.amount*1.05);
            $("#amountLeft").html(data.amount*1.05);
            $(".statusTranslated").html(data.status_trans);

            var url = data.url_qrcode;

            if ($("#imgQrcode").attr("src") != url) {
                $("#imgQrcode").attr("src", url);
            }

        })
    }, 3000);
</script>

@endsection