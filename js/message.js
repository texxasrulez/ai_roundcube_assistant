(function () {
  if (!window.rcmail || !window.aiAssistant) {
    return;
  }

  function uidPayload() {
    return {
      _uid: currentUid(),
      _mbox: currentMailbox()
    };
  }

  function currentUid() {
    return firstValue([
      rcmail.env.uid,
      rcmail.env.message_uid,
      rcmail.env._uid,
      queryValue('_uid'),
      formValue('_uid'),
      selectedMessageUid()
    ]);
  }

  function currentMailbox() {
    return firstValue([
      rcmail.env.mailbox,
      rcmail.env.mbox,
      rcmail.env._mbox,
      queryValue('_mbox'),
      formValue('_mbox'),
      'INBOX'
    ]);
  }

  function firstValue(values) {
    for (var i = 0; i < values.length; i++) {
      if (values[i] !== undefined && values[i] !== null && String(values[i]) !== '') {
        return values[i];
      }
    }
    return '';
  }

  function queryValue(name) {
    var match = new RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search || '');
    return match ? decodeURIComponent(match[1].replace(/\+/g, ' ')) : '';
  }

  function formValue(name) {
    return $('input[name="' + name + '"], textarea[name="' + name + '"]').first().val() || '';
  }

  function selectedMessageUid() {
    var selection;
    if (rcmail.message_list && typeof rcmail.message_list.get_single_selection === 'function') {
      selection = rcmail.message_list.get_single_selection();
      if (selection) {
        return normalizeUid(selection);
      }
    }
    if (rcmail.message_list && typeof rcmail.message_list.get_selection === 'function') {
      selection = rcmail.message_list.get_selection();
      if (selection && selection.length) {
        return normalizeUid(selection[0]);
      }
    }
    return '';
  }

  function normalizeUid(value) {
    value = String(value || '');
    return value.indexOf('rcmrow') === 0 ? value.substring(6) : value;
  }

  function addButton(id, label, iconClass, handler) {
    var toolbar = $('#messagetoolbar, #toolbar, .toolbar').first();
    if (!toolbar.length || $('#' + id).length) {
      return;
    }
    $('<a href="#" class="button ai-assistant-toolbar ' + iconClass + '" id="' + id + '" title="' + window.aiAssistant.escapeText(label) + '"><span>' + window.aiAssistant.escapeText(label) + '</span></a>')
      .on('click', function (event) {
        event.preventDefault();
        handler();
      })
      .appendTo(toolbar);
  }

  rcmail.addEventListener('init', function () {
    addButton('ai-assistant-summarize', 'Summarize', 'ai-assistant-toolbar-summarize', function () {
      window.aiAssistant.postAsync('ai_assistant.summarize', uidPayload());
    });
    addButton('ai-assistant-threat', 'Threat scan', 'ai-assistant-toolbar-threat', function () {
      window.aiAssistant.postAsync('ai_assistant.threat_scan', uidPayload());
    });
    addButton('ai-assistant-actions', 'Actions', 'ai-assistant-toolbar-actions', function () {
      window.aiAssistant.postAsync('ai_assistant.extract_actions', uidPayload());
    });
    addButton('ai-assistant-priority', 'Prioritize', 'ai-assistant-toolbar-priority', function () {
      window.aiAssistant.postAsync('ai_assistant.prioritize', uidPayload());
    });
  });
})();
