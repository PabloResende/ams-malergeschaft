<?php
// app/helpers.php
function generateInvoiceNumber() {
    return 'INV' . date('Ymd') . '-' . rand(1000, 9999);
}
