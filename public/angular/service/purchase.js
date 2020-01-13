app.config(['$routeProvider',function($routeProvider) {        
    $routeProvider
    .when('/create-purchase', {
        controller: 'CreatePurchaseCtr',
        templateUrl: '/app/purchase.form.html',
        title: 'New Purchase'
    })
    .when('/purchase', {
        controller: 'PurchaseCtr',
        templateUrl: '/app/purchase.index.html',
        title: 'Purchase'
    });
}])
.controller('CreatePurchaseCtr', function($rootScope, $mdDialog, $filter,$q,$timeout, $http,$scope,$cacheFactory,ngTableParams,$mdToast,$mdDialog,$location,$routeParams){

    var httpCache = $cacheFactory.get('$http');  

    $http({url: 'suppliers/1', method: 'GET', ignoreLoadingBar:true}).success(function(sync){
        $scope.Suppliers=sync;               
    });

    $scope.Type="Create";    
    $scope.form={Dat: new Date()};

    $scope.query = function(searchText) {
    var deferred = $q.defer();
    $timeout(function() {
        var states = $scope.Suppliers.filter(function(state) {
            return (state.CName.toUpperCase().indexOf(searchText.toUpperCase()) !== -1);
        });
        deferred.resolve(states);
    }, 0);
    return deferred.promise;
    };

    $scope.edit_purchase =  function(data,index,ev){
        $scope.curid=index;
        $scope.form=angular.copy(data);
        $scope.form.Dat = new Date();
        $scope.form.Supplier = $filter('filter')($scope.Suppliers, {SID:angular.copy(parseInt(data.SID))}, true)[0];
        $scope.Type='Edit';
    }

    $scope.create =  function(){

        $scope.form.Date = Math.round(new Date($scope.form.Dat).getTime() / 1000);

        $scope.formError={};
        var errors=[];

       
        if(!$scope.form.SID)
        {
            errors.push('Please Select Supplier');
        }

        if(!$scope.form.Amount)
        {
            errors.push('Please fill amount');
        }

        if(!$scope.form.InvNo)
        {
            errors.push('Please fill Invoice No');
        }


        if($rootScope.processing==true){return;}

        if(errors.length>0)
        {
            $scope.alerttext={errors:errors};
            $mdDialog.show($mdDialog.confirm({targetEvent:event, templateUrl:'ok.html',preserveScope: true,scope: $scope})).then(function() {
            }, function() {});
            return;
        }
        $rootScope.processing=true;
        ($scope.Type=='Create') ? newin() : updatein();
    }

    function newin()
    {
        $http({ url: 'purchase', method: 'POST',data:{purchase:$scope.form}}).success(function(data){
            al('Purchase created Successfully');
            $scope.form={Dat: new Date()};
            $scope.Type="Create";
            $scope.data.unshift(data);
            $rootScope.processing=false;
        }).error(function(data,status){
           $rootScope.processing=false;
            var confirm = $mdDialog.alert({
                title: 'Warning',
                textContent: data,
                ok: 'Close'
            });
            $mdDialog.show(confirm);
        }); 
    }

    function updatein()
    {
        $http({ url: 'purchase/'+$scope.form.PUID, method: 'PUT',data:{purchase:$scope.form}}).success(function(data){
            al('Purchase Details Updated Successfully');
            $rootScope.processing=false;
            $scope.data[$scope.curid]=(data);
            $scope.form={Dat: new Date()};
            $scope.Type="Create";
        }).error(function(data,status){
            $rootScope.processing=false;
            var confirm = $mdDialog.alert({
                    title: 'Warning',
                    textContent: data,
                    ok: 'Close'
                  });
            $mdDialog.show(confirm);
        });
    }


    function al(text)
    {
        $mdToast.show($mdToast.simple().textContent(text).position('bottom right').hideDelay(3000));
    }

    var dialog = {scope:$scope, preserveScope: true, controller: function($scope, $mdDialog){$scope.hide=function(){$mdDialog.hide(); if($scope.Type=='Edit'){$scope.custform="";}$scope.formError='';};}, clickOutsideToHide:true};

  
    $scope.customerchange = function()
    {
        if(angular.isDefined($scope.form.Supplier)&&$scope.form.Supplier!=null)
        {        
            $scope.form.SID=angular.copy($scope.form.Supplier.SID);
        }

    }
    $scope.data=[];
    $scope.tableSorting = new ngTableParams({
        page: 1,
        count: 10,
        sorting: {
            PUID: 'desc'
        }
        }, {
        total: $scope.data.length, 
        getData: function($defer, params) {
            $rootScope.isLoading=true;
            path = 'purchase?'+jQuery.param({
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



    $scope.delete =  function(cid,index,ev){
        var confirm = $mdDialog.confirm({targetEvent:ev})
              .title('Are You sure to delete this Purchase?')
              .ok('Yes')
              .cancel('No');

        $mdDialog.show(confirm).then(function() {
                $http({ url: 'purchase/'+cid, method: 'DELETE'}).success(function(data){
                    al('Deleted Successfully');
                    $scope.data.splice(index,1);
                    });
        }, function() {
            al('Delete Cancelled');
        });
    }

    

})

.controller('PurchaseCtr', function($http,$scope,$cacheFactory,ngTableParams,$mdToast,$mdDialog,$rootScope,$location){

var httpCache = $cacheFactory.get('$http');
var path;
var dialog = {scope:$scope, preserveScope: true, controller: function($scope, $mdDialog){$scope.hide=function(){$mdDialog.hide(); if($scope.Type=='Edit'){$scope.custform="";}$scope.formError='';};}, clickOutsideToHide:true};
$scope.data=[];
$scope.tableSorting = new ngTableParams({
    page: 1,
    count: 10,
    sorting: {
        PUID: 'desc'
    }
    }, {
    total: $scope.data.length, 
    getData: function($defer, params) {
        $rootScope.isLoading=true;
        path = 'purchase?'+jQuery.param({
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


$scope.delete =  function(cid,index,ev){
    var confirm = $mdDialog.confirm({targetEvent:ev})
          .title('Are You sure to delete this PO?')
          .ok('Yes')
          .cancel('No');

    $mdDialog.show(confirm).then(function() {
            $http({ url: 'purchase/'+cid, method: 'DELETE'}).success(function(data){
                al('Deleted Successfully');
                $scope.data.splice(index,1);
                }).error(function(data,status){
                    al('Cant Delete');
                });
    }, function() {
        al('Delete Cancelled');
    });
}


function al(text)
{
    $mdToast.show($mdToast.simple().textContent(text).position('bottom right').hideDelay(3000));
}
            
$scope.search={};

$scope.searchform =  function(){
    $scope.search.FromDate = Math.round(new Date($scope.FromDat).getTime() / 1000);
    $scope.search.ToDate = Math.round(new Date($scope.ToDat).getTime() / 1000);
    angular.extend($scope.tableSorting.filter(), $scope.search);  
}

 $scope.status = function(pid,data,index,ev) {

        var confirm = $mdDialog.confirm({targetEvent:ev})
        .title('Are You sure to Verify this Purchase?')
        .ok('Yes')
        .cancel('No');

        $mdDialog.show(confirm).then(function() {
            $http({ url: 'purchase-status/' + pid, method: 'PUT', data: {status:((data.Status=='Unverify')?'Verify':'Unverify')} }).success(function(data) {
                $scope.data[index]=data;
            }).error(function(data, status) {
                $scope.formError = data;
            });
        }, function() {
            al('Delete Cancelled');
        });

        
    }

})
