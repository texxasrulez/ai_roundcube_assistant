<?php

/**
 * AI Roundcube Assistant
 *
 * Production-oriented first version of an AI assistant for Roundcube.
 */
class ai_roundcube_assistant extends rcube_plugin
{
    const PLUGIN_VERSION = '0.0.1';
    const PLUGIN_INFO = array(
        'name' => 'ai_roundcube_assistant',
        'vendor' => 'Gene Hawkins',
        'version' => self::PLUGIN_VERSION,
        'license' => 'GPL-3.0',
        'uri' => 'https://github.com/texxasrulez/ai_roundcube_assistant',
    );

    public static function info(): array
    {
        return self::PLUGIN_INFO;
    }
    public $task = '.*';

    private $rc;
    private $config = array();
    private $skin = 'elastic';

    public function init()
    {
        $this->rc = rcmail::get_instance();
        $this->load_config();
        $this->add_texts('localization/', true);
        $this->register_autoload();

        $this->config = $this->load_plugin_config();
        if (empty($this->config['ai_assistant_enabled'])) {
            return;
        }

        $this->skin = $this->detect_skin();
        $this->include_stylesheet($this->skin_css_path($this->skin));
        $this->include_script('js/ai_assistant.js');

        $this->register_action('plugin.ai_assistant.summarize', array($this, 'ajax_summarize'));
        $this->register_action('plugin.ai_assistant.threat_scan', array($this, 'ajax_threat_scan'));
        $this->register_action('plugin.ai_assistant.compose', array($this, 'ajax_compose'));
        $this->register_action('plugin.ai_assistant.rewrite', array($this, 'ajax_rewrite'));
        $this->register_action('plugin.ai_assistant.summarize_async', array($this, 'ajax_summarize_async'));
        $this->register_action('plugin.ai_assistant.threat_scan_async', array($this, 'ajax_threat_scan_async'));
        $this->register_action('plugin.ai_assistant.compose_async', array($this, 'ajax_compose_async'));
        $this->register_action('plugin.ai_assistant.rewrite_async', array($this, 'ajax_rewrite_async'));
        $this->register_action('plugin.ai_assistant.extract_actions_async', array($this, 'ajax_extract_actions_async'));
        $this->register_action('plugin.ai_assistant.prioritize_async', array($this, 'ajax_prioritize_async'));
        $this->register_action('plugin.ai_assistant.job_status', array($this, 'ajax_job_status'));
        $this->register_action('plugin.ai_assistant.extract_actions', array($this, 'ajax_extract_actions'));
        $this->register_action('plugin.ai_assistant.prioritize', array($this, 'ajax_prioritize'));
        $this->register_action('plugin.ai_assistant.followup_candidates', array($this, 'ajax_followup_candidates'));
        $this->register_action('plugin.ai_assistant.digest', array($this, 'ajax_digest'));

        if (!empty($this->config['ai_assistant_enable_dashboard'])) {
            $this->register_task('ai_assistant');
            $this->add_button(array(
                'type' => 'link',
                'class' => $this->rc->task === 'ai_assistant' ? 'button-ai-assistant button-selected' : 'button-ai-assistant',
                'innerclass' => 'button-inner',
                'label' => 'ai_roundcube_assistant.aiassistant',
                'title' => 'ai_roundcube_assistant.aiassistant',
                'href' => './?_task=ai_assistant',
            ), 'taskbar');
        }

        if ($this->rc->task === 'mail') {
            $this->include_mail_assets();
        }

        if ($this->rc->task === 'ai_assistant') {
            $this->register_action('index', array($this, 'dashboard'));
            $this->include_script('js/dashboard.js');
        }
    }

    public function dashboard()
    {
        if (empty($this->config['ai_assistant_enable_dashboard'])) {
            $this->rc->output->show_message('ai_assistant.disabled', 'warning');
            $this->rc->output->send('iframe');
            return;
        }

        $this->rc->output->set_pagetitle($this->gettext('aiassistant'));
        $this->rc->output->send('ai_roundcube_assistant.dashboard');
    }

