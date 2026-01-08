<?php
// index.php - Upload this to Render
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

// 1. RECEIVE WEBHOOK DATA
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 2. CHECK EVENT TYPE
// We only care if the payment was "Captured" (Successful)
if (isset($data['event']) && $data['event'] === 'payment.captured') {
    
    // 3. GET DATA
    $payment = $data['payload']['payment']['entity'];
    $amount = $payment['amount']; // 4900 (for ₹49)
    $email = $payment['email'];
    
    // CRITICAL: Get the User ID we attached from the Frontend
    // Razorpay puts this inside 'notes'
    $userId = $payment['notes']['firebase_uid'] ?? null;

    if ($userId && $amount == 4900) {
        
        // 4. CONNECT TO FIREBASE
        // We use an Environment Variable on Render for security
        $factory = (new Factory)
            ->withServiceAccount(json_decode(getenv('FIREBASE_CREDENTIALS'), true));
        
        $firestore = $factory->createFirestore();
        $database = $firestore->database();

        // 5. UPDATE USER SUBSCRIPTION
        $newExpiry = time() + (30 * 24 * 60 * 60); // 30 Days from now
        
        $userRef = $database->collection('users')->document($userId);
        $userRef->set([
            'subscriptionExpiry' => $newExpiry,
            'lastPaymentId' => $payment['id'],
            'plan' => 'premium'
        ], ['merge' => true]);

        echo "Success: User $userId Upgraded.";
    } else {
        echo "Error: Invalid User ID or Amount.";
    }
} else {
    echo "Ignored: Not a payment.captured event.";
}
?>
