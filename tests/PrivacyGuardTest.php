<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../lib/AiProviderInterface.php';
require_once __DIR__ . '/../lib/services/PrivacyGuard.php';

final class PrivacyGuardTest extends TestCase
{
    public function testBlocksCloudEmailBodyByDefault()
    {
        $guard = new AiAssistant_PrivacyGuard(array(
            'ai_assistant_allow_cloud_email_body' => false,
            'ai_assistant_allow_cloud_attachments' => false,
        ));

        $this->expectException(RuntimeException::class);
        $guard->assertAllowed(new TestCloudProvider(), array('contains_email_body' => true));
    }

    public function testAllowsLocalEmailBody()
    {
        $guard = new AiAssistant_PrivacyGuard(array(
            'ai_assistant_allow_cloud_email_body' => false,
            'ai_assistant_allow_cloud_attachments' => false,
        ));

        $guard->assertAllowed(new TestLocalProvider(), array('contains_email_body' => true));
        $this->assertTrue(true);
    }
}

class TestCloudProvider implements AiAssistant_AiProviderInterface
{
    public function generate(array $messages, array $options = array()) { return ''; }
    public function name() { return 'Cloud'; }
    public function model() { return 'test'; }
    public function isCloud() { return true; }
}

class TestLocalProvider extends TestCloudProvider
{
    public function isCloud() { return false; }
}
