(function() {
  $(document).ready(function() {
    var clientActivity, clientConnected, clientDisconnected, log, refreshServerinfo, serverUrl, socket, statusMsg;
    var log = function(msg) {
      return $('#log').prepend("" + msg + "<br />");
    };
    serverUrl = 'ws://localhost:8000/status';
    var socket = (window.MozWebSocket) ? new MozWebSocket(serverUrl) : new WebSocket(serverUrl);
    
    socket.onopen = function(msg) {
      return $('#status').removeClass().addClass('online').html('connected');
    };
    socket.onmessage = function(msg) {
      var response;
      response = JSON.parse(msg.data);
      switch (response.action) {
        case "statusMsg":
          return statusMsg(response.data);
        case "clientConnected":
          return clientConnected(response.data);
        case "clientDisconnected":
          return clientDisconnected(response.data);
        case "clientActivity":
          return clientActivity(response.data);
        case "serverInfo":
          return refreshServerinfo(response.data);
      }
    };
    socket.onclose = function(msg) {
      return $('#status').removeClass().addClass('offline').html('disconnected');
    };
    $('#status').click(function() {
      return socket.close();
    });
    var statusMsg = function(msgData) {
      switch (msgData.type) {
        case "info":
          return log(msgData.text);
        case "warning":
          return log("<span class=\"warning\">" + msgData.text + "</span>");
      }
    };
    var clientConnected = function(data) {
        addClientToList(data);
        return $('#clientCount').text(data.clientCount);
    };
    var clientDisconnected = function(data) {
      $("#clientListSelect option[value='" + data.port + "']").remove();
      return $('#clientCount').text(data.clientCount);
    };
    var refreshServerinfo = function(serverinfo) {
      var ip, port, _ref, _results;
      $('#clientCount').text(serverinfo.clientCount);
      $('#maxClients').text(serverinfo.maxClients);
      $('#maxConnections').text(serverinfo.maxConnectionsPerIp);
      $('#maxRequetsPerMinute').text(serverinfo.maxRequetsPerMinute);
      _ref = serverinfo.clients;
      _results = [];
      for (port in _ref) {
        ip = _ref[port];
        addClientToList({"ip" : ip, "port" : port});
        //_results.push($('#clientListSelect').append(new Option(ip + ':' + port, port)));
      }
      return _results;
    };
    
    var addClientToList = function(data)
    {
        if($("option[value='" + data.port + "']").length < 1){
            $('#clientListSelect').append(new Option("" + data.ip + ":" + data.port, data.port));
        }
    }
    
    return clientActivity = function(port) {
      return $("#clientListSelect option[value='" + port + "']").css("color", "red").animate({
        opacity: 100
      }, 600, function() {
        return $(this).css("color", "black");
      });
    };
  });
}).call(this);