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
            
            // Jika tidak dikonfigurasi dari config, cek env
            if (!$firebaseCredentials) {
                $firebaseCredentials = env('FIREBASE_CREDENTIALS');
            }

            if ($firebaseCredentials) {
                // Jika berupa JSON string
                if (is_string($firebaseCredentials) && str_starts_with(trim($firebaseCredentials), '{')) {
                    $credentialsData = json_decode($firebaseCredentials, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $factory = (new Factory)->withServiceAccount($credentialsData);
                        $this->messaging = $factory->createMessaging();
                    } else {
                        $this->safeLogWarning('Firebase credentials JSON string is invalid');
                    }
                } 
                // Jika berupa file path
                else {
                    // Jika path relatif, sesuaikan dengan base_path
                    if (is_string($firebaseCredentials) && !str_starts_with($firebaseCredentials, '/')) {
                        $fullPath = base_path($firebaseCredentials);
                    } else {
                        $fullPath = $firebaseCredentials;
                    }

                    if (file_exists($fullPath)) {
                        $factory = (new Factory)->withServiceAccount($fullPath);
                        $this->messaging = $factory->createMessaging();
                    } else {
                        $this->safeLogWarning('Firebase credentials file not found at: ' . $fullPath);
                    }
                }
            } else {
                $this->safeLogWarning('Firebase credentials are not configured');
            }
        } catch (\Exception $e) {
            $this->safeLogError('Firebase Initialization Error: ' . $e->getMessage());
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
            $this->safeLogInfo('Notification skipped. Messaging service not initialized or token is empty. Title: ' . $title);
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
            $this->safeLogError('FCM Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Safe log helpers to prevent crashes when log file permissions are invalid.
     */
    protected function safeLogWarning($message)
    {
        try {
            Log::warning($message);
        } catch (\Exception $e) {
            error_log('FirebaseNotificationService Warning: ' . $message);
        }
    }

    protected function safeLogError($message)
    {
        try {
            Log::error($message);
        } catch (\Exception $e) {
            error_log('FirebaseNotificationService Error: ' . $message);
        }
    }

    protected function safeLogInfo($message)
    {
        try {
            Log::info($message);
        } catch (\Exception $e) {
            error_log('FirebaseNotificationService Info: ' . $message);
        }
    }
}
