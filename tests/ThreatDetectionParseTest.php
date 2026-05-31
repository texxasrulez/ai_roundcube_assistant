<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../lib/AiProviderInterface.php';
require_once __DIR__ . '/../lib/AiProviderFactory.php';
require_once __DIR__ . '/../lib/services/AuditLogService.php';
require_once __DIR__ . '/../lib/services/PrivacyGuard.php';
require_once __DIR__ . '/../lib/services/PromptBuilder.php';
require_once __DIR__ . '/../lib/services/BaseAiService.php';

final class ThreatDetectionParseTest extends TestCase
{
    public function testJsonParseFallbackShapeIsDocumented()
    {
        $reflection = new ReflectionClass(TestBaseAiServiceHarness::class);
        $method = $reflection->getMethod('parseJson');
        $method->setAccessible(true);
        $service = $reflection->newInstanceWithoutConstructor();

        $this->assertSame(array('risk_level' => 'Low'), $method->invoke($service, '{"risk_level":"Low"}'));
        $this->assertSame(array('text' => 'not json'), $method->invoke($service, 'not json'));
    }
}

class TestBaseAiServiceHarness extends AiAssistant_BaseAiService
{
}
