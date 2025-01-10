<?php
session_start();
include('includes/db.php');

// Set content type to JSON for AJAX responses
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $orderId = $_POST['order_id'];
    $orderLineId = $_POST['order_line_id'];
    $quantity = $_POST['quantity'];

    if ($quantity > 0) {
        try {
            // Fetch the current quantity
            $fetchQuery = "SELECT Quantity FROM OrderLine WHERE OrderLineID = :orderLineId AND OrderID = :orderId";
            $stmt = $conn->prepare($fetchQuery);
            $stmt->execute(['orderLineId' => $orderLineId, 'orderId' => $orderId]);
            $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentData) {
                echo json_encode(['success' => false, 'message' => 'Invalid OrderLine or OrderID.']);
                exit;
            }

            // If the quantity is the same
            if ((int)$currentData['Quantity'] === (int)$quantity) {
                echo json_encode(['success' => false, 'message' => 'No changes made because the quantity is the same.']);
                exit;
            }

            // Start transaction
            $conn->beginTransaction();

            // Update the quantity
            $updateQuery = "UPDATE OrderLine SET Quantity = :quantity WHERE OrderLineID = :orderLineId AND OrderID = :orderId";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute(['quantity' => $quantity, 'orderLineId' => $orderLineId, 'orderId' => $orderId]);

            if ($stmt->rowCount() > 0) {
                // Recalculate the total price
                $totalQuery = "
                    UPDATE Orders
                    SET Price = (
                        SELECT SUM(OrderLine.Quantity * PrintingPackage.Price)
                        FROM OrderLine
                        JOIN PrintingPackage ON OrderLine.PackageID = PrintingPackage.PackageID
                        WHERE OrderLine.OrderID = :orderId
                    )
                    WHERE OrderID = :orderId";
                $stmt = $conn->prepare($totalQuery);
                $stmt->execute(['orderId' => $orderId]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Order updated successfully.']);
            } else {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to update the order line.']);
            }
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than zero.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
