var serviceApp = angular.module('serviceApp', ['ngRoute']);

serviceApp.controller('indexCtrl', function ($scope){
})
.controller('servicesCtrl', function($scope) {
    $scope.admin = true;
})
.controller('reportCtrl', function($scope) {
    $scope.admin = true;
    $scope.config_open = false;
    $scope.toggleConfig = function(open) {
        if (open) {
            $scope.config_open = false;
        } else {
            $scope.config_open = true;
        }
    };
})
.controller('blocksCtrl', function($scope) {
})
.controller('sermonsCtrl', function($scope) {
})
.controller('xrefCtrl', function($scope) {
})
.controller('churchyearCtrl', function($scope) {
})
.controller('adminCtrl', function($scope) {
});

serviceApp.config(function($routeProvider, $locationProvider) {
    $routeProvider.when('/', {
        controller: 'indexCtrl',
        templateUrl: 'partials/index.html'
    })
    .when('/services', {
        controller: 'servicesCtrl',
        templateUrl: 'partials/services.html'
    })
    .when('/report', {
        controller: 'reportCtrl',
        templateUrl: 'partials/report.html'
    })
    .when('/blocks', {
        controller: 'blocksCtrl',
        templateUrl: 'partials/blocks.html'
    })
    .when('/sermons', {
        controller: 'sermonsCtrl',
        templateUrl: 'partials/sermons.html'
    })
    .when('/xref', {
        controller: 'xrefCtrl',
        templateUrl: 'partials/xref.html'
    })
    .when('/churchyear', {
        controller: 'churchyearCtrl',
        templateUrl: 'partials/churchyear.html'
    })
    .when('/admin', {
        controller: 'adminCtrl',
        templateUrl: 'partials/admin.html'
    })
    .otherwise({
        redirectTo: '/'
    });
});
