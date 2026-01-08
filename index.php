<?php
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

// Enable Logs
file_put_contents('php://stderr', "Hit received at " . date('Y-m-d H:i:s') . "\n");

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['event']) && $data['event'] === 'payment.captured') {
    
    $payment = $data['payload']['payment']['entity'];
    $amount = $payment['amount']; 
    $payerEmail = $payment['email']; 

    file_put_contents('php://stderr', "Payment Details: Email=$payerEmail, Amount=$amount\n");

    // --- FIX: Allow ANY amount greater than or equal to ₹1 (100 paise) ---
    if ($payerEmail && $amount >= 100) {
        
        try {
            $factory = (new Factory)
                ->withServiceAccount(json_decode(getenv('FIREBASE_CREDENTIALS'), true));
            
            $firestore = $factory->createFirestore();
            $database = $firestore->database();
            $usersRef = $database->collection('users');

            // Find user by Email
            $query = $usersRef->where('email', '=', $payerEmail);
            $documents = $query->documents();

            $found = false;
            foreach ($documents as $document) {
                $found = true;
                $userId = $document->id();
                
                // Convert Seconds to Milliseconds for JS compatibility
                $newExpiry = (time() + (30 * 24 * 60 * 60)) * 1000; 
                
                $usersRef->document($userId)->set([
                    'subscriptionExpiry' => $newExpiry,
                    'lastPaymentId' => $payment['id'],
                    'plan' => 'premium'
                ], ['merge' => true]);

                file_put_contents('php://stderr', "SUCCESS: Updated User $userId ($payerEmail) to expiry $newExpiry\n");
            }

            if (!$found) {
                file_put_contents('php://stderr', "ERROR: No user found for email $payerEmail\n");
            }

        } catch (Exception $e) {
            file_put_contents('php://stderr', "CRITICAL ERROR: " . $e->getMessage() . "\n");
        }

    } else {
        file_put_contents('php://stderr', "IGNORED: Amount $amount is too low (Needs 100+).\n");
    }
} else {
    file_put_contents('php://stderr', "PING: Connection working, but not a payment event.\n");
}
?>
