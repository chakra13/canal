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
        return view('upgrader',[
            'allClients' => Client::all(),
            // 'allFormules' => Formule::all(),
            // 'allMateriels' => Materiel::all(),
            // 'allDecodeurs' => Decodeur::all(),
            // 'allUsers' => User::all(),
            // 'allMessages' => Message::all(),
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


        if (empty($deco[0])){
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
//        foreach($formul as $formul1){
            $id_formule = $formul[0]->id_formule;
             $statutcaisse = (new MessageController)->resteCaisse();
             if ($statutcaisse < $formul[0]->prix_formule * $request->duree){
                 session()->flash('message', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');

                 return redirect()->back()->with('warning', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');
             }
//        }
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
                'date_abonnement'=> $data->date_abonnement,
                'date_reabonnement'=>$date_reabonnement,
                'id_formule'=>$id_formule,
            ]);

            $reabonnement = Reabonnement::create(['id_decodeur'=>$deco[0]->id_decodeur,
                'id_client'=>$client->id_client,
                'id_formule'=>$id_formule,
                'id_user'=>$userid,
                'type_reabonement'=>1,
                'duree'=>$data->duree,
                'date_reabonnement'=>$date_reabonnement
            ]);
                $message = ($request->nom_client." Merci de vous etre abonné chez nous! Formule: " .$request->formule . ", expire le: ".$data->date_reabonnement .".");
                $envoi = (new MessageController)->sendMessage($message,$request->telephone_client );
                if ($envoi == 0) {
                    $message_con ="Un message a été envoyé au client.";
                }else{
                    $sendError =$envoi;
                }

        }

        if (!empty($client) && $message_con!="") {
            session()->flash('message', 'Le client a bien été enregistré dans la base de données. '.$message_con);
            //$pdf = (new PDFController)->createPDF($data);
            //return  redirect()->back()->with('info', 'Le client a bien été enregistré dans la base de données. '.$message_con);
        }
        if (!empty($client) && $message_con!="") {
           // session()->flash('message', 'Le client a bien été enregistré dans la base de données. '.$message_con);
            $pdf = (new PDFController)->createPDF($data);
            return  redirect()->back()->with('info', 'Le client a bien été enregistré dans la base de données. '.$message_con);
        }

        if (!empty($client)  and empty($message_con)) {
            session()->flash('message', 'Le client a bien été enregistré dans la base de données. Mais le message n\'a pas été envoyé '.$sendError  );
            return  redirect()->back()->with('warning', 'Le client a bien été enregistré dans la base de données. Mais le message n\'a pas été envoyé.'."\n Statut:".$sendError);
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
        $decos = Decodeur::join('client_decodeurs','client_decodeurs.id_decodeur','decodeurs.id_decodeur')
            ->where('client_decodeurs.id_client',$id_client)
            ->get();
        return view('new_reabonner',compact('datas','decos'));
    }

    public function edit_client( $id_client)
    {
        $datas = Client::find($id_client);
        //dd($datas);
        return view('modif_client',compact('datas'));
    }

    public function up_client( $id_client)
    {
        $datas = Client::find($id_client);
        $formule = Formule::where('id_formule',$datas->id_formule)->get();
        $decodeur = Decodeur::where('id_decodeur',$datas->id_decodeur)->get();
        $decos = Decodeur::join('client_decodeurs','client_decodeurs.id_decodeur','decodeurs.id_decodeur')
            ->where('client_decodeurs.id_client',$id_client)
            ->get();
        return view('upgrade',[
            'datas' => Client::find($id_client),
            'formule' => Formule::where('id_formule',$datas->id_formule)->get(),
            'decodeur' => Decodeur::where('id_decodeur',$datas->id_decodeur)->get(),
            'decos'=>$decos,
            // 'allDecodeurs' => Decodeur::all(),
            // 'allUsers' => User::all(),
            // 'allMessages' => Message::all(),
        ]);
        //return view('upgrade',compact('datas','formule','decodeur'));
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
        $statutcaisse = (new MessageController)->resteCaisse();
        if ($statutcaisse < $formul[0]->prix_formule * $request->duree){
            session()->flash('message', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');

            return redirect()->back()->with('warning', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');
        }
        $data->duree = $request->duree;
        $date_reabonnement = date_format(date_add(date_create("$request->date_abonnement"),date_interval_create_from_date_string("$request->duree months")),'Y-m-d');
//        DD($request);exit();
        $nom  =$request->nom_client;
        $userid= Auth::user()->id;
        $telephone = $request->telephone_client;
        $reabonnement = Reabonnement::create(['id_decodeur'=>$request->id_decodeur,
            'id_client'=>$id_client,
            'id_formule'=>$id_formule,
            'id_user'=>$userid,
            'type_reabonement'=>$request->type,
            'duree'=>$request->duree,
            'date_reabonnement'=>$date_reabonnement,
            'id_decodeur'=>$request->id_decodeur
        ]);
        $decodeur = ClientDecodeur::where('id_decodeur',$request->id_decodeur)
                    ->where('id_client',$id_client)
                    ->update([
                            'date_reabonnement'=>$date_reabonnement,

                        ]);
        if ($reabonnement){
            $message_con ='';
                $message = $nom." Votre réabonnement à été effectué avec success! Formule: " .$request->formule . ", expire le: ".$data->date_reabonnement .".";
                var_dump($message_con);
                $envoi = (new MessageController)->sendMessage($message,$telephone );
                if ($envoi == 0) {
                    $message_con ="Un message a été envoyé au client.";
                }else{
                    $message_con ="Erreur d'envoie du message".$envoi;
                }
        }
//        $data->save();
        session()->flash('message', 'Le reabonnement a reussi. '.$message_con);
        return  redirect()->route('reabonner')->with('info', 'Le reabonnement a reussi.'.$message_con);
    }

    public function upgradeClient(Request $request,$id_client)
    {
        $request->validate([
            'formule'=>'required',
        ]);
        $data = Client::find($id_client);
        $dt = Reabonnement::find($id_client);
        $formule = Formule::where('id_formule',$dt->id_formule)->get();
        $formul = Formule::where('nom_formule',$request->formule)->get();
            $id_formule = $formul[0]->id_formule;
        $statutcaisse = (new MessageController)->resteCaisse();
        $difference = $formul[0]->prix_formule - $formule->prix_formule;
        if ( $difference > 0){
            if ($statutcaisse < $difference){
                session()->flash('message', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');

                return redirect()->back()->with('warning', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');
            }
        }
        $userid= Auth::user()->id;
        $reabonnement = Reabonnement::create(['id_decodeur'=>$request->id_decodeur,
            'id_client'=>$id_client,
            'id_formule'=>$id_formule,
            'id_user'=>$userid,
            'type_reabonement'=>$request->type,
            'id_decodeur'=>$request->id_decodeur
        ]);

        if ($reabonnement){
            $message_con ='';
                $message = $data->nom_client." Mis à jour de la formule réussi ! Formule: " .$request->formule . ", expire le: ".$data->date_reabonnement .".";
                var_dump($message_con);
                $envoi = (new MessageController)->sendMessage($message,$data->telephone_client );
                if ($envoi == 0) {
                    $message_con ="Un message a été envoyé au client.";
                }else{
                    $message_con ="Erreur d'envoie du message".$envoi;
                }
        }
//        $data->save();
        session()->flash('message', "L'upgrate du client a reussi. ".$message_con);
        return  redirect()->route('upgrader')->with('info', "L'upgrate du client a reussi. ".$message_con);
    }



    public function storeDecCli(Request $request,$id_client)
    {
        $data = Client::find($id_client);
        $formul = Formule::where('nom_formule',$request->formule)->get();
        $formule_actuel = ClientDecodeur::find($id_client);
        $deco = Decodeur::where('num_decodeur',$request->num_decodeur)->get();
        $clientdeco = ClientDecodeur::where('id_decodeur',$request->num_decodeur)->get();
        $date_rea = date_format(date_add(date_create("$request->date_abonnement"),date_interval_create_from_date_string("$request->duree months")),'Y-m-d');

//        Auth::user()->role==='admin';
        $userid= Auth::user()->id;


        if (empty($deco)){
            session()->flash('message', ' Le décodeur n\'existe pas! Veillez l\'enregistrer ou entrez un autre.');

            return redirect()->back()->with('warning', ' Le décodeur n\'existe pas! Veillez l\'enregistrer ou entrez un autre!');
        }

                if(!empty($clientdeco[0])){
                session()->flash('message', ' Ce décodeur est déja utilisé par client!');

                return redirect()->back()->with('warning', 'Ce décodeur est déja utilisé par client!');
            }


//        foreach($formul as $formul1){
            $id_formule = $formul[0]->id_formule;
//        }
//        "237679353205",
//        $id_formule = $formul[0]->id_formule;

        $statutcaisse = (new MessageController)->resteCaisse();
       // if($formul[0]->prix_formule >)
        if ($statutcaisse < $formul[0]->prix_formule){
            session()->flash('message', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');

            return redirect()->back()->with('warning', 'Le montant en caisse n\'est pas suffisant pour cette opération! il ne reste que: ' .$statutcaisse.' FCFA en caisse.');
        }
        $message_con ="";
//        DD($client->id_client);exit();



        if (!empty($client)){
            $CD = ClientDecodeur::create(['id_decodeur'=>$deco[0]->id_decodeur,
            'id_client'=>$data->id_client,
            'id_formule'=>$id_formule,
            'date_abonnement'=> $request->date_abonnement,
            'date_reabonnement'=>$date_rea,
            'id_user'=>$data->id_user,
            ]);

            $reabonnement = Reabonnement::create(['id_decodeur'=>$deco[0]->id_decodeur,
                'id_client'=>$data->id_client,
                'id_formule'=>$id_formule,
                'id_user'=>$userid,
                'type_reabonement'=>1,
                'duree'=>$data->duree,
                'date_reabonnement'=>$date_rea,
            ]);
                $message = ($data->nom_client." Merci de vous etre abonné chez nous! Formule: " .$request->formule . ", expire le: ".$date_rea .".");
                $envoi = (new MessageController)->sendMessage($message,$data->telephone_client );
                if ($envoi == 0) {
                    $message_con ="Un message a été envoyé au client.";
                }else{
                    $sendError =$envoi;
                }

        }

        if (!empty($client) && $message_con!="") {
            session()->flash('message', 'Le client a bien été enregistré dans la base de données. '.$message_con);
            $pdf = (new PDFController)->createPDF($data);
            return  redirect()->back()->with('info', 'Le client a bien été enregistré dans la base de données. '.$message_con);
        }

        if (!empty($client)  and empty($message_con)) {
            session()->flash('message', 'Le client a bien été enregistré dans la base de données. Mais le message n\'a pas été envoyé '.$sendError  );
            return  redirect()->back()->with('warning', 'Le client a bien été enregistré dans la base de données. Mais le message n\'a pas été envoyé.'+"\n Statut:".$sendError);
        } else {
            session()->flash('message', 'Erreur! Le client n\' pas été enrgistré!');

            return redirect()->back()->with('danger', 'Erreur! Le client n\' pas été enrgistré!');
        }
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
