<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Encryption extends Controller
{
    //


    public function encodeFile()
    {
        //URLs of the carrier medium and the file to be hidden
        $cover = public_path('cover/vboy.png');
        $hidden = public_path('cover/rose.jpeg');
        $hidden2 = public_path('cover/COMPUTERHARDWARE.docx');
        $hidden3 = public_path('cover/1647044580.png');
        $src_container = $cover; //file_get_contents('cover/vboy.png');//$_GET['img_container'];
        // return $src_container;
        $src_payload = $hidden2; //$_GET['payload_file'];

        //Read out image size and calculate maximum byte size
        $container_size = getimagesize($src_container);
        $maxPayloadByte = $container_size[0] * $container_size[1] - 4;

        //Write payload to byte array and calculate size
        $payloadByteArr = unpack("C*", file_get_contents($src_payload));
        $payloadByteSize = count($payloadByteArr);

        //File size security check
        if ($payloadByteSize > $maxPayloadByte) {
            die('The payload is larger than the cryptcontainer.');
        }

        //Read carrier medium in file and "open" as image
        $container = file_get_contents($src_container);
        $img = imagecreatefromstring($container);
        if (!$img) echo "error";

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
                    $payloadSubBlock1 = ($payloadByteSizeArr[$y] & 0xE0) >> 5;
                    $payloadSubBlock2 = ($payloadByteSizeArr[$y] & 0x1C) >> 2;
                    $payloadSubBlock3 = ($payloadByteSizeArr[$y] & 0x3);
                    $payloadBlock = $payloadSubBlock1 << 16 | $payloadSubBlock2 << 8 | $payloadSubBlock3;
                    $pixel = ($pixel & 0xF8F8FC) | $payloadBlock;
                    imagesetpixel($img, $x, $y, $pixel);
                } else {
                    //If payload not yet fully hidden
                    if ((($x * $container_size[1]) + $y - 3) <= $payloadByteSize) {
                        //Code payload
                        $pixel = imagecolorat($img, $x, $y);
                        $payloadSubBlock1 = ($payloadByteArr[($x * $container_size[1]) + $y - 3] & 0xE0) >> 5;
                        $payloadSubBlock2 = ($payloadByteArr[($x * $container_size[1]) + $y - 3] & 0x1C) >> 2;
                        $payloadSubBlock3 = ($payloadByteArr[($x * $container_size[1]) + $y - 3] & 0x3);
                        $payloadBlock = $payloadSubBlock1 << 16 | $payloadSubBlock2 << 8 | $payloadSubBlock3;
                        $pixel = ($pixel & 0xF8F8FC) | $payloadBlock;
                        imagesetpixel($img, $x, $y, $pixel);
                    }
                }
            }
        }
        header('Content-type: ' . $container_size['mime']);
        imagepng($img);
        imagedestroy($img);
    }

    public function decodeFile()
    {
        $cover = public_path('stego/1648896983.png');
        $src_container = $cover;
        $container_size = getimagesize($src_container);
        $container = file_get_contents($src_container);
        // return print_r($container);
        $payloadBlockSizeArray =[];
        $payloadBlocksArray=[];

        $img = imagecreatefromstring($container);

        $maxPayloadByte = $container_size[0] * $container_size[1] - 4;
        // $mypixel = $block = $block1 = $block2 = $block3 = $yd = $b = $b1 = $b2 = $b3 = $xd = $pixel1 = $xy3 = [];
        //For each pixel on the horizontal
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
                    // $bc1 = (($payloadExtract >> 16) & 0xFF);
                    // $bc2 = (($payloadExtract >> 8) & 0xFF);
                    // $bc3 = (($payloadExtract) & 0xFF);
                    // echo "Pixel1 = $pixel_1  ". bindec($pixel_1) ."<br>Pixel2 = $pixel_2  ". bindec($pixel_2) ."<br>Pixel3 = $pixel_3  ". bindec($pixel_3) ."<br>";
                    // echo "lp1 = $lp1  ". bindec($lp1) ."<br>lp2 = $lp2  ". bindec($lp2) ."<br>Lp3 = $lp3  ". bindec($lp3) ."<br>";
                    // echo "Payload $payloadExtract <br>";
                    // echo "BC1 = $bc1 Bin ". decbin($bc1)."<br>BC2 = $bc2 Bin ". decbin($bc2)."<br>BC3 = $bc3 Bin ". decbin($bc3)."<br>";

                    // echo print_r()

                    // echo "Pixel Init $pixel Bin  $pixelBinary<br>";
                    // $pixel = ($pixel & 0xF8F8FC);
                    // echo "Pixel mani = $pixel<br>";
                    // $bc1 = (($pixel >> 16) & 0xFF); // >> 5;
                    // $bc2 = (($pixel >> 8) & 0xFF) >> 2;
                    // $bc3 = (($pixel) & 0xFF);
                    // $block = $bc1 | $bc2 | $bc3;
                    // echo "Block = $"
                    // echo "Pixel = $pixel<br>";

                    // return $pixel;
                    // echo "B1 = $bc1<br>B2 = $bc2 <br> B3 = $bc3 <br> B = $block<br>";
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
                    // array_push($payloadBlocksArray, $payloadExtract);

                }
            }
            }
        }
            //    return dd($payloadBlocksArray);
               $pc =  pack("C*", ...$payloadBlocksArray);
            //    $hidden3 = public_path('cover/rose.jpeg');
            //    $payloadByteArr = unpack("C*", file_get_contents($hidden3));
            // return (array_diff_assoc($payloadBlocksArray, $payloadByteArr));
               return file_put_contents(public_path("cover/yesov.pdf"), $pc);


               echo "<br>$payloadSize<br>";
    }
    public function index()
    {
        //URLs of the carrier medium and the file to be hidden
        // return "hello";
        $cover = public_path('cover/vboy.png');
        $hidden = public_path('cover/pdf.pdf');
        $hidden2 = public_path('cover/COMPUTERHARDWARE.docx');
        $hidden3 = public_path('cover/1647044580.png');

        // $mes = "hello";
        // $mes[2] = "d";
        // return $mes;
        // asset();
        // $ext = strtolower(pathinfo(public_path('cover/vboy.png'), PATHINFO_EXTENSION));
        // return $ext;

        // return $path;
        $src_container = $cover; //file_get_contents('cover/vboy.png');//$_GET['img_container'];
        // return $src_container;
        $src_payload = $hidden; //$_GET['payload_file'];

        //Read out image size and calculate maximum byte size
        $container_size = getimagesize($src_container);
        // return $container_size;
        $maxPayloadByte = $container_size[0] * $container_size[1] - 4;
        // return $maxPayloadByte;
        //Write payload to byte array and calculate size
        // pack()
        // pack
        // return pack("C*", 80,72,80,5,70,30, 600);

        $payloadByteArr = unpack("C*", file_get_contents($src_payload));

        // $vb = implode(",", $payloadByteArr);
        // return print_r($payloadByteArr);
        // return count($payloadByteArr);
        // $o = [80,72,80,34,69,90];
        // $p = implode(",", $o);
        // $i = "80,72,80";
        // echo "$p<br>";
        $pc =  pack("C*", ...$payloadByteArr);
        // return file_get_contents($pc);
        // return print($o);
        // $file = fopen(public_path("cover/hello.docx"), "w+");
        // fwrite($file, $pc);
        // fclose($file);
        // return;

        //  return file_put_contents(public_path("cover/fromcover"), $pc);
        // $out = [];

        // return $vb;
        // $data = pack("C*", $payloadByteArr);
        // return file_put_contents(public_path("stego/hello"), $data);
        // return $payloadByteArr;
        $payloadByteSize = count($payloadByteArr);
        // return $payloadByteSize;

        //File size security check
        if ($payloadByteSize > $maxPayloadByte) {
            return ('The payload is larger than the cryptcontainer.');
        }

        //Read carrier medium in file and "open" as image
        $container = file_get_contents($src_container);
        // return dd($container);

        $img = imagecreatefromstring($container);
        // $vb="";
        // return $img;
        if (!$img) echo "error";
        // return dd(($payloadByteSize >> 24) & 0xFF);
        //Rewrite payload size to byte array
        $payloadByteSizeArr = array((($payloadByteSize >> 24) & 0xFF),
            (($payloadByteSize >> 16) & 0xFF),
            (($payloadByteSize >> 8) & 0xFF),
            ($payloadByteSize & 0xFF)
        );
        // return $payloadByteSizeArr;/
        // return $payloadByteSizeArr;
        // referse = value <<24 +value<<16 + value<< 8 value

        // $mypixel = $block = $block1 = $block2 = $block3 = $yd = $b = $b1 = $b2 = $b3 = $xd = $pixel1 = $xy3 = [];
        //For each pixel on the horizontal
        for ($x = 0; $x < $container_size[0]; $x++) {
            //For each pixel on the vertical
            for ($y = 0; $y < $container_size[1]; $y++) {
                //Treat the first 4 pixels (=bytes) differently
                if ($y < 4 && $x == 0) {
                    //Encode payload size
                    // echo "Y = $y and X = $x<br>";
                    $pixel = imagecolorat($img, $x, $y);
                    // return $pixel;
                    // echo "Pixel init $pixel ";
                    // echo decbin(255)."<br><br>";
                    // ($payloadByteSizeArr[$y] & 0xE0) >>/ 5;
                   echo "sub ". substr(str_repeat(0, 8). decbin($payloadByteSizeArr[$y]), - 8)."<br>";

                    $payloadSubBlock1 = ($payloadByteSizeArr[$y] & 0xE0) >> 5;


                    // echo "Block1 =  $payloadSubBlock1 Bin ". decbin( $payloadSubBlock1) . "<br>";
                    // echo "Block<<16 =  " .$payloadSubBlock1 << 16  . " Bin ". decbin(($payloadSubBlock1 << 16)) . "<br>";

                    // echo "Bloack1 $payloadSubBlock1 ".$payloadByteSizeArr[$y]. " ".($payloadByteSizeArr[$y] & 0xE0)." <br>";
                    $payloadSubBlock2 = ($payloadByteSizeArr[$y] & 0x1C) >> 2;
                    // echo "Bloack2 $payloadSubBlock2 ".$payloadByteSizeArr[$y]. " ".($payloadByteSizeArr[$y] & 0x1C)." <br>";

                    // echo "Block2 =  $payloadSubBlock2 Bin ". decbin( $payloadSubBlock2) . "<br>";
                    // echo "Block<<8 =  " .$payloadSubBlock2<<8  . " Bin ". decbin( $payloadSubBlock2<<8) . "<br>";
                    $payloadSubBlock3 = ($payloadByteSizeArr[$y] & 0x3);
                    // echo "Bloack3 $payloadSubBlock3 ".$payloadByteSizeArr[$y]. " ".($payloadByteSizeArr[$y] & 0x3)." <br>";
                    // echo "Block3=  $payloadSubBlock3 Bin ". decbin($payloadSubBlock3) . "<br>";
                    // echo "Block<<0 =  " .$payloadSubBlock3  . " Bin ". decbin( $payloadSubBlock3) . "<br>";
                    $p1 =$payloadSubBlock1 << 16;
                    $p2 = $payloadSubBlock2 << 8;
                    $p3 = $payloadSubBlock3;
                    $oldpixel = decbin($pixel);
                    $pixel_1 = substr($oldpixel,0,8);
                    $pixel_2 = substr($oldpixel, 8, 8);
                    $pixel_3 = substr($oldpixel, 16, 8);

                    // echo "Old Pixel =$oldpixel ". bindec($oldpixel) ." <br> Pixel1 = $pixel_1  ". bindec($pixel_1) ."<br>Pixel2 = $pixel_2  ". bindec($pixel_2) ."<br>Pixel3 = $pixel_3  ". bindec($pixel_3) ."<br>";
                    // echo "Block<<16 =  " .$p1  . " Bin ". decbin(($p1)) . "<br>";
                    // echo "Block<<8 =  " .$p2  . " Bin ". decbin(($p2)) . "<br>";
                    // echo "Block<<3 =  " .$p3  . " Bin ". decbin(($p3)) . "<br>";



                    $payloadBlock = $p1 | $p2 | $p3;
                //     $VpayloadBlock = decbin($payloadSubBlock1)|decbin($payloadSubBlock2)|decbin($payloadSubBlock3);
                //    echo "Lenthe ".  substr(str_repeat(0, 8).$VpayloadBlock, -8)."<br>";
                //     // echo $payloadSubBlock1 << 16;
                    // echo "Payload Block  $payloadBlock Bin ". decbin($payloadBlock) ."<br>";


                    $bc1 = (($payloadBlock >> 16) & 0xFF) << 5;
                    // echo "BC1 = $bc1 Bin ". decbin($bc1)."<br>";
                    $bc2 = (($payloadBlock >> 8) & 0xFF) << 2;
                    // echo "BC2 = $bc2 Bin ". decbin($bc2)."<br>";

                    $bc3 = (($payloadBlock) & 0xFF);
                    // echo "BC3 = $bc3 Bin ". decbin($bc3)."<br>";

                    $bloc = $bc1 | $bc2 | $bc3;
                    // echo "Total Block = $bloc Binary = ". decbin($bloc)."<br>";

                    // $VpayloadBlock = decbin($payloadSubBlock1)|decbin($payloadSubBlock2)|decbin($payloadSubBlock3);
                    $totalPayload =substr(str_repeat(0, 8). decbin($payloadByteSizeArr[$y]), - 8);
                    //substr(str_repeat(0, 8).decbin($bloc), -8);

                    $lp1 = substr($totalPayload, 0, 3);
                    $lp2 = substr($totalPayload, 3, 3);
                    $lp3 = substr($totalPayload, 6, 3);
                    $pm1 = substr_replace($pixel_1, $lp1, 5);
                    $pm2 = substr_replace($pixel_2, $lp2, 5);
                    $pm3 = substr_replace($pixel_3, $lp3, 6);
                    $newpixel = $pm1.$pm2.$pm3;
                    $joinPixel = bindec($newpixel);
                    // echo "TPayload =$totalPayload ". bindec($totalPayload) ." <br> lp1 = $lp1  ". bindec($lp1) ."<br>lp2 = $lp2  ". bindec($lp2) ."<br>Lp3 = $lp3  ". bindec($lp3) ."<br>";
                    // echo "New Pixel $newpixel<br> Bas = $joinPixel<br>";
                    // echo "Pm1 = $pm1  ". bindec($pm1)."<br>Pm2 = $pm2  ". bindec($pm2)."<br>Pm3 = $pm3  ". bindec($pm3)."<br>";

                    // return $bloc;
                    // echo "<br>Revers Block1 = $bc1 Block2 = $bc2 Block3 = $bc3<br> Block $bloc";
                    // return $payloadBlock;
                    // echo "Pixel1 = $pixel  Bin ". decbin($pixel);

                    // $pixel = ($pixel & 0xF8F8FC) | $payloadBlock;

                    // $not = !($pixel);
                    // echo "Pixel final = $pixel <br>";
                    // echo "x =$x Y = $y Pixel<br> Not = $not  = $pixel<br>";
                    // echo ""
                    // echo "<br>Pixel = $pixel Bin ". decbin($pixel) ."<br><br>";
                    // return $pixel;
                    imagesetpixel($img, $x, $y, $joinPixel);
                } else {
                    //If payload not yet fully hidden
                    if ((($x * $container_size[1]) + $y - 3) <= $payloadByteSize) {
                        //Code payload
                        $pixel = imagecolorat($img, $x, $y);
                        $pixel1[] = $pixel;

                        $payloadSubBlock1 = ($payloadByteArr[($x * $container_size[1]) + $y - 3] & 0xE0) >> 5;
                        $payloadSubBlock2 = ($payloadByteArr[($x * $container_size[1]) + $y - 3] & 0x1C) >> 2;
                        $payloadSubBlock3 = ($payloadByteArr[($x * $container_size[1]) + $y - 3] & 0x3);
                        $payloadBlock = $payloadSubBlock1 << 16 | $payloadSubBlock2 << 8 | $payloadSubBlock3;
                        // echo "Block1 = $payloadSubBlock1 Block2 = $payloadSubBlock2 Block3 $payloadSubBlock3 Block $payloadBlock <br>";
                        // $b[] = $payloadSubBlock1 >> 16 | $payloadSubBlock2 >> 8 | $payloadSubBlock3;
                        // $b1[] = $payloadSubBlock3;
                        // $b2[] = $payloadSubBlock2 >> 5;
                        // $b3[]  = $payloadSubBlock3 >> 2;
                        // $yd[] = $y;
                        // $xd[] = $x;
                        // $xy3[] = ($x * $container_size[1]) + $y - 3;
                        // $out[] = $payloadBlock;
                        // $block1[] = $payloadSubBlock1;
                        // $block2[] = $payloadSubBlock2;
                        // $block3[] = $payloadSubBlock3;
                        // $block[] = $payloadBlock;

                        $p1 =$payloadSubBlock1 << 16;
                        $p2 = $payloadSubBlock2 << 8;
                        $p3 = $payloadSubBlock3;
                        $oldpixel = decbin($pixel);
                        $pixel_1 = substr($oldpixel,0,8);
                        $pixel_2 = substr($oldpixel, 8, 8);
                        $pixel_3 = substr($oldpixel, 16, 8);
                        $payloadBlock = $p1 | $p2 | $p3;
                        $bc1 = (($payloadBlock >> 16) & 0xFF) << 5;
                        // echo "BC1 = $bc1 Bin ". decbin($bc1)."<br>";
                        $bc2 = (($payloadBlock >> 8) & 0xFF) << 2;
                        // echo "BC2 = $bc2 Bin ". decbin($bc2)."<br>";

                        $bc3 = (($payloadBlock) & 0xFF);
                        // echo "BC3 = $bc3 Bin ". decbin($bc3)."<br>";

                        $bloc = $bc1 | $bc2 | $bc3;
                        // echo "Total Block = $bloc Binary = ". decbin($bloc)."<br>";

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
                        // $pixel = ($pixel xor 0xF8F8FC) | $payloadBlock;
                        // $b1[] =
                        // return "x =$x Y = $y Pixel = $pixel";


                        // $mypixel[] = $pixel;

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
}
