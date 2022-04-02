<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EncryptionController extends Controller
{
    //
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



    public function decodeSignature(){
        $cover = public_path('stego/1648896983.png');
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
        $pc =  pack("C*", ...$payloadBlocksArray);
        return file_put_contents(public_path("cover/yesov.pdf"), $pc);



    }
}
