/*
 * Runs public/js/chat-conversation.js against a stand-in page, API and Twilio SDK:
 *   node tests/smoke/chat-conversation.js
 * It checks what the script asks of the SDK and the API, and what it puts on the page.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

let checks = 0;
const check = (actual, expected, what) => { assert.deepStrictEqual(actual, expected, what); checks++; };

/* A page with just what the script touches. */
function element(tag) {
  const classes = new Set();
  const el = {
    tag, children: [], listeners: {}, textContent: '', value: '', disabled: true, src: '', dataPart: null,
    scrollTop: 0, scrollHeight: 100,
    classList: { add: (c) => classes.add(c), contains: (c) => classes.has(c) },
    appendChild(child) { el.children.push(child); return child; },
    addEventListener(type, fn) { el.listeners[type] = fn; },
    all() { return el.children.flatMap((c) => [c, ...c.all()]); },
    querySelector(selector) { return el.querySelectorAll(selector)[0] || null; },
    querySelectorAll(selector) {
      const part = /^\[data-part="(\w+)"\]$/.exec(selector);
      return el.all().filter((c) => (part ? c.dataPart === part[1] : c.classList.contains(selector.slice(1))));
    },
    cloneNode() {
      const copy = element(tag);
      copy.dataPart = el.dataPart;
      classes.forEach((c) => copy.classList.add(c));
      el.children.forEach((c) => copy.appendChild(c.cloneNode()));
      return copy;
    },
  };
  return el;
}
function row() {
  const r = element('div');
  ['image', 'name', 'date', 'body'].forEach((part) => { const e = element('span'); e.dataPart = part; r.appendChild(e); });
  const mark = element('svg'); mark.classList.add('check2'); r.appendChild(mark);
  return r;
}
const ids = { 'message-list': element('div'), 'input-text': element('textarea'), 'send_btn': element('button'), 'typing-placeholder': element('p'), 'message-template': { content: { firstElementChild: row() } } };

/* The API and the Twilio SDK. */
const posts = [];
let tokens = 0;
function fetch(url, options) {
  const fields = Object.fromEntries(options.body);
  posts.push({ url, fields });
  const answers = {
    '/api/chat-token': () => ({ identity: fields.identity, token: 'token-' + (++tokens) }),
    '/api/users/get': () => (fields.identity === '5' ? { name: 'Pat Test', image: '/images/users/pat.png' } : { name: 'Dee Test', image: '' }),
    '/api/chats/new_message': () => ({ result: 'true' }),
  };
  return Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve(answers[url]()) });
}
class Emitter {
  constructor() { this.handlers = {}; }
  on(name, fn) { this.handlers[name] = fn; }
  emit(name, value) { return this.handlers[name](value); }
}
const sdk = { clients: [], sent: [], read: [], typing: 0, asked: [] };
class Conversation extends Emitter {
  sendMessage(text) { sdk.sent.push(text); return Promise.resolve(0); }
  advanceLastReadMessageIndex(index) { sdk.read.push(index); return Promise.resolve(index); }
  typing() { sdk.typing++; return Promise.resolve(); }
}
const conversation = new Conversation();
class Client extends Emitter {
  constructor(token) { super(); this.token = token; this.updated = []; sdk.clients.push(this); }
  getConversationByUniqueName(name) { sdk.asked.push(name); return Promise.resolve(conversation); }
  updateToken(token) { this.updated.push(token); return Promise.resolve(this); }
}

