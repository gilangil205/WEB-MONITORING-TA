<?php

namespace Tests\Unit;

use App\Http\Controllers\SensorController;
use ReflectionClass;
use Exception;

class FuzzyAndDecisionRuleTest
{
    private SensorController $controller;

    public function setUp(): void
    {
        $this->controller = new SensorController();
    }

    private function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception("Assertion Failed! Expected: '" . var_export($expected, true) . "', Actual: '" . var_export($actual, true) . "'. " . $message);
        }
    }

    private function invokeGetStatus(float $nilai): array
    {
        $reflection = new ReflectionClass(SensorController::class);
        $method = $reflection->getMethod('getStatus');
        $method->setAccessible(true);
        return $method->invoke($this->controller, $nilai);
    }

    /**
     * Uji Klasifikasi Fuzzy Sugeno (Tingkat Risiko Lingkungan)
     */
    public function test_fuzzy_classification_thresholds()
    {
        $tw = \App\Models\ThresholdSetting::getValue('threshold_waspada', 0.45);
        $th = \App\Models\ThresholdSetting::getValue('threshold_hama', 0.70);

        // 0.00 -> RENDAH
        [$status0] = $this->invokeGetStatus(0.00);
        $this->assertEquals('RENDAH', $status0, 'Fuzzy 0.00');

        // Nilai tepat di bawah threshold waspada -> RENDAH
        [$statusBelow] = $this->invokeGetStatus($tw - 0.0001);
        $this->assertEquals('RENDAH', $statusBelow, "Fuzzy " . ($tw - 0.0001));

        // Nilai tepat pada threshold waspada -> SEDANG
        [$statusAtTw] = $this->invokeGetStatus($tw);
        $this->assertEquals('SEDANG', $statusAtTw, "Fuzzy {$tw}");

        // Nilai tepat di bawah threshold hama -> SEDANG
        [$statusBelowTh] = $this->invokeGetStatus($th - 0.0001);
        $this->assertEquals('SEDANG', $statusBelowTh, "Fuzzy " . ($th - 0.0001));

        // Nilai tepat pada threshold hama -> TINGGI
        [$statusAtTh] = $this->invokeGetStatus($th);
        $this->assertEquals('TINGGI', $statusAtTh, "Fuzzy {$th}");

        // 1.00 -> TINGGI
        [$status5] = $this->invokeGetStatus(1.00);
        $this->assertEquals('TINGGI', $status5, 'Fuzzy 1.00');
    }

    /**
     * Uji Penggabungan Keputusan (Decision Rule)
     */
    public function test_decision_rule_combinations()
    {
        // YOLO ON + RENDAH -> HAMA
        $this->assertEquals('HAMA', $this->controller->getSystemDecision('ON', 'RENDAH'), 'ON + RENDAH');

        // YOLO ON + SEDANG -> HAMA
        $this->assertEquals('HAMA', $this->controller->getSystemDecision('ON', 'SEDANG'), 'ON + SEDANG');

        // YOLO ON + TINGGI -> HAMA
        $this->assertEquals('HAMA', $this->controller->getSystemDecision('ON', 'TINGGI'), 'ON + TINGGI');

        // YOLO OFF + RENDAH -> RENDAH
        $this->assertEquals('RENDAH', $this->controller->getSystemDecision('OFF', 'RENDAH'), 'OFF + RENDAH');

        // YOLO OFF + SEDANG -> SEDANG
        $this->assertEquals('SEDANG', $this->controller->getSystemDecision('OFF', 'SEDANG'), 'OFF + SEDANG');

        // YOLO OFF + TINGGI -> TINGGI (Fuzzy TINGGI TIDAK BISA menghasilkan HAMA!)
        $this->assertEquals('TINGGI', $this->controller->getSystemDecision('OFF', 'TINGGI'), 'OFF + TINGGI');

        // YOLO null + RENDAH -> RENDAH
        $this->assertEquals('RENDAH', $this->controller->getSystemDecision(null, 'RENDAH'), 'null + RENDAH');

        // YOLO null + SEDANG -> SEDANG
        $this->assertEquals('SEDANG', $this->controller->getSystemDecision(null, 'SEDANG'), 'null + SEDANG');

        // YOLO null + TINGGI -> TINGGI
        $this->assertEquals('TINGGI', $this->controller->getSystemDecision(null, 'TINGGI'), 'null + TINGGI');

        // YOLO string kosong + TINGGI -> TINGGI
        $this->assertEquals('TINGGI', $this->controller->getSystemDecision('', 'TINGGI'), "'' + TINGGI");

        // YOLO invalid + TINGGI -> TINGGI
        $this->assertEquals('TINGGI', $this->controller->getSystemDecision('INVALID', 'TINGGI'), 'INVALID + TINGGI');
    }
}
