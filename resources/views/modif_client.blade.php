<x-app-layout>
  <x-slot name="slot">
      <center>
          <div class="card shadow mb-4 col-xs-12 col-md-8 border-bottom-primary">
              <div class="card-header py-3">
                  <h4 class="m-2 font-weight-bold text-primary">Modifier client</h4>
              </div><a  type="button" class="btn btn-primary bg-gradient-primary btn-block" href="{{route('clients')}}"> <i class="fas fa-flip-horizontal fa-fw fa-share"></i> Retour </a>
              <div class="card-body">


                  <form role="form" method="post" action="{{route('updateM.client')}}">
                    @csrf
                    <input type="hidden" name="id_client" value="{{ $datas->id_client }}" required>
                    <div class="form-group">
                      Nom client<br><input class="form-control" value="{{ $datas->nom_client }}" name="nom_client" required>
                    </div>
                    <div class="form-group">
                      Prenom client<br><input class="form-control" value="{{ $datas->prenom_client }}" name="prenom_client" required>
                    </div>
                    <div class="form-group">
                      N° téléphone client<br><input class="form-control" value="{{ $datas->telephone_client }}" name="telephone_client" required>
                    </div>
{{--                    <div class="form-group">--}}
{{--                      N° decodeur<br><input class="form-control" value="{{ $datas->num_decodeur }}" name="num_decodeur" disabled>--}}
{{--                    </div>--}}
                    <div class="form-group">
                      Adresse client<br><input class="form-control" value="{{ $datas->adresse_client }}" name="adresse_client" required>
                    </div>
                      Décodeurs
                      @foreach( $decodeurs as $key =>$item)
                          <input type="text"  maxlength="14" minlength="14" class="form-control" value="{{ $item->num_decodeur }}" name="num_decodeur[]" required>
                          <input type="hidden"   value="{{ $item->id_decodeur }}" name="id_decodeur[]" required>
                      @endforeach
{{--                    <div class="form-group">--}}
{{--                      Formule: <select  name="formule" disabled>--}}
{{--                        <option value="ACCESS" selected> ACCESS </option>--}}
{{--                        <option value="ACCESS +"> ACCESS + </option>--}}
{{--                        <option value="EVASION"> EVASION </option>--}}
{{--                        <option value="EVASION +"> EVASION + </option>--}}
{{--                        <option value="PRESTIGE"> PRESTIGE </option>--}}
{{--                        <option value="ESSENTIEL +"> ESSENTIEL + </option>--}}
{{--                        <option value="TOUT CANAL"> TOUT CANAL </option>--}}
{{--                       </select>--}}
{{--                    </div>--}}
{{--                    <div class="form-group">--}}
{{--                      Date reabonnement<br><input class="form-control" name="date_reabonnement" type="date" disabled>--}}
{{--                    </div>--}}
                    <hr>


                      <button type="submit" class="btn btn-warning btn-block"><i class="fa fa-edit fa-fw"></i>Modifier</button>
              </form>
          </div>
          </div>
      </center>
  </x-slot>
</x-app-layout>
