<div id="ticket_{{$order->id}}">
    @for ($i = 1; ($i-1) < $order->count_bags; $i++)
        <div class="row" style="background: white;">
            <div class="text-center" style="width:306px;height: 406px;background: #ffffff;">
                <p><img src="https://cp.a2brx.com/images/logoprint.png" alt="logo" height="75" style="margin-top: 17px;margin-left: 25px;"></p>
                <span style="font-size:13px;line-height: 12px;padding: 5px;color: #000000;border: solid 1px #000;">Bags: {{$order->count_bags}} -- RX qty: {{count($order->rxs)}} </span>
                <p class="mb-0" style="font-size:14px;color:#000;margin-left:0px;margin-top: 0px; height: 130px">
                <img src="data:image/png;base64,{{DNS2D::getBarcodePNG($order->id.'_'.$i, 'QRCODE',5,5)}}" alt="qrcode" style="margin-left: 20px;margin-top: 20px; float: left;"/>
                <br><span style="margin-top: 10px;">Order # {{$order->id}}</span><br>
                <span style="">{{$pharmacys[$order->pharmacy_id]->name}}</span><br>
                <span style=""><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16"> <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/> </svg> {{$pharmacys[$order->pharmacy_id]->phone}}</span><br>                                                            
                </p>
                <p style="font-size:14px;color:#000;margin-left:18px;width:270px;margin-top: 0px;">
                <span style="word-wrap: break-word;text-transform: capitalize;"> {{$patients[$order->user_id]->last_name}} {{$patients[$order->user_id]->name}}</span><br>
                <span style="word-wrap: break-word;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/> <path fill-rule="evenodd" d="M8 1a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/> </svg> {{$patients[$order->user_id]->address}}, {{$patients[$order->user_id]->zip}} <b>{{$patients[$order->user_id]->apartment}}</b></span><br>
                </p>
                @if($order->fridge>0 && $i==1)
                <p class="text-center" style="color: #000000;text-transform: uppercase;font-size: 24px;font-weight: bold;text-decoration: underline;">Refrigerator</p>
                @endif
                @if($order->signature && ($order->delivery_method_id==3 || $order->delivery_method_id==4) && $i==1)
                <p class="text-center" style="color: #000000;text-transform: uppercase;font-size: 15px;font-weight: bold;">Signature Required</p>
                @endif
                <div class="row" style="width:306px; height: auto; background: #ffffff;">
                <div align="center" style="width: 120px; height: auto; float: left; vertical-align: middle"><img src="https://cp.a2brx.com/images/smile.png" alt="" height="80" style="margin-top: 0px;margin-left: 30px; float: left;" > </div>
                    <div align="center"  style="width: 180px;height: auto vertical-align: middle;text-align: center; float: none;">
                        <span style="font-weight: normal; color: #000000; text-align: center;">{{$wishs[array_rand($wishs)]}}</span><br><b>Have a Nice Day!</b>
                    </div>
                </div>
            </div>
        </div>
        <br><div class="row other-pages" style="page-break-after: always;"> </div>
    @endfor
</div>