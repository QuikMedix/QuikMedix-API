var twiliochat = (function() {
  var tc = {};

  var MESSAGES_HISTORY_LIMIT = 50;

  var $channelList;
  var $inputText;
  var $statusRow;
  var $connectPanel;
  var $newChannelInputRow;
  var $newChannelInput;
  var $typingRow;
  var $typingPlaceholder;
  var nameuser1;
  var logouser1;
  var nameuser2;
  var logouser2;

  $(document).ready(function() {
    tc.$messageList = $('#message-list');
    $channelList = $('#channel-list');
    $inputText = $('#input-text');
    $statusRow = $('#status-row');
    $connectPanel = $('#connect-panel');
    $newChannelInputRow = $('#new-channel-input-row');
    $newChannelInput = $('#new-channel-input');
    $typingRow = $('#typing-row');
    $typingPlaceholder = $('#typing-placeholder');
    connectClientWithUsername();
    $inputText.on('keypress', handleInputTextKeypress);
    $newChannelInput.on('keypress', tc.handleNewChannelInputKeypress);
    $('#connect-image').on('click', connectClientWithUsername);
    $('#send_btn').on('click', handleInputTextClickBtn);
    $('#add-channel-image').on('click', showAddChannelInput);
  });

  function handleInputTextKeypress(event) {
    if (event.keyCode === 13) {
      tc.currentChannel.sendMessage($(this).val());
      event.preventDefault();
      $(this).val('');
    }
    else {
      notifyTyping();
    }
  }

  function handleInputTextClickBtn(event) {
      tc.currentChannel.sendMessage($inputText.val());
      event.preventDefault();
      $inputText.val('');
  }

  var notifyTyping = $.throttle(function() {
    tc.currentChannel.typing();
  }, 1000);

  tc.handleNewChannelInputKeypress = function(event) {
    if (event.keyCode === 13) {
      tc.messagingClient.createChannel({
        friendlyName: $newChannelInput.val()
      }).then(hideAddChannelInput);
      $(this).val('');
      event.preventDefault();
    }
  };

  function connectClientWithUsername() {
    var usernameText = username;
    if (usernameText == '') {
      alert('Username cannot be empty');
      return;
    }
    tc.username = usernameText;
    fetchAccessToken(tc.username, connectMessagingClient);
  }

  function fetchAccessToken(username0, handler) {
    $.post('https://cp.a2brx.com/api/chat-token', {identity: username0, _token: $("input[name='_token']").val()}, null, 'json')
      .done(function(response) {
        handler(response.token);
      })
      .fail(function(error) {
        console.log('Failed to fetch the Access Token with error: ' + JSON.stringify(error));
      });
    $.post('https://cp.a2brx.com/api/users/get', {identity: username, _token: $("input[name='_token']").val()}, null, 'json')
      .done(function(response) {
        if(response.name!='') {
          nameuser1=response.name;
          logouser1="https://cp.a2brx.com"+response.image;
        } else {
          console.log('Failed to fetch the username with error: ' + response.message);
        }
      });
    $.post('https://cp.a2brx.com/api/users/get', {identity: username2, _token: $("input[name='_token']").val()}, null, 'json')
      .done(function(response) {
        if(response.name!='') {
          nameuser2=response.name;
          logouser2="https://cp.a2brx.com"+response.image;
        } else {
          console.log('Failed to fetch the username with error: ' + response.message);
        }
      });
  }

  function connectMessagingClient(token) {
    // Initialize the Chat messaging client
    tc.accessManager = new Twilio.AccessManager(token);
    Twilio.Chat.Client.create(token).then(function(client) {
      tc.messagingClient = client;
      updateConnectedUI();
      tc.loadChannelList(tc.joinGeneralChannel);
      tc.messagingClient.on('channelAdded', $.throttle(tc.loadChannelList));
      tc.messagingClient.on('channelRemoved', $.throttle(tc.loadChannelList));
      tc.messagingClient.on('tokenExpired', refreshToken);
    });
  }

  function refreshToken() {
    fetchAccessToken(tc.username, setNewToken);
  }

  function setNewToken(tokenResponse) {
    tc.accessManager.updateToken(tokenResponse.token);
  }

  function updateConnectedUI() {
    $statusRow.addClass('connected').removeClass('disconnected');
    tc.$messageList.addClass('connected').removeClass('disconnected');
    $connectPanel.addClass('connected').removeClass('disconnected');
    $inputText.addClass('with-shadow');
    $typingRow.addClass('connected').removeClass('disconnected');
  }

  tc.loadChannelList = function(handler) {
    if (tc.messagingClient === undefined) {
      console.log('Client is not initialized');
      return;
    }

    tc.messagingClient.getPublicChannelDescriptors().then(function(channels) {
      tc.channelArray = tc.sortChannelsByName(channels.items);
      $channelList.text('');
      tc.channelArray.forEach(addChannel);
      if (typeof handler === 'function') {
        handler();
      }
    });
  };

  tc.joinGeneralChannel = function() {
    console.log('Attempting to join "general" chat channel...');
    if (!tc.generalChannel) {
      // If it doesn't exist, let's create it
      tc.messagingClient.createChannel({
        uniqueName: GENERAL_CHANNEL_UNIQUE_NAME,
        friendlyName: GENERAL_CHANNEL_NAME
      }).then(function(channel) {
        console.log('Created general channel');
        tc.generalChannel = channel;
        tc.loadChannelList(tc.joinGeneralChannel);
      });
    }
    else {
      console.log('Found general channel:');
      setupChannel(tc.generalChannel);
    }
  };

  function initChannel(channel) {
    console.log('Initialized channel ' + channel.friendlyName);
    return tc.messagingClient.getChannelBySid(channel.sid);
  }

  function joinChannel(_channel) {
    if(_channel.channelState.status !== "joined") {
      return _channel.join()
      .then(function(joinedChannel) {
        console.log('Joined channel ' + joinedChannel.friendlyName);
        updateChannelUI(_channel);
        tc.currentChannel = _channel;
        return joinedChannel;
      });
    } else {
      console.log('Joined channel ' + _channel.channelState.friendlyName);
      updateChannelUI(_channel);
      tc.currentChannel = _channel;
      return _channel;
    }
  }

  function initChannelEvents() {
    console.log(tc.currentChannel.friendlyName + ' ready.');
    tc.currentChannel.on('messageAdded', tc.addMessageToList);
    tc.currentChannel.on('typingStarted', showTypingStarted);
    tc.currentChannel.on('typingEnded', hideTypingStarted);
    tc.currentChannel.on('memberUpdated', memberJoined);
    tc.currentChannel.on('memberLeft', notifyMemberLeft);
    $inputText.prop('disabled', false).focus();
    scrollToMessageListBottom();
  }

  function setupChannel(channel) {
    return leaveCurrentChannel()
      .then(function() {
        return initChannel(channel);
      })
      .then(function(_channel) {
        return joinChannel(_channel);
      })
      .then(initChannelEvents);
  }

  function leaveCurrentChannel() {
    if (tc.currentChannel) {
      return tc.currentChannel.leave().then(function(leftChannel) {
        console.log('left ' + leftChannel.friendlyName);
        leftChannel.removeListener('messageAdded', tc.addMessageToList);
        leftChannel.removeListener('typingStarted', showTypingStarted);
        leftChannel.removeListener('typingEnded', hideTypingStarted);
        leftChannel.removeListener('memberLeft', notifyMemberLeft);
      });
    } else {
      return Promise.resolve();
    }
  }

  function messageAddAjax(username0,created,body) {
    if (username0 === tc.username) {
      $.post('https://cp.a2brx.com/api/chats/new_message', {chat_name: GENERAL_CHANNEL_UNIQUE_NAME,created: created,body: body,user: tc.username,_token: $("input[name='_token']").val()}, null, 'json')
      .done(function(response) {
        if(response.result!='true') {
          console.log('Failed with error: ' + response.message);
        }
      });
    } else {
      $.post('https://cp.a2brx.com/api/chats/new_message', {chat_name: GENERAL_CHANNEL_UNIQUE_NAME,created: created,body: body,user: tc.username,not_me_author:1,_token: $("input[name='_token']").val()}, null, 'json')
      .done(function(response) {
        if(response.result!='true') {
          console.log('Failed with error: ' + response.message);
        }
      });
    }
    
  }

  tc.addMessageToList = function(message) {
    tc.currentChannel.updateLastConsumedMessageIndex(message.index);
    var rowDiv = $('<div>').addClass('row no-margin');
    var nameuser;
    var logouser;
    if (message.author === tc.username) {
      nameuser=nameuser1;
      logouser=logouser1;
    } else {
      nameuser=nameuser2;
      logouser=logouser2;
    }
    rowDiv.loadTemplate($('#message-template'), {
      username: nameuser,
      userimage: logouser,
      date: dateFormatter.getTodayDate(message.dateCreated),
      body: message.body
    });
    messageAddAjax(message.author,message.dateCreated.toLocaleString("en-US", {timeZone: "America/New_York"}),message.body);
    if (message.author === tc.username) {
      rowDiv.addClass('own-message');
    }

    tc.$messageList.append(rowDiv);
    scrollToMessageListBottom();
  };

  function notifyMemberLeft(member) {
    notify(member.identity + ' left the channel');
  }

  function memberJoined(member) {
    if(member.member.state.identity==username2 && member.updateReasons[0]=="lastConsumptionTimestamp") {
      $('#message-list .check2').addClass('viewed');
    }
  }

  function notify(message) {
    var row = $('<div>').addClass('col-md-12');
    row.loadTemplate('#member-notification-template', {
      status: message
    });
    tc.$messageList.append(row);
    scrollToMessageListBottom();
  }

  function showTypingStarted(member) {
    if (member.identity === tc.username) {
      $typingPlaceholder.text(nameuser1 + ' is typing...');
    } else {
      $typingPlaceholder.text(nameuser2 + ' is typing...');
    }
  }

  function hideTypingStarted(member) {
    $typingPlaceholder.text('');
  }

  function scrollToMessageListBottom() {
    tc.$messageList.scrollTop(tc.$messageList[0].scrollHeight);
  }

  function updateChannelUI(selectedChannel) {
    var channelElements = $('.channel-element').toArray();
    var channelElement = channelElements.filter(function(element) {
      return $(element).data().sid === selectedChannel.sid;
    });
    channelElement = $(channelElement);
    if (tc.currentChannelContainer === undefined && selectedChannel.uniqueName === GENERAL_CHANNEL_UNIQUE_NAME) {
      tc.currentChannelContainer = channelElement;
    }
    tc.currentChannelContainer.removeClass('selected-channel').addClass('unselected-channel');
    channelElement.removeClass('unselected-channel').addClass('selected-channel');
    tc.currentChannelContainer = channelElement;
  }

  function showAddChannelInput() {
    if (tc.messagingClient) {
      $newChannelInputRow.addClass('showing').removeClass('not-showing');
      $channelList.addClass('showing').removeClass('not-showing');
      $newChannelInput.focus();
    }
  }

  function hideAddChannelInput() {
    $newChannelInputRow.addClass('not-showing').removeClass('showing');
    $channelList.addClass('not-showing').removeClass('showing');
    $newChannelInput.val('');
  }

  function addChannel(channel) {
    if (channel.uniqueName === GENERAL_CHANNEL_UNIQUE_NAME) {
      tc.generalChannel = channel;
    }
    var rowDiv = $('<div>').addClass('row channel-row');
    rowDiv.loadTemplate('#channel-template', {
      channelName: channel.friendlyName
    });

    var channelP = rowDiv.children().children().first();

    rowDiv.on('click', selectChannel);
    channelP.data('sid', channel.sid);
    if (tc.currentChannel && channel.sid === tc.currentChannel.sid) {
      tc.currentChannelContainer = channelP;
      channelP.addClass('selected-channel');
    }
    else {
      channelP.addClass('unselected-channel')
    }

    $channelList.append(rowDiv);
  }

  function selectChannel(event) {
    var target = $(event.target);
    var channelSid = target.data().sid;
    var selectedChannel = tc.channelArray.filter(function(channel) {
      return channel.sid === channelSid;
    })[0];
    if (selectedChannel === tc.currentChannel) {
      return;
    }
    setupChannel(selectedChannel);
  };

  tc.sortChannelsByName = function(channels) {
    return channels.sort(function(a, b) {
      if (a.friendlyName === GENERAL_CHANNEL_NAME) {
        return -1;
      }
      if (b.friendlyName === GENERAL_CHANNEL_NAME) {
        return 1;
      }
      return a.friendlyName.localeCompare(b.friendlyName);
    });
  };

  return tc;
})();
