app.config(['$routeProvider',function($routeProvider) {        
    $routeProvider
   
    .when('/purchase-payments', {
        controller: 'PurPayCtr',
        templateUrl: '/app/payments.index.html',
        title: 'Purchase Payments'
    })

    .when('/verification-print', {
        templateUrl: '/app/report.verification_print',
        title: 'Verification Print',
        controller: 'VPCtr'
    })

    .when('/supplier-wise-payment', {
        templateUrl: '/app/report.supplier_payment.html',
        controller: 'SPCtr',
        title: 'Supplierwise Payment Report'
    })

    .when('/all-payment-report', {
        templateUrl: '/app/report.all_payment.html',
        controller: 'APCtr',
        title: 'All Payment Report'
    });


}])

.controller('PurPayCtr', function($q,$filter,$window,$http,$scope,$cacheFactory,ngTableParams,$mdToast,$mdDialog,$rootScope,$route){
    var httpCache = $cacheFactory.get('$http');
    var path;

      $http({url: 'suppliers/1', method: 'GET',cache:true, ignoreLoadingBar:true}).success(function(sync){
            $scope.Suppliers=sync;               
      });

    $scope.query = function(searchText) {
    var deferred = $q.defer();
        var states = $scope.Suppliers.filter(function(state) {
            return (state.Name.toUpperCase().indexOf(searchText.toUpperCase()) !== -1 || state.CName.toUpperCase().indexOf(searchText.toUpperCase()) !== -1);
        });
        deferred.resolve(states);
    return deferred.promise;
    };


    var dialog = {scope:$scope, preserveScope: true, controller: function($scope, $mdDialog){$scope.hide=function(){$mdDialog.hide(); if($scope.Type=='Edit'){$scope.custform="";}$scope.formError='';};}, clickOutsideToHide:true};
    $scope.data=[];
    $scope.tableSorting = new ngTableParams({
        page: 1,
        count: 10,
        sorting: {
            PID: 'desc'
        }
        }, {
        total: $scope.data.length, 
        getData: function($defer, params) {
            $rootScope.isLoading=true;
            path = 'purchase-payment?'+jQuery.param({
            "skip": (params.page() - 1) * params.count(),
            "take": params.count(),
            "filter":params.filter(),
            "orderBy": params.sorting()});

            $http({ cache: true, url: path, method: 'GET'})
            .success(function (result) {
                $scope.data = result.data;
                params.total(result.total);
                $defer.resolve(result.data);
                $rootScope.isLoading=false;
            }); 

            var cachedResponse = httpCache.get(path);
            if(cachedResponse)
            {
              $http({url: path, method: 'GET', ignoreLoadingBar:true}).success(function(sync){
                    cachedResponse[1]=sync;
                    httpCache.put(path,cachedResponse);
                    $scope.data = sync.data;
                    params.total(sync.total);
                    $defer.resolve(sync.data); 
                    $rootScope.isLoading=false;                  
              });  
            } 
        }
    })

    $scope.search={};

    $scope.searchform =  function(){
        $scope.search.FromDate = Math.round(new Date($scope.FromDat).getTime() / 1000);
        $scope.search.ToDate = Math.round(new Date($scope.ToDat).getTime() / 1000);
        angular.extend($scope.tableSorting.filter(), $scope.search);   
    }

    $scope.makepay =  function(ev){
        $scope.Type='New';    
        $scope.form={Dat:new Date(),Type:'Purchase'};
        $mdDialog.show({      
          scope:$scope,  
          preserveScope: true,
          controller: DialogController,
          targetEvent:ev,
          templateUrl: 'register.html',
          clickOutsideToHide:true
        });
    }

    $scope.editpay =  function(data,index,ev){
        $scope.curid=index;
        $scope.form=angular.copy(data);
        $scope.form.Dat = new Date();
        $scope.form.Supplier = $filter('filter')($scope.Suppliers, {SID:angular.copy(parseInt(data.SID))}, true)[0];
        $scope.Type='Edit';
        $mdDialog.show({      
          scope:$scope,  
          preserveScope: true,
          targetEvent: ev,
          controller: DialogController,
          templateUrl: 'register.html',
          clickOutsideToHide:true
        });
        
    }


    $scope.customerchange = function()
    {
        if(angular.isDefined($scope.form.Supplier)&&$scope.form.Supplier!=null)
        {        
            $scope.form.SID=angular.copy($scope.form.Supplier.SID);
        }

    }

    function DialogController($scope, $mdDialog) {
    $scope.hide = function() {
      $mdDialog.hide();
    };
    }

    $scope.submit =  function(){
        ($scope.Type=='New') ? add() : update();
    }
    function add()
    {    
        if($scope.submitbutton==false)    
        {
            return;
        }
        $scope.submitbutton=false;

        var form=angular.copy($scope.form);
        form.Date= Math.round(new Date($scope.form.Dat).getTime() / 1000);
        $http({ url: 'purchase-payment', method: 'POST',data:form}).success(function(data){
            $mdDialog.hide();
            al('Payment created Successfully');
            $scope.data.push(data);
            $scope.submitbutton=true;
        }).error(function(data,status){
            $scope.formError=data;
            $scope.submitbutton=true;
        });
    }

    function update()
    {    
        var form=angular.copy($scope.form);
        form.Date= Math.round(new Date($scope.form.Dat).getTime() / 1000);
        $http({ url: 'purchase-payment/'+$scope.form.PID, method: 'PUT',data:form}).success(function(data){
            $mdDialog.hide();
            $scope.data[$scope.curid]=data;
            al('Payment Details Updated Successfully');
            $route.reload();
        }).error(function(data,status){
            $scope.formError=data;
        });
    }

    $scope.delete =  function(cid,index,ev){
        var confirm = $mdDialog.confirm({targetEvent:ev})
              .title('Are You sure to delete this Payment?')
              .ok('Yes')
              .cancel('No');

        $mdDialog.show(confirm).then(function() {
                $http({ url: 'purchase-payment/'+cid, method: 'DELETE'}).success(function(data){
                    al('Deleted Successfully');
                    $scope.data.splice(index,1);
                    });
        }, function() {
            al('Delete Cancelled');
        });
    }


    function al(text)
    {
        $mdToast.show($mdToast.simple().textContent(text).position('bottom right').hideDelay(3000));
    }

})

