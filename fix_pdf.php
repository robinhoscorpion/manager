<?php
$content = file_get_contents('app/Http/Controllers/SalesServiceController.php');
$target = <<<EOF
        $formatPaymentType = function ($type) {
            $map = [
                'credit_card' => 'Cartão de Crédito',
                'debit_card' => 'Cartão de Débito',
                'pix' => 'PIX',
                'boleto' => 'Boleto Bancário',
                'cash' => 'Dinheiro',
                'bank_transfer' => 'Transferência Bancária'
            ];
            return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
        };
EOF;
$replacement = <<<EOF
        $formatPaymentType = function ($type, $name = null) {
            if ($name) {
                $lowerName = mb_strtolower($name, 'UTF-8');
                if (str_contains($lowerName, 'cartão de credito') || str_contains($lowerName, 'cartão de crédito')) return 'Cartão de Crédito';
                if (str_contains($lowerName, 'boleto')) return 'Boleto Bancário';
                if (str_contains($lowerName, 'pix')) return 'PIX';
            }
            $map = [
                'credit_card' => 'Cartão de Crédito',
                'debit_card' => 'Cartão de Débito',
                'pix' => 'PIX',
                'boleto' => 'Boleto Bancário',
                'cash' => 'Dinheiro',
                'bank_transfer' => 'Transferência Bancária'
            ];
            return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
        };
EOF;
$content = str_replace($target, $replacement, $content);

// Update calls to formatPaymentType inside buildPaymentSummary
$target2 = <<<EOF
                $formattedType = $formatPaymentType($methodType);
EOF;
$replacement2 = <<<EOF
                $formattedType = $formatPaymentType($methodType, $methodName);
EOF;
$content = str_replace($target2, $replacement2, $content);

// Update calls for CONTRATO_FORMA_PAGAMENTO_ENTRADA etc
$content = preg_replace(
    '/\$formatPaymentType\(\$paymentMethodsMap\[(\$proposal->payments->where\(\'category\', \'[a-z]+\'\)->first\(\)\?->payment_method)\] \?\? (.*?)\)/', 
    '$formatPaymentType($paymentMethodsMap[] ?? , )', 
    $content
);


file_put_contents('app/Http/Controllers/SalesServiceController.php', $content);
echo 'Replaced: ' . (strpos($content, '$name = null') !== false ? 'Yes' : 'No');
