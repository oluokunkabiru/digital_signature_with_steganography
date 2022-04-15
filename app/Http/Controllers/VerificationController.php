<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\TryCatch;

class VerificationController extends Controller
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
        // return dd($base64);
        list($plain_data,$old_sig) = explode("----SIGNATURE:----", $base64);
        try {
            //code...
        openssl_public_decrypt($old_sig, $decrypted_sig, $public_key);
        $data_hash = md5($plain_data);
        if($decrypted_sig == $data_hash && strlen($data_hash)>0){
                return $plain_data;
            }else{
                return redirect()->back()->with(['message'=>"Invalid public key detected, please use valid public key", 'alert-type'=>'error']);
            }

    } catch (\Throwable $th) {
        // return dd($th);
            return redirect()->back()->with(['message'=>"Invalid public key detected, please use valid public key", 'alert-type'=>'error']);
    }
}

    public function store(Request $request)
    {
       $request->validate([
           'stego' => 'required|image|mimes:png,jpg, jpeg',
           'public' => 'required',
           'public.*' => 'file'
       ]);
        $verification = new Verification();
        $cover = $request->file("stego");
        $key = $request->file("public")[0];
        $src_container = $cover;
        $container_size = getimagesize($src_container);
        $container = file_get_contents($src_container);
        // return print_r($container);
        $payloadBlockSizeArray =[];
        $payloadBlocksArray=[];
        $img = imagecreatefromstring($container);

        // $maxPayloadByte = $container_size[0] * $container_size[1] - 4;
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
                    $pixelBinary = substr(str_repeat(0, 24).decbin($pixel), - 24);
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
        $keycontent=file_get_contents($key);
        $verify = $this->verify($data, $keycontent);
        // return $verify;
        list($type, $data) = explode(";", $verify);
        try {
            //code...
        list(, $data) = explode(",", $data);
        $data = base64_decode($data);
        $detals = explode(";base64,", $verify);
        $realData = $detals[1];
        $extention = explode("/", $detals[0]);

        $ext = $extention[1];
        $verificationname = time() .".$ext";
       


        $stegoname = time() .".$ext";
        $verifystego = "verification/stego/". $stegoname; 
        $cover->storeAs("verification/stego", $stegoname, 'public');
        // Storage::disk('public')->put($verifystego, $data);

        $verification->stego = $verifystego;



        $verifypkey = $key->getClientOriginalName(); 
        // return $keycontent;
        $signature = "verification/message/". $verificationname; 
        Storage::disk('public')->put($signature, $data);
        $verification->message  = json_encode(array([
            'download_link' => $signature,
            'original_name' => $verificationname,
        ]));

        $publicKey = "verification/key/". $verifypkey;
        // Storage::disk('public')->put($publicKey, $keycontent);
        $key->storeAs("verification/key", $verifypkey, 'public');
        $verification->public  = json_encode(array([
            'download_link' => $publicKey,
            'original_name' => $verifypkey,
        ]));

        $verification->save();
         return redirect()->route('voyager.verifications.index')->with(['message'=> 'Verification successfully', 'alert-type'=>'success']);
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->with(['message'=>"Invalid public key detected, please use valid public key", 'alert-type'=>'error']);

        }
        

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
