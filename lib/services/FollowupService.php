<?php

class AiAssistant_FollowupService
{
    private $repo;
    private $config;

    public function __construct(AiAssistant_FollowupRepository $repo, array $config)
    {
        $this->repo = $repo;
        $this->config = $config;
    }

    public function candidates($userId)
    {
        return array(
            'days' => (int) $this->config['ai_assistant_followup_days'],
            'items' => $this->repo->candidates($userId, (int) $this->config['ai_assistant_followup_days']),
        );
    }
}
