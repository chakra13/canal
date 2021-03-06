<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('clients', function (Blueprint $table) {
            $table->id('id_client');
            $table->string("nom_client");
            $table->string("prenom_client");
            $table->bigInteger("num_abonne");
            $table->string("adresse_client");
            $table->bigInteger("telephone_client");
            $table->date("date_abonnement");
            $table->integer("duree");
            $table->date("date_reabonnement");
            $table->integer("id_user");

            $table->unsignedBigInteger('id_materiel')
            ->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
