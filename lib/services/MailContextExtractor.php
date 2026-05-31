<?php

class AiAssistant_MailContextExtractor
{
    private $rc;
    private $config;

    public function __construct($rc, array $config)
    {
        $this->rc = $rc;
        $this->config = $config;
    }

    public function message($mailbox, $uid)
    {
        $storage = $this->rc->get_storage();
        $storage->set_folder($mailbox);
        $message = $uid ? new rcube_message($uid) : null;
        $headers = $message && $message->headers ? $message->headers : null;
        $body = $message ? $this->messageBody($message) : '';
        $attachments = $message ? $this->attachmentNames($message) : array();
        if ($uid && !$headers && $body === '') {
            throw new RuntimeException('Roundcube could not load the selected message content. Confirm the message is still selected and the mailbox name is correct.');
        }

        return array(
            'mailbox' => $mailbox,
            'uid' => $uid,
            'message_ref' => $mailbox . ':' . $uid,
            'subject' => $headers ? (string) $headers->subject : '',
            'from' => $headers ? (string) $headers->from : '',
            'to' => $headers ? (string) $headers->to : '',
            'date' => $headers ? (string) $headers->date : '',
            'body' => $this->truncate($body),
            'attachments' => $attachments,
            'has_attachments' => count($attachments) > 0,
            'contains_email_body' => $body !== '',
        );
    }

    public function messages($mailbox, array $uids)
    {
        $items = array();
        foreach ($uids as $uid) {
            $uid = trim((string) $uid);
            if ($uid !== '') {
                $items[] = $this->message($mailbox, $uid);
            }
        }

        return array(
            'mailbox' => $mailbox,
            'messages' => $items,
            'contains_email_body' => count($items) > 0,
            'message_ref' => $mailbox . ':' . implode(',', $uids),
        );
    }

    private function messageBody($message)
    {
        if (method_exists($message, 'first_text_part')) {
            $part = $message->first_text_part();
            if ($part) {
                $body = $this->partContent($message, $part);
                if ($body !== '') {
                    return $body;
                }
            }
        }

        if (method_exists($message, 'first_html_part')) {
            $part = $message->first_html_part();
            if ($part) {
                $body = $this->partContent($message, $part, true);
                if ($body !== '') {
                    return $body;
                }
            }
        }

        foreach ($this->messageParts($message) as $part) {
            $type = strtolower($this->partMimeType($part));
            if ($type === 'text/plain') {
                $body = $this->partContent($message, $part);
                if ($body !== '') {
                    return $body;
                }
            }
        }

        foreach ($this->messageParts($message) as $part) {
            $type = strtolower($this->partMimeType($part));
            if ($type === 'text/html') {
                $body = $this->partContent($message, $part, true);
                if ($body !== '') {
                    return $body;
                }
            }
        }

        return '';
    }

    private function partContent($message, $part, $html = false)
    {
        $mimeId = $this->partMimeId($part);
        if ($mimeId === '') {
            return '';
        }

        $content = trim((string) $message->get_part_content($mimeId));
        return $html ? $this->htmlToText($content) : $content;
    }

    private function messageParts($message)
    {
        $parts = array();
        foreach (array('parts', 'mime_parts') as $property) {
            if (!empty($message->{$property}) && is_array($message->{$property})) {
                foreach ($message->{$property} as $part) {
                    $this->collectParts($part, $parts);
                }
            }
        }
        return $parts;
    }

    private function collectParts($part, array &$parts)
    {
        $parts[] = $part;
        $children = array();
        if (is_object($part) && !empty($part->parts) && is_array($part->parts)) {
            $children = $part->parts;
        } elseif (is_array($part) && !empty($part['parts']) && is_array($part['parts'])) {
            $children = $part['parts'];
        }

        foreach ($children as $child) {
            $this->collectParts($child, $parts);
        }
    }

    private function partMimeId($part)
    {
        if (is_object($part) && isset($part->mime_id)) {
            return (string) $part->mime_id;
        }
        if (is_array($part) && isset($part['mime_id'])) {
            return (string) $part['mime_id'];
        }
        return is_scalar($part) ? (string) $part : '';
    }

    private function partMimeType($part)
    {
        if (is_object($part)) {
            if (!empty($part->mimetype)) {
                return (string) $part->mimetype;
            }
            if (!empty($part->ctype_primary) && !empty($part->ctype_secondary)) {
                return $part->ctype_primary . '/' . $part->ctype_secondary;
            }
        }
        if (is_array($part)) {
            if (!empty($part['mimetype'])) {
                return (string) $part['mimetype'];
            }
            if (!empty($part['ctype_primary']) && !empty($part['ctype_secondary'])) {
                return $part['ctype_primary'] . '/' . $part['ctype_secondary'];
            }
        }
        return '';
    }

    private function htmlToText($html)
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', (string) $html);
        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/\s*(p|div|li|tr|h[1-6])\s*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function attachmentNames($message)
    {
        $names = array();
        if (!empty($message->attachments) && is_array($message->attachments)) {
            foreach ($message->attachments as $attachment) {
                if (is_object($attachment) && !empty($attachment->filename)) {
                    $names[] = (string) $attachment->filename;
                } elseif (is_array($attachment) && !empty($attachment['filename'])) {
                    $names[] = (string) $attachment['filename'];
                }
            }
        }

        return $names;
    }

    private function truncate($body)
    {
        $max = max(1000, (int) $this->config['ai_assistant_max_email_chars']);
        if (function_exists('mb_substr')) {
            return mb_substr((string) $body, 0, $max);
        }
        return substr((string) $body, 0, $max);
    }
}
