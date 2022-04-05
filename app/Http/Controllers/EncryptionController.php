<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
// use phpseclib\Crypt\RSA as Crypt_RSA;
// use RSA;

class EncryptionController extends Controller
{
    //
    public function index(){
        return view('vendor.voyager.signs.index');
    }

    function sign($cleartext,$private_key, $user)
    {
        $msg_hash = md5($cleartext);
        openssl_private_encrypt($msg_hash, $sig, $private_key);
        $signed_data = base64_encode($cleartext. "----SIGNATURE:----" . $sig."[villageboy]".$user) ;
        return $signed_data;
    }


public function keyGeneration(){
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    $resource = openssl_pkey_new($config);
    //   return dd($resource);

    // Extract private key from the pair
    openssl_pkey_export($resource, $private_key);

    // Extract public key from the pair
    $key_details = openssl_pkey_get_details($resource);
    $public_key = $key_details["key"];
    // return $private_key;
    $privpath = "private/".time().".key";
    $publicpath = "public/".time().".key";
    Storage::disk('public')->put($privpath, $private_key);
    Storage::disk('public')->put($publicpath, $public_key);
    $keys = array('private' => $privpath, 'public' => $publicpath);
    return $keys;
}

function verify($my_signed_data,$public_key)
{
    $encodeWWithUser = base64_decode($my_signed_data);
    $decodeWWithUser = explode("[villageboy]", $encodeWWithUser);
    $signMessage = $decodeWWithUser[0];
    $userDetails = $decodeWWithUser[1];
    // return $userDetails;
    $userdatails = explode("[vb]", $userDetails);
// return print_r($userdatails);
    list($plain_data,$old_sig) = explode("----SIGNATURE:----", $signMessage);
    openssl_public_decrypt($old_sig, $decrypted_sig, $public_key);
    $data_hash = md5($plain_data);
    if($decrypted_sig == $data_hash && strlen($data_hash)>0){
    return $plain_data;


}

}



function publicKey($key)
    {
        return hash('sha256', $key);
    }

    function privateKey($priv){
        return substr(hash('sha256', $priv), 0, 16);
    }
public function testme(Request $request){
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
      echo "Data = $base64<br><br>";
    //   return storage_path(Auth::user()->public);
    // return   file_get_contents("storage/". Auth::user()->public);
      $encodeDatail =  $this->sign($base64,  file_get_contents("storage/". Auth::user()->private), array("name"=> Auth::user()->name, 'email'=>Auth::user()->email));
    // echo $encodeDatail."<br><br>End<br>";
    // $decodeDatail = $this->verify($encodeDatail, file_get_contents("storage/public/1649085106.key"));

    // return $decodeDatail;
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

        header('Content-type: '.$container_size['mime']);
        $ext = explode("/", $container_size['mime']);
       imagepng($img, 'stego/'.time().".".$ext[1]);
        imagedestroy($img);

    }


    public function encodeFile(){
        $cover = public_path('cover/vboy.png');
        $hidden = public_path('cover/pdf.pdf');
        // return $path;
        $src_container = $cover;
        // return $src_container;
        $src_payload = $hidden;
        //Read out image size and calculate maximum byte size
        $container_size = getimagesize($src_container);
        // return $container_size;
        $maxPayloadByte = $container_size[0] * $container_size[1] - 4;
        //Write payload to byte array and calculate size
        $payloadByteArr = unpack("C*", file_get_contents($src_payload));
        // get payload size
        $payloadByteSize = count($payloadByteArr);
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

        header('Content-type: '.$container_size['mime']);
        $ext = explode("/", $container_size['mime']);
        imagepng($img, 'stego/'.time().".".$ext[1]);
        imagedestroy($img);

    }



    public function decodeSignature(Request $request){
        $cover = $request->file("cover");
        $src_container = $cover;
        $container_size = getimagesize($src_container);
        $container = file_get_contents($src_container);
        // return print_r($container);
        $payloadBlockSizeArray =[];
        $payloadBlocksArray=[];
        $img = imagecreatefromstring($container);

        $maxPayloadByte = $container_size[0] * $container_size[1] - 4;
        for ($x = 0; $x < $container_size[0]; $x++) {
            //For each pixel on the vertical
            for ($y = 0; $y < $container_size[1]; $y++) {
                if ($y < 4 && $x == 0) {
                    //Encode payload size
                    // echo "Y = $y and X = $x<br>";
                    $pixel = imagecolorat($img, $x, $y);
                    $pixelBinary = decbin($pixel);
                    $pixel_1 = substr($pixelBinary,0,8);
                    $pixel_2 = substr($pixelBinary, 8, 8);
                    $pixel_3 = substr($pixelBinary, 16, 8);

                    $lp1 = substr($pixel_1, 5, 3);
                    $lp2 = substr($pixel_2, 5, 3);
                    $lp3 = substr($pixel_3, 6, 2);
                    $payloadSizeExtract = bindec($lp1. $lp2. $lp3);
                    $payloadBlockSizeArray[] = $payloadSizeExtract;
                }
            }
        }

        // print_r($payloadBlockSizeArray);


        $payloadSize = ($payloadBlockSizeArray[0] << 24) +($payloadBlockSizeArray[1] << 16) +($payloadBlockSizeArray[2] << 8) +$payloadBlockSizeArray[3];
        $i =1;
        for ($x = 0; $x < $container_size[0]; $x++) {
            //For each pixel on the vertical
            for ($y = 0; $y < $container_size[1]; $y++) {
                if ($y < 4 && $x == 0) {

                }else{
                if ((($x * $container_size[1]) + $y - 3) <= $payloadSize) {
                    $pixel = imagecolorat($img, $x, $y);
                    $pixelBinary = decbin($pixel);
                    $pixel_1 = substr($pixelBinary,0,8);
                    $pixel_2 = substr($pixelBinary, 8, 8);
                    $pixel_3 = substr($pixelBinary, 16, 8);

                    $lp1 = substr($pixel_1, 5, 3);
                    $lp2 = substr($pixel_2, 5, 3);
                    $lp3 = substr($pixel_3, 6, 2);
                    $payloadExtract = bindec($lp1. $lp2. $lp3);
                    $payloadBlocksArray[$i]= $payloadExtract;
                    $i++;
                }
            }
            }
        }
        $data =  pack("C*", ...$payloadBlocksArray);
        return ($data);
        return file_put_contents(public_path("cover/". time().".docx"), $data);



    }
}
