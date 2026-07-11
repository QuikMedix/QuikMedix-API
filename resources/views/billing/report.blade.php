<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
  <!-- Meta Tags -->
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Site Title -->
  <title>Report - Invoice-{{$invoice->id}}</title>
  <link rel="stylesheet" href="/css/print-style.css">

</head>

<body>
  <div class="tm_container tm_container2">
    <div class="tm_invoice_wrap">
      <div class="tm_invoice tm_style1 tm_type1" id="tm_download_section">
        <div class="tm_invoice_in">
          <div class="tm_invoice_head tm_top_head tm_mb15 tm_align_center">
            <div class="tm_invoice_left">
              <div class="tm_logo"><img src="https://a2brx.com/assets/media/logo-2.svg" alt="Logo"></div>
            </div>
            <div class="tm_invoice_right tm_text_right tm_mobile_hide">
              <div class="tm_f18 tm_text_uppercase tm_white_color">Weekly Statement</div>
            </div>
            <div class="tm_shape_bg tm_accent_bg tm_mobile_hide"></div>
          </div>
          <div class="tm_invoice_info tm_mb0">
            <div class="tm_card_note tm_mobile_hide"></div>
            <div class="tm_invoice_info_list tm_white_color">
              <p class="tm_invoice_number tm_m0">Invoice No: <b>#{{$invoice->id}}</b></p>
              <p class="tm_invoice_date tm_m0">Date: <b>{{date('m.d.Y', strtotime($invoice->created))}}</b></p>
            </div>
            <div class="tm_invoice_seperator tm_accent_bg"></div>
          </div>
          <div class="tm_invoice_head tm_mb0" style="height: auto;">
            <div class="tm_invoice_left">
              <p class="tm_mb2"><b class="tm_primary_color">Pharmacy:</b></p>
              <p>
                {{$pharmacy->name}} <br>
                {{$pharmacy->address}}<br>                
              </p>
            </div>
            <div class="tm_invoice_right tm_text_right">
              <p class="tm_mb2"><b class="tm_primary_color"></b></p>
              <p>   
              {{$pharmacy->email}}<br>
                {{$pharmacy->phone}}            
              </p>
            </div>
          </div>
          <div class="tm_table tm_style1">
            <div class="">
              <div class="tm_table_responsive">
                <table>
                  <thead>
                    <tr class="tm_accent_bg">
                      <th class="tm_width_2 tm_semi_bold tm_white_color tm_f10" style="padding: 0px 10px;">Order</th>
                      <th class="tm_width_2 tm_semi_bold tm_white_color tm_f10" style="padding: 0px 10px;">Date</th>
                      <th class="tm_width_3 tm_semi_bold tm_white_color tm_f10" style="padding: 0px 10px;">Delivery day</th>
                      <th class="tm_width_2 tm_semi_bold tm_white_color tm_f10" style="padding: 0px 10px;">Status</th>
                      <th class="tm_width_4 tm_semi_bold tm_white_color tm_f10" style="padding: 0px 10px;">Patient Name</th>
                      <th class="tm_width_2 tm_semi_bold tm_white_color tm_f10" style="padding: 0px 10px;">Rate</th>
                      <th class="tm_width_2 tm_semi_bold tm_white_color tm_f10 tm_text_right" style="padding: 0px 10px;">Cost</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($orders as $order)
                    <tr>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">#{{$order->id}}</td>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">{{date('m.d.Y', strtotime($order->finish))}}</td>
                      <td class="tm_width_3 tm_f10" style="padding: 0px 10px;">{{$order->delivery_time}}</td>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">{{$order->status}}</td>  
                      <td class="tm_width_4 tm_f10" style="padding: 0px 10px;">{{$order->username}}</td>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">${{number_format($order->tariff,2)}}</td>                      
                      <td class="tm_width_2 tm_f10 tm_text_right" style="padding: 0px 10px;">${{number_format($order->tariff,2)}}</td>
                    </tr>
                    @endforeach               
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tm_invoice_footer tm_border_top tm_mb15 tm_m0_md">
              <div class="tm_left_footer">
                
              </div>
              <div class="tm_right_footer">
                <table class="tm_mb15">
                  <tbody>
                    <tr class="tm_gray_bg ">
                      <td class="tm_width_3 tm_f12 tm_primary_color tm_bold" style="padding: 0px 10px;">Subtotal</td>
                      <td class="tm_width_3 tm_f12 tm_primary_color tm_bold tm_text_right" style="padding: 0px 10px;">
                        ${{number_format($invoice->amount,2)}}
                      </td>
                    </tr>                    
                    <tr class="tm_accent_bg">
                      <td class="tm_width_3 tm_f12 tm_border_top_0 tm_bold tm_white_color" style="padding: 0px 10px;">Total Amount</td>
                      <td class="tm_width_3 tm_f12 tm_border_top_0 tm_bold tm_white_color tm_text_right" style="padding: 0px 10px;">
                        ${{number_format($invoice->amount,2)}}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>            
          </div>
          @if($invoice->copay>0)
          <div class="tm_table tm_style1">
            <div class="">
              <div class="tm_table_responsive">
                <table>
                  <thead>
                    <tr class="tm_accent_bg">
                      <th class="tm_width_2 tm_f10 tm_semi_bold tm_white_color" style="padding: 0px 10px;">Order</th>
                      <th class="tm_width_2 tm_f10 tm_semi_bold tm_white_color" style="padding: 0px 10px;">Date</th>
                      <th class="tm_width_3 tm_f10 tm_semi_bold tm_white_color" style="padding: 0px 10px;">Delivery day</th>
                      <th class="tm_width_2 tm_f10 tm_semi_bold tm_white_color" style="padding: 0px 10px;">Status</th>
                      <th class="tm_width_4 tm_f10 tm_semi_bold tm_white_color" style="padding: 0px 10px;">Patient Name</th>
                      <th class="tm_width_2 tm_f10 tm_semi_bold tm_white_color" style="padding: 0px 0px;">Co-pay</th>
                      <th class="tm_width_2 tm_f10 tm_semi_bold tm_white_color tm_text_right" style="padding: 0px 10px;">Paid</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($orders as $order)
                    @if($order->copay>0 && ($order->statuse_copay=='3' || $order->statuse_copay=='4'))
                    <tr>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">#{{$order->id}}</td>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">{{date('m.d.Y', strtotime($order->finish))}}</td>
                      <td class="tm_width_3 tm_f10" style="padding: 0px 10px;">{{$order->delivery_time}}</td>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">{{$order->status}}</td>  
                      <td class="tm_width_4 tm_f10" style="padding: 0px 10px;">{{$order->username}}</td>
                      <td class="tm_width_2 tm_f10" style="padding: 0px 10px;">${{number_format($order->copay,2)}}</td>                      
                      <td class="tm_width_2 tm_f10 tm_text_right" style="padding: 0px 10px;">${{number_format($order->copay,2)}}</td>
                    </tr>
                    @endif
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tm_invoice_footer tm_border_top tm_mb15 tm_m0_md">
              <div class="tm_left_footer">
                
              </div>
              <div class="tm_right_footer">
                <table class="tm_mb15">
                  <tbody>
                    <tr class="tm_gray_bg ">
                      <td class="tm_width_3 tm_f12 tm_primary_color tm_bold" style="padding: 0px 10px;">Subtotal</td>
                      <td class="tm_width_3 tm_f12 tm_primary_color tm_bold tm_text_right" style="padding: 0px 10px;">
                        ${{number_format($invoice->copay,2)}}
                      </td>
                    </tr>                    
                    <tr class="tm_accent_bg">
                      <td class="tm_width_3 tm_f12 tm_border_top_0 tm_bold tm_white_color" style="padding: 0px 10px;">Total Amount</td>
                      <td class="tm_width_3 tm_f12 tm_border_top_0 tm_bold tm_white_color tm_text_right" style="padding: 0px 10px;">         
                        ${{number_format($invoice->copay,2)}}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>            
          </div>
        @endif
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