<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AccionesRolesPermisos;
use App\Estudiante;
use App\Evento;
use Auth;
use DB;
use Cache;
use Jenssegers\Date\Date;
use Carbon\Carbon;

class VotacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request,$eventos_id=246)
    {
        $this->actualizarSesion();
        //VERIFICA SI TIENE EL PERMISO
        if(!isset( session("permisosTotales")["asistencia"]["permisos"]["nuevo"]   ) ){  
            Auth::logout();
            return redirect('/login');
        }

        $roles = AccionesRolesPermisos::getRolesByUser(\Auth::User()->id);
        $permParam["modulo_alias"] = "asistencia";
        $permParam["roles"] = $roles;
        $permisos = AccionesRolesPermisos::getPermisosByRolesController($permParam);


        if($request->eventos_id != ""){
            $rs_tipo_asistencia = Evento::findOrFail($request->eventos_id);

            if($rs_tipo_asistencia){
                $e_tipo_id = $rs_tipo_asistencia->eventos_tipo_id;
                session(['eventos_id'=> $request->eventos_id, 't'=>$e_tipo_id]);
            }
        }
        
        if(session('eventos_id') == false){
            return redirect()->route('home');
        }

        return view('votacion.buscardni', compact('permisos'));
    }

    public function validar_dni (Request $request,$dni)
    {
        
        //return "Asistencia General";
        if($request->ajax()){

            $dni = trim($dni);
            
            // ESTRUCTURA PARA ASISTENCIA CON FECHA
            $date = Carbon::now();
            $fecha_hoy = $date->format('Y-m-d');
            $fecha_hoy_vista = $date->format('d/m/Y');
            $h_act = $date->format('H:i');

            $minutes = 60;#minutos de tolerancia para la asistencia
            $minutos = 240;#minutos de bloqueo de asistencia
            $h_tolerancia_c = Carbon::parse($h_act)->addMinutes($minutes);
            $h_tolerancia = Carbon::parse($h_tolerancia_c)->format('H:i');
            //return "h_act= $h_act -- h_tolerancia= $h_tolerancia";
            
            /*$fecha_hoy = "2019-12-02";
            $fecha_hoy_vista = "02-12-2019";
            $h_act = "14:30";
            $h_tolerancia = "15:00";*/

            $msg = "";
            $msg_tipo = 1; // 1: error  2: warning 3: succes
            $msg_error = "danger";
            $nombres = "";
            $titulo = "";

            // validar si existe evento en la fecha actual
            #primera consulta
            $rs_act_hoy = DB::table('eventos')
                            ->select('nombre_evento','fechaf_evento','hora','hora_fin','eventos_tipo_id')
                            ->where('id',session('eventos_id'))
                            ->where(DB::raw("str_to_date(fechaf_evento, '%Y-%m-%d')"), '>=', $fecha_hoy)
                            ->orderBy("fechaf_evento")
                            ->get();

            if(count($rs_act_hoy) > 0){

                $tipo_evento = $rs_act_hoy[0]->eventos_tipo_id;

                $flag_pasa = false;
                //TIPO 2: MOD EVENTOS
                if($tipo_evento == 2){
                    $flag_pasa = true;
                }

                $fe = $rs_act_hoy[0]->fechaf_evento;
                $h_ini = $rs_act_hoy[0]->hora;
                $h_fin = $rs_act_hoy[0]->hora_fin;

                $h_ini_a = Carbon::parse($h_ini)->subMinutes($minutes);
                $h_ini_a = Carbon::parse($h_ini_a)->format('H:i');

                $h_fin_a = Carbon::parse($h_fin)->addMinutes($minutes);
                $h_fin_a = Carbon::parse($h_fin_a)->format('H:i');

                //return("H ini: ".$h_ini ." h_ini_a: ".$h_ini_a. " H fin: ".$h_fin ." h_fin_a: ".$h_fin_a);

            }else{
            
                $msg = "No existe ningún evento en el sistema con la<br> fecha: $fecha_hoy_vista";
                $respuesta = array(
                    'msg'   => $msg,
                    'tipo'  => $msg_tipo,
                    'error' => $msg_error,
                    'nombres'=>$nombres,
                    'titulo' => $titulo
                );

                return $respuesta; // Para la fecha: 12/12/2019 no existe ningún evento
            }
            #segunda consulta
            // VALIDAR CON EL DNI si ya esta registrado
            $fe_mod = Carbon::now()->format('d/m/Y');
            $rs_validar_dni_asistencia = DB::table('asistencia_eventos')
                                    ->where('estudiantes_id', $dni)
                                    ->where('fecha',$fe_mod)
                                    ->orderBy('id','desc')
                                    ->limit(1)
                                    ->get();

            //dni: 72431426 - fecha: 2019-12-02 - actividad: 1 - mod: 02/12/2019
            //return "dni: $dni - fecha: $fe - actividad: $actividad_id - fe_mod: $fe_mod";

            if(count($rs_validar_dni_asistencia) > 0){

                $h_tb = Carbon::parse($rs_validar_dni_asistencia[0]->created_at);
                $hh_act = Carbon::now();
                $dif_minutos = $h_tb->diffInMinutes($hh_act);
                //return $dif_minutos;
                if($dif_minutos <= $minutos){

                    $msg = "ESTUDIANTE REGISTRADO<br>REGISTRADO HACE: ".$dif_minutos. " min";
                    $msg_error = "warning";

                    $respuesta = array(
                        'msg'   => $msg,
                        'tipo'  => $msg_tipo,
                        'error' => $msg_error,
                        'nombres'=>$nombres,
                        'titulo' => $titulo
                    );

                    return $respuesta; 
                }

            }
            
            #tercera consulta
            $data = Estudiante::join('estudiantes_act_detalle as de','estudiantes.dni_doc','=','de.estudiantes_id')
                            ->join('eventos as e', 'e.id','=','de.eventos_id')
                            ->select('estudiantes.nombres','estudiantes.ap_paterno','estudiantes.ap_materno','e.nombre_evento')
                            ->where('de.estudiantes_id',$dni)
                            ->where('de.eventos_id',session('eventos_id'))
                            ->where('de.daccedio','SI')
                            ->get();
            
            if(count($data) > 0){
                $msg_error = "succes";
                $msg = "";
                $msg_tipo = 3;
                $nombres = $data[0]->nombres. " ".$data[0]->ap_paterno. " ".$data[0]->ap_materno;
                $titulo = $data[0]->nombre_evento;

                $respuesta = array(
                    'msg'   => $msg,
                    'tipo'  => $msg_tipo,
                    'error' => $msg_error,
                    'nombres'=>$nombres,
                    'titulo' => $titulo
                );

                return $respuesta; 

            }else{
                $msg_error = "danger";
                $msg_tipo = 1;
                $msg = "ESTUDIANTE NO REGISTRADO";
                $nombres = "";
                $titulo = "";

                $respuesta = array(
                    'msg'   => $msg,
                    'tipo'  => $msg_tipo,
                    'error' => $msg_error,
                    'nombres'=>$nombres,
                    'titulo' => $titulo
                );

                return $respuesta; 
            }
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        dd('holaaaaa');
        dd($request->all());
        if($request->ajax()){

            $this->validate($request,[
                'asistencia_dni'=>'required',
                //'asistencia_dni'=>'required|unique:estudiantes,dni_doc',
            ]);

            $dni = trim($request->input('asistencia_dni'));

            DB::table('asistencia_eventos')->insert([
                    'estudiantes_id'    =>  $dni,
                    'fecha'         =>  date('d/m/Y'),
                    'hora'          =>  date('H:i'),
                    'usuario_id'    =>  Auth::user()->id,
                    'evento_id'     =>  session('eventos_id'),
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now()
            ]);

            $cant = Asistencia_evento::
                                    where('evento_id',$request->session()->get('eventos_id'))
                                    ->whereNull('actividad_id')
                                    ->where('fecha',date('d/m/Y'))
                                    ->select('id')
                                    ->count();

            #return back()->with([ 'info' => 'Asistencia Registrada','cant'=>$cant]);

            $msg_error = "succes";
            $msg_tipo = 1;
            $msg = "ASISTENCIA REGISTRADA - INSCRITOS: $cant";
            $nombres = "";
            $titulo = "";

            $respuesta = array(
                'msg'   => $msg,
                'tipo'  => $msg_tipo,
                'error' => $msg_error,
                'nombres'=>$nombres,
                'titulo' => $titulo
            );
                        
            return $respuesta; 
        
        }

    }


    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function elegir(Request $request)
    {

        session(['user' => $request->asistencia_dni]);
        if (!session()->has('user')) return redirect()->route('caii.login');
        
        //$eventos_id = decrypt(session('eventos_id'));
        $eventos_id = session('eventos_id');

        $tpoEst = Estudiante::join('estudiantes_act_detalle as de','de.estudiantes_id','=','estudiantes.dni_doc')
                    ->where('de.estudiantes_id',session('user'))
                    ->select('de.modalidad_id')
                    ->first();
        $modalidad = $tpoEst->modalidad_id;
        $eventos = DB::table('eventos')
                    ->select('id','fechai_evento','fechaf_evento')
                    ->where('id',$eventos_id)
                    ->first();

            $fechai_evento = Carbon::parse($eventos->fechai_evento);
            $fechaf_evento = Carbon::parse($eventos->fechaf_evento);

        $cant_dias = ($fechaf_evento->diffInDays($fechai_evento))+1;

        $actividades = DB::table('actividades as a')
                        ->join('eventos as e','a.eventos_id','=','e.id')
                        ->select('a.id','a.fecha_desde','e.id as evento','a.titulo','a.subtitulo', 'a.hora_inicio','a.vacantes','a.inscritos','a.ubicacion','a.imagen','a.desc_ponentes','a.desc_actividad','a.vacantes_v','a.inscritos_v')
                        ->where('eventos_id',session('eventos_id'))
                        ->orderBy('fecha_desde')
                        ->orderBy('hora_inicio')
                        ->orderBy('titulo')
                        ->get();

        $data=array();
        
        $fecha_desde2='';
        $i=-1;
        if(count($actividades)>0){
            foreach($actividades as $j=>$actividad){
                $hora_inicio=$actividad->hora_inicio;
                $fecha_desde=$actividad->fecha_desde;
                if($fecha_desde!==$fecha_desde2){$data[++$i]=array("fecha_desde"=>$fecha_desde,"horas"=>array());$hora_inicio2='';$i2=-1;}
                if($hora_inicio!==$hora_inicio2)$data[$i]["horas"][++$i2]=array("hora_inicio"=>$hora_inicio,"actividades"=>array());
                $fila=array(
                    "titulo"      => $actividad->titulo,
                    "subtitulo"   => $actividad->subtitulo,
                    "id"          => $actividad->id,
                    "vacantes"    => $actividad->vacantes,
                    "vacantes_v"  => $actividad->vacantes_v,
                    "inscritos"   => $actividad->inscritos,
                    "inscritos_v" => $actividad->inscritos_v,
                    "ubicacion"   => $actividad->ubicacion,
                    "imagen"      => $actividad->imagen,
                    "desc_ponentes"    =>  $actividad->desc_ponentes,
                    "desc_actividad"   =>  $actividad->desc_actividad
                    /*otras columnas*/
                );
                $data[$i]["horas"][$i2]["actividades"][]=$fila;
                $hora_inicio2=$hora_inicio;
                $fecha_desde2=$fecha_desde;
            }
        }
        
        $datos = DB::table('eventos as e')
                            ->join('e_plantillas as p', 'e.id', '=', 'p.eventos_id')
                            ->join('e_formularios as f', 'e.id','=','f.eventos_id')
                            ->where('e.id',session('eventos_id'))
                            ->orderBy('e.id', 'desc')
                            ->first();
        
        $estudiante = Estudiante::where('dni_doc',$request->asistencia_dni)
                            ->first();
        if($modalidad==2)
            return "aaaaaaa";#
        else
            return view('votacion.votacion', compact('eventos','cant_dias', 'actividades', 'datos','data','estudiante'));

        #view('evento.seleccionar_act_virtual', compact('eventos','cant_dias', 'actividades', 'datos','data'));
   
    }

    public function confirmar_act(Request $request)
    {
        //session()->forget('user','eventos_id');
        #dd(session('user'),session('eventos_id'),$request->all());
        ##
        if (!session()->has('user') or !session()->has('eventos_id')) {
            return redirect('/login');
        }
        
            $val_reg = DB::table('estudiantes as e')
                ->join('estudiantes_act_detalle as de','e.dni_doc','=','de.estudiantes_id')
                ->join('actividades_estudiantes as a','e.dni_doc','=','a.estudiantes_id')
                ->join('actividades as act','a.actividad_id','=','act.id')
                ->select('act.titulo','act.subtitulo')
                ->where('a.eventos_id',session('eventos_id'))
                ->where('a.estudiantes_id',session('user'))
                ->where('de.eventos_id',session('eventos_id'))
                ->where('de.estudiantes_id',session('user'))
                ->where('de.daccedio','SI')
                ->get();
                #->count();
            
            $act_sel = '';
            if(count($val_reg) >= 1){
                $act_sel = "Estudiante:". session('user'). ". Usted ya selecciono su candidato: ";
                foreach ($val_reg as $val) {
                    $act_sel .= $val->titulo ." ". $val->subtitulo ;        
                }
                alert()->warning('Advertencia', $act_sel);
                return redirect()->route('votacion.index',['eventos_id'=>session('eventos_id')])->with('actividades_selec', $act_sel);
            }

            $varios = ($request->input('varios'));
            
            $input_2 = "";
            $si_hay_act = DB::table('actividades')
                    ->where('eventos_id',session('eventos_id'))
                    ->count();
            if($si_hay_act == 0){
                return redirect()->back()->with('no_actividades', 'No existen actividades para registrar');
            }
     
            DB::table('estudiantes')->where('dni_doc',session('user'))->update([
                    'accedio'  =>  'SI'
                ]);

            $rs = DB::table('estudiantes_act_detalle')
                        ->where('eventos_id', session('eventos_id'))
                        ->where('estudiantes_id', session('user'))
                        ->update([
                            'dtrack'       => "SI",
                            'daccedio'     => "SI",
                            'confirmado'   => 1,
                            'fecha_conf'   => Carbon::now()
                        ]);

            
                
            
            # AUMENTAR LOS VOTOS
            foreach ($varios as $voto) {
                    
                $id_actividad = $voto;
                $inscritos = 'inscritos';
   
                // save incrementar campo inscritos
                DB::table('actividades')->where('id', $id_actividad)
                                      ->increment($inscritos, 1);
                
                 $sv = DB::table('actividades_estudiantes')->insert([
                    'actividad_id'    =>  $id_actividad,
                    'estudiantes_id'  =>  session('user'),
                    'eventos_id'      =>  session('eventos_id'),
                    'confirmado'      =>  'S',
                    'fecha_reg'       =>  Carbon::now()->format('d/m/Y')
                ]);

                // AGREGAR asistencia
                DB::table('asistencia_eventos')->insert([
                        'estudiantes_id'=> session('user'),
                        'fecha'         =>  date('d/m/Y'),
                        'hora'          =>  date('H:i'),
                        'usuario_id'    =>  Auth::user()->id,
                        'actividad_id'  =>  $id_actividad,
                        'evento_id'     =>  session('eventos_id'),
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                ]);
                

                

            } //foreach

            #session()->forget('user');#eventos_id
            alert()->success('Mensaje Satisfactorio','Registro guardado correctamente.');
            #return redirect()->back();
            return redirect()->route('votacion.index');
      
    } // end public function store
}
