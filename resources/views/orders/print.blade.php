@foreach($orders as $order)
                        <div class="row">
                            <div class="col-3">
                            </div>
                            <div class="col-6" style="color: #000000;padding: 5px 0;text-align: center;">
                                <h1 style="color: #000000;padding: 5px 0;text-align: center; margin: 30px 0 0 0;font-size: 20px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{ $order->pharmacyname }}</h1 >
                                <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Address: {{ $order->pharmacyaddress }}</h2>
                                <h2 style="margin: 10px 0 0 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Phone: {{ $order->pharmacyphone }}</h2>                    
                            </div>
                            <div class="col-3">
                            </div>
                            <div class="col-5">
                        </div>
                        <div class="col-2" style="color: #000000;padding: 5px 8px 0 8px;font-weight: bold;text-align: center; border: 2px solid #000; margin: 15px 0 0 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;"><h3>Delivery Slip</h3></div>
                        <div class="col-5"></div>
                        </div>
                        <table width="2500px" border="0">
							  <tbody>
								<tr style="border-bottom: 2px solid #000;">
								  <td>&nbsp;</td>
								</tr>
						</tbody>
						</table>
                        <div class="row" style="margin: 30px 20px;">
                        <div class="col-6" style="color: #000000;line-height:27px;"font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;>
                                <span style="color: #000000; font-size: 17px; text-decoration: underline;font-weight: bold;text-transform: uppercase;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Client:</span><br>    
                                <span style="font-size: 15px;margin-top: 15pxfont-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{ $order->last_name }} {{ $order->username }}</span><br>
                                <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">{{ $order->useraddress }} {{ $order->userzip }} / {{ $order->userapartment }}</span><br>
                                <span style="font-size: 15px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">Ph#: {{ $order->userphone }}</span><br>
                            </div>
                            <div class="col-6" style="color: #000000;line-height: 30px;">			
                            </div>
                            <br><br>
                            <div class="col-12" style="color: #000000;line-height: 30px;border-bottom: 1px solid #000;border-top: 1px solid #000;margin: 15px 0;padding: 5px 0 5px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                    <table width="100%" border="0" style="color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">
                                    <tbody>
                                        <tr bgcolor="#E3E3E3" style="font-weight: bold">
                                        <td bgcolor="#E3E3E3" width="100" align="center">Date</td>
                                        <td bgcolor="#E3E3E3" width="150" align="center">RX#</td>
                                        <td bgcolor="#E3E3E3" width="150" align="center">RF#</td>
                                        <td bgcolor="#E3E3E3" width="250" align="center">RX Barcode</td>
                                        <td bgcolor="#E3E3E3" width="250" align="center">Qty</td>
                                        </tr>
                                        @foreach($order->rxs as $key=>$rx)
                                        <tr>
                                        <td align="center">{{ $rx->rx_date }}</td>
                                        <td align="center">{{explode('-',$rx->rx_id)[0]}}</td>
                                        <td align="center">@if(count(explode('-',$rx->rx_id))>1){{explode('-',$rx->rx_id)[1]}}@endif</td>
                                        <td align="center"><img style="margin-bottom:10px;" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($rx->rx_id, 'C128',1.5,24) }}" alt="barcode"/></td>
                                        <td align="center">{{ $rx->rx_count }}</td>    
                                        </tr>
                                        @endforeach 
                                    </tbody>
                                    </table>            
                            </div>
                        </div>
                        <div class="row" style="margin: 30px 20px;">
                            
                                                <div class="col-8" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">     
                                                    Total Rx Count: {{ count($order->rxs) }}<br>
                                                    Patient is requesting Counseling: &nbsp;&nbsp;&nbsp;&nbsp; Yes <span style="border-bottom: 1px solid #000;">_____ </span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; No <span style="border-bottom: 1px solid #000;">______ </span>
                                                    </div>
                                                    <div class="col-4" style="color: #000000;margin: 15px 0;font-size: 16px;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">       
                                                    ${{ $order->copay }}<br>                            
                                                    </div>
                                                    <div class="col-12" style="font-size: 16px;color: #000000;margin: 15px 0;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;">	
                                                        I certify that I requested and received my medication listed above from {{$order->pharmacyname}} located at {{$order->pharmacyaddress}} (the “Pharmacy”). I further certify that I had a patient relationship with the
                                                        ordering medical provider indicated on the prescription label and that I requested that the prescriber send this
                                                        prescription to the Pharmacy. The foregoing is certified as true and accurate under the penalty of perjury.
                                                    </div>  

                            <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Receiver'ce Name:  <b>{{ $order->username }} {{ $order->last_name }}</b><br><br>
                                Receiver'ce Signature: <img src="{{ $order->signature_photo }}" alt="Signature Photo" style="width:auto;height: 120px;margin: 0px;position: absolute; left: 175px; top: 30px;">
                            </div>
                            <div class="col-6" style="font-size: 16px;color: #000000;font-family: TimesNewRoman,Times New Roman,Times,Baskerville,Georgia,serif;margin-top: 20px;">
                                Date/Time: <b>{{ date('m.d.Y g:i A', strtotime($order->finish)) }}</b> <br><br>
                                Delivered By <b style="text-transform: capitalize;">{{ $order->driver_name }} {{ $order->driver_last_name }}</b>
                            </div>
                        </div>

<div class="row other-pages" style="page-break-before: always;"> </div> 
@endforeach