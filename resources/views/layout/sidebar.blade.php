

<div class="sidebar sidebar-main sidebar-fixed">
	<div class="sidebar-content">
		<div class="sidebar-user-material">
			<div class="category-content">
				<div class="sidebar-user-material-content"></div>
				<div class="sidebar-user-material-menu">
					<a  href="#user-nav" target="_self" style="background-color: rgba(2, 2, 2, 0.26);" data-toggle="collapse">
						<span>Account : {{Auth::user()->name}}</span>
					</a>
				</div>
			</div>
		</div>
		<div class="sidebar-category sidebar-category-visible">
			<div class="category-content no-padding">
				<ul class="navigation navigation-main navigation-accordion">
					<li class="navigation-header">
						<span>Home</span>
						<i class="icon-menu" title="HOME"></i>
					</li>
					<li>
						<a href="#/dashboard">
							<md-tooltip md-direction="right">Dashboard</md-tooltip>
							<i class="icon-home2"></i>
							<span>Dashboard</span>
						</a>
					</li>
					<li class="navigation-header">
						<span>Purchase</span>
						<i class="icon-menu" title="Purchase"></i>
					</li>
					
					<li ng-class="{'active':title=='Purchase'}">
						<a href="#/purchase">
							<i class="icon icon-calculator"></i>
							<span>Purchase</span>
						</a>
					</li>
					<li ng-class="{'active':title=='Purchase Payments'}">
						<md-tooltip md-direction="right">Purchase Payments</md-tooltip>
						<a href="#/purchase-payments">
							<i class="icon icon-coin-dollar"></i>
							<span>Purchase Payments</span>
						</a>
					</li>
					<li ng-class="{'active':title=='Verification Print'}">
						<md-tooltip md-direction="right">Verification Print</md-tooltip>
						<a href="#/verification-print">
							<i class="icon icon-stairs-up"></i>
							<span>Verification Print</span>
						</a>
					</li>

					<li ng-class="{'active':title=='Supplierwise Payment'}">
						<md-tooltip md-direction="right">Supplierwise Payment</md-tooltip>
						<a href="#/supplier-wise-payment">
							<i class="icon icon-stairs-up"></i>
							<span>Supplier Payment</span>
						</a>
					</li>
					<li ng-class="{'active':title=='All Payment Report'}">
						<md-tooltip md-direction="right">All Payment</md-tooltip>
						<a href="#/all-payment-report">
							<i class="icon icon-stairs-up"></i>
							<span>All Payment</span>
						</a>
					</li>

					<li class="navigation-header">
						<span>Invoice</span>
						<i class="icon-menu" title="Invoice"></i>
					</li>
				
					<li ng-class="{'active':title=='Invoices'}">
						<md-tooltip md-direction="right">Invoices</md-tooltip>
						<a href="#/invoice">
							<i class="icon icon-clipboard6"></i>
							<span>Invoice</span>
						</a>
					</li>
					
					
					<li class="navigation-header">
						<span>Master</span>
						<i class="icon-menu" title="Master"></i>
					</li>
					<li ng-class="{'active':title=='Suppliers'}">
						<a href="#/suppliers">
							<i class="icon-users"></i>
							<span>Suppliers</span>
						</a>
					</li>
					<li ng-class="{'active':title=='Customers'}">
						<md-tooltip md-direction="right">Customers</md-tooltip>
						<a href="#/customers">
							<i class="icon-user"></i>
							<span>Customers</span>
						</a>
					</li>
					<li ng-class="{'active':title=='Items'}">
						<md-tooltip md-direction="right">Items</md-tooltip>
						<a href="#/items">
							<i class="icon-wall"></i>
							<span>Items</span>
						</a>
					</li>
					<li ng-class="{'active':title=='HSN'}">
						<md-tooltip md-direction="right">HSN</md-tooltip>
						<a href="#/hsn">
							<i class="icon-percent"></i>
							<span>HSN</span>
						</a>
					</li>
					<li ng-class="{'active':title=='Settings'}">
						<md-tooltip md-direction="right">Settings</md-tooltip>
						<a href="#/settings">
							<i class="icon-wrench"></i>
							<span>Settings</span>
						</a>
					</li>
					<li ng-class="{'active':title=='Settings'}">
						<md-tooltip md-direction="right">Logout</md-tooltip>
						<a href="/logout">
							<i class="icon-switch2"></i>
							<span>Logout</span>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
