(function () {
  if (!window.rcmail) {
    return;
  }

  var panel;
  var pendingJobs = {};

  function escapeText(value) {
    return $('<div/>').text(value == null ? '' : String(value)).html();
  }

  function ensurePanel() {
    if (panel && panel.length) {
      return panel;
    }
    panel = $('<div id="ai-assistant-panel" class="ai-assistant-panel" aria-live="polite"></div>');
    panel.append('<div class="ai-assistant-panel-head"><strong>AI Roundcube Assistant</strong><button type="button" class="ai-assistant-close">×</button></div>');
    panel.append('<div class="ai-assistant-panel-body"></div>');
    $('body').append(panel);
    panel.on('click', '.ai-assistant-close', function () {
      panel.removeClass('is-open');
    });
    return panel;
  }

  function showLoading(label) {
    ensurePanel().addClass('is-open').find('.ai-assistant-panel-body').html('<p class="ai-assistant-muted">' + escapeText(label || 'Working...') + '</p>');
  }

  function showError(message) {
    ensurePanel().addClass('is-open').find('.ai-assistant-panel-body').html('<div class="ai-assistant-error">' + escapeText(message) + '</div>');
  }

  function renderResult(response, insertHandler) {
    if (!response || !response.ok) {
      showError(response && response.error ? response.error : 'AI request failed.');
      return;
    }

    var data = response.data || {};
    var result = normalizeResult(data.result || {});
    var body = $('<div/>');
    body.append('<div class="ai-assistant-badge">' + escapeText(data.badge || data.provider || 'AI') + '</div>');

    body.append(renderStructuredResult(result, data));

    if (typeof insertHandler === 'function') {
      var insert = $('<button type="button" class="button mainaction ai-assistant-insert">Insert approved text</button>');
      insert.on('click', function () {
        insertHandler(result);
      });
      body.append(insert);
    }

    ensurePanel().addClass('is-open').find('.ai-assistant-panel-body').empty().append(body);
  }

  function renderStructuredResult(result, data) {
    var content = $('<div class="ai-assistant-result"></div>');

    if (result.risk_level || result.red_flags || result.safe_actions || result.suspicious_links || result.suspicious_attachments) {
      return renderThreat(result, data);
    }
    if (result.summary || result.bullets || result.timeline) {
      return renderSummary(result);
    }
    if (result.tasks || result.due_dates || result.requested_replies || result.meetings || result.payments || result.documents) {
      return renderActions(result);
    }
    if (result.priority && result.reason) {
      return renderPriority(result);
    }
    if (result.body || result.text) {
      content.append('<div class="ai-assistant-section"><div class="ai-assistant-section-title">Draft</div><div class="ai-assistant-text"></div></div>');
      content.find('.ai-assistant-text').text(formatResult(result));
      return content;
    }

    content.append('<pre class="ai-assistant-output"></pre>');
    content.find('pre').text(formatResult(result));
    return content;
  }

  function normalizeResult(result) {
    if (result && typeof result.text === 'string') {
      var parsed = parseJsonish(result.text);
      if (parsed) {
        return parsed;
      }
    }
    return result || {};
  }

  function parseJsonish(text) {
    var value = $.trim(text || '');
    var parsed;

    parsed = tryJson(value);
    if (parsed) {
      return parsed;
    }

    if (value.indexOf('{') !== -1 && value.lastIndexOf('}') !== -1) {
      parsed = tryJson(value.substring(value.indexOf('{'), value.lastIndexOf('}') + 1));
      if (parsed) {
        return parsed;
      }
    }

    if (value.slice(-2) === '}}') {
      parsed = tryJson(value.slice(0, -1));
      if (parsed) {
        return parsed;
      }
    }

    return null;
  }

  function tryJson(text) {
    try {
      var parsed = JSON.parse(text);
      return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (error) {
      return null;
    }
  }

  function renderSummary(result) {
    var content = $('<div class="ai-assistant-result"></div>');
    addTextSection(content, 'Summary', result.summary || 'No summary was generated.');
    addListSection(content, 'Key Points', result.bullets);
    addListSection(content, 'Timeline', result.timeline);
    addListSection(content, 'Action Items', result.actions);
    return content;
  }

  function renderThreat(result, data) {
    var content = $('<div class="ai-assistant-result"></div>');
    var level = String(result.risk_level || 'Unknown').toLowerCase();
    content.append('<div class="ai-threat ai-threat-' + escapeText(level) + '"><strong>' + escapeText(result.risk_level || 'Unknown risk') + '</strong><span></span></div>');
    content.find('.ai-threat span').text(result.summary ? ' ' + result.summary : '');
    if (result.confidence) {
      addTextSection(content, 'Confidence', result.confidence);
    }
    addListSection(content, 'Red Flags', result.red_flags);
    addListSection(content, 'Suspicious Links', result.suspicious_links);
    addListSection(content, 'Suspicious Attachments', result.suspicious_attachments);
    addListSection(content, 'Safer Next Steps', result.safe_actions);
    addListSection(content, 'Local Heuristics', data.heuristics);
    return content;
  }

  function renderActions(result) {
    var content = $('<div class="ai-assistant-result"></div>');
    if (result.priority) {
      addTextSection(content, 'Priority', result.priority);
    }
    addListSection(content, 'Tasks', result.tasks);
    addListSection(content, 'Due Dates', result.due_dates);
    addListSection(content, 'Requested Replies', result.requested_replies);
    addListSection(content, 'Meetings', result.meetings);
    addListSection(content, 'Payments', result.payments);
    addListSection(content, 'Documents', result.documents);
    return content;
  }

  function renderPriority(result) {
    var content = $('<div class="ai-assistant-result"></div>');
    content.append('<div class="ai-priority"><strong></strong></div>');
    content.find('.ai-priority strong').text(result.priority || 'Normal');
    addTextSection(content, 'Reason', result.reason || 'No reason provided.');
    return content;
  }

  function addTextSection(container, title, text) {
    if (text == null || text === '') {
      return;
    }
    var section = $('<div class="ai-assistant-section"><div class="ai-assistant-section-title"></div><div class="ai-assistant-text"></div></div>');
    section.find('.ai-assistant-section-title').text(title);
    section.find('.ai-assistant-text').text(String(text));
    container.append(section);
  }

  function addListSection(container, title, items) {
    if (!items || !items.length) {
      return;
    }
    var section = $('<div class="ai-assistant-section"><div class="ai-assistant-section-title"></div><ul class="ai-assistant-list"></ul></div>');
    section.find('.ai-assistant-section-title').text(title);
    $.each(items, function (_, item) {
      section.find('ul').append($('<li/>').text(formatListItem(item)));
    });
    container.append(section);
  }

  function formatListItem(item) {
    if (item == null) {
      return '';
    }
    if (typeof item === 'object') {
      return Object.keys(item).map(function (key) {
        return key + ': ' + item[key];
      }).join(', ');
    }
    return String(item);
  }

  function formatResult(result) {
    if (result.body) {
      return (result.subject ? 'Subject: ' + result.subject + "\n\n" : '') + result.body;
    }
    if (result.text) {
      return result.text;
    }
    return JSON.stringify(result, null, 2);
  }

  function post(action, payload, callback) {
    payload = payload || {};
    showLoading('Contacting AI provider...');
    rcmail.http_post('plugin.' + action, payload, false);
    window.aiAssistantPendingCallback = callback;
  }

  function postAsync(action, payload, callback) {
    payload = payload || {};
    showLoading('Starting AI job...');
    jsonPost(action + '_async', payload, function (response) {
      if (!response || !response.ok) {
        showError(response && response.error ? response.error : 'AI job could not be started.');
        return;
      }
      pendingJobs[response.job_id] = {
        callback: callback,
        started: new Date().getTime()
      };
      pollJob(response.job_id);
    });
  }

  function pollJob(jobId) {
    var job = pendingJobs[jobId];
    if (!job) {
      return;
    }

    var elapsed = Math.max(1, Math.round((new Date().getTime() - job.started) / 1000));
    showLoading('AI is still working... ' + elapsed + 's');

    jsonPost('ai_assistant.job_status', { job_id: jobId }, function (response) {
      if (!response || !response.ok) {
        delete pendingJobs[jobId];
        showError(response && response.error ? response.error : 'AI job status could not be loaded.');
        return;
      }

      var status = response.job || {};
      if (status.status === 'done') {
        delete pendingJobs[jobId];
        renderResult({ ok: true, data: status.data }, job.callback);
        return;
      }

      if (status.status === 'failed') {
        delete pendingJobs[jobId];
        showError(status.error || 'AI job failed.');
        return;
      }

      window.setTimeout(function () {
        pollJob(jobId);
      }, 2500);
    }, function () {
      window.setTimeout(function () {
        pollJob(jobId);
      }, 4000);
    });
  }

  function jsonPost(action, payload, success, failure) {
    payload = payload || {};
    if (rcmail.env && rcmail.env.request_token) {
      payload._token = rcmail.env.request_token;
    }

    $.ajax({
      type: 'POST',
      url: actionUrl(action),
      data: payload,
      dataType: 'json',
      success: success,
      error: failure || function () {
        showError('AI request failed.');
      }
    });
  }

  function actionUrl(action) {
    if (typeof rcmail.url === 'function') {
      return rcmail.url('plugin.' + action);
    }
    return './?_task=' + encodeURIComponent((rcmail.env && rcmail.env.task) || 'mail') + '&_action=plugin.' + encodeURIComponent(action);
  }

  rcmail.addEventListener('plugin.ai_assistant_response', function (response) {
    renderResult(response, window.aiAssistantPendingCallback);
    window.aiAssistantPendingCallback = null;
  });

  window.aiAssistant = {
    post: post,
    postAsync: postAsync,
    showError: showError,
    renderResult: renderResult,
    escapeText: escapeText
  };
})();
