<?php

use App\Http\Controllers\Encryption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

function gcd($x, $y)
{
    if ($x > $y) {
        $temp = $x;
        $x = $y;
        $y = $temp;
    }

    for ($i = 1; $i < ($x + 1); $i++) {
        if ($x % $i == 0 and $y % $i == 0)
            $gcd = $i;
    }
    return $gcd;
    // echo "GCD of $x and $y is: $gcd";
}

function checkPrime($num)
{
    if ($num == 1)
        return 0;
    for ($i = 2; $i <= $num / 2; $i++) {
        if ($num % $i == 0)
            return 0;
    }
    return 1;
}


function gcd2($a, $b)
{
    if ($a == 0)
        return $b;
    return gcd($b % $a, $a);
}
// multiplicative inverse
function modInverse($a, $m)
{

    for ($x = 1; $x < $m; $x++)
        if ((($a % $m) * ($x % $m)) % $m == 1)
            return $x;
}

function gcdExtended(
    $a,
    $b,
    $x,
    $y
) {
    // Base Case
    if ($a == 0) {
        $x = 0;
        $y = 1;
        return $b;
    }

    // To store results
    // of recursive call
    $gcd = gcdExtended($b % $a, $a, $x, $y);

    // Update x and y using
    // results of recursive
    // call
    $x = $y - floor($b / $a) * $x;
    $y = $x;

    // return $gcd;
    echo "Y = $y : X = $x  : GCDD = $gcd<br>";
}

// Driver Code



// generate multiplicative inverse
function exteuclid($a, $b)
{
    $r1 = $a;
    $r2 = $b;
    $s1 = 1; //int(1)
    $s2 = 0; //int(0)
    $t1 = 0; //int(0)
    $t2 = 1; //int(1)
    while ($r2 > 0) {
        $q = floor($r1 / $r2);
        $r = $r1 - ($q * $r2);
        $r1 = $r2;
        $r2 = $r;
        $s = $s1 - ($q * $s2);
        $s1 = $s2;
        $s2 = $s;
        $t = $t1 - ($q * $t2);
        $t1 = $t2;
        $t2 = $t;

        if ($t1 < 0) {
            $t1 = $t1 % $a;
        }
        $result = [];
        $result['r'] = $r1;
        $result['t'] = $t1;
    }
        return $result;

}





function getRands($last)
{
    $rand = [];
    for ($num = 1; $num <= $last; $num++) {
        $flag = checkPrime($num);
        if ($flag == 1) {
            // echo $num." ";
            $rand[] = $num;
        }
    }
    shuffle($rand);
    $x = $rand[0];
    $y = $rand[1];
    $key = [];
    $key['p'] = $x;
    $key['q'] = $y;
    return $key;
}
function keyGeneration($pn){
    $key = [];
    for ($i=2; $i < $pn; $i++) {
        # code...
        $gcd = euclid($pn, $i);
        if($gcd==1){
            $key[] = $i;
            // echo "I = $i +gcd = $gcd Key Exit here $i <br>";

        }
        // echo "I = $i +gcd = $gcd <br>";
    }
    shuffle($key);
    return $key[0];
    // for i in range(2, Pn):

    //     gcd = euclid(Pn, i)
    //     # print(i, " = GCD = ",gcd)

    //     if gcd == 1:
    //         key.append(i)
}

function euclid($m, $n){
    if($n==0){
        return $m;
    }else{
        $r = $m % $n;
        return euclid($n, $r);
    }
}
// def euclid(m, n):

// 	if n == 0:
// 		return m
// 	else:
// 		r = m % n
// 		return euclid(n, r)


Route::get('/hello', function () {
    // return view('welcome');
    // return "hello";
    $key = getRands(10);
    // print_r($key);
    # Enter two large prime
# numbers p and q
// return 3000000 * 40000*800000000278974827983287387293;
return (1008**102) % 1097;
    $p = $key['p']; //823
    $q = $key['q']; //953
    $n = $p * $q;
    $Pn = ($p-1)*($q-1);
    $encryptionKey = keyGeneration($Pn);
    $decryptionKey = exteuclid($Pn, $encryptionKey);

    // echo "Encryption ". $encryptionKey."<br> Decryption ". $decryptionKey['t']. "<br>";

    # Enter the message to be sent
$M = 2;
print("P = $p\n Q= $q \n N = $n \n Pn = $Pn \n Message = ". $M);

# Signature is created by Alice
$me = ($M**$decryptionKey);
echo "<br>Me= $me<br>";
$S =  $me % $n;
print("\nSigned = ". $S. "\nMe = ".$n);
# Alice sends M and S both to Bob
# Bob generates message M1 using the
# signature S, Alice's public key e
# and product n.
$M1 = ($S**$encryptionKey) % $n;
print("M2 = ". $M1);

# If M = M1 only then Bob accepts
# the message sent by Alice.

if ($M == $M1){
	print("As M = M1, Accept the\
	message sent by Alice");
}else{
	print("As M not equal to M1,\
	Do not accept the message\
	sent by Alice ");
}
    // return redirect('/admin');

});

Route::get('/', [Encryption::class,'index']);
Route::get('/decode', [Encryption::class,'decodeFile']);


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
