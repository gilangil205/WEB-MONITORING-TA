<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Unit/FuzzyAndDecisionRuleTest.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "==================================================\n";
echo "    RUNNING FUZZY & DECISION RULE UNIT TESTS      \n";
echo "==================================================\n\n";



$test = new Tests\Unit\FuzzyAndDecisionRuleTest();
$methods = ['test_fuzzy_classification_thresholds', 'test_decision_rule_combinations'];

$passCount = 0;
$failCount = 0;

foreach ($methods as $method) {
    try {
        $test->setUp();
        $test->$method();
        echo "✅ [PASS] {$method}\n";
        $passCount++;
    } catch (\Throwable $e) {
        echo "❌ [FAIL] {$method}: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "\n--------------------------------------------------\n";
echo "Result: {$passCount} Passed, {$failCount} Failed.\n";
echo "==================================================\n";

if ($failCount > 0) {
    exit(1);
}
