@extends('layout.home')

@section('content')

<div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    
    @include('layout.nav_superior')
    <!-- end encabezado -->
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">

      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">

            @if(in_array('bd',session('permisosMenu')))
            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 grid-margin stretch-card">
              <div class="card card-statistics">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-center">
                    <div class="highlight-icon bg-light mr-3">
                      <i class="mdi mdi-database text-danger icon-lg"></i>
                    </div>
                    <div class="wrapper">
                      <h4 class="card-text mb-0"><a href="{{ route('bd.index')}}">Base de Datos</a></h4>
                      <p class="card-text mb-0">Todos los registros</p>
                      <!-- <div class="fluid-container">
                        <h3 class="card-title mb-0">$65,650</h3>
                      </div> -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif
  
            @if(in_array('estudiantes',session('permisosMenu')))
            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 grid-margin stretch-card">
              <div class="card card-statistics">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-center">
                    <div class="highlight-icon bg-light mr-3">
                      <i class="mdi mdi-account-plus text-info icon-lg"></i>
                    </div>
                    <div class="wrapper">
                      <h4 class="card-text mb-0"><a href="{{ route('caii.index')}}">Jornada Electoral</a></h4>
                      <p class="card-text mb-0">Registros de los estudiantes 2025</p>

                      <div class="fluid-container">
                        <p class="card-text mb-0">
                          
                        </p>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif
          
            @if(in_array('usuarios',session('permisosMenu')))
            <div class="col-xl-4 col-lg-4 col-md-4 col-sm-6 grid-margin stretch-card">
              <div class="card card-statistics">
                <div class="card-body">
                  <div class="d-flex align-items-left ">
                    <div class="highlight-icon bg-light mr-3">
                      <i class="mdi mdi-account text-info icon-lg"></i>
                    </div>
                    <div class="wrapper">
                      <h4 class="card-text mb-0"><a href="{{ route('usuarios.index') }}">Usuarios</a></h4>

                      <div class="fluid-container">
                        <p class="card-text mb-0">Administre los usuarios del sistema.</p>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif
          </div>
          
          
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
        @include('layout.footer')
        
        <!-- end footer.php -->
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->


  {{-- code js --}}

@endsection