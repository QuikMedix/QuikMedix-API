<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class SendOrderStatusToBestRx
{
    /**
     * Reports an order's delivery status back to BestRx, for orders that came from BestRx.
     */
    public function handle(int|string $order_id)
    {
        $order = DB::table('orders')->where('id',$order_id)->first();
        if(!empty($order) && !empty($order->bestrx_order_id)) {
            $pharmacy = DB::table('pharmacys')->where('id',$order->pharmacy_id)->first();
            $stat_id='1';
            $stat_name='Ready for pick up';
            if($order->statuse_id==2) {
                $stat_id='2';
                $stat_name='In process';
            }
            if($order->statuse_id==3) {
                $stat_id='6';
                $stat_name='On the way';
            }
            if($order->statuse_id==4) {
                $stat_id='8';
                $stat_name='Delivered';
            }
            if($order->statuse_id==5) {
                $stat_id='3';
                $stat_name='Canceled';
            }
            if($order->statuse_id==6) {
                $stat_id='4';
                $stat_name='Picked up';
            }
            if($order->statuse_id==7) {
                $stat_id='5';
                $stat_name='Office';
            }
            if($order->statuse_id==8) {
                $stat_id='9';
                $stat_name='Unavailable';
            }
            if($order->statuse_id==9) {
                $stat_id='9';
                $stat_name='Refused';
            }
            if($order->statuse_id==10) {
                $stat_id='10';
                $stat_name='Back to Pharmacy';
            }
            $signature_url='';
            $signer_info = [];
            if($order->signature_type=='Patient' || empty($order->signature_type)) {
                $signer_type = '1';
                $signer_name = 'Patient';
            } else if($order->signature_type=='Mother' || $order->signature_type=='Father' || $order->signature_type=='Grandmother' || $order->signature_type=='Son' || $order->signature_type=='Daughter' || $order->signature_type=='Sister' || $order->signature_type=='Brother') {
                $signer_type = '2';
                $signer_name = $order->signature_type;
            } else if($order->signature_type=='Boyfriend'){
                $signer_type = '3';
                $signer_name = 'Boyfriend';
            } else {
                $signer_type = '99';
                $signer_name = $order->signature_type;
            }
            if(!empty($order->signature_photo)){
                $signature_url = url('/').$order->signature_photo;
                $signer_info = [
                    "relation"=> $signer_type,
                    "first_name"=> $signer_name,
                    "last_name"=> "",
                    "jurisdiction"=> "IL",
                    "id_type"=> "6",
                    "id_no"=> ""
                ];
            }
            $dt = new \DateTime();
            $dt->setTimeZone(new \DateTimeZone('UTC'));
            $data = [
                'bestrx_pharmacy_id'=>$pharmacy->bestrx_pharmacy_id,
                'bestrx_order_id'=>$order->bestrx_order_id,
                'provider_order_id'=>strval($order_id),
                'tracking_id'=>strval($order_id),
                'order_status'=>[
                    'date'=>$dt->format('Y-m-d\TH:i:s.\0\0\0\0\0\0\0\Z'),
                    'status_code'=>$stat_id,
                    'status_code_description'=>$stat_name,
                    'status_notes'=>''
                ],
                "signature_url"=> $signature_url,
                "signer_info"=> $signer_info
            ];
            $json = json_encode($data);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://developer.bestrxconnect.com/TestDispenseService/Order/UpdateOrderStatus",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".config('services.bestrx.basic_auth'),
                    "cache-control: no-cache",
                    "content-type: application/json",
                ),
            ));
            curl_exec($curl);
        }
        return true;
    }
}
