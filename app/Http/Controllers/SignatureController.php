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

    public function sign($cleartext,$private_key, $user)
    {
        $msg_hash = md5($cleartext);
        openssl_private_encrypt($msg_hash, $sig, $private_key);
        $signed_data = base64_encode($cleartext. "----SIGNATURE:----" . $sig."[villageboy]".implode(",",$user)) ;
        return $signed_data;
    }

    public function store(Request $request)
    {
        //
        $signatures = new Signature();
        $cover = $request->file("cover");
        $hidden =$request->file("message")[0];
        $ext = $hidden->extension();
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
        $base64 = 'data:'.$ext. ';base64,' . base64_encode($load);
        $signature = "signatures/". time() .".txt";
        $encodeDatail =  $this->sign($base64,  file_get_contents("storage/". Auth::user()->private), array("name"=> Auth::user()->name, 'email'=>Auth::user()->email));
        Storage::disk('public')->put($signature, $encodeDatail);
        $signatures->signature = $signature;

        $payloadByteArr = unpack("C*",$encodeDatail);
        // get payload size
        $payloadByteSize = count($payloadByteArr);
        // return $payloadByteSize;
        if ($payloadByteSize > $maxPayloadByte) {
            die('The payload is larger than the cryptcontainer.');
        }

                //Read carrier medium in file and "open" as image
                $container = file_get_contents($src_container);
                // return dd($container);

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
                        $oldpixel = decbin($pixel);
                        $pixel_1 = substr($oldpixel,0,8);
                        $pixel_2 = substr($oldpixel, 8, 8);
                        $pixel_3 = substr($oldpixel, 16, 8);
                        // $VpayloadBlock = decbin($payloadSubBlock1)|decbin($payloadSubBlock2)|decbin($payloadSubBlock3);
                        $totalPayload =substr(str_repeat(0, 8). decbin($payloadByteArr[($x * $container_size[1]) + $y - 3]), - 8);
                        //substr(str_repeat(0, 8).decbin($bloc), -8);
                        $lp1 = substr($totalPayload, 0, 3);
                        $lp2 = substr($totalPayload, 3, 3);
                        $lp3 = substr($totalPayload, 6, 3);
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


        // header('Content-type: '.$container_size['mime']);
        $ext = explode("/", $container_size['mime']);
        $stegopath = 'stego/'.time().".".$ext[1] ;
        imagepng($img,"storage/".$stegopath);

        imagedestroy($img);
        $signatures->message=$stegopath;


        $file_name=time().$cover->getClientOriginalName();
        // Storage::disk('public')->put('users/'. $file_name);
        $coverpath = "cover/".$file_name;
        $cover->storeAs("cover", $file_name, 'public');
        // $photo = Picture::create(['file'=> $file_name]);
        // $input['picture_id']=$photo->id;
        $signatures->cover=$coverpath;
        $signatures->save();
        return redirect()->route('voyager.signatures.index');


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