    public function ajax_summarize()
    {
        $this->run_ajax('ai_assistant_enable_summarization', function () {
            $context = $this->mail_context_from_request();
            $mode = $this->post('mode', 'busy');
            $service = new AiAssistant_SummaryService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->summarize($context, $mode));
        });
    }

    public function ajax_threat_scan()
    {
        $this->run_ajax('ai_assistant_enable_threat_detection', function () {
            $context = $this->mail_context_from_request();
            $service = new AiAssistant_ThreatDetectionService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->scan($context));
        });
    }

    public function ajax_summarize_async()
    {
        try {
            $this->ajax_guard_or_throw('ai_assistant_enable_summarization');
            $this->start_async_ai_job('summarize', array(
                'context' => $this->mail_context_from_request(),
                'mode' => $this->post('mode', 'busy'),
            ), null);
        } catch (Exception $e) {
            $this->raw_json(array('ok' => false, 'error' => $e->getMessage()));
        }
    }

    public function ajax_threat_scan_async()
    {
        try {
            $this->ajax_guard_or_throw('ai_assistant_enable_threat_detection');
            $this->start_async_ai_job('threat_scan', array(
                'context' => $this->mail_context_from_request(),
            ), null);
        } catch (Exception $e) {
            $this->raw_json(array('ok' => false, 'error' => $e->getMessage()));
        }
    }

    public function ajax_compose()
    {
        $this->run_ajax('ai_assistant_enable_compose_tools', function () {
            $payload = array(
                'prompt' => $this->post('prompt', ''),
                'tone' => $this->post('tone', ''),
                'context' => $this->post('context', ''),
            );
            $service = new AiAssistant_ComposeService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->compose($payload));
        });
    }

    public function ajax_rewrite()
    {
        $this->run_ajax('ai_assistant_enable_compose_tools', function () {
            $payload = array(
                'text' => $this->post('text', ''),
                'instruction' => $this->post('instruction', 'improve_clarity'),
                'context' => $this->post('context', ''),
            );
            $service = new AiAssistant_ComposeService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->rewrite($payload));
        });
    }

    public function ajax_compose_async()
    {
        $payload = array(
            'prompt' => $this->post('prompt', ''),
            'tone' => $this->post('tone', ''),
            'context' => $this->post('context', ''),
        );
        $this->start_async_ai_job('compose', $payload, 'ai_assistant_enable_compose_tools');
    }

    public function ajax_rewrite_async()
    {
        $payload = array(
            'text' => $this->post('text', ''),
            'instruction' => $this->post('instruction', 'improve_clarity'),
            'context' => $this->post('context', ''),
        );
        $this->start_async_ai_job('rewrite', $payload, 'ai_assistant_enable_compose_tools');
    }

    public function ajax_job_status()
    {
        try {
            $this->ajax_guard_or_throw(null);
            $job = $this->read_async_job($this->post('job_id', ''));
            if (!$job || (int) $job['user_id'] !== (int) $this->rc->user->ID) {
                $this->raw_json(array('ok' => false, 'error' => 'AI job was not found.'));
            }
            $this->raw_json(array('ok' => true, 'job' => $job));
        } catch (Exception $e) {
            $this->raw_json(array('ok' => false, 'error' => $e->getMessage()));
        }
    }

    public function ajax_extract_actions()
    {
        $this->run_ajax(null, function () {
            $context = $this->mail_context_from_request();
            $service = new AiAssistant_ActionItemService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->extract($context));
        });
    }

    public function ajax_prioritize()
    {
        $this->run_ajax(null, function () {
            $context = $this->mail_context_from_request();
            $service = new AiAssistant_PriorityService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->classify($context));
        });
    }

    public function ajax_extract_actions_async()
    {
        try {
            $this->ajax_guard_or_throw(null);
            $this->start_async_ai_job('extract_actions', array(
                'context' => $this->mail_context_from_request(),
            ), null);
        } catch (Exception $e) {
            $this->raw_json(array('ok' => false, 'error' => $e->getMessage()));
        }
    }

    public function ajax_prioritize_async()
    {
        try {
            $this->ajax_guard_or_throw(null);
            $this->start_async_ai_job('prioritize', array(
                'context' => $this->mail_context_from_request(),
            ), null);
        } catch (Exception $e) {
            $this->raw_json(array('ok' => false, 'error' => $e->getMessage()));
        }
    }

    public function ajax_followup_candidates()
    {
        $this->run_ajax('ai_assistant_enable_followup_tracker', function () {
            $service = new AiAssistant_FollowupService(new AiAssistant_FollowupRepository($this->rc->db), $this->config);
            $this->json_success($service->candidates((int) $this->rc->user->ID));
        });
    }

    public function ajax_digest()
    {
        $this->run_ajax(null, function () {
            $context = $this->selected_messages_context();
            $service = new AiAssistant_DigestService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
            $this->json_success($service->digest($context));
        });
    }

    private function include_mail_assets()
    {
        if ($this->rc->action === 'compose') {
            $this->include_script('js/compose.js');
            return;
        }

        $this->include_script('js/message.js');
    }

    private function register_autoload()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AiAssistant_') !== 0) {
                return;
            }

            $map = array(
                'AiAssistant_AiProviderInterface' => 'lib/AiProviderInterface.php',
                'AiAssistant_AiProviderFactory' => 'lib/AiProviderFactory.php',
                'AiAssistant_BaseAiService' => 'lib/services/BaseAiService.php',
                'AiAssistant_OllamaProvider' => 'lib/providers/OllamaProvider.php',
                'AiAssistant_OpenAIProvider' => 'lib/providers/OpenAIProvider.php',
                'AiAssistant_OpenAICompatibleProvider' => 'lib/providers/OpenAICompatibleProvider.php',
                'AiAssistant_AnthropicProvider' => 'lib/providers/AnthropicProvider.php',
                'AiAssistant_GeminiProvider' => 'lib/providers/GeminiProvider.php',
                'AiAssistant_ComposeService' => 'lib/services/ComposeService.php',
                'AiAssistant_SummaryService' => 'lib/services/SummaryService.php',
                'AiAssistant_ThreatDetectionService' => 'lib/services/ThreatDetectionService.php',
                'AiAssistant_ActionItemService' => 'lib/services/ActionItemService.php',
                'AiAssistant_FollowupService' => 'lib/services/FollowupService.php',
                'AiAssistant_PriorityService' => 'lib/services/PriorityService.php',
                'AiAssistant_DigestService' => 'lib/services/DigestService.php',
                'AiAssistant_AuditLogService' => 'lib/services/AuditLogService.php',
                'AiAssistant_PrivacyGuard' => 'lib/services/PrivacyGuard.php',
                'AiAssistant_PromptBuilder' => 'lib/services/PromptBuilder.php',
                'AiAssistant_MailContextExtractor' => 'lib/services/MailContextExtractor.php',
                'AiAssistant_AnalysisCacheRepository' => 'lib/repositories/AnalysisCacheRepository.php',
                'AiAssistant_FollowupRepository' => 'lib/repositories/FollowupRepository.php',
                'AiAssistant_UserPrefsRepository' => 'lib/repositories/UserPrefsRepository.php',
            );

            if (isset($map[$class])) {
                require_once __DIR__ . '/' . $map[$class];
            }
        });
    }

    private function ajax_guard($feature_key)
    {
        if (!empty($feature_key) && empty($this->config[$feature_key])) {
            $this->json_error($this->gettext('featuredisabled'));
        }

        if (method_exists($this->rc, 'check_request') && !$this->rc->check_request(rcube_utils::INPUT_POST)) {
            $this->json_error($this->gettext('invalidrequest'));
        }
    }

    private function ajax_guard_or_throw($feature_key)
    {
        if (!empty($feature_key) && empty($this->config[$feature_key])) {
            throw new RuntimeException($this->gettext('featuredisabled'));
        }

        if (method_exists($this->rc, 'check_request') && !$this->rc->check_request(rcube_utils::INPUT_POST)) {
            throw new RuntimeException($this->gettext('invalidrequest'));
        }
    }

    private function run_ajax($feature_key, $callback)
    {
        try {
            $this->ajax_guard($feature_key);
            $callback();
        } catch (Exception $e) {
            $this->json_error($e->getMessage());
        }
    }

    private function mail_context_from_request()
    {
        $uid = $this->post('_uid', $this->post('uid', ''));
        $mbox = $this->post('_mbox', $this->post('mbox', 'INBOX'));
        if ((string) $uid === '') {
            throw new RuntimeException('No message UID was provided. Open or select a message, then run the AI action again.');
        }
        $extractor = new AiAssistant_MailContextExtractor($this->rc, $this->config);
        return $extractor->message($mbox, $uid);
    }

    private function selected_messages_context()
    {
        $uids = $this->post('uids', '');
        $mbox = $this->post('mbox', 'INBOX');
        $extractor = new AiAssistant_MailContextExtractor($this->rc, $this->config);
        return $extractor->messages($mbox, is_array($uids) ? $uids : explode(',', $uids));
    }

    private function provider_factory()
    {
        return new AiAssistant_AiProviderFactory($this->config);
    }

    private function audit_log()
    {
        return new AiAssistant_AuditLogService($this->rc->db, $this->config, (int) $this->rc->user->ID);
    }

    private function load_plugin_config()
    {
        return array(
            'ai_assistant_enabled' => (bool) $this->rc->config->get('ai_assistant_enabled', true),
            'ai_assistant_default_provider' => $this->rc->config->get('ai_assistant_default_provider', 'ollama'),
            'ai_assistant_providers' => $this->rc->config->get('ai_assistant_providers', array()),
            'ai_assistant_allow_cloud_email_body' => (bool) $this->rc->config->get('ai_assistant_allow_cloud_email_body', false),
            'ai_assistant_allow_cloud_attachments' => (bool) $this->rc->config->get('ai_assistant_allow_cloud_attachments', false),
            'ai_assistant_allow_external_reputation_checks' => (bool) $this->rc->config->get('ai_assistant_allow_external_reputation_checks', false),
            'ai_assistant_enable_threat_detection' => (bool) $this->rc->config->get('ai_assistant_enable_threat_detection', true),
            'ai_assistant_enable_summarization' => (bool) $this->rc->config->get('ai_assistant_enable_summarization', true),
            'ai_assistant_enable_compose_tools' => (bool) $this->rc->config->get('ai_assistant_enable_compose_tools', true),
            'ai_assistant_enable_followup_tracker' => (bool) $this->rc->config->get('ai_assistant_enable_followup_tracker', true),
            'ai_assistant_enable_dashboard' => (bool) $this->rc->config->get('ai_assistant_enable_dashboard', true),
            'ai_assistant_followup_days' => (int) $this->rc->config->get('ai_assistant_followup_days', 5),
            'ai_assistant_async_timeout' => (int) $this->rc->config->get('ai_assistant_async_timeout', 180),
            'ai_assistant_log_level' => $this->rc->config->get('ai_assistant_log_level', 'warning'),
            'ai_assistant_cache_minutes' => (int) $this->rc->config->get('ai_assistant_cache_minutes', 60),
            'ai_assistant_max_email_chars' => (int) $this->rc->config->get('ai_assistant_max_email_chars', 16000),
        );
    }

    private function detect_skin()
    {
        $skin = (string) $this->rc->config->get('skin', 'elastic');
        return preg_replace('/[^a-z0-9_\\-]/i', '', $skin);
    }

    private function skin_css_path($skin)
    {
        $available = array(
            'elastic', 'larry', 'classic', 'autumn_larry', 'black_larry', 'blue_larry',
            'green_larry', 'grey_larry', 'pink_larry', 'plata_larry', 'summer_larry',
            'teal_larry', 'violet_larry'
        );

        if (in_array($skin, $available, true)) {
            return 'skins/' . $skin . '/ai_assistant.css';
        }

        if (strpos($skin, 'larry') !== false) {
            return 'skins/larry/ai_assistant.css';
        }

        return 'skins/elastic/ai_assistant.css';
    }

    private function post($name, $default = null)
    {
        $value = rcube_utils::get_input_value($name, rcube_utils::INPUT_POST, true);
        return $value === null || $value === '' ? $default : $value;
    }

    private function json_success($payload)
    {
        $this->rc->output->command('plugin.ai_assistant_response', array('ok' => true, 'data' => $payload));
        $this->rc->output->send();
        exit;
    }

    private function json_error($message)
    {
        $this->rc->output->command('plugin.ai_assistant_response', array('ok' => false, 'error' => $message));
        $this->rc->output->send();
        exit;
    }

    private function start_async_ai_job($action, array $payload, $featureKey)
    {
        try {
            $this->ajax_guard_or_throw($featureKey);
            if (!function_exists('fastcgi_finish_request')) {
                $this->raw_json(array('ok' => false, 'error' => 'Async AI jobs require PHP-FPM fastcgi_finish_request support. Use a faster model or raise the webserver FastCGI timeout for synchronous AI requests.'));
            }

            $this->cleanup_async_jobs();
            $jobId = $this->new_async_job_id();
            $job = array(
                'job_id' => $jobId,
                'user_id' => (int) $this->rc->user->ID,
                'action' => $action,
                'status' => 'running',
                'created_at' => time(),
                'updated_at' => time(),
                'data' => null,
                'error' => null,
            );
            $this->write_async_job($jobId, $job);

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $this->raw_json(array('ok' => true, 'job_id' => $jobId, 'status' => 'running'), true);
            $this->run_async_ai_job($jobId, $action, $payload);
        } catch (Exception $e) {
            $this->raw_json(array('ok' => false, 'error' => $e->getMessage()));
        }
        exit;
    }

    private function run_async_ai_job($jobId, $action, array $payload)
    {
        $timeout = max(30, min((int) $this->config['ai_assistant_async_timeout'], 300));
        @set_time_limit($timeout + 30);

        try {
            $options = array('max_tokens' => $this->async_max_tokens($action), 'timeout' => $timeout, 'allow_long_timeout' => true);
            $result = $this->run_async_service_action($action, $payload, $options);

            $job = $this->read_async_job($jobId);
            if ($job) {
                $job['status'] = 'done';
                $job['updated_at'] = time();
                $job['data'] = $result;
                $this->write_async_job($jobId, $job);
            }
        } catch (Exception $e) {
            $job = $this->read_async_job($jobId);
            if ($job) {
                $job['status'] = 'failed';
                $job['updated_at'] = time();
                $job['error'] = $e->getMessage();
                $this->write_async_job($jobId, $job);
            }
        }
    }

    private function run_async_service_action($action, array $payload, array $options)
    {
        switch ($action) {
            case 'compose':
                $service = new AiAssistant_ComposeService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
                return $service->compose($payload, $options);
            case 'rewrite':
                $service = new AiAssistant_ComposeService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
                return $service->rewrite($payload, $options);
            case 'summarize':
                $service = new AiAssistant_SummaryService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
                return $service->summarize($payload['context'], isset($payload['mode']) ? $payload['mode'] : 'busy', $options);
            case 'threat_scan':
                $service = new AiAssistant_ThreatDetectionService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
                return $service->scan($payload['context'], $options);
            case 'extract_actions':
                $service = new AiAssistant_ActionItemService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
                return $service->extract($payload['context'], $options);
            case 'prioritize':
                $service = new AiAssistant_PriorityService($this->provider_factory(), new AiAssistant_PromptBuilder(), new AiAssistant_PrivacyGuard($this->config), $this->audit_log());
                return $service->classify($payload['context'], $options);
        }

        throw new RuntimeException('Unknown async AI action.');
    }

    private function async_max_tokens($action)
    {
        $tokens = array(
            'prioritize' => 400,
            'compose' => 900,
            'rewrite' => 900,
            'summarize' => 900,
            'extract_actions' => 900,
            'threat_scan' => 1000,
        );

        return isset($tokens[$action]) ? $tokens[$action] : 900;
    }

    private function raw_json(array $payload, $finish = false)
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload);
        if ($finish && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }
        exit;
    }

    private function new_async_job_id()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(16));
        }
        return md5(uniqid('', true));
    }

    private function async_job_dir()
    {
        $base = (string) $this->rc->config->get('temp_dir', sys_get_temp_dir());
        $dir = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'ai_roundcube_assistant_jobs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return $dir;
    }

    private function async_job_path($jobId)
    {
        $jobId = preg_replace('/[^a-f0-9]/', '', strtolower((string) $jobId));
        if ($jobId === '') {
            throw new RuntimeException('AI job id is missing.');
        }
        return $this->async_job_dir() . DIRECTORY_SEPARATOR . $jobId . '.json';
    }

    private function read_async_job($jobId)
    {
        $path = $this->async_job_path($jobId);
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    private function write_async_job($jobId, array $job)
    {
        $dir = $this->async_job_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new RuntimeException('AI async job directory is not writable. Check Roundcube temp_dir permissions.');
        }
        file_put_contents($this->async_job_path($jobId), json_encode($job), LOCK_EX);
    }

    private function cleanup_async_jobs()
    {
        $dir = $this->async_job_dir();
        if (!is_dir($dir)) {
            return;
        }

        $expires = time() - 86400;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
            if (is_file($file) && filemtime($file) < $expires) {
                @unlink($file);
            }
        }
    }
}
