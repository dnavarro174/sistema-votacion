@extends('layout.home')

@section('content')

<div class="horizontal-menu bg_fondo">
    <!-- partial:partials/_navbar.html -->

    {{-- @include('layout.nav_superior') --}}
    <!-- end encabezado -->
    <!-- partial -->
    <div class="container-fluid page-body-wrapper ">
      
      <!-- end menu_right -->
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper ">
          <div class="container texto_caii">
            <div class="row justify-content-center">
              <div class="col-md-10 grid-margin stretch-card">
                <form class="forms-sample" id="elegirCandidato" action="{{ route('votacion.confirmar') }}" method="post" autocomplete="on">
                  
                  {!! csrf_field() !!}

                  <div class="row ">

                    <div class="col-sm-12 col-md-12  grid-margin stretch-card mb-0">
                      <div class="card">
                          <div class="card-body">
                            <h4 class="card-title text-danger2">Estudiante: {{ $estudiante->nombres }}, {{ $estudiante->ap_paterno }} {{ $estudiante->ap_materno }}</h4>
                            @if(Session::has('actividades_selec'))
                              <p class="alert alert-danger">{!! Session::get('actividades_selec') !!}</p>
                            @endif

                            <p class="card-text"><strong>Indicaciones: </strong>
                              Elige el candidato de tu preferencia.
                            </p>
                            <ul>
                              <li>Una vez elegido su candidato no se podrá cambiar.</li>
                            </ul>

                          </div>
                      </div>
                    </div>
                            

                    <div class="col-sm-12 col-md-12  grid-margin stretch-card">

                      <div class="row">
                        <div class="row">
                             
                                <div class="form-group row">
                                  <div class="col-sm-12">
                                   @if(Session::has('no_actividades'))
                                    <p class="alert alert-danger">{{ Session::get('no_actividades') }}</p>
                                    @endif
                                  </div>
                                </div>
                                <div class="row mr-4 ml-4">
                                  {{-- <div class="col-sm-12 col-md-12"> --}}

                                  @foreach($data as $i=>$d)
                                        <?php
                                          $xdias = \Carbon\Carbon::parse($eventos->fechai_evento)->format('d');
                                          $xmes_ano = \Carbon\Carbon::parse($eventos->fechai_evento)->format('m-Y');
                                          $ev_f_desde = $xdias+$i;
                                          $ndia = $d["fecha_desde"];

                                          $dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
                                          $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Dic");

                                          //$ndia = $xdias+$i.'-'.$xmes_ano;
                                   
                                          $dia_texto = \Carbon\Carbon::parse($ndia)->format('d')." ".$meses[\Carbon\Carbon::parse($ndia)->format('n')-1];
                                        ?>

                                        @foreach($d["horas"] as $j=>$d2)
                                          
                                          

                                          <div class="row  ">
                                            @foreach($d2["actividades"] as $k=>$d3)

                                                <div class="col-md-6 grid-margin stretch-card"> 
                                                    <div class="card">
                                                      <div class="card-body">
                                                        <label class="form-radio">
                                                          <div class="row">
                                                            <div class="col-md-6">
                                                              <img class="img-fluid rounded border-gray" src="{{ asset('images/act/')}}/{{$d3["imagen"]}}" alt="{{$d3["titulo"]}} {{date('Y')}}">
                                                            </div>
                                                            <div class="col-md-6">
                                                              <img class="img-fluid border-gray" src="{{ asset('images/act/')}}/{{$d3["desc_ponentes"]}}" alt="logo candidato">
                                                            </div>
                                                            
                                                          </div>
                                                        
                                                          <input type="radio" id="varios[{{$i}}_{{$j}}]" class="form-check-" name="varios[{{$i}}_{{$j}}]" value="{{$d3["id"]}}" required="" @if($d3["vacantes"] <= $d3["inscritos"]) disabled @endif>
                                                              <em>{{$d3["titulo"]}}</em> <span>{{$d3["subtitulo"]}} </span>
                                                        </label>
                                                      </div>
                                                    </div>
                                                  </div>
                                            
                                          

                                             @endforeach
                                          </div>
                                        @endforeach
                                      
                                    
                                  @endforeach

                                  

                                  <div class="col-sm-12 col-md-12">
                                    <div class="form-group ">
                                      {{-- <input type="hidden" name="id_evento" id="id_evento" value="{{ $id_evento }}">
                                      <input type="hidden" name="user" id="user" value="{{ $user }}"> --}}
                                      <div class="col-sm-12 col-md-12 p-4">
                                        <button type="submit" class="btn btn-dark mr-2" @if(Session::has('no_actividades')) disabled="" @endif>Registrar</button>
                                      </div>
                                    </div>

                                  </div>

                                </div> {{-- end row --}}


                          </div>
                        </div>
                      </div>
                      
                    





                  </div>


                </form>
                
              </div>
            </div>
          </div>
          
          
          
        </div>
<style>
.form-control2 label.form-radio{font-weight: bold;font-size: 14px;}
.form-control2 label.form-radio em, .color_verde{color: #41464a;font-style: normal;}
.form-control2 label.form-radio span,.color_azul{color:#41464a;}
.texto_foros p{padding-left: 25px;}
.text-danger2{color: #ed1c24 !important;}
</style>

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


@endsection

@section('scripts')

<script>
var btnRegister = $('#actionSubmit');
$("#elegirCandidato").on("submit", function(e) {
  e.preventDefault();
  console.log('guardando...');

  swal({
          title: "Advertencia:",
          text: "¿Estás seguro de elegir este Candidato?",
          icon: "warning",
          //buttons: true,
          dangerMode: true,
          buttons: ["No, cancelar", "Sí, Votar"],
        })
        .then((value) => {

          if(value == true){
            console.log('Aprobado aa');

            $.ajax({
                url: "{{ route('votacion.confirmar') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function(respuesta) {
                    //swal("Registro Existoso", "Registro guardado correctamente.", "success");
                    swal({ type:'success',title:'Registro Existoso', text:'Registro guardado correctamente', icon:'success', showConfirmButton: false,timer: 2000});

                    setTimeout(function() {
                        window.location.href = "{{ route('votacion.index') }}";
                    }, 3000);
                    /*$('#respuesta').html(
                        "<p style='color: green;'>" + respuesta.mensaje + "</p>"
                    );*/
                },
                error: function(xhr) {
                    $('#respuesta').html(
                        "<p style='color: red;'>Error: " + xhr.responseJSON.message + "</p>"
                    );
                }
            });



        } // end if


      });



  

      
  });


// submit


</script>

@endsection