.controller('VPCtr', function(Excel,$q,$timeout,$window,$http,$scope,$cacheFactory,ngTableParams,$mdToast,$mdDialog,$location,$routeParams){
    $scope.search={};
    var dialog = {scope:$scope, preserveScope: true, controller: function($scope, $mdDialog){$scope.hide=function(){$mdDialog.hide(); if($scope.Type=='Edit'){$scope.form="";}$scope.formError='';};}, clickOutsideToHide:true};
    var date = new Date();
    $scope.FromDat = new Date(date.getFullYear(), date.getMonth(), 1);
    $scope.ToDat = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    $scope.searchform =  function(){
        $scope.search.FromDate = Math.round(new Date($scope.FromDat).getTime() / 1000);
        $scope.search.ToDate = Math.round(new Date($scope.ToDat).getTime() / 1000);
        $http({ url: 'purchase-report', method: 'GET',params:$scope.search}).success(function(data){
            $scope.data=data;
        });
     
    }
    $scope.searchform();

    function al(text)
    {
        $mdToast.show($mdToast.simple().textContent(text).position('bottom right').hideDelay(3000));
    }
    
    $scope.print = function(selector)
    {
        $scope.url = true;
        kendo.drawing.drawDOM($('.printpage'),{paperSize:"a3"}).then(function(group){
            kendo.drawing.pdf.toDataURL(group, function(dataURL){
                document.getElementById("print").src=dataURL;
            });
        });
    }
})



