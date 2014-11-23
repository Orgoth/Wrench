(function($)
{
    var statusApp = angular.module('statusApp', []);
    
    statusApp.controller('WebSocketController', ['$scope', function($scope)
    {
        $scope.connectedClients     = '';
        $scope.maxClients           = '';
        $scope.connectionsPerIp     = '';
        $scope.requestsPerMinute    = '';
        $scope.currentMemory        = '';
        $scope.maxMemory            = '';
        
        $scope.socket;
        $scope.serverUrl = 'ws://localhost:8000/status';
        $scope.statusButtonClass = 'offline';
        $scope.statusButtonText = 'offline';
        
        $scope.clientsList = new Array();
        
        $scope.init = function()
        {
            $scope.socket = (window.MozWebSocket) ? new MozWebSocket($scope.serverUrl) : new WebSocket($scope.serverUrl);
            $scope.socket.onopen    = function(data){ $scope.open(data); };
            $scope.socket.onclose   = function(data){ $scope.close(data); };
            $scope.socket.onmessage = function(data){ $scope.message(data); };
            
        };
        
        $scope.open = function(data)
        {
            $scope.statusButtonClass = 'online';
            $scope.statusButtonText = 'online';
            $scope.$apply();
            $scope.askInformations();
        };
        
        $scope.askInformations = function()
        {
            $scope.socket.send(JSON.stringify({"action" : "askInformations", "data" : ""}));
        };
        
        window.onbeforeunload = function (event)
        {
            $scope.socket.close();
        };
        
        $scope.close = function(data)
        {
            var d = new Date();
            $scope.resetServerInformations();
            $scope.statusMsg({
                "type" : "warning",
                "text" : '[' + (d.getMonth() + 1) + '-' + d.getDate() + ' ' + d.getHours() + ':' + d.getMinutes() + "] Server Disconnected"
            });
            $scope.statusButtonClass = 'offline';
            $scope.statusButtonText = 'disconnected';
            $scope.$apply();
        };
        
        $scope.message = function(message)
        {
            var response = JSON.parse(message.data);
            switch (response.action)
            {
              case "statusMsg":
                return $scope.statusMsg(response.data);
              case "clientConnected":
                return $scope.clientConnected(response.data);
              case "clientDisconnected":
                return $scope.clientDisconnected(response.data);
              case "clientActivity":
                return $scope.clientActivity(response.data);
              case "serverInfo":
                return $scope.refreshServerInformations(response.data);
            }
        };
        
        $scope.resetServerInformations = function()
        {
            $scope.connectedClients     = '';
            $scope.maxClients           = '';
            $scope.connectionsPerIp     = '';
            $scope.requestsPerMinute    = '';
            $scope.currentMemory        = '';
            $scope.maxMemory            = '';
            $scope.$apply();
        };
        
        $scope.refreshServerInformations = function(data)
        {
            var ip, port, _ref;
            $scope.connectedClients     = data.clientCount;
            $scope.maxClients           = data.maxClients;
            $scope.connectionsPerIp     = data.maxConnections;
            $scope.requestsPerMinute    = data.maxRequestsPerMinute;
            $scope.currentMemory        = data.currentMemory;
            $scope.maxMemory            = data.maxMemory;
            _ref = data.clients;
            for (port in _ref)
            {
                ip = _ref[port];
                $scope.clientsList.push({"ip" : ip, "port" : port});
            }
            $scope.$apply();
        };
        
        $scope.clientConnected = function(data)
        {
            $scope.clientsList.push({"ip" : data.ip, "port" : data.port});
            $scope.currentMemory    = data.currentMemory;
            $scope.maxMemory        = data.maxMemory;
            $scope.connectedClients = data.clientCount;
            $scope.$apply();
        };
        
        $scope.clientDisconnected = function(data)
        {
            var index = $scope.clientsList.map(function(e){ return e.port }).indexOf(data.port);
            if(index !== -1)
            {
                $scope.clientsList.splice(index, 1);
            }
            $scope.currentMemory    = data.currentMemory;
            $scope.maxMemory        = data.maxMemory;
            $scope.connectedClients = data.clientCount;
            $scope.$apply();
        };
        
        $scope.clientActivity = function(data)
        {
            return $("#clientListSelect option[value='" + data.port + "']").css("color", "red").animate({
                opacity: 100
            }, 600, function() {
                return $(this).css("color", "black");
            });
        };
        
        $scope.statusMsg = function(data)
        {
            switch (data.type)
            {
                case "info":
                  $scope.log(data.text);
                  break;
                case "warning":
                  $scope.log("<span class=\"warning\">" + data.text + "</span>");
                  break;
            };
        };
        
        $scope.log = function(text)
        {
            $('#log').prepend("" + text + "<br />");
        };
    }]);
})(jQuery);