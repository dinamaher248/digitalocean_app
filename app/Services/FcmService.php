<?php



namespace App\Services;



use App\Models\User;

use Illuminate\Support\Facades\Http;



class FcmService

{

    public function projectId(): string

    {

        return (string) config('services.firebase.project_id', 'app1-8687c');

    }



    public function topic(): string

    {

        return (string) config('services.firebase.topic', 'motorent');

    }



    public function credentialsPath(): ?string

    {

        $configured = config('services.firebase.credentials');

        if (is_string($configured) && $configured !== '') {

            $absolute = str_starts_with($configured, DIRECTORY_SEPARATOR)

                ? $configured

                : base_path($configured);



            if (file_exists($absolute)) {

                return $absolute;

            }

        }



        $storageRelative = config('services.firebase.credentials_storage');

        if (is_string($storageRelative) && $storageRelative !== '') {

            $storageAbsolute = storage_path($storageRelative);

            if (file_exists($storageAbsolute)) {

                return $storageAbsolute;

            }

        }



        return null;

    }



    public function getAccessToken(): ?string

    {

        $keyFilePath = $this->credentialsPath();

        if ($keyFilePath === null) {

            return null;

        }



        $serviceAccount = json_decode(file_get_contents($keyFilePath), true);

        if (!is_array($serviceAccount)) {

            return null;

        }



        $googleAuthUrl = 'https://oauth2.googleapis.com/token';

        $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $iat = time();

        $exp = $iat + 3600;



        $jwtPayload = base64_encode(json_encode([

            'iss' => $serviceAccount['client_email'],

            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',

            'aud' => $googleAuthUrl,

            'exp' => $exp,

            'iat' => $iat,

        ]));



        $signature = '';

        openssl_sign(

            "$jwtHeader.$jwtPayload",

            $signature,

            openssl_pkey_get_private($serviceAccount['private_key']),

            OPENSSL_ALGO_SHA256

        );



        $jwtAssertion = "$jwtHeader.$jwtPayload." . base64_encode($signature);



        $response = Http::post($googleAuthUrl, [

            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',

            'assertion' => $jwtAssertion,

        ]);



        return $response->json()['access_token'] ?? null;

    }



    public function sendToTopic(

        string $title,

        string $body,

        ?string $topic = null,

        string $action = 'general',

        ?string $refId = null,

        ?string $url = null,

    ): array {

        return $this->sendToTokenOrTopic(

            token: null,

            topic: $topic ?? $this->topic(),

            title: $title,

            body: $body,

            action: $action,

            refId: $refId,

            url: $url,

        );

    }



    public function sendToUser(

        User $user,

        string $title,

        string $body,

        string $action = 'general',

        ?string $refId = null,

        ?string $url = null,

    ): array {

        $token = trim((string) $user->fcm_token);

        if ($token === '') {

            return [

                'success' => false,

                'error' => 'المستخدم ليس لديه رمز FCM',

            ];

        }



        return $this->sendToTokenOrTopic(

            token: $token,

            topic: null,

            title: $title,

            body: $body,

            action: $action,

            refId: $refId,

            url: $url,

        );

    }



    private function sendToTokenOrTopic(

        ?string $token,

        ?string $topic,

        string $title,

        string $body,

        string $action = 'general',

        ?string $refId = null,

        ?string $url = null,

    ): array {

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {

            return [

                'success' => false,

                'error' => 'ملف Firebase Service Account غير موجود. ضعه في storage/app/firebase/',

            ];

        }



        $projectId = $this->projectId();

        $urlEndpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";



        $target = $token

            ? ['token' => $token]

            : ['topic' => $topic ?? $this->topic()];



        $payload = [

            'message' => [

                ...$target,

                'notification' => [

                    'title' => $title,

                    'body' => $body,

                ],

                'data' => [

                    'head' => $title,

                    'desc' => $body,

                    'title' => $title,

                    'body' => $body,

                    'action' => $action,

                    'ref_id' => (string) ($refId ?? ''),

                    'url' => (string) ($url ?? ''),

                    'mtopic' => $this->topic(),

                ],

                'android' => [

                    'priority' => 'HIGH',

                    'notification' => [

                        'channel_id' => 'fcm_data_channel',

                    ],

                ],

            ],

        ];



        $response = Http::withHeaders([

            'Authorization' => 'Bearer ' . $accessToken,

            'Content-Type' => 'application/json',

        ])->post($urlEndpoint, $payload);



        if ($response->successful()) {

            return [

                'success' => true,

                'response' => $response->json(),

            ];

        }



        return [

            'success' => false,

            'error' => $response->json()['error']['message'] ?? 'فشل إرسال الإشعار',

            'status' => $response->status(),

            'response' => $response->json(),

        ];

    }

}

