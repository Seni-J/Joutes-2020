<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Team extends Model
{
    public $timestamps = false;
    protected $fillable = ['name','isCaptain','owner_id','validation'];

    //Get team participants
    public function participants()
    {
        return $this->belongsToMany('App\User')->withPivot('isCaptain');
        //return $this->belongsToMany('App\Participant')->withPivot('isCaptain');
    }


    public function users()
    {
        return $this->belongsToMany('App\User')->withPivot('isCaptain');
    }


    //Get team tournament
    public function tournament()
    {
        return $this->belongsTo('App\Tournament');
    }

    //
    public function sport()
    {
        return $this->tournament->sport();
    }

    public function games_contender_1()
    {
        return $this->hasManyThrough('App\Game', 'App\Contender', 'team_id', 'contender1_id');
    }

    public function games_contender_2()
    {
        return $this->hasManyThrough('App\Game', 'App\Contender', 'team_id', 'contender2_id');
    }

    public function games()
    {
        $collection = $this->games_contender_1->merge($this->games_contender_2);
        return $collection->sortBy('start_time');
    }


    // Verify if the teams have all participants required to be full
    public function isComplete()
    {
        if ($this->participants()->count() >= $this->sport->max_participant) return true;
        else return false;
    }

    public function isValid()
    {
        if ($this->participants()->count() >= $this->sport->min_participant) return true;
        else return false;
    }


    public function isOwner($id)
    {
        $participant = User::find($id);
        $user_id = $participant->id;
        if ($this->owner_id == $user_id) return true;
        else return false;
    }

    public function captain()
    {
        //$this->participants()->where('isCaptain', 1)->get();
        $this->users()->when('isCaptain', 1)->get();
    }

}
