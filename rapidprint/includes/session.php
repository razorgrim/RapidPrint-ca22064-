<?php

    $lifetime = 1800; // Set to 60 seconds for demo, 1800 for 30 minutes in real usage
    ini_set('session.gc_maxlifetime', $lifetime);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'secure' => false,        // Set to true if using HTTPS
        'httponly' => true,       // Prevent JavaScript access
        'samesite' => 'Strict',   // Prevent cross-site cookie usage
    ]);
    session_start();

    // Set the session expiration time
    $session_expiration_time = time() + $lifetime;

    // Check for session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $lifetime)) {
        $_SESSION['session_expired'] = 'Your session has expired due to inactivity. Please log in again.';
        session_unset();
        session_destroy();
        header("Location: logout.php");
        exit();
    }

    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Pass session expiration time to frontend (JavaScript)
    echo "<script>
        var sessionExpirationTime = " . (isset($session_expiration_time) ? $session_expiration_time : 0) . ";
        var warningTime = 30; // 30 seconds before session expiry

        // Calculate the countdown in seconds
        var countdown = sessionExpirationTime - warningTime - Math.floor(Date.now() / 1000);

        if (countdown > 0) {
            setTimeout(function() {
                alert('Your session will expire in 30 seconds. Please save your work.');
            }, countdown * 1000);
        }
    </script>";

?>
