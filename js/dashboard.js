(function () {
  if (!window.rcmail || !window.aiAssistant) {
    return;
  }

  rcmail.addEventListener('init', function () {
    $('#ai-assistant-load-followups').on('click', function () {
      window.aiAssistant.post('ai_assistant.followup_candidates', {}, null);
    });
  });
})();