const window = {
  chatPage: { me: '5', other: '6', conversation: '5_6', viewer: { viewer: '5', signature: 'signed' }, avatar: 'http://app.test/images/users/Avatar.png', baseUrl: 'http://app.test' },
  document: { readyState: 'loading', getElementById: (id) => ids[id], addEventListener() {} },
  Twilio: { Conversations: { Client } },
  fetch, URLSearchParams, Intl, Date, Promise, console: { log() {} },
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/chat-conversation.js'), 'utf8'), { window, URLSearchParams });
const tick = () => new Promise((resolve) => setImmediate(resolve));

(async () => {
  const list = ids['message-list'];
  const input = ids['input-text'];

  await window.chatConversation.start();
  check(posts.map((p) => p.url).sort(), ['/api/chat-token', '/api/users/get', '/api/users/get'], 'asks for a token and both people');
  check(posts.every((p) => p.fields.viewer === '5' && p.fields.signature === 'signed'), true, 'every request says who is asking');
  check(posts.find((p) => p.url === '/api/chat-token').fields.identity, '5', 'the token is for the viewer');
  check(sdk.clients.length, 1, 'one client');
  check(sdk.clients[0].token, 'token-1', 'the client signs in with the token');
  check(input.disabled, true, 'no typing before the conversation is found');

  sdk.clients[0].emit('initialized');
  await tick();
  check(sdk.asked, ['5_6'], 'finds the conversation by its name');
  check(input.disabled, false, 'typing is possible once the conversation is found');

  // A message from the other person.
  posts.length = 0;
  conversation.emit('messageAdded', { author: '6', body: '<b>Hi</b>', index: 3, dateCreated: new Date('2026-03-02T20:05:00Z') });
  await tick();
  const first = list.children[0];
  check(list.children.length, 1, 'the message is added to the page');
  check(first.querySelector('[data-part="body"]').textContent, '<b>Hi</b>', 'the text is written as text');
  check(first.querySelector('[data-part="name"]').textContent, 'Dee Test', 'with the other person\'s name');
  check(first.querySelector('[data-part="image"]').src, 'http://app.test/images/users/Avatar.png', 'and the default picture when they have none');
  check(first.querySelector('[data-part="date"]').textContent, 'March 2 - 03:05 PM', 'dated in New York time');
  check(first.classList.contains('own-message'), false, 'not marked as the viewer\'s own');
  check(sdk.read, [3], 'the message is marked read');
  check(posts.map((p) => [p.url, p.fields.chat_name, p.fields.user, p.fields.body, p.fields.not_me_author, p.fields.created]), [['/api/chats/new_message', '5_6', '5', '<b>Hi</b>', '1', '3/2/2026, 3:05:00 PM']], 'and reported as someone else\'s');

  // The viewer's own message.
  posts.length = 0;
  input.value = '  On my way  ';
  ids.send_btn.listeners.click({ preventDefault() {} });
  check(sdk.sent, ['On my way'], 'sends the trimmed text');
  check(input.value, '', 'and empties the box');
  conversation.emit('messageAdded', { author: '5', body: 'On my way', index: 4, dateCreated: new Date() });
  await tick();
  const own = list.children[1];
  check(own.classList.contains('own-message'), true, 'the viewer\'s message is marked as their own');
  check(own.querySelector('[data-part="image"]').src, 'http://app.test/images/users/pat.png', 'with their picture');
  check(own.querySelector('[data-part="date"]').textContent.startsWith('Today - '), true, 'dated today');
  check('not_me_author' in posts[0].fields, false, 'and reported as their own');

  // Enter sends, other keys say "typing" at most once a second, nothing empty is sent.
  input.value = 'Second';
  let prevented = false;
  input.listeners.keydown({ key: 'Enter', shiftKey: false, preventDefault() { prevented = true; } });
  check([sdk.sent, prevented], [['On my way', 'Second'], true], 'Enter sends');
  input.value = '   ';
  ids.send_btn.listeners.click({ preventDefault() {} });
  check(sdk.sent.length, 2, 'an empty message is not sent');
  input.listeners.keydown({ key: 'a' });
  input.listeners.keydown({ key: 'b' });
  check(sdk.typing, 1, 'typing is announced once');

  // Typing and read marks from the other person.
  conversation.emit('typingStarted', { identity: '6' });
  check(ids['typing-placeholder'].textContent, 'Dee Test is typing...', 'shows who is typing');
  conversation.emit('typingEnded', { identity: '6' });
  check(ids['typing-placeholder'].textContent, '', 'and clears it');
  const marks = () => list.querySelectorAll('.check2').map((m) => m.classList.contains('viewed'));
  conversation.emit('participantUpdated', { participant: { identity: '5' }, updateReasons: ['lastReadMessageIndex'] });
  check(marks(), [false, false], 'the viewer reading changes nothing');
  conversation.emit('participantUpdated', { participant: { identity: '6' }, updateReasons: ['attributes'] });
  check(marks(), [false, false], 'nor does another kind of update');
  conversation.emit('participantUpdated', { participant: { identity: '6' }, updateReasons: ['lastReadMessageIndex', 'lastReadTimestamp'] });
  check(marks(), [true, true], 'the other person reading turns the marks on');

  // A token about to expire is replaced in the same client.
  sdk.clients[0].emit('tokenAboutToExpire');
  await tick();
  await tick();
  check(sdk.clients[0].updated, ['token-2'], 'the client gets a fresh token');
  check(sdk.clients.length, 1, 'without a second client');

  // A client that cannot start says so.
  sdk.clients[0].emit('initFailed', { error: new Error('denied') });
  check(ids['typing-placeholder'].textContent, 'Chat is not available right now.', 'a failed start is shown');

  console.log(checks + ' chat page checks passed with a stand-in for the Twilio SDK.');
})().catch((error) => { console.error(error); process.exit(1); });
