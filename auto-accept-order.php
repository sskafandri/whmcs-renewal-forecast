<?php

use WHMCS\Database\Capsule;

add_hook('InvoicePaid', 1, function ($vars) {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);
    if (!$invoiceId) {
        logActivity('InvoicePaid hook: missing invoiceid in $vars');
        return;
    }

    // Try to resolve the related order id safely
    $orderId = Capsule::table('tblorders')
        ->where('invoiceid', '=', $invoiceId)
        ->value('id');

    if (!$orderId) {
        // No directly-linked order; nothing to accept
        logActivity("InvoicePaid hook: no order linked to invoice #{$invoiceId}");
        return;
    }

    // ------ OPTIONAL THRESHOLD GATE ------
    // If you want to gate on amount, use the API to read totals reliably.
    try {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId], '');
        if (($invoice['result'] ?? '') !== 'success') {
            logActivity("InvoicePaid hook: GetInvoice failed for #{$invoiceId} - " . ($invoice['message'] ?? 'unknown'));
            // Bail or proceed (choose your policy). We'll proceed here.
        } else {
            // Example: accept only if total <= 1 (or change to >=), using the invoice currency/values WHMCS returns.
            $thresholdEnabled = false; // set true to enable
            $operator = '<=';          // or '>='
            $threshold = 1.00;         // your threshold

            if ($thresholdEnabled) {
                $total  = (float) ($invoice['total']  ?? 0);
                $credit = (float) ($invoice['credit'] ?? 0);
                $val = max($total, $credit); // mimic the original logic that checked either column

                $pass = ($operator === '<=') ? ($val <= $threshold) : ($val >= $threshold);
                if (!$pass) {
                    logActivity("InvoicePaid hook: threshold check failed (val={$val} {$operator} {$threshold}) on invoice #{$invoiceId}");
                    return;
                }
            }
        }
    } catch (\Throwable $e) {
        logActivity('InvoicePaid hook: GetInvoice exception - ' . $e->getMessage());
        // Decide if you want to stop here or continue accepting
    }

    // Accept the order with explicit flags
    try {
        $post = [
            'orderid'   => (int) $orderId,
            'autosetup' => true,
            'sendemail' => true,
        ];
        $res = localAPI('AcceptOrder', $post, ''); // admin username optional ≥ 7.2

        if (($res['result'] ?? '') !== 'success') {
            logActivity("InvoicePaid hook: AcceptOrder failed for order #{$orderId} - " . ($res['message'] ?? 'unknown'));
        }
    } catch (\Throwable $e) {
        logActivity("InvoicePaid hook: exception on AcceptOrder for order #{$orderId} - " . $e->getMessage());
    }
});

add_hook('AfterProductUpgrade', 1, function ($vars) {
    $upgradeId = (int) ($vars['upgradeid'] ?? 0);
    if (!$upgradeId) {
        logActivity('AfterProductUpgrade hook: missing upgradeid in $vars');
        return;
    }

    // Resolve the order created for this upgrade
    $orderId = Capsule::table('tblupgrades')
        ->where('id', '=', $upgradeId)
        ->value('orderid');

    if (!$orderId) {
        logActivity("AfterProductUpgrade hook: no order linked to upgrade #{$upgradeId}");
        return;
    }

    // (Optional) If you also want to gate by the related invoice amount for the upgrade, you can
    // resolve the invoice via tblorders->invoiceid as above and call GetInvoice.

    try {
        $res = localAPI('AcceptOrder', [
            'orderid'   => (int) $orderId,
            'autosetup' => true,
            'sendemail' => true,
        ], '');

        if (($res['result'] ?? '') !== 'success') {
            logActivity("AfterProductUpgrade hook: AcceptOrder failed for order #{$orderId} - " . ($res['message'] ?? 'unknown'));
        }
    } catch (\Throwable $e) {
        logActivity("AfterProductUpgrade hook: exception on AcceptOrder for order #{$orderId} - " . $e->getMessage());
    }
});
