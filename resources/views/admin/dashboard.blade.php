@extends('admin.layout.admin_app')

@section('content')
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card text-white bg-primary">
      <div class="card-body pb-0 d-flex justify-content-between align-items-start">
        <div>
          <div class="fs-4 fw-semibold">84 <span class="fs-6 fw-normal">(-12.4%
              <svg class="icon">
                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-arrow-bottom"></use>
              </svg>)</span></div>
          <div>Users</div>
        </div>
        <div class="dropdown">
          <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <svg class="icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-options"></use>
            </svg>
          </button>
          <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
        </div>
      </div>
      <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
        <canvas class="chart" id="card-chart1" height="70"></canvas>
      </div>
    </div>
  </div>
  <!-- /.col-->
  <div class="col-sm-6 col-xl-3">
    <div class="card text-white bg-info">
      <div class="card-body pb-0 d-flex justify-content-between align-items-start">
        <div>
          <div class="fs-4 fw-semibold">$700 <span class="fs-6 fw-normal">(40.9%
              <svg class="icon">
                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-arrow-top"></use>
              </svg>)</span></div>
          <div>Income</div>
        </div>
        <div class="dropdown">
          <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <svg class="icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-options"></use>
            </svg>
          </button>
          <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
        </div>
      </div>
      <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
        <canvas class="chart" id="card-chart2" height="70"></canvas>
      </div>
    </div>
  </div>
  <!-- /.col-->
  <div class="col-sm-6 col-xl-3">
    <div class="card text-white bg-warning">
      <div class="card-body pb-0 d-flex justify-content-between align-items-start">
        <div>
          <div class="fs-4 fw-semibold">30 <span class="fs-6 fw-normal">(84.7%
              <svg class="icon">
                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-arrow-top"></use>
              </svg>)</span></div>
          <div>Rides</div>
        </div>
        <div class="dropdown">
          <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <svg class="icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-options"></use>
            </svg>
          </button>
          <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
        </div>
      </div>
      <div class="c-chart-wrapper mt-3" style="height:70px;">
        <canvas class="chart" id="card-chart3" height="70"></canvas>
      </div>
    </div>
  </div>
  <!-- /.col-->
  <div class="col-sm-6 col-xl-3">
    <div class="card text-white bg-danger">
      <div class="card-body pb-0 d-flex justify-content-between align-items-start">
        <div>
          <div class="fs-4 fw-semibold">44K <span class="fs-6 fw-normal">(-23.6%
              <svg class="icon">
                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-arrow-bottom"></use>
              </svg>)</span></div>
          <div>Sessions</div>
        </div>
        <div class="dropdown">
          <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <svg class="icon">
              <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-options"></use>
            </svg>
          </button>
          <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#">Action</a><a class="dropdown-item" href="#">Another action</a><a class="dropdown-item" href="#">Something else here</a></div>
        </div>
      </div>
      <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
        <canvas class="chart" id="card-chart4" height="70"></canvas>
      </div>
    </div>
  </div>
  <!-- /.col-->
</div>
<!-- /.row-->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between">
      <div>
        <h4 class="card-title mb-0">Traffic</h4>
        <div class="small text-body-secondary">January - May 2025</div>
      </div>
      <div class="btn-toolbar d-none d-md-block" role="toolbar" aria-label="Toolbar with buttons">
        <div class="btn-group btn-group-toggle mx-3" data-coreui-toggle="buttons">
          <input class="btn-check" id="option1" type="radio" name="options" autocomplete="off">
          <label class="btn btn-outline-secondary"> Day</label>
          <input class="btn-check" id="option2" type="radio" name="options" autocomplete="off" checked="">
          <label class="btn btn-outline-secondary active"> Month</label>
          <input class="btn-check" id="option3" type="radio" name="options" autocomplete="off">
          <label class="btn btn-outline-secondary"> Year</label>
        </div>
        <button class="btn btn-primary" type="button">
          <svg class="icon">
            <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-cloud-download"></use>
          </svg>
        </button>
      </div>
    </div>
    <div class="c-chart-wrapper" style="height:300px;margin-top:40px;">
      <canvas class="chart" id="main-chart" height="300"></canvas>
    </div>
  </div>
  <div class="card-footer">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 row-cols-xl-5 g-4 mb-2 text-center">
      <div class="col">
        <div class="text-body-secondary">Visits</div>
        <div class="fw-semibold text-truncate">29.703 Users (40%)</div>
        <div class="progress progress-thin mt-2">
          <div class="progress-bar bg-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div class="col">
        <div class="text-body-secondary">Unique</div>
        <div class="fw-semibold text-truncate">24.093 Users (20%)</div>
        <div class="progress progress-thin mt-2">
          <div class="progress-bar bg-info" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div class="col">
        <div class="text-body-secondary">Pageviews</div>
        <div class="fw-semibold text-truncate">78.706 Views (60%)</div>
        <div class="progress progress-thin mt-2">
          <div class="progress-bar bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div class="col">
        <div class="text-body-secondary">New Users</div>
        <div class="fw-semibold text-truncate">22.123 Users (80%)</div>
        <div class="progress progress-thin mt-2">
          <div class="progress-bar bg-danger" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div class="col d-none d-xl-block">
        <div class="text-body-secondary">Bounce Rate</div>
        <div class="fw-semibold text-truncate">40.15%</div>
        <div class="progress progress-thin mt-2">
          <div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection