/*
 * The one-to-one chat page shown inside the mobile apps, on Twilio Conversations (classic).
 *
 * The server has already put both people into the conversation and rendered its history. This
 * script signs in with a token for the viewer, finds the conversation by its name, and then
 * sends, receives, shows typing and read marks. It needs nothing but the Twilio SDK.
 */
(function (root) {
  'use strict';

  var page = root.chatPage;
  var doc = root.document;
  var NEW_YORK = 'America/New_York';
  var people = {};
  var client = null;
  var conversation = null;
  var lastTypingSent = 0;

  var list = doc.getElementById('message-list');
  var input = doc.getElementById('input-text');
  var sendButton = doc.getElementById('send_btn');
  var typing = doc.getElementById('typing-placeholder');
  var template = doc.getElementById('message-template');

  /** POSTs a form to the API as the page's viewer and answers with the parsed JSON. */
  function post(path, fields) {
    var body = new URLSearchParams(fields);
    body.set('viewer', page.viewer.viewer);
    body.set('signature', page.viewer.signature);
    return root.fetch(path, { method: 'POST', headers: { Accept: 'application/json' }, body: body }).then(function (response) {
      if (!response.ok) {
        throw new Error(path + ' answered ' + response.status);
      }
      return response.json();
    });
  }

  function fetchToken() {
    return post('/api/chat-token', { identity: page.me }).then(function (answer) {
      return answer.token;
    });
  }

  function loadPerson(identity) {
    return post('/api/users/get', { identity: identity }).then(function (answer) {
      people[identity] = { name: answer.name || '', image: answer.image ? page.baseUrl + answer.image : page.avatar };
    }).catch(function () {
      people[identity] = { name: '', image: page.avatar };
    });
  }

  function partsIn(date) {
    var parts = {};
    new Intl.DateTimeFormat('en-US', { timeZone: NEW_YORK, year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })
      .formatToParts(date).forEach(function (part) { parts[part.type] = part.value; });
    return parts;
  }

  /** "Today - 03:05 PM" or "November 19 - 03:05 PM", in New York time like the history the server rendered. */
  function when(date) {
    var then = partsIn(date);
    var now = partsIn(new Date());
    var sameDay = then.year === now.year && then.month === now.month && then.day === now.day;
    return (sameDay ? 'Today' : then.month + ' ' + then.day) + ' - ' + then.hour + ':' + then.minute + ' ' + then.dayPeriod;
  }

  function scrollToEnd() {
    list.scrollTop = list.scrollHeight;
  }

  function setStatus(text) {
    typing.textContent = text;
  }

  function showMessage(message) {
    var mine = message.author === page.me;
    var person = people[mine ? page.me : page.other] || { name: '', image: page.avatar };
    var row = template.content.firstElementChild.cloneNode(true);
    if (mine) {
      row.classList.add('own-message');
    }
    row.querySelector('[data-part="image"]').src = person.image;
    row.querySelector('[data-part="name"]').textContent = person.name;
    row.querySelector('[data-part="date"]').textContent = when(message.dateCreated);
    row.querySelector('[data-part="body"]').textContent = message.body || '';
    list.appendChild(row);
    scrollToEnd();
  }

  /** Tells the server about a message so it can keep the chat list, unread counts and push notices right. */
  function report(message) {
    var fields = {
      chat_name: page.conversation,
      created: message.dateCreated.toLocaleString('en-US', { timeZone: NEW_YORK }),
      body: message.body || '',
      user: page.me,
    };
    if (message.author !== page.me) {
      fields.not_me_author = '1';
    }
    post('/api/chats/new_message', fields).catch(function (error) {
      root.console.log('The message was not reported: ' + error.message);
    });
  }

  function onMessage(message) {
    showMessage(message);
    report(message);
    conversation.advanceLastReadMessageIndex(message.index).catch(function () {});
  }

  /** The second check mark turns on when the other person has read the messages. */
  function onParticipantUpdated(update) {
    var reasons = update.updateReasons || [];
    var read = reasons.indexOf('lastReadMessageIndex') !== -1 || reasons.indexOf('lastReadTimestamp') !== -1;
    if (read && update.participant.identity === page.other) {
      list.querySelectorAll('.check2').forEach(function (mark) { mark.classList.add('viewed'); });
    }
  }

  function onTypingStarted(participant) {
    var person = people[participant.identity] || { name: '' };
    setStatus((person.name || 'Someone') + ' is typing...');
  }

  function send() {
    var text = input.value.trim();
    if (text === '' || conversation === null) {
      return;
    }
    input.value = '';
    conversation.sendMessage(text).catch(function (error) {
      input.value = text;
      setStatus('The message was not sent. Try again.');
      root.console.log('The message was not sent: ' + error.message);
    });
  }

  function onKey(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      send();
      return;
    }
    var now = Date.now();
    if (conversation !== null && now - lastTypingSent > 1000) {
      lastTypingSent = now;
      conversation.typing();
    }
  }

  function join() {
    return client.getConversationByUniqueName(page.conversation).then(function (found) {
      conversation = found;
      conversation.on('messageAdded', onMessage);
      conversation.on('typingStarted', onTypingStarted);
      conversation.on('typingEnded', function () { setStatus(''); });
      conversation.on('participantUpdated', onParticipantUpdated);
      input.disabled = false;
      setStatus('');
      scrollToEnd();
    });
  }

  function refreshToken() {
    fetchToken().then(function (token) {
      return client.updateToken(token);
    }).catch(function (error) {
      root.console.log('The chat token was not renewed: ' + error.message);
    });
  }

  function failed(error) {
    setStatus('Chat is not available right now.');
    root.console.log('Chat did not start: ' + (error && error.message ? error.message : error));
  }

  function start() {
    scrollToEnd();
    sendButton.addEventListener('click', function (event) { event.preventDefault(); send(); });
    input.addEventListener('keydown', onKey);
    return Promise.all([fetchToken(), loadPerson(page.me), loadPerson(page.other)]).then(function (loaded) {
      client = new root.Twilio.Conversations.Client(loaded[0]);
      client.on('initialized', function () { join().catch(failed); });
      client.on('initFailed', function (event) { failed(event && event.error); });
      client.on('tokenAboutToExpire', refreshToken);
      client.on('tokenExpired', refreshToken);
    }).catch(failed);
  }

  root.chatConversation = { start: start, when: when };
  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})(window);
