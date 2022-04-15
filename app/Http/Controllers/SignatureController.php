<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function verify($my_signed_data,$public_key)
    {
        $base64 = base64_decode($my_signed_data);
        
        list($plain_data,$old_sig) = explode("----SIGNATURE:----", $base64);
        openssl_public_decrypt($old_sig, $decrypted_sig, $public_key);
        $data_hash = md5($plain_data);
        if($decrypted_sig == $data_hash && strlen($data_hash)>0){
        return $plain_data;
}
   else
       return redirect()->back()->with('error', "Invalid signature key, please request for owner public key");

}
    public function sign($cleartext,$private_key)
    {
        $msg_hash = md5($cleartext);
        openssl_private_encrypt($msg_hash, $sig, $private_key);
        $signed_data = base64_encode($cleartext. "----SIGNATURE:----" . $sig) ;
        return $signed_data;
    }

    public function store(Request $request)
    {
        //
        $request->validate([
            'cover' =>'required|image|mimes:png,jpg, jpeg',
            'message' => 'required'
        ]);

        $signatures = new Signature();
        $cover = $request->file("cover");
        $hidden =$request->file("message")[0];
        
        // return dd($hidden->getMimeType());
        $type = $hidden->getMimeType();
        $src_container = $cover;
        // return $src_container;
        $src_payload = $hidden;
        // return $src_payload;
        //Read out image size and calculate maximum byte size
        $container_size = getimagesize($src_container);
        // return $container_size;
        $maxPayloadByte = $container_size[0] * $container_size[1] - 4;
        //Write payload to byte array and calculate size
        $load = file_get_contents($src_payload);
        
        // return $hidden;
        $base64 = 'data:'.$type. ';base64,' . base64_encode($load);

       
        // return json_decode(Auth::user()->private, true)[0]['download_link'];

        $encodeDatail =  $this->sign($base64,  file_get_contents('storage/'.json_decode(Auth::user()->private, true)[0]['download_link']));

        // return ;

        $payloadByteArr = unpack("C*",$encodeDatail);

        // return dd($payloadByteArr);
        // get payload size
        $payloadByteSize = count($payloadByteArr);
        // return $payloadByteSize;
        if ($payloadByteSize > $maxPayloadByte) {
            // die('The payload is larger than the cryptcontainer.');
            return redirect()->back()->with(['message'=>"The payload is larger than the carrier container, please reduce the file size", 'alert-type'=>'error']);

        }

                //Read carrier medium in file and "open" as image
                $container = file_get_contents($src_container);
                // return dd($container);
                $checkHidden = [];
                $img = imagecreatefromstring($container);
                if (!$img) echo "error";
                // return dd(($payloadByteSize >> 24) & 0xFF);
                //Rewrite payload size to byte array

                $payloadByteSizeArr = array((($payloadByteSize >> 24) & 0xFF),
                    (($payloadByteSize >> 16) & 0xFF),
                    (($payloadByteSize >> 8) & 0xFF),
                    ($payloadByteSize & 0xFF)
                );
                //For each pixel on the horizontal
                $i =1;
        for ($x = 0; $x < $container_size[0]; $x++) {
            //For each pixel on the vertical
            for ($y = 0; $y < $container_size[1]; $y++) {
                //Treat the first 4 pixels (=bytes) differently
                if ($y < 4 && $x == 0) {
                    //Encode payload size
                    $pixel = imagecolorat($img, $x, $y);
                    $oldpixel = decbin($pixel);
                    $pixel_1 = substr($oldpixel,0,8);
                    $pixel_2 = substr($oldpixel, 8, 8);
                    $pixel_3 = substr($oldpixel, 16, 8);
                    $totalPayload =substr(str_repeat(0, 8). decbin($payloadByteSizeArr[$y]), - 8);
                    $lp1 = substr($totalPayload, 0, 3);
                    $lp2 = substr($totalPayload, 3, 3);
                    $lp3 = substr($totalPayload, 6, 3);
                    $pm1 = substr_replace($pixel_1, $lp1, 5);
                    $pm2 = substr_replace($pixel_2, $lp2, 5);
                    $pm3 = substr_replace($pixel_3, $lp3, 6);
                    $newpixel = $pm1.$pm2.$pm3;
                    $joinPixel = bindec($newpixel);
                    imagesetpixel($img, $x, $y, $joinPixel);
                } else {
                    //If payload not yet fully hidden
                    if ((($x * $container_size[1]) + $y - 3) <= $payloadByteSize) {
                        //Code payload
                        $pixel = imagecolorat($img, $x, $y);
                        $oldpixel = substr(str_repeat(0, 24).decbin($pixel), - 24);
                        $pixel_1 = substr($oldpixel,0,8);
                        $pixel_2 = substr($oldpixel, 8, 8);
                        $pixel_3 = substr($oldpixel, 16, 8);
                        // $VpayloadBlock = decbin($payloadSubBlock1)|decbin($payloadSubBlock2)|decbin($payloadSubBlock3);
                        $totalPayload = substr(str_repeat(0, 8). decbin($payloadByteArr[($x * $container_size[1]) + $y - 3]), - 8);
                        // $checkHidden[] = bindec($totalPayload);// $payloadByteArr[($x * $container_size[1]) + $y - 3];
                        //substr(str_repeat(0, 8).decbin($bloc), -8);
                        $lp1 = substr($totalPayload, 0, 3);
                        $lp2 = substr($totalPayload, 3, 3);
                        $lp3 = substr($totalPayload, 6, 2);
                        // $checkHidden[]= bindec($lp1.$lp2.$lp3);
                        $pm1 = substr_replace($pixel_1, $lp1, 5);
                        $pm2 = substr_replace($pixel_2, $lp2, 5);
                        $pm3 = substr_replace($pixel_3, $lp3, 6);
                        $newpixel = $pm1.$pm2.$pm3;
                        $joinPixel = bindec($newpixel);
                        imagesetpixel($img, $x, $y, $joinPixel);


                    }
                }
            }
        }
        $ext = explode("/", $container_size['mime']);
        $stegoname = time().".".$ext[1] ;
        $stegopath = 'stego/'.$stegoname;
        imagepng($img,"storage/".$stegopath);
        $signatures->message= json_encode(array([
            'download_link' => $stegopath,
            'original_name' => $stegoname,
        ]));

        imagedestroy($img);
        $signaturename = time() .".sign";
        $signature = "signatures/". $signaturename;
        Storage::disk('public')->put($signature, $encodeDatail);
        $signatures->signature = json_encode(array([
            'download_link' => $signature,
            'original_name' => $signaturename,
        ]));
       
        


        $file_name=time().$cover->getClientOriginalName();
        $coverpath = "cover/".$file_name;
        $cover->storeAs("cover", $file_name, 'public');
        $signatures->cover=$coverpath;
        $signatures->save();
        return redirect()->route('voyager.signatures.index')->with(['message'=>'Signature create successfully', 'alert-type'=>'success']);


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
