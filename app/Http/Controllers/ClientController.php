<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Formule;
use App\Models\Message;
use App\Models\ClientDecodeur;
use App\Models\Reabonnement;
use App\Models\User;
use App\Models\Materiel;
use App\Models\Decodeur;
use Illuminate\Support\Facades\Auth;
use Vonage\Client\Exception\Exception;
//use Exception;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function view()
    {
        $allClients = Client::all();
        $allFormules = Formule::all();
        return view('abonner',compact('allClients','allFormules'));
    }

    public function review()
    {
        $allClients = Client::all();
        $allFormules = Formule::all();
        return view('reabonner',compact('allClients','allFormules'));
    }

    public function allview()
    {
        $allClients = Client::all();
        $allFormules = Formule::all();
        return view('clients',compact('allClients','allFormules'));
    }

    public function viewModif()
    {
        return view('modifier',[
            'allClients' => Client::all(),
            'allFormules' => Formule::all(),
            'allMateriels' => Materiel::all(),
            'allDecodeurs' => Decodeur::all(),
            'allUsers' => User::all(),
            'allMessages' => Message::all(),
        ]);
    }

    public function add()
    {
        return view('abonner_add');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $clients = Client::all();
        $formul = Formule::where('nom_formule',$request->formule)->get();
        $deco = Decodeur::where('num_decodeur',$request->num_decodeur)->get();
        $clientdeco = ClientDecodeur::where('id_decodeur',$request->num_decodeur)->get();
        $data = new client;

//        Auth::user()->role==='admin';
        $userid= Auth::user()->id;


        if (empty($deco)){
            session()->flash('message', ' Le décodeur n\'existe pas! Veillez l\'enregistrer ou entrez un autre.');

            return redirect()->back()->with('warning', ' Le décodeur n\'existe pas! Veillez l\'enregistrer ou entrez un autre!');
        }
        $data->telephone_client = $request->telephone_client;

        foreach($clients as $cli){
            if($cli->telephone_client == $request->telephone_client or $cli->num_abonne == $request->num_abonne){
                session()->flash('message', ' Le client existe déja!');

                return redirect()->back()->with('warning', 'Le client existe déja!');
            }
            else{
               $data->telephone_client = $request->telephone_client;
               $data->num_abonne = $request->num_abonne;
            }
        }

                if(!empty($clientdeco[0])){
                session()->flash('message', ' Ce décodeur est déja utilisé par client!');

                return redirect()->back()->with('warning', 'Ce décodeur est déja utilisé par client!');
            }

//        $data->id_decodeur = $deco[0]->id_decodeur;
        foreach($formul as $formul1){
            $id_formule = $formul1->id_formule;
        }
        $data->nom_client = $request->nom_client;
        $data->num_abonne = $request->num_abonne;
        $data->prenom_client = $request->prenom_client;
        $data->adresse_client = $request->adresse_client;

        $data->duree = $request->duree;
        $data->id_materiel = $deco[0]->id_decodeur;
        $data->date_abonnement = $request->date_abonnement;
        $data->date_reabonnement = date_format(date_add(date_create("$request->date_abonnement"),date_interval_create_from_date_string("$request->duree months")),'Y-m-d');
        $date_reabonnement=$data->date_reabonnement;
        $data->id_user = $userid;
//        "237679353205",


        $client = Client::create([
            'nom_client'=>$data->nom_client,
            'num_abonne'=>$data->num_abonne,
            'prenom_client' =>$data->prenom_client,
            'adresse_client'=>$data->adresse_client,
            'duree' => $data->duree,
            'id_materiel' => $deco[0]->id_decodeur,
            'date_abonnement'=> $data->date_abonnement,
            'date_reabonnement'=>$data->date_reabonnement,
            'id_user'=>$data->id_user,
            'telephone_client'=>$data->telephone_client
        ]);
        $message_con ="";
//        DD($client->id_client);exit();



        if (!empty($client)){
            $CD = ClientDecodeur::create(['id_decodeur'=>$deco[0]->id_decodeur,
                'id_client'=>$client->id_client,
                'id_user'=>$userid,
                'date_reabonnement'=>$date_reabonnement,
            ]);

            $reabonnement = Reabonnement::create(['id_decodeur'=>$deco[0]->id_decodeur,
                'id_client'=>$client->id_client,
                'id_formule'=>$id_formule,
                'id_user'=>$userid,
                'type_reabonement'=>1,
                'duree'=>$data->duree,
                'date_reabonnement'=>$date_reabonnement
            ]);
            try {
                $basic  = new \Vonage\Client\Credentials\Basic("955fc9c6", "mAWAdKoZ6Emoe6QU");
                $client = new \Vonage\Client($basic);
                $response = $client->sms()->send(
                    new \Vonage\SMS\Message\SMS(
                        $request->telephone_client,
                        'GETEL',
                        'Merci de vous etre abonné chez nous!')
                );
                $message = $response->current();

                if ($message->getStatus() == 0) {
                    $message_con ="Un message a été envoyé au client";
                }
            } catch (Exception $e) {
            $sendError = "Error: ". $e->getMessage();
        }

        }

        if (!empty($client)) {
            session()->flash('message', 'Le client a bien été enregistré dans la base de données. '.$message_con);
            $pdf = (new PDFController)->createPDF($data);
            return  redirect()->back()->with('info', 'Le client a bien été enregistré dans la base de données. '.$message_con);
        }

        if (!empty($client)  and empty($message_con)) {
            session()->flash('message', 'Le client a bien été enregistré dans la base de données. Mais le message n\'a pas été envoyé'  );
            return  redirect()->back()->with('warning', 'Le client a bien été enregistré dans la base de données. Mais le message n\'a pas été envoyé.'+"\n Statut:"+$sendError);
        } else {
            session()->flash('message', 'Erreur! Le client n\' pas été enrgistré!');

            return redirect()->back()->with('danger', 'Erreur! Le client n\' pas été enrgistré!');
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //return view('abonner',compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reabonne( $id_client)
    {
        $datas = Client::find($id_client);
        //dd($datas);
        return view('new_reabonner',compact('datas'));
    }

    public function edit_client( $id_client)
    {
        $datas = Client::find($id_client);
        //dd($datas);
        return view('modif_client',compact('datas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateR(Request $request,$id_client)
    {
        $request->validate([
            'formule'=>'required',
            'date_reabonnement'=>'required',
        ]);
        $data = Client::find($id_client);
        $formul = Formule::where('nom_formule',$request->formule)->get();
            $id_formule = $formul[0]->id_formule;

        $data->duree = $request->duree;
        $date_reabonnement = date_format(date_add(date_create("$request->date_abonnement"),date_interval_create_from_date_string("$request->duree months")),'Y-m-d');
        $userid= Auth::user()->id;
        $reabonnement = Reabonnement::create(['id_decodeur'=>$request->id_decodeur,
            'id_client'=>$id_client,
            'id_formule'=>$id_formule,
            'id_user'=>$userid,
            'type_reabonement'=>$request->type,
            'duree'=>$request->duree,
            'date_reabonnement'=>$date_reabonnement
        ]);

//        $data->save();
        session()->flash('message', 'La modification a reussi.');
        return  redirect()->route('modifier')->with('info', 'La modification a reussi.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return back()->with('info','Le client a été effacé avec succès.');
    }




}
