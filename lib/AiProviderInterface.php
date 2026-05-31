<?php

interface AiAssistant_AiProviderInterface
{
    public function generate(array $messages, array $options = array());

    public function name();

    public function model();

    public function isCloud();
}
