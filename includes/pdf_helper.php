<?php
/**
 * PDF Generator for Booking Confirmations
 * Génère des PDFs de confirmation de réservation
 */

/**
 * Générer un PDF de confirmation de réservation
 */
function generateBookingPDF($bookingData) {
    $barcode = strtoupper(substr(md5($bookingData['booking_id'] . time()), 0, 12));
    $carModel = !empty($bookingData['car_model']) ? htmlspecialchars($bookingData['car_model']) : '';
    $carImmat = !empty($bookingData['car_immat']) ? htmlspecialchars($bookingData['car_immat']) : '';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation #' . htmlspecialchars($bookingData['booking_id']) . '</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0d6efd;
        }
        .logo {
            font-size: 32px;
            color: #0d6efd;
            font-weight: bold;
        }
        .title {
            font-size: 24px;
            color: #0d6efd;
            margin: 20px 0;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        .detail-row {
            margin: 10px 0;
            display: table;
            width: 100%;
        }
        .label {
            display: table-cell;
            font-weight: bold;
            color: #495057;
            width: 50%;
        }
        .value {
            display: table-cell;
            color: #212529;
            text-align: right;
        }
        .total {
            font-size: 20px;
            font-weight: bold;
            color: #20c997;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
            font-family: "Courier New", monospace;
            font-size: 24px;
            letter-spacing: 2px;
            padding: 15px;
            background: #f8f9fa;
            border: 2px dashed #0d6efd;
        }
        .important {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🚗 VroomVroom</div>
        <div style="color: #6c757d;">Carpooling Platform</div>
    </div>
    
    <h1 class="title">Booking Confirmation</h1>
    
    <div class="barcode">
        * ' . $barcode . ' *
    </div>
    
    <div class="section">
        <div class="section-title">📋 Booking Information</div>
        <div class="detail-row">
            <span class="label">Booking ID:</span>
            <span class="value">#' . htmlspecialchars($bookingData['booking_id']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Booking Date:</span>
            <span class="value">' . date('d/m/Y H:i') . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Passenger:</span>
            <span class="value">' . htmlspecialchars($bookingData['passenger_name']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Passenger CIN:</span>
            <span class="value">' . htmlspecialchars($bookingData['passenger_cin']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Passenger Phone:</span>
            <span class="value">' . htmlspecialchars($bookingData['passenger_phone'] ?? 'N/A') . '</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">🚗 Journey Details</div>
        <div class="detail-row">
            <span class="label">From:</span>
            <span class="value">' . htmlspecialchars($bookingData['from']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">To:</span>
            <span class="value">' . htmlspecialchars($bookingData['to']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Departure Date:</span>
            <span class="value">' . htmlspecialchars($bookingData['date']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Departure Time:</span>
            <span class="value">' . htmlspecialchars($bookingData['time']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Number of Seats:</span>
            <span class="value">' . htmlspecialchars($bookingData['seats']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Price per Seat:</span>
            <span class="value">' . htmlspecialchars($bookingData['price_per_seat']) . ' DT</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">👤 Driver Information</div>
        <div class="detail-row">
            <span class="label">Driver Name:</span>
            <span class="value">' . htmlspecialchars($bookingData['driver_name']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Driver Phone:</span>
            <span class="value">' . htmlspecialchars($bookingData['driver_phone']) . '</span>
        </div>';
    
    if ($carModel) {
        $html .= '
        <div class="detail-row">
            <span class="label">Car Model:</span>
            <span class="value">' . $carModel . '</span>
        </div>';
    }
    
    if ($carImmat) {
        $html .= '
        <div class="detail-row">
            <span class="label">License Plate:</span>
            <span class="value">' . $carImmat . '</span>
        </div>';
    }
    
    $html .= '
    </div>
    
    <div class="section">
        <div class="section-title">💰 Payment Summary</div>
        <div class="detail-row">
            <span class="label">Seats Booked:</span>
            <span class="value">' . htmlspecialchars($bookingData['seats']) . ' × ' . htmlspecialchars($bookingData['price_per_seat']) . ' DT</span>
        </div>
        <div class="total">
            <div class="detail-row">
                <span>TOTAL AMOUNT:</span>
                <span>' . htmlspecialchars($bookingData['total']) . ' DT</span>
            </div>
        </div>
    </div>
    
    <div class="important">
        <strong>⚠️ Important Information:</strong><br>
        • Please arrive 10 minutes before departure time<br>
        • Contact the driver if you have any questions<br>
        • Keep this confirmation for your records<br>
        • In case of cancellation, contact support
    </div>
    
    <div class="footer">
        <p><strong>VroomVroom - Carpooling Platform</strong></p>
        <p>This is an official booking confirmation</p>
        <p>Generated on ' . date('d/m/Y à H:i:s') . '</p>
        <p>For support: support@vroomvroom.com</p>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
    
    return $html;
}

/**
 * Générer et télécharger le PDF (version impression navigateur)
 */
function downloadBookingPDF($bookingData) {
    $html = generateBookingPDF($bookingData);
    echo $html;
}

/**
 * Générer et télécharger le PDF pour plusieurs réservations
 */
function downloadBookingsPDF($bookings, $grandTotal) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation - Multiple Bookings</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0d6efd;
        }
        .logo {
            font-size: 32px;
            color: #0d6efd;
            font-weight: bold;
        }
        .title {
            font-size: 24px;
            color: #0d6efd;
            margin: 20px 0;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        .detail-row {
            margin: 10px 0;
            display: table;
            width: 100%;
        }
        .label {
            display: table-cell;
            font-weight: bold;
            color: #495057;
            width: 50%;
        }
        .value {
            display: table-cell;
            color: #212529;
            text-align: right;
        }
        .total {
            font-size: 20px;
            font-weight: bold;
            color: #20c997;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
        }
        .grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-top: 30px;
            padding: 20px;
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            text-align: center;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
            font-family: "Courier New", monospace;
            font-size: 20px;
            letter-spacing: 2px;
            padding: 12px;
            background: #f8f9fa;
            border: 2px dashed #0d6efd;
        }
        .important {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .booking-separator {
            margin: 40px 0;
            border-top: 3px dashed #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🚗 VroomVroom</div>
        <div style="color: #6c757d;">Carpooling Platform</div>
    </div>
    
    <h1 class="title">Booking Confirmation - Multiple Journeys</h1>
    <p style="text-align: center; color: #6c757d;">You have booked ' . count($bookings) . ' journey(s)</p>
    ';
    
    // Générer chaque réservation
    foreach ($bookings as $index => $booking) {
        if ($index > 0) {
            $html .= '<div class="booking-separator"></div>';
        }
        
        $barcode = strtoupper(substr(md5($booking['idBooking'] . time()), 0, 12));
        $carModel = !empty($booking['car_model']) ? htmlspecialchars($booking['car_model']) : '';
        $carImmat = !empty($booking['car_immat']) ? htmlspecialchars($booking['car_immat']) : '';
        
        $passengerName = htmlspecialchars(($booking['passenger_firstName'] ?? '') . ' ' . ($booking['passenger_lastName'] ?? ''));
        $driverName = htmlspecialchars(($booking['driver_firstName'] ?? '') . ' ' . ($booking['driver_lastName'] ?? ''));
        
        $html .= '
    <h2 style="color: #0d6efd; margin-top: 30px;">Journey #' . ($index + 1) . '</h2>
    
    <div class="barcode">
        * ' . $barcode . ' *
    </div>
    
    <div class="section">
        <div class="section-title">📋 Booking Information</div>
        <div class="detail-row">
            <span class="label">Booking ID:</span>
            <span class="value">#' . htmlspecialchars($booking['idBooking']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Booking Date:</span>
            <span class="value">' . date('d/m/Y H:i') . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Passenger:</span>
            <span class="value">' . $passengerName . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Passenger CIN:</span>
            <span class="value">' . htmlspecialchars($booking['passenger_cin']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Passenger Phone:</span>
            <span class="value">' . htmlspecialchars($booking['passenger_phone'] ?? 'N/A') . '</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">🚗 Journey Details</div>
        <div class="detail-row">
            <span class="label">From:</span>
            <span class="value">' . htmlspecialchars($booking['dep_city']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">To:</span>
            <span class="value">' . htmlspecialchars($booking['dest_city']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Departure Date:</span>
            <span class="value">' . htmlspecialchars($booking['depDate']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Departure Time:</span>
            <span class="value">' . htmlspecialchars($booking['depTime']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Number of Seats:</span>
            <span class="value">' . htmlspecialchars($booking['requestedSeats']) . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Price per Seat:</span>
            <span class="value">' . htmlspecialchars($booking['price']) . ' DT</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">👤 Driver Information</div>
        <div class="detail-row">
            <span class="label">Driver Name:</span>
            <span class="value">' . $driverName . '</span>
        </div>
        <div class="detail-row">
            <span class="label">Driver Phone:</span>
            <span class="value">' . htmlspecialchars($booking['driver_phone']) . '</span>
        </div>';
        
        if ($carModel) {
            $html .= '
        <div class="detail-row">
            <span class="label">Car Model:</span>
            <span class="value">' . $carModel . '</span>
        </div>';
        }
        
        if ($carImmat) {
            $html .= '
        <div class="detail-row">
            <span class="label">License Plate:</span>
            <span class="value">' . $carImmat . '</span>
        </div>';
        }
        
        $html .= '
    </div>
    
    <div class="section">
        <div class="section-title">💰 Payment Summary</div>
        <div class="detail-row">
            <span class="label">Seats Booked:</span>
            <span class="value">' . htmlspecialchars($booking['requestedSeats']) . ' × ' . htmlspecialchars($booking['price']) . ' DT</span>
        </div>
        <div class="total">
            <div class="detail-row">
                <span>JOURNEY TOTAL:</span>
                <span>' . number_format($booking['totalPrice'], 2) . ' DT</span>
            </div>
        </div>
    </div>';
    }
    
    // Grand total si plusieurs réservations
    if (count($bookings) > 1) {
        $html .= '
    <div class="grand-total">
        <div style="font-size: 18px; margin-bottom: 10px;">GRAND TOTAL FOR ALL JOURNEYS</div>
        <div style="font-size: 32px;">' . number_format($grandTotal, 2) . ' DT</div>
    </div>';
    }
    
    $html .= '
    <div class="important">
        <strong>⚠️ Important Information:</strong><br>
        • Please arrive 10 minutes before departure time<br>
        • Contact the driver if you have any questions<br>
        • Keep this confirmation for your records<br>
        • In case of cancellation, contact support
    </div>
    
    <div class="footer">
        <p><strong>VroomVroom - Carpooling Platform</strong></p>
        <p>This is an official booking confirmation</p>
        <p>Generated on ' . date('d/m/Y à H:i:s') . '</p>
        <p>For support: support@vroomvroom.com</p>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
    
    echo $html;
}