.controller('SPCtr',function(Excel,$q,$timeout,$window,$http,$scope,$cacheFactory,ngTableParams,$mdToast,$mdDialog,$location,$routeParams){
        
        $scope.search={}; 

        var dialog = {scope:$scope, preserveScope: true, controller: function($scope, $mdDialog){$scope.hide=function(){$mdDialog.hide(); if($scope.Type=='Edit'){$scope.form="";}$scope.formError='';};}, clickOutsideToHide:true};
        var date = new Date();
        $scope.FromDat = new Date(date.getFullYear(), date.getMonth(), 1);
        $scope.ToDat = new Date(date.getFullYear(), date.getMonth() + 1, 0);

       
        $http({ url: 'suppliers/1', method: 'GET', ignoreLoadingBar: true }).success(function(response) {
            $scope.Suppliers = response;
        });

        $scope.query = function(searchText) {
        var deferred = $q.defer();
            $timeout(function() {
                var states = $scope.Suppliers.filter(function(state) {
                    return ( state.CName.toUpperCase().indexOf(searchText.toUpperCase()) !== -1);
                });
                deferred.resolve(states);
            });
            return deferred.promise;
        };


        $scope.searchform = function(){

            $scope.search.FromDate = Math.round(new Date($scope.FromDat).getTime() / 1000);
            $scope.search.ToDate = Math.round(new Date($scope.ToDat).getTime() / 1000);
            if($scope.search.Supplier){
                $scope.search.SID=$scope.search.Supplier.SID;
            }
            $http({ url: 'supplier-payment', method: 'GET',params:$scope.search}).success(function(data){
                $scope.data=data;

                for (var i = 0; i <  $scope.data.Report.length; i++) {
                    $scope.data.Report[i].Bal=$scope.data.Beginning;
                    for (var j = 0; j <=  i; j++) {
                        if($scope.data.Report[j].InvAmt&&$scope.data.Report[j].InvAmt!=''){
                            $scope.data.Report[i].Bal+=parseInt($scope.data.Report[j].InvAmt);
                        }
                        if($scope.data.Report[j].RecAmt&&$scope.data.Report[j].RecAmt!=''){
                            $scope.data.Report[i].Bal-=parseInt($scope.data.Report[j].RecAmt);
                        }
                    }
                }
            });  
           
        }

        $scope.print = function(selector)
        {
            $scope.url = true;
            kendo.drawing.drawDOM($('.printpage'),{paperSize:"a3"}).then(function(group){
                kendo.drawing.pdf.toDataURL(group, function(dataURL){
                    document.getElementById("print").src=dataURL;
                });
            });
        }

        
        function al(text)
        {
            $mdToast.show($mdToast.simple().textContent(text).position('bottom right').hideDelay(3000));
        }
})


.controller('APCtr',function(Excel,$q,$timeout,$window,$http,$scope,$cacheFactory,ngTableParams,$mdToast,$mdDialog,$location,$routeParams){
        
        $scope.search={}; 

        var dialog = {scope:$scope, preserveScope: true, controller: function($scope, $mdDialog){$scope.hide=function(){$mdDialog.hide(); if($scope.Type=='Edit'){$scope.form="";}$scope.formError='';};}, clickOutsideToHide:true};
        var date = new Date();
        $scope.FromDat = new Date(date.getFullYear(), date.getMonth(), 1);
        $scope.ToDat = new Date(date.getFullYear(), date.getMonth() + 1, 0);

       
        $http({ url: 'suppliers/1', method: 'GET', ignoreLoadingBar: true }).success(function(response) {
            $scope.Suppliers = response;
        });

        $scope.query = function(searchText) {
        var deferred = $q.defer();
            $timeout(function() {
                var states = $scope.Suppliers.filter(function(state) {
                    return ( state.CName.toUpperCase().indexOf(searchText.toUpperCase()) !== -1);
                });
                deferred.resolve(states);
            });
            return deferred.promise;
        };


        $scope.searchform = function(){

            $scope.search.FromDate = Math.round(new Date($scope.FromDat).getTime() / 1000);
            $scope.search.ToDate = Math.round(new Date($scope.ToDat).getTime() / 1000);
            if($scope.search.Supplier){
                $scope.search.SID=$scope.search.Supplier.SID;
            }
            $http({ url: 'all-payment', method: 'GET',params:$scope.search}).success(function(data){
                $scope.data=data;
            });  
           
        }

         $scope.searchform();

        $scope.print = function(selector)
        {
            $scope.url = true;
            kendo.drawing.drawDOM($('.printpage'),{paperSize:"a3"}).then(function(group){
                kendo.drawing.pdf.toDataURL(group, function(dataURL){
                    document.getElementById("print").src=dataURL;
                });
            });
        }

        
        function al(text)
        {
            $mdToast.show($mdToast.simple().textContent(text).position('bottom right').hideDelay(3000));
        }
})