<?php
// index.php - Matches User by Email
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

// 1. Receive Webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 2. Check Event
if (isset($data['event']) && $data['event'] === 'payment.captured') {
    
    $payment = $data['payload']['payment']['entity'];
    $amount = $payment['amount']; // 4900 = ₹49
    $payerEmail = $payment['email']; // The email they entered in Razorpay

   // Allow either ₹49 OR ₹1 (for testing)
if ($payerEmail && ($amount == 4900 || $amount == 100)) {
        
        // 3. Connect to Firebase
        $factory = (new Factory)
            ->withServiceAccount(json_decode(getenv('FIREBASE_CREDENTIALS'), true));
        
        $firestore = $factory->createFirestore();
        $database = $firestore->database();
        $usersRef = $database->collection('users');

        // 4. SEARCH for the user with this email
        // (Because we don't have the UID directly)
        $query = $usersRef->where('email', '=', $payerEmail);
        $documents = $query->documents();

        $found = false;
        foreach ($documents as $document) {
            $found = true;
            $userId = $document->id();

            // 5. Update Subscription
            $newExpiry = time() + (30 * 24 * 60 * 60); // 30 Days
            
            $usersRef->document($userId)->set([
                'subscriptionExpiry' => $newExpiry,
                'lastPaymentId' => $payment['id'],
                'plan' => 'premium'
            ], ['merge' => true]);

            echo "Success: User $userId ($payerEmail) Upgraded.";
        }

        if (!$found) {
            // This happens if they used an email that isn't in your database
            echo "Error: No user found with email $payerEmail";
        }

    } else {
        echo "Error: Invalid Amount or Missing Email.";
    }
} else {
    echo "Ignored event.";
}
?>
