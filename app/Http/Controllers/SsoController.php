<?php

namespace App\Http\Controllers;

use App\Events\UserActionLogged;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SsoController extends Controller
{
    public function dbauth(Request $request)
    {
        return $this->handleDbauth($request, route('goals', absolute: false), 'kpnpm');
    }

    public function dbauthReimburse(Request $request)
    {
        return $this->handleDbauth($request, route('reimbursements', absolute: false), 'kpnreimburse');
    }

    public function authService(Request $request)
    {
        return $this->handleJWTAuth($request);
    }

    private function handleDbauth(Request $request, $redirectRoute, $sessionValue)
    {
        $encryptedData = $request->data;
        $decodedData = base64_decode($encryptedData);

        $key = '666666';
        $decryptedDataxor = $this->xorDecrypt($decodedData, $key);
        $decryptedData = base64_decode($decryptedDataxor);

        $decryptedDataArray = json_decode($decryptedData, true);
        $email = $decryptedDataArray['email'];
        $token = $decryptedDataArray['token'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kpncorporation.darwinbox.com/checkToken',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array(
                "api_key" => "3bbfc6dfa28df2a81bd45192bf4f96b72628ae0ec9921a062aef937b7f25d6c704ccfc9539e70e5939a45cc43f3b7ce61477c7135a83bdbd6f85d5c38b5fc563",
                "token" => $token,
            )),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic S1BOX1NTTzpUTXNfJDU2T3BzJXB3',
                'Cookie: __cf_bm=4uUEj1zmjV.MExppSaO8PotAtVYX3j1LC37K7VZbRrA-1712303016-1.0.1.1-t6I22efQWtYGVIwVMpn7P63eop_5tmi8pU7n_ju6i2_AD1YM846eQF2VlfbZKoC.ZwvzWCyaXDISwvp.JP2TPQ; _cfuvid=kEL.TVWTCuZAsepIdMuvd7X9.q7rTz4SP9.769IZWFQ-1712126032738-0.0.1.1-604800000; session=83c35e478c2cafccac60fced59ff2f30'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $responseData = json_decode($response, true);
        $status = $responseData['status'];

        if($status==1){
            $user = User::where('email', $email)->first();
            if ($user) {
                Auth::login($user);
                $user->token = $token;
                $user->email_log = $email;
                $user->save();
                $request->session()->put('system', $sessionValue);
                $request->session()->regenerate();
                return redirect()->intended($redirectRoute);
            } else {
                Alert::error('Login Failed, Please Contact Administrator')->showConfirmButton('OK');
                return redirect('https://kpncorporation.darwinbox.com/');
            }
        } else {
            Alert::error('Login Failed, Please Contact Administrator')->showConfirmButton('OK');
            return redirect('https://kpncorporation.darwinbox.com/');
        }
    }

    public function handleJWTAuth(Request $request)
    {
        $encryptedData = $request->data;
        $decodedData = base64_decode($encryptedData);

        $key = '666666';
        $decryptedDataxor = $this->xorDecrypt($decodedData, $key);
        $decryptedData = base64_decode($decryptedDataxor);

        $decryptedDataArray = json_decode($decryptedData, true);
        $email = $decryptedDataArray['email'];
        $token = $decryptedDataArray['token'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kpncorporation.darwinbox.com/checkToken',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array(
                "api_key" => "3bbfc6dfa28df2a81bd45192bf4f96b72628ae0ec9921a062aef937b7f25d6c704ccfc9539e70e5939a45cc43f3b7ce61477c7135a83bdbd6f85d5c38b5fc563",
                "token" => $token,
            )),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic S1BOX1NTTzpUTXNfJDU2T3BzJXB3',
                'Cookie: __cf_bm=4uUEj1zmjV.MExppSaO8PotAtVYX3j1LC37K7VZbRrA-1712303016-1.0.1.1-t6I22efQWtYGVIwVMpn7P63eop_5tmi8pU7n_ju6i2_AD1YM846eQF2VlfbZKoC.ZwvzWCyaXDISwvp.JP2TPQ; _cfuvid=kEL.TVWTCuZAsepIdMuvd7X9.q7rTz4SP9.769IZWFQ-1712126032738-0.0.1.1-604800000; session=83c35e478c2cafccac60fced59ff2f30'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $responseData = json_decode($response, true);
        $status = $responseData['status'];

        try {
            if ($status == 1) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                'token' => $token,
                'email_log' => $email,
                ]);

                // Generate JWT with custom claims
                $customClaims = [
                    'employee_id' => $user->employee_id,
                    'fullname' => $user->name,
                ];
                $jwtToken = JWTAuth::claims($customClaims)->fromUser($user);

                // Trigger UserActionLogged event for audit trail
                event(new UserActionLogged(
                $user->id,
                'user_logged_in',
                [
                    'url' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'old_values' => null,
                    'new_values' => null,
                ]
                ));

                return response()->json(['token' => $jwtToken, 'user' => $user]);

            } else {
                Alert::error('Login Failed, Please Contact Administrator')->showConfirmButton('OK');
                return redirect('https://kpncorporation.darwinbox.com/');
            }
            } else {
            Alert::error('Login Failed, Please Contact Administrator')->showConfirmButton('OK');
            return redirect('https://kpncorporation.darwinbox.com/');
            }
        } catch (JWTException $e) {
            Alert::error('Could not create token, Please Contact Administrator')->showConfirmButton('OK');
            return redirect('https://kpncorporation.darwinbox.com/');
        }

    }

    public function user()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json($user);
    }

    private function xorDecrypt($data, $key) {
        $keyLength = strlen($key);
        $dataLength = strlen($data);
        $decrypted = '';
    
        // Loop melalui data dan melakukan XOR dengan key
        for ($i = 0; $i < $dataLength; $i++) {
            $decrypted .= $data[$i] ^ $key[$i % $keyLength];
        }
    
        return $decrypted;
    }
}
