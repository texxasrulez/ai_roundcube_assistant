(function () {
  if (!window.rcmail || !window.aiAssistant) {
    return;
  }

  function getEditorText() {
    if (window.tinyMCE && tinyMCE.activeEditor) {
      return tinyMCE.activeEditor.getContent({ format: 'text' });
    }
    return $('#composebody').val() || '';
  }

  function insertText(text) {
    if (!text) {
      return;
    }
    if (window.tinyMCE && tinyMCE.activeEditor) {
      tinyMCE.activeEditor.execCommand('mceInsertContent', false, window.aiAssistant.escapeText(text).replace(/\n/g, '<br>'));
      return;
    }
    var body = $('#composebody');
    body.val((body.val() || '') + "\n" + text).trigger('change');
  }

  function promptForDraft() {
    var promptText = window.prompt('Describe the email you want to draft.');
    if (!promptText) {
      return;
    }
    window.aiAssistant.postAsync('ai_assistant.compose', { prompt: promptText, context: composeContext() }, function (result) {
      insertText(result.body || result.text || '');
      if (result.subject && $('#compose-subject').length && !$('#compose-subject').val()) {
        $('#compose-subject').val(result.subject);
      }
    });
  }

  function rewrite(instruction) {
    var selected = window.getSelection ? String(window.getSelection()) : '';
    var text = selected || getEditorText();
    if (!text) {
      window.aiAssistant.showError('Select text or type a draft first.');
      return;
    }
    window.aiAssistant.postAsync('ai_assistant.rewrite', { text: text, instruction: instruction }, function (result) {
      insertText(result.text || result.body || '');
    });
  }

  function composeContext() {
    var subject = $('#compose-subject').val() || '';
    var to = $('#_to, textarea[name=\"_to\"], input[name=\"_to\"]').val() || '';
    return truncateText('Subject: ' + subject + "\nTo: " + to, 600);
  }

  function truncateText(text, limit) {
    text = String(text || '');
    return text.length > limit ? text.substring(0, limit) : text;
  }

  function addControls() {
    var toolbar = $('#compose-toolbar, #composebuttons, .composebuttons, .toolbar').first();
    if (!toolbar.length || $('#ai-assistant-compose').length) {
      return;
    }
    var group = $('<span class="ai-assistant-compose-tools" id="ai-assistant-compose"></span>');
    addComposeButton(group, 'ai-assistant-compose-draft', 'AI draft', promptForDraft);
    addComposeButton(group, 'ai-assistant-compose-rewrite', 'Rewrite', function () { rewrite('improve_clarity'); });
    addComposeButton(group, 'ai-assistant-compose-shorter', 'Shorter', function () { rewrite('make_shorter'); });
    addComposeButton(group, 'ai-assistant-compose-professional', 'Professional', function () { rewrite('make_more_professional'); });
    toolbar.append(group);
  }

  function addComposeButton(group, iconClass, label, handler) {
    $('<a href="#" class="button ai-assistant-compose-button ' + iconClass + '" title="' + window.aiAssistant.escapeText(label) + '"><span>' + window.aiAssistant.escapeText(label) + '</span></a>')
      .on('click', function (event) {
        event.preventDefault();
        handler();
      })
      .appendTo(group);
  }

  rcmail.addEventListener('init', addControls);
})();
