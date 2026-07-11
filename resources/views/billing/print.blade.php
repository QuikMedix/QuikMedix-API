
<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Site Title -->
  <title>General Purpose Invoice-{{$invoice->id}}</title>
  <link rel="stylesheet" href="/css/print-style.css">
</head>

<body>
  <div class="tm_container">
    <div class="tm_invoice_wrap">
      <div class="tm_invoice tm_style1 tm_type1" id="tm_download_section">
        <div class="tm_invoice_in">
          <div class="tm_invoice_head tm_top_head tm_mb15 tm_align_center">
            <div class="tm_invoice_left">
              <div class="tm_logo"><img src="https://a2brx.com/assets/media/logo-2.svg" alt="Logo"></div>
            </div>
            <div class="tm_invoice_right tm_text_right tm_mobile_hide">
              <div class="tm_f50 tm_text_uppercase tm_white_color">Invoice</div>
            </div>
            <div class="tm_shape_bg tm_accent_bg tm_mobile_hide"></div>
          </div>
          <div class="tm_invoice_info tm_mb25">
            <div class="tm_card_note tm_mobile_hide"><b class="tm_primary_color">Payment Method: </b>Credit card</div>
            <div class="tm_invoice_info_list tm_white_color">
              <p class="tm_invoice_number tm_m0">Invoice No: <b>#{{$invoice->id}}</b></p>
              <p class="tm_invoice_date tm_m0">Date: <b>{{date('m.d.Y', strtotime($invoice->created))}}</b></p>
            </div>
            <div class="tm_invoice_seperator tm_accent_bg"></div>
          </div>
          <div class="tm_invoice_head tm_mb10">
            <div class="tm_invoice_left">
              <p class="tm_mb2"><b class="tm_primary_color">Invoice To:</b></p>
              <p>
                {{$pharmacy->name}} <br>
                {{$pharmacy->address}}<br>
                {{$pharmacy->email}}<br>
                {{$pharmacy->phone}}
              </p>
            </div>
            <div class="tm_invoice_right tm_text_right">
              <p class="tm_mb2"><b class="tm_primary_color">Pay To:</b></p>
              <p>
                A2B RX Inc <br>
                204 25th St Ste 203<br>
				        Brooklyn, NY 11232 US<br>
                billing@a2brx.com
              </p>
            </div>
          </div>
          <div class="tm_table tm_style1">
            <div class="">
              <div class="tm_table_responsive">
                <table>
                  <thead>
                    <tr class="tm_accent_bg">
                      <th class="tm_width_3 tm_semi_bold tm_white_color">Item</th>
                      <th class="tm_width_4 tm_semi_bold tm_white_color">Description</th>
                      <th class="tm_width_1 tm_semi_bold tm_white_color">Qty</th>
                      <th class="tm_width_2 tm_semi_bold tm_white_color tm_text_right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="tm_width_3">A2BRx Driver</td>
                      <td class="tm_width_4"></td>
                      <td class="tm_width_1"></td>
                      <td class="tm_width_2 tm_text_right"></td>
                    </tr>
                    <tr>
                      <td class="tm_width_3">&emsp; 1. Delivery next day</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">{{$next_day["count"]}}</td>
                      <td class="tm_width_2 tm_text_right">${{number_format($next_day["amount"],2)}}</td>
                    </tr>
                    <tr>
                      <td class="tm_width_3">&emsp; 2. Delivery same day</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">{{$same_day["count"]}}</td>
                      <td class="tm_width_2 tm_text_right">${{number_format($same_day["amount"],2)}}</td>
                    </tr>
                    <tr>
                      <td class="tm_width_3">&emsp; 3. Delivery ASAP</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">{{$asap["count"]}}</td>
                      <td class="tm_width_2 tm_text_right">${{number_format($asap["amount"],2)}}</td>
                    </tr>
                    <tr>
                      <td class="tm_width_3">&emsp; 4. Delivery After hours</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">{{$after_hours["count"]}}</td>
                      <td class="tm_width_2 tm_text_right">${{number_format($after_hours["amount"],2)}}</td>
                    </tr>
                    @if(($pharmacy->copay_bill=='1' && empty($invoice->payed_amount)) || ($pharmacy->copay_bill=='1' && !empty($invoice->payed_amount) && (($invoice->amount+$invoice->corrections)-$invoice->copay)==$invoice->payed_amount))
                    <tr>
                      <td class="tm_width_3">&emsp; 5. Co-pay</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">1</td>
                      <td class="tm_width_2 tm_text_right">-${{number_format($invoice->copay,2)}}</td>
                    </tr>
                    @endif
                    <tr>
                      <td class="tm_width_3">&emsp; @if(($pharmacy->copay_bill=='1' && empty($invoice->payed_amount)) || ($pharmacy->copay_bill=='1' && !empty($invoice->payed_amount) && (($invoice->amount+$invoice->corrections)-$invoice->copay)==$invoice->payed_amount)){{"6"}}@else{{"5"}}@endif. Corrections</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">1</td>
                      <td class="tm_width_2 tm_text_right">{{($invoice->corrections<0)?'-$'.number_format(abs($invoice->corrections),2):'$'.number_format($invoice->corrections,2)}}</td>
                    </tr>
                    @if($pharmacy_driver["count"]>0)
                    <tr>
                      <td class="tm_width_3">Pharmacy Driver</td>
                      <td class="tm_width_4"></td>
                      <td class="tm_width_1"></td>
                      <td class="tm_width_2 tm_text_right"></td>
                    </tr>
                    <tr>
                      <td class="tm_width_3">&emsp; 1. Orders</td>
                      <td class="tm_width_4">{{date('M d', strtotime($invoice->date_from))}} - {{date('M d, Y', strtotime($invoice->date_to))}}</td>
                      <td class="tm_width_1">{{$pharmacy_driver["count"]}}</td>
                      <td class="tm_width_2 tm_text_right">${{number_format($pharmacy_driver["amount"],2)}}</td>
                    </tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tm_invoice_footer tm_border_top tm_mb15 tm_m0_md">
              <div class="tm_left_footer">
                <p class="tm_mb2"><b class="tm_primary_color">Payment info:</b></p>
                @if(!empty($payment_account))
                @if($payment_account->type=="card")
                <p class="tm_m0">Card - ****{{substr($payment_account->card,-4)}}
                @else
                <p class="tm_m0">Bank Account
                @endif
                @else
                <p class="tm_m0">-
                @endif
                <br>Amount: ${{number_format($invoice->payed_amount,2)}}</p>
              </div>
              <div class="tm_right_footer">
                <table class="tm_mb15">
                  <tbody>
                    <tr class="tm_gray_bg ">
                      <td class="tm_width_3 tm_primary_color tm_bold">Subtotal</td>
                      <td class="tm_width_3 tm_primary_color tm_bold tm_text_right">
                        @if($invoice->payed=='1')
                        ${{number_format($invoice->payed_amount,2)}}
                        @else
                        @if($pharmacy->copay_bill=='1')
                        @if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0)
                        $0
                        @else
                        ${{number_format((($invoice->amount+$invoice->corrections)-$invoice->copay),2)}}
                        @endif
                        @else
                        ${{number_format(($invoice->amount+$invoice->corrections),2)}}
                        @endif
                        @endif
                      </td>
                    </tr>                    
                    <tr class="tm_accent_bg">
                      <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color">Total	</td>
                      <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_white_color tm_text_right">
                        @if($invoice->payed=='1')
                        ${{number_format($invoice->payed_amount,2)}}
                        @else
                        @if($pharmacy->copay_bill=='1')
                        @if((($invoice->amount+$invoice->corrections)-$invoice->copay)<0)
                        $0
                        @else
                        ${{number_format((($invoice->amount+$invoice->corrections)-$invoice->copay),2)}}
                        @endif
                        @else
                        ${{number_format(($invoice->amount+$invoice->corrections),2)}}
                        @endif
                        @endif
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tm_invoice_footer tm_type1">
              <div class="tm_left_footer"></div>
              <div class="tm_right_footer">
                <div class="tm_sign tm_text_center">
                  <img src="/images/sign.svg" alt="Sign">
                  <p class="tm_m0 tm_ternary_color">Evgeny Shchukin</p>
                  <p class="tm_m0 tm_f16 tm_primary_color">A2B Rx Inc.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="tm_note tm_text_center tm_font_style_normal">
            <hr class="tm_mb15">
            
          </div><!-- .tm_note -->
        </div>
      </div>
      <div id="editor"></div>
      <div class="tm_invoice_btns tm_hide_print">
        <a href="javascript:window.print()" class="tm_invoice_btn tm_color1">
          <span class="tm_btn_icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path d="M384 368h24a40.12 40.12 0 0040-40V168a40.12 40.12 0 00-40-40H104a40.12 40.12 0 00-40 40v160a40.12 40.12 0 0040 40h24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><rect x="128" y="240" width="256" height="208" rx="24.32" ry="24.32" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><path d="M384 128v-24a40.12 40.12 0 00-40-40H168a40.12 40.12 0 00-40 40v24" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><circle cx="392" cy="184" r="24" fill='currentColor'/></svg>
          </span>
          <span class="tm_btn_text">Print</span>
        </a>
      </div>
    </div>
  </div>
  <script src="/libs/jquery/jquery.min.js"></script>
  <script src="/js/jspdf.min.js"></script>
  <script src="/js/html2canvas.min.js"></script>
</body>
</html>