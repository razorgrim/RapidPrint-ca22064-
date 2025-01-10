<?php
require 'vendor/autoload.php'; // Include the QR Code library
include('includes/db.php'); // Include your database connection

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Color\Color;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f2f2f2;
        }

        img {
            display: block;
            margin: auto;
            max-width: 100px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>QR Code Generator</h1>
        <table>
            <thead>
                <tr>
                    <th>Membership ID</th>
                    <th>User ID</th>
                    <th>Status</th>
                    <th>QR Code</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Fetch membership records
                    $stmt = $conn->prepare("SELECT * FROM membership");
                    $stmt->execute();

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $membershipID = $row['MembershipID'];
                        $userID = $row['UserID'];
                        $status = $row['MembershipStatus'];
                        $qrData = "Membership ID: $membershipID, User ID: $userID, Status: $status";

                        // Generate QR Code
                        $qrCode = new QrCode($qrData);
                        $writer = new PngWriter();

                        $qrCode->setEncoding(new Encoding('UTF-8'))
                            ->setSize(200)
                            ->setMargin(10)
                            ->setForegroundColor(new Color(0, 0, 0))
                            ->setBackgroundColor(new Color(255, 255, 255));

                        // Define QR Code Image Path
                        $qrFileName = "qr_$membershipID.png";
                        $qrFilePath = "assets/qrcodes/" . $qrFileName;

                        // Write the QR Code to a file
                        $writer->write($qrCode)->saveToFile($qrFilePath);

                        echo "<tr>
                                <td>$membershipID</td>
                                <td>$userID</td>
                                <td>$status</td>
                                <td><img src='$qrFilePath' alt='QR Code'></td>
                              </tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='4'>Error: " . $e->getMessage() . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
