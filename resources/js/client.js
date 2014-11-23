(function($)
{
    var clientApp = angular.module('clientApp', []);
    
    clientApp.controller('WebSocketController', ['$scope', function($scope)
    {
        $scope.socket;
        
        $scope.action;
        $scope.data;
        $scope.fileAction = 'setFilename';
        
        $scope.serverUrl = 'ws://localhost:8000/demo';
        $scope.statusButtonClass = 'offline';
        $scope.statusButtonText = 'offline';
        
        $scope.init = function()
        {
            $scope.socket = (window.MozWebSocket) ? new MozWebSocket($scope.serverUrl) : new WebSocket($scope.serverUrl);
            $scope.socket.binaryType = 'blob';
            $scope.socket.onopen    = function(data){ $scope.open(data); };
            $scope.socket.onclose   = function(data){ $scope.close(data); };
            $scope.socket.onmessage = function(data){ $scope.message(data); };
        };
        
        $scope.open = function()
        {
            $scope.statusButtonClass = 'online';
            $scope.statusButtonText = 'online';
            $scope.$apply();
        };
        
        $scope.close= function()
        {
            var d = new Date();
            $scope.log(
                '<span class="warning">[' + (d.getMonth() + 1) +
                '-' + d.getDate() + ' ' + d.getHours() +
                ':' + d.getMinutes() + "] Server Disconnected</span>"
            );
            $scope.statusButtonClass = 'offline';
            $scope.statusButtonText = 'disconnected';
            $scope.$apply();
        };
        
        $scope.message = function(message)
        {
            var response = JSON.parse(message.data);
            $scope.log("Action: " + response.action);
            $scope.log("Data: " + response.data);
        };
        
        $scope.send = function()
        {
            $scope.socket.send(JSON.stringify({"action" : $scope.action, "data" : $scope.data}));
        };
        
        $scope.sendMessage = function()
        {
            $scope.socket.send(JSON.stringify({"action" : $scope.fileAction, "data" : $("#file").val()}));
        };
        
        $scope.log = function(message)
        {
            return $('#log').append("" + message + "<br />");
        };
    }]);
})(jQuery);