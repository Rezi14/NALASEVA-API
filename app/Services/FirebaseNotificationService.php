<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $firebaseCredentials = config('firebase.credentials');
            // Jika dikonfigurasi melalui ENV FIREBASE_CREDENTIALS
            if (!$firebaseCredentials && env('FIREBASE_CREDENTIALS')) {
                $firebaseCredentials = base_path(env('FIREBASE_CREDENTIALS'));
            }

            if ($firebaseCredentials && file_exists($firebaseCredentials)) {
                $factory = (new Factory)->withServiceAccount($firebaseCredentials);
                $this->messaging = $factory->createMessaging();
            } else {
                Log::warning('Firebase credentials file not found at: ' . $firebaseCredentials);
            }
        } catch (\Exception $e) {
            Log::error('Firebase Initialization Error: ' . $e->getMessage());
        }
    }

    /**
     * Send Push Notification to a specific FCM Token.
     *
     * @param string|null $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToToken($token, $title, $body, $data = [])
    {
        if (!$this->messaging || empty($token)) {
            Log::info('Notification skipped. Messaging service not initialized or token is empty. Title: ' . $title);
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage());
            return false;
        }
    }
}